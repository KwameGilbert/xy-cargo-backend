<?php

declare(strict_types=1);

require_once CONFIG . 'Database.php';

/**
 * Countries Model aligned to database/schema.sql
 *
 * Tables used:
 * - countries(country_id, name, code, created_at, updated_at)
 */
class CountriesModel
{
    /** @var PDO */
    protected PDO $db;

    /** @var string */
    private string $tableName = 'countries';

    /** @var string */
    private string $lastError = '';

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
            throw $e;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Execute a prepared statement with error handling
     */
    private function executeStatement(PDOStatement $stmt, array $params): bool
    {
        try {
            $stmt->execute($params);
            return true;
        } catch (PDOException $e) {
            $this->lastError = 'Statement execution failed: ' . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }


    /**
     * List all countries
     */
    public function getAllCountries(): array
    {
        $sql = "SELECT country_id, name, code, created_at, updated_at FROM " . $this->tableName . " ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        if (!$this->executeStatement($stmt, [])) {
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get country by ID
     */
    public function getCountryById(int $id): array
    {
        $sql = "SELECT country_id, name, code, created_at, updated_at FROM " . $this->tableName . " WHERE country_id = :id";
        $stmt = $this->db->prepare($sql);
        if (!$this->executeStatement($stmt, ['id' => $id])) {
            return [];
        }
        $country = $stmt->fetch(PDO::FETCH_ASSOC);
        return $country ? $country : [];
    }

    /**
     * Get country by code
     */
    public function getCountryByCode(string $code): array
    {
        $sql = "SELECT country_id, name, code, created_at, updated_at FROM " . $this->tableName . " WHERE code = :code";
        $stmt = $this->db->prepare($sql);
        if (!$this->executeStatement($stmt, ['code' => $code])) {
            return [];
        }
        $country = $stmt->fetch(PDO::FETCH_ASSOC);
        return $country ? $country : [];
    }

    /**
     * Create a new country
     */
    public function createCountry(string $name, string $code): int|false
    {
        $sql = "INSERT INTO " . $this->tableName . " (name, code, created_at, updated_at) VALUES (:name, :code, NOW(), NOW())";
        $stmt = $this->db->prepare($sql);
        if(!$this->executeStatement($stmt, ['name' => $name, 'code' => $code])){
            return false;
        }
        return (int) $this->db->lastInsertId();
    }   
    /**
     * Update an existing country
     */
    public function updateCountry(int $id, string $name, string $code): bool
    {
        $sql = "UPDATE " . $this->tableName . " SET name = :name, code = :code, updated_at = NOW() WHERE country_id = :id";
        $stmt = $this->db->prepare($sql);
        return $this->executeStatement($stmt, ['id' => $id, 'name' => $name, 'code' => $code]);
    }

    /**
     * Delete a country by ID
     */
    public function deleteCountry(int $id): bool
    {
        $sql = "DELETE FROM " . $this->tableName . " WHERE country_id = :id";
        $stmt = $this->db->prepare($sql);
        return $this->executeStatement($stmt, ['id' => $id]);
    }

}