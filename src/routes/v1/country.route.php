<?php

declare(strict_types=1);

/**
 * Country API Routs
 */

require_once CONTROLLER . 'countries.controller.php';

return function ($app): void {
    $countryController = new CountriesController();

    //Get all countries
    $app->get('/v1/countries', function ($request, $response) use ($countryController){
        $result = $countryController->getAllCountries();
        $code = $result['code'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    });

    $app->get('/v1/countries/{id}', function($request, $response, $args) use ($countryController){
        $id = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $countryController->getCountryById($id);
        $code = $result['code'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    });

    $app->get('/v1/countries/code/{code}', function ($request, $response, $args) use ($countryController){
        $args = isset($args['code']) ? $args['code'] : '';
        $result = $countryController->getCountryByCode($args);
        $code = $result['code'];
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    });

    $app->post('/v1/countries', function ($request, $response) use ($countryController){
        $data = json_decode((string) $request->getBody(), true) ?? [];
        $name = $data['name'];
        $code = $data['code'];
        $result = $countryController->createCountry($name, $code);
        $response->getBody()->write(json_encode($result));
       return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });

    $app->patch('/v1/countries/{id}', function ($request, $response, $args) use ($countryController){
        $id = isset($args['id']) ? $args['id'] : 0;
        $data = json_decode((string) $request->getBody(), true);
        $name = $data['name'];
        $code = $data['code'];
        $result = $countryController->updateCountry($id, $name, $code);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
    });

    $app->delete('/v1/countries/{id}', function($request, $response, $args) use ($countryController){
        $countryId = isset($args['id']) ? (int) $args['id'] : 0;
        $result = $countryController->deleteCountry($countryId);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($result['code']);
});
};