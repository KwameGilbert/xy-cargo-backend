<?php

declare(strict_types=1);

require_once MODEL . 'countries.model.php';

/**
 * CountriesController
 *
 * Handles country CRUD operations.
 */
class CountriesController
{
    protected CountriesModel $countriesModel;

    public function __construct()
    {
        // NOTE: The MODEL constant should be defined to point to the directory 
        // containing the CountriesModel.php file.
        $this->countriesModel = new CountriesModel();
    }

    /**
     * Get all countries
     */
    public function getAllCountries(): array
    {
        $countries = $this->countriesModel->getAllCountries();
        return [
            'status' => !empty($countries) ? 'success' : 'error',
            'countries' => $countries,
            'message' => empty($countries) ? 'No countries found' : null,
            'code' => !empty($countries) ? 200 : 404,
        ];
    }

    /**
     * Get country by id
     */
    public function getCountryById(int $id): array{
        $country = $this->countriesModel->getCountryById($id);
        return [
          'status' => !empty($country) ? 'success' : 'error',
            'country' => $country,
            'message' => empty($countries) ? 'No country found with id: {$id}' : null,
            'code' => !empty($country) ? 200 : 404,
        ];
    }

    /**
     * Get Country by code
     */
    public function getCountryByCode(string $code): array{
        $country = $this->countriesModel->getCountryByCode($code);
        return [
            'status' => !empty($country) ? 'success' : 'error',
            'country' => $country,
            'message' => empty($country) ? 'No country found with this code:{$code}' : 'Country found with code: {$code}',
            'code' => !empty($country) ? 200 : 404,
        ];
    }

    /**
     * Create a new country
     */
    public function createCountry(string $name, string $code): array
    {
        $countryId = $this->countriesModel->createCountry($name, $code);
        if(!$countryId){
            return [ 'status' => 'error', 'code' => 500, 'message' => 'Failed to create country: ' . $this->countriesModel->getLastError()];
        }
        $country = $this->countriesModel->getCountryById($countryId);
        return [ 'status' => 'success', 'code' => 201, 'country' => $country , 'message' => 'Country created successfully'];
    }

    /**
     * Update a country 
     */
    public function updateCountry(int $id, string $name, string $code): array
    {
        $countryExists = $this->countriesModel->getCountryById($id);
        if(!$countryExists){
            return ['status' => 'error', 'code' => 404, 'message' => 'Country not found'];
        }

        $updatedCountry = $this->countriesModel->updateCountry($id, $name, $code);
        if(!$updatedCountry){
            return ['status' => 'error', 'code' => 500, 'message' => 'Failed to update country: '. $this->countriesModel->getLastError()];
        }

        $country = $this->countriesModel->getCountryById($id);
        return [
            'status' => 'success', 'code' => 200, 'country' => $country, 'message' => 'Country updated successfully'
        ];
    }

    /**
     * Delete a country
     */
    public function deleteCountry(int $id): array{
       $countryExists = $this->countriesModel->getCountryById($id);
        if(!$countryExists){
            return ['status' => 'error', 'code' => 404, 'message' => 'Country not found'];
        }
        $deletedCountry = $this->countriesModel->deleteCountry($id);
        return [
            'status' => $deletedCountry ? 'success' : 'error',
            'code' => $deletedCountry ? 200 : 500,
            'message' => $deletedCountry ? 'Shipment deleted successfully' : ('Failed to delete country: ' . $this->countriesModel->getLastError())
        ];
    }
}