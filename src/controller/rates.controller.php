<?php

declare(strict_types=1);

require_once MODEL . 'rates.model.php';

class RatesController
{
    private $ratesModel;

    public function __construct()
    {
        $this->ratesModel = new RatesModel();
    }

    /**
     * Get all countries for dropdowns
     */
    public function getCountries(): array
    {
        try {
            $countries = $this->ratesModel->getAllCountries();
            return [
                'status' => 'success',
                'code' => 200,
                'data' => $countries
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to fetch countries: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get cities by country ID
     */
    public function getCitiesByCountry(int $countryId): array
    {
        try {
            $cities = $this->ratesModel->getCitiesByCountryId($countryId);
            return [
                'status' => 'success',
                'code' => 200,
                'data' => $cities
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to fetch cities: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all shipment types
     */
    public function getShipmentTypes(): array
    {
        try {
            $shipmentTypes = $this->ratesModel->getAllShipmentTypes();
            return [
                'status' => 'success',
                'code' => 200,
                'data' => $shipmentTypes
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to fetch shipment types: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all cargo categories
     */
    public function getCargoCategories(): array
    {
        try {
            $cargoCategories = $this->ratesModel->getAllCargoCategories();
            return [
                'status' => 'success',
                'code' => 200,
                'data' => $cargoCategories
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to fetch cargo categories: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate rate based on provided parameters
     */
    public function calculateRate(array $data): array
    {
        try {
            // Validate required fields
            $required = ['originCountryId', 'destinationCountryId', 'shipmentTypeId', 'cargoCategoryId'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return [
                        'status' => 'error',
                        'code' => 400,
                        'message' => "Missing required field: {$field}"
                    ];
                }
            }

            // Get rate data
            $rate = $this->ratesModel->getRate(
                (int) $data['shipmentTypeId'],
                (int) $data['cargoCategoryId'],
                (int) $data['originCountryId'],
                (int) $data['destinationCountryId']
            );

            if (!$rate) {
                return [
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'No rate found for the selected route and cargo type, contact support.'
                ];
            }

            // Calculate cost based on charging method and input values
            $calculation = $this->calculateCost($rate, $data);

            // Get additional details for the response
            $originCountry = $this->ratesModel->getCountryById($rate['origin_country_id']);
            $destinationCountry = $this->ratesModel->getCountryById($rate['destination_country_id']);
            $shipmentType = $this->ratesModel->getShipmentTypeById($rate['shipment_type_id']);
            $cargoCategory = $this->ratesModel->getCargoCategoryById($rate['cargo_category_id']);

            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'origin_country' => $originCountry['name'] ?? 'Unknown',
                    'destination_country' => $destinationCountry['name'] ?? 'Unknown',
                    'shipment_type' => $shipmentType['name'] ?? 'Unknown',
                    'cargo_category' => $cargoCategory['name'] ?? 'Unknown',
                    'estimated_days' => $shipmentType['estimated_days'] ?? 0,
                    'total_cost' => $calculation['totalCost'],
                    'currency' => $rate['currency'] ?? 'USD',
                    'weight' => $data['weight'] ?? null,
                    'volume' => isset($data['volume']) ? (float)$data['volume'] : null,
                    'unit' => $cargoCategory['unit'] ?? 'kg'
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to calculate rate: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate cost based on rate and input parameters
     */
    private function calculateCost(array $rate, array $data): array
    {
        $baseRate = (float) $rate['base_rate'];
        $additionalCharges = (float) ($rate['additional_charges'] ?? 0);
        $totalCost = $baseRate + $additionalCharges;

        $calculation = [
            'baseRate' => $baseRate,
            'additionalCharges' => $additionalCharges,
            'totalCost' => round($totalCost, 2)
        ];

        return $calculation;
    }
}