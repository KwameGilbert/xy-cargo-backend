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
        $stmt = $this->db->prepare("SELECT type_id, name, description, estimated_days FROM shipment_types ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all cargo categories
     */
    public function getAllCargoCategories(): array
    {
        $stmt = $this->db->prepare("
            SELECT cc.category_id, cc.name, cc.description, cc.base_rate, cc.unit, cc.min_quantity, cc.shipment_type_id
            FROM cargo_categories cc
            ORDER BY cc.name
        ");
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
     * Get country by ID
     */
    public function getCountryById(int $countryId): ?array
    {
        $stmt = $this->db->prepare("SELECT country_id, name, code FROM countries WHERE country_id = ?");
        $stmt->execute([$countryId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get shipment type by ID
     */
    public function getShipmentTypeById(int $typeId): ?array
    {
        $stmt = $this->db->prepare("SELECT type_id, name, description, estimated_days FROM shipment_types WHERE type_id = ?");
        $stmt->execute([$typeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get cargo category by ID
     */
    public function getCargoCategoryById(int $categoryId): ?array
    {
        $stmt = $this->db->prepare("SELECT category_id, name, description, base_rate, unit, min_quantity FROM cargo_categories WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}