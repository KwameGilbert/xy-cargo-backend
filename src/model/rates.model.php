<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';


class RatesModel 
{
     /** @var PDO */
    protected PDO $db;

    /** @var string */
    protected string $lastError = '';

    public function __construct()
    {
        try {
            $database = new Database();
            $connection = $database->getConnection();
            if (!$connection) {
                throw new PDOException('Database connection is null');
            }
            $this->db = $connection;
        } catch (PDOException $e) {
            $this->lastError = 'Database connection failed: ' . $e->getMessage();
            error_log($this->lastError);
            throw $e; // Re-throw to let calling code handle it
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Execute a prepared statement with error handling
     */
    protected function executeQuery(\PDOStatement $statement, array $params = []): bool
    {
        try {
            return $statement->execute($params);
        } catch (PDOException $e) {
            $this->lastError = 'Query execution failed: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }
    
    /**
     * Get all countries
     */
    public function getAllCountries(): array
    {
        $stmt = $this->db->prepare("SELECT country_id, name, code FROM countries ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get cities by country ID
     */
    public function getCitiesByCountryId(int $countryId): array
    {
        $stmt = $this->db->prepare("SELECT city_id, name FROM cities WHERE country_id = ? ORDER BY name");
        $stmt->execute([$countryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all shipment types
     */
    public function getAllShipmentTypes(): array
    {
        $stmt = $this->db->prepare("SELECT type_id, name, description FROM shipment_types ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all cargo categories
     */
    public function getAllCargoCategories(): array
    {
        $stmt = $this->db->prepare("SELECT category_id, name, description FROM cargo_categories ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get rate by route and cargo parameters
     */
    public function getRate(int $shipmentTypeId, int $cargoCategoryId, int $originCountryId, int $destinationCountryId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM rates 
            WHERE shipment_type_id = ? 
            AND cargo_category_id = ? 
            AND origin_country_id = ? 
            AND destination_country_id = ? 
            AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$shipmentTypeId, $cargoCategoryId, $originCountryId, $destinationCountryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get all active rates (for admin purposes)
     */
    public function getAllRates(): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   st.name as shipment_type_name,
                   cc.name as cargo_category_name,
                   oc.name as origin_country_name,
                   dc.name as destination_country_name
            FROM rates r
            LEFT JOIN shipment_types st ON r.shipment_type_id = st.type_id
            LEFT JOIN cargo_categories cc ON r.cargo_category_id = cc.category_id
            LEFT JOIN countries oc ON r.origin_country_id = oc.country_id
            LEFT JOIN countries dc ON r.destination_country_id = dc.country_id
            WHERE r.status = 'active'
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}