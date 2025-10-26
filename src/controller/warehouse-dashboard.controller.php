<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';
require_once MODEL . 'warehouse-notification.model.php';
require_once MODEL . 'warehouse-activity-log.model.php';

/**
 * WarehouseDashboardController
 *
 * Handles warehouse dashboard data aggregation and retrieval.
 */
class WarehouseDashboardController
{
    protected PDO $db;
    protected WarehouseNotificationModel $notificationModel;
    protected WarehouseActivityLogModel $activityLogModel;

    public function __construct()
    {
        try {
            $database = new Database();
            $connection = $database->getConnection();
            if (!$connection) {
                throw new PDOException('Database connection is null');
            }
            $this->db = $connection;
        } catch (Exception $e) {
            error_log("WarehouseDashboardController constructor error: " . $e->getMessage());
            throw $e;
        }

        $this->notificationModel = new WarehouseNotificationModel();
        $this->activityLogModel = new WarehouseActivityLogModel();
    }

    /**
     * Get complete dashboard data for a warehouse
     */
    public function getDashboardData(int $warehouseId): array
    {
        try {
            $kpiData = $this->getKPIData($warehouseId);
            $parcelIntakeTrend = $this->getParcelIntakeTrend($warehouseId);
            $shipmentStatusDistribution = $this->getShipmentStatusDistribution($warehouseId);
            $paymentsSummary = $this->getPaymentsSummary($warehouseId);
            $recentActivities = $this->getRecentActivities($warehouseId);
            $notifications = $this->getNotifications($warehouseId);

            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'kpiData' => $kpiData,
                    'parcelIntakeTrend' => $parcelIntakeTrend,
                    'shipmentStatusDistribution' => $shipmentStatusDistribution,
                    'paymentsSummary' => $paymentsSummary,
                    'recentActivities' => $recentActivities,
                    'notifications' => $notifications
                ],
                'message' => 'Dashboard data retrieved successfully'
            ];
        } catch (Exception $e) {
            error_log('WarehouseDashboardController getDashboardData error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to retrieve dashboard data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get KPI data for warehouse dashboard
     */
    private function getKPIData(int $warehouseId): array
    {
        // Parcels received today
        $parcelsReceivedToday = $this->getParcelsReceivedToday($warehouseId);

        // Pending shipments
        $pendingShipments = $this->getPendingShipments($warehouseId);

        // Unpaid parcels
        $unpaidParcels = $this->getUnpaidParcels($warehouseId);

        // Claims opened (we'll count parcels with issues or special handling)
        $claimsOpened = $this->getClaimsOpened($warehouseId);

        // Total weight received today
        $totalWeightReceivedToday = $this->getTotalWeightReceivedToday($warehouseId);

        return [
            'parcelsReceivedToday' => $parcelsReceivedToday,
            'pendingShipments' => $pendingShipments,
            'unpaidParcels' => $unpaidParcels,
            'claimsOpened' => $claimsOpened,
            'totalWeightReceivedToday' => $totalWeightReceivedToday
        ];
    }

    /**
     * Get parcels received today
     */
    private function getParcelsReceivedToday(int $warehouseId): int
    {
        try {
            // Count parcels created today for this warehouse
            $sql = "SELECT COUNT(*) as count FROM parcels p
                    INNER JOIN shipments s ON p.shipment_id = s.shipment_id
                    WHERE s.warehouse_id = :warehouse_id
                    AND DATE(p.created_at) = CURDATE()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Error getting parcels received today: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get pending shipments
     */
    private function getPendingShipments(int $warehouseId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM shipments
                    WHERE warehouse_id = :warehouse_id
                    AND status = 'pending'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Error getting pending shipments: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get unpaid parcels
     */
    private function getUnpaidParcels(int $warehouseId): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM parcels p
                    INNER JOIN shipments s ON p.shipment_id = s.shipment_id
                    WHERE s.warehouse_id = :warehouse_id
                    AND p.payment_status = 'unpaid'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Error getting unpaid parcels: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get claims opened (parcels with special packaging or issues)
     */
    private function getClaimsOpened(int $warehouseId): int
    {
        try {
            // Count parcels with special packaging requests or that might indicate claims
            $sql = "SELECT COUNT(DISTINCT p.parcel_id) as count FROM parcels p
                    INNER JOIN shipments s ON p.shipment_id = s.shipment_id
                    INNER JOIN parcel_items pi ON p.parcel_id = pi.parcel_id
                    WHERE s.warehouse_id = :warehouse_id
                    AND (pi.special_packaging = TRUE OR p.status = 'damaged' OR p.status = 'lost')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Error getting claims opened: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total weight received today
     */
    private function getTotalWeightReceivedToday(int $warehouseId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(p.weight), 0) as total_weight FROM parcels p
                    INNER JOIN shipments s ON p.shipment_id = s.shipment_id
                    WHERE s.warehouse_id = :warehouse_id
                    AND DATE(p.created_at) = CURDATE()";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float) ($result['total_weight'] ?? 0);
        } catch (Exception $e) {
            error_log('Error getting total weight received today: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get parcel intake trend (last 7 days)
     */
    private function getParcelIntakeTrend(int $warehouseId): array
    {
        try {
            $sql = "SELECT DATE(p.created_at) as date, COUNT(*) as count
                    FROM parcels p
                    INNER JOIN shipments s ON p.shipment_id = s.shipment_id
                    WHERE s.warehouse_id = :warehouse_id
                    AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(p.created_at)
                    ORDER BY date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fill in missing dates with 0 counts
            $trend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $found = false;
                foreach ($results as $result) {
                    if ($result['date'] === $date) {
                        $trend[] = ['date' => $date, 'count' => (int) $result['count']];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $trend[] = ['date' => $date, 'count' => 0];
                }
            }

            return $trend;
        } catch (Exception $e) {
            error_log('Error getting parcel intake trend: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get shipment status distribution
     */
    private function getShipmentStatusDistribution(int $warehouseId): array
    {
        try {
            $sql = "SELECT status, COUNT(*) as count
                    FROM shipments
                    WHERE warehouse_id = :warehouse_id
                    GROUP BY status
                    ORDER BY count DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $distribution = [];
            foreach ($results as $result) {
                $distribution[] = [
                    'name' => ucfirst($result['status']),
                    'count' => (int) $result['count']
                ];
            }

            return $distribution;
        } catch (Exception $e) {
            error_log('Error getting shipment status distribution: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get payments summary for the last 7 days
     */
    private function getPaymentsSummary(int $warehouseId): array
    {
        try {
            $sql = "SELECT
                        DAYNAME(pay.created_at) as period,
                        SUM(CASE WHEN pay.status = 'completed' THEN 1 ELSE 0 END) as paid,
                        SUM(CASE WHEN pay.status != 'completed' THEN 1 ELSE 0 END) as unpaid
                    FROM payments pay
                    INNER JOIN invoices inv ON pay.invoice_id = inv.invoice_id
                    INNER JOIN parcels par ON inv.parcel_id = par.parcel_id
                    INNER JOIN shipments s ON par.shipment_id = s.shipment_id
                    WHERE s.warehouse_id = :warehouse_id
                    AND pay.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DAYOFWEEK(pay.created_at), DAYNAME(pay.created_at)
                    ORDER BY DAYOFWEEK(pay.created_at)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['warehouse_id' => $warehouseId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $summary = [];
            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

            foreach ($days as $day) {
                $found = false;
                foreach ($results as $result) {
                    if ($result['period'] === $day) {
                        $summary[] = [
                            'period' => $day,
                            'paid' => (int) $result['paid'],
                            'unpaid' => (int) $result['unpaid']
                        ];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $summary[] = ['period' => $day, 'paid' => 0, 'unpaid' => 0];
                }
            }

            return $summary;
        } catch (Exception $e) {
            error_log('Error getting payments summary: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activities from activity log
     */
    private function getRecentActivities(int $warehouseId): array
    {
        try {
            $activities = $this->activityLogModel->getRecentActivities($warehouseId, 10);

            $formattedActivities = [];
            foreach ($activities as $activity) {
                $formattedActivities[] = [
                    'id' => $activity['activity_id'],
                    'type' => $activity['action'],
                    'message' => $activity['description'],
                    'timestamp' => $this->formatTimestamp($activity['created_at']),
                    'staff' => trim($activity['firstName'] . ' ' . $activity['lastName']),
                    'reference_id' => $activity['reference_id'],
                    'reference_type' => $activity['reference_type']
                ];
            }

            return $formattedActivities;
        } catch (Exception $e) {
            error_log('Error getting recent activities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get notifications for warehouse
     */
    private function getNotifications(int $warehouseId): array
    {
        try {
            $notifications = $this->notificationModel->getWarehouseNotifications($warehouseId);

            $formattedNotifications = [];
            foreach ($notifications as $notification) {
                $formattedNotifications[] = [
                    'id' => $notification['notification_id'],
                    'message' => $notification['message'],
                    'type' => $notification['priority'] === 'urgent' ? 'urgent' : ($notification['priority'] === 'high' ? 'warning' : 'info'),
                    'timestamp' => $this->formatTimestamp($notification['created_at']),
                    'is_read' => (bool) $notification['is_read']
                ];
            }

            return $formattedNotifications;
        } catch (Exception $e) {
            error_log('Error getting notifications: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format timestamp to relative time
     */
    private function formatTimestamp(string $timestamp): string
    {
        $now = new DateTime();
        $activityTime = new DateTime($timestamp);
        $interval = $now->diff($activityTime);

        if ($interval->days === 0) {
            if ($interval->h === 0) {
                if ($interval->i === 0) {
                    return 'Just now';
                }
                return $interval->i . ' minutes ago';
            }
            return $interval->h . ' hours ago';
        } elseif ($interval->days === 1) {
            return 'Yesterday';
        } elseif ($interval->days < 7) {
            return $interval->days . ' days ago';
        } else {
            return $activityTime->format('M j, Y');
        }
    }
}