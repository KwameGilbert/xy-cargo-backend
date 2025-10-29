<?php

declare(strict_types=1);

require_once MODEL . '/rates.model.php';

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
                    'message' => 'No rate found for the selected route and cargo type'
                ];
            }

            // Calculate cost based on charging method and input values
            $calculation = $this->calculateCost($rate, $data);

            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'rate' => $rate,
                    'calculation' => $calculation
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
        $chargingMethod = $rate['charging_method'];
        $baseCost = (float) $rate['base_cost'];
        $totalCost = $baseCost;

        $calculation = [
            'chargingMethod' => $chargingMethod,
            'baseCost' => $baseCost,
            'additionalCosts' => [],
            'totalCost' => 0
        ];

        switch ($chargingMethod) {
            case 'weight':
                if (isset($data['weight']) && is_numeric($data['weight'])) {
                    $weight = (float) $data['weight'];
                    $minWeight = (float) ($rate['min_chargeable_weight'] ?? 0);
                    $chargeableWeight = max($weight, $minWeight);
                    $costPerKg = (float) ($rate['cost_per_kg'] ?? 0);
                    $weightCost = $chargeableWeight * $costPerKg;
                    
                    $calculation['additionalCosts'][] = [
                        'type' => 'weight',
                        'input' => $weight,
                        'minChargeable' => $minWeight,
                        'chargeable' => $chargeableWeight,
                        'costPerUnit' => $costPerKg,
                        'cost' => $weightCost
                    ];
                    
                    $totalCost += $weightCost;
                }
                break;

            case 'piece':
                if (isset($data['pieces']) && is_numeric($data['pieces'])) {
                    $pieces = (int) $data['pieces'];
                    $minPieces = (int) ($rate['min_chargeable_pieces'] ?? 1);
                    $chargeablePieces = max($pieces, $minPieces);
                    $costPerPiece = (float) ($rate['cost_per_piece'] ?? 0);
                    $piecesCost = $chargeablePieces * $costPerPiece;
                    
                    $calculation['additionalCosts'][] = [
                        'type' => 'pieces',
                        'input' => $pieces,
                        'minChargeable' => $minPieces,
                        'chargeable' => $chargeablePieces,
                        'costPerUnit' => $costPerPiece,
                        'cost' => $piecesCost
                    ];
                    
                    $totalCost += $piecesCost;
                }
                break;

            case 'size':
                if (isset($data['cbm']) && is_numeric($data['cbm'])) {
                    $cbm = (float) $data['cbm'];
                    $minSize = (float) ($rate['min_chargeable_size'] ?? 0);
                    $chargeableSize = max($cbm, $minSize);
                    $costPerCbm = (float) ($rate['cost_per_cbm'] ?? 0);
                    $sizeCost = $chargeableSize * $costPerCbm;
                    
                    $calculation['additionalCosts'][] = [
                        'type' => 'cbm',
                        'input' => $cbm,
                        'minChargeable' => $minSize,
                        'chargeable' => $chargeableSize,
                        'costPerUnit' => $costPerCbm,
                        'cost' => $sizeCost
                    ];
                    
                    $totalCost += $sizeCost;
                }
                break;
        }

        $calculation['totalCost'] = round($totalCost, 2);
        return $calculation;
    }
}