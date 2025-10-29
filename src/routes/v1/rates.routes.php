<?php

declare(strict_types=1);

/**
 * Rates API Routes
 *
 * These routes handle rate calculation and lookup operations
 */

require_once CONTROLLER . '/rates.controller.php';

return function ($app): void {
    $ratesController = new RatesController();
    // Get all countries for dropdowns
    $app->get('/v1/rates/countries', function ($request, $response, $args) use ($ratesController) {
        $result = $ratesController->getCountries();
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });

    // Get cities by country ID
    $app->get('/v1/rates/countries/{countryId}/cities', function ($request, $response, $args) use ($ratesController) {
        $countryId = (int) $args['countryId'];
        $result = $ratesController->getCitiesByCountry($countryId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });

    // Get all shipment types
    $app->get('/v1/rates/shipment-types', function ($request, $response, $args) use ($ratesController) {
        $result = $ratesController->getShipmentTypes();
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });

    // Get all cargo categories
    $app->get('/v1/rates/cargo-categories', function ($request, $response, $args) use ($ratesController) {
        $result = $ratesController->getCargoCategories();
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });

    // Calculate rate based on parameters
    $app->post('/v1/rates/calculate', function ($request, $response, $args) use ($ratesController) {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $ratesController->calculateRate($data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });
};