<?php

declare(strict_types=1);

require_once MODEL . 'client.model.php';
require_once MODEL . 'shipment.model.php';
require_once MODEL . 'parcel.model.php';
require_once MODEL . 'invoice.model.php';
require_once MODEL . 'payment.model.php';

/**
 * ClientsController
 *
 * Handles client CRUD and authentication.
 * Works with ClientsModel that aligns with the clients table schema.
 */
class ClientsController
{
    protected ClientsModel $clientModel;
    protected ShipmentModel $shipmentModel;
    protected ParcelModel $parcelModel;
    protected InvoiceModel $invoiceModel;
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->clientModel = new ClientsModel();
        $this->shipmentModel = new ShipmentModel();
        $this->parcelModel = new ParcelModel();
        $this->invoiceModel = new InvoiceModel();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Get all clients
     */
    public function getAllClients(): string
    {
        if($clients = $this->clientModel->getAllClients()){
            return json_encode([
                'status' => 'success',
                'code' => 200,
                'clients' => $clients,
                'message' => null
            ], JSON_PRETTY_PRINT);
        }
        return json_encode([
            'status' => 'error',
            'code' => 404,
            'message' => 'No clients found'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get client by ID
     * @param int $clientId
     * @return string
     */
    public function getClientById(int $clientId): string
    {
        if(!$clientId){
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid client ID'
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientById($clientId);
        if (!$client) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => 'Client not found'
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'code' => 200,
            'client' => $client,
            'message' => null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get client by email
     * @param string $email
     * @return string
     */
    public function getClientByEmail(string $email): string
    {
        if (!$email) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'message' => 'Email is required',
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientByEmail($email);
        if ($client) {
            return json_encode([
                'status' => 'success',
                'code' => 200,
                'client' => $client,
                'message' => null,
            ], JSON_PRETTY_PRINT);
        }
        return json_encode([
            'status' => 'error',
            'code' => 404,
            'client' => null,
            'message' => 'Client not found with this email',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get client by phone number
     * @param string $phone
     * @return string
     */
    public function getClientByPhone(string $phone): string
    {
        if (!$phone) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'message' => 'Phone number is required',
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientByPhone($phone);
        if ($client) {
            return json_encode([
                'status' => 'success',
                'code' => 200,
                'client' => $client,
                'message' => null,
            ], JSON_PRETTY_PRINT);
        }
        return json_encode([
            'status' => 'error',
            'code' => 404,
            'client' => null,
            'message' => 'Client not found with this phone number',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new client
     * Expected data: firstName, lastName, email, password, phone(optional), address(optional), company(optional)
     */
    public function createClient(array $data): string
    {
        $required = ['firstName', 'lastName', 'email', 'password'];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'message' => 'Missing required fields: ' . implode(', ', $missing),
            ], JSON_PRETTY_PRINT);
        }

        if ($client = $this->clientModel->getClientByEmail($data['email'])) {
            return json_encode([
                'status' => 'error',
                'code' => 409,
                'field' => 'email',
                'message' => 'Email already in use by another account',
            ], JSON_PRETTY_PRINT);
        }

        $violation = $this->checkUniqueConstraints($data, null);
        if ($violation) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'field' => $violation['field'],
                'message' => $violation['message'],
            ], JSON_PRETTY_PRINT);
        }

        // Hash the password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);

        $clientId = $this->clientModel->createClient($data);
        if ($clientId === false) {
            return json_encode([
                'status' => 'error',
                'code' => 500,
                'client' => null,
                'message' => 'Failed to create client: ' . $this->clientModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientById((int) $clientId);
        return json_encode([
            'status' => 'success',
            'code' => 201,
            'client' => $client,
            'message' => 'Client created successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing client
     * Allowed fields: firstName, lastName, email, phone, address, company
     */
    public function updateClient(int $id, array $data): string
    {
        $existing = $this->clientModel->getClientById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'client' => null,
                'message' => 'Client not found',
            ], JSON_PRETTY_PRINT);
        }

        $violation = $this->checkUniqueConstraints($data, $id);
        if ($violation) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'field' => $violation['field'],
                'message' => $violation['message'],
            ], JSON_PRETTY_PRINT);
        }

        $updated = $this->clientModel->updateClient($id, $data);
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'code' => 500,
                'client' => null,
                'message' => 'Failed to update client: ' . $this->clientModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientById($id);
        return json_encode([
            'status' => 'success',
            'code' => 200,
            'client' => $client,
            'message' => 'Client updated successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a client by ID
     * @param int $id
     * @return string
     */
    public function deleteClient(int $id): string
    {
        $existing = $this->clientModel->getClientById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'client' => null,
                'message' => 'Client not found',
            ], JSON_PRETTY_PRINT);
        }

        $deleted = $this->clientModel->deleteClient($id);
        return json_encode([
            'status' => $deleted ? 'success' : 'error',
            'code' => $deleted ? 200 : 500,
            'client' => null,
            'message' => $deleted ? 'Client deleted successfully' : ('Failed to delete client: ' . $this->clientModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Login with email and password
     */
    public function login(string $email, string $password): string
    {
        $client = $this->clientModel->clientLogin($email, $password);
        return json_encode([
            'status' => $client ? 'success' : 'error',
            'code' => $client ? 200 : 401,
            'client' => $client,
            'message' => $client ? 'Login successful' : ('Login failed: ' . $this->clientModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update password for a client
     */
    public function updatePassword(int $clientId, string $newPassword): string
    {
        $existing = $this->clientModel->getClientById($clientId);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => 'Client not found',
            ], JSON_PRETTY_PRINT);
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->clientModel->updateClientPassword($clientId, $hashedPassword);
        return json_encode([
            'status' => $updated ? 'success' : 'error',
            'code' => $updated ? 200 : 500,
            'client' => null,
            'message' => $updated ? 'Password updated successfully' : ('Failed to update password: ' . $this->clientModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Enforce unique email. $currentClientId excludes that client when updating.
     * @return array{field:string,message:string}|null
     */
    private function checkUniqueConstraints(array $data, ?int $currentClientId = null): ?array
    {
        if (!empty($data['email'])) {
            $existing = $this->clientModel->getClientByEmail($data['email']);
            if ($existing && (!isset($existing['client_id']) || (int) $existing['client_id'] !== (int) ($currentClientId ?? -1))) {
                return ['field' => 'email', 'message' => 'Email already in use by another account'];
            }
        }

        return null;
    }

    /**
     * Get client dashboard data
     * @param int $clientId
     * @return string
     */
    public function getClientDashboard(int $clientId): string
    {
        try {
            // Get metrics
            $metrics = $this->getDashboardMetrics($clientId);

            // Get recent shipments
            $recentShipments = $this->getRecentShipments($clientId);

            // Get notifications
            $notifications = $this->getClientNotifications($clientId);

            // Welcome message
            $welcomeMessage = "Welcome back! Here's an overview of your shipments and account.";

            $dashboardData = [
                'metrics' => $metrics,
                'recentShipments' => $recentShipments,
                'notifications' => $notifications,
                'welcomeMessage' => $welcomeMessage
            ];

            return json_encode([
                'status' => 'success',
                'code' => 200,
                'data' => $dashboardData
            ], JSON_PRETTY_PRINT);

        } catch (Exception $e) {
            return json_encode([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to load dashboard data: ' . $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Get dashboard metrics for client
     * @param int $clientId
     * @return array
     */
    private function getDashboardMetrics(int $clientId): array
    {
        // Active shipments (not delivered)
        $activeShipments = $this->shipmentModel->getActiveShipmentsCount($clientId);

        // Pending payments (sum of unpaid invoice amounts)
        $pendingPayments = $this->invoiceModel->getTotalUnpaidAmount($clientId);

        // Delivered this month
        $deliveredThisMonth = $this->shipmentModel->getDeliveredThisMonthCount($clientId);

        // In warehouse items (parcels with status 'in_warehouse')
        $inWarehouseItems = $this->parcelModel->getInWarehouseCount($clientId);

        return [
            'activeShipments' => [
                'count' => (int) $activeShipments,
                'icon' => 'package',
                'label' => 'Active Shipments'
            ],
            'pendingPayments' => [
                'count' => (float) $pendingPayments,
                'currency' => '$',
                'icon' => 'dollar',
                'label' => 'Pending Payments'
            ],
            'deliveredThisMonth' => [
                'count' => (int) $deliveredThisMonth,
                'icon' => 'check',
                'label' => 'Delivered This Month'
            ],
            'inWarehouse' => [
                'count' => (int) $inWarehouseItems,
                'icon' => 'box',
                'label' => 'In Warehouse'
            ]
        ];
    }

    /**
     * Get recent shipments for client
     * @param int $clientId
     * @return array
     */
    private function getRecentShipments(int $clientId): array
    {
        $shipments = $this->shipmentModel->getRecentShipmentsByClient($clientId, 5);

        $result = [];
        foreach ($shipments as $shipment) {
            // Get destination city name if available
            $destination = $shipment['destination_country'];
            if (!empty($shipment['destination_city_id'])) {
                // This would need to be implemented in the model to join with cities table
                $cityName = $this->getCityName($shipment['destination_city_id']);
                if ($cityName) {
                    $destination = $cityName . ', ' . $shipment['destination_country'];
                }
            }

            $result[] = [
                'id' => $shipment['tracking_number'],
                'status' => $shipment['status'],
                'destination' => $destination,
                'date' => date('Y-m-d', strtotime($shipment['created_at'])),
                'statusColor' => $this->getStatusColor($shipment['status'])
            ];
        }

        return $result;
    }

    /**
     * Get client notifications
     * @param int $clientId
     * @return array
     */
    private function getClientNotifications(int $clientId): array
    {
        // For now, return empty array since we don't have the notification model yet
        // This will be implemented when we create the ClientNotificationModel
        return [];
    }

    /**
     * Get city name by ID
     * @param int $cityId
     * @return string|null
     */
    private function getCityName(int $cityId): ?string
    {
        // This would need to be implemented - for now return null
        // We'll need to create a CityModel or add this to an existing model
        return null;
    }

    /**
     * Get status color mapping
     * @param string $status
     * @return string
     */
    private function getStatusColor(string $status): string
    {
        $statusColors = [
            'pending' => 'gray',
            'processing' => 'yellow',
            'in_transit' => 'blue',
            'customs' => 'orange',
            'out_for_delivery' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            'returned' => 'red'
        ];

        return $statusColors[$status] ?? 'gray';
    }
}
