<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        // Shipments Dashboard Data
        $group->get('/warehouse-staff/shipments/dashboard', function ($request, $response) {
            require_once CONTROLLER . 'shipment.controller.php';
            $controller = new ShipmentsController();
            
            $result = $controller->getShipmentsDashboard();
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Get All Shipments with Details
        $group->get('/warehouse-staff/shipments/data', function ($request, $response) {
            require_once CONTROLLER . 'shipment.controller.php';
            $controller = new ShipmentsController();
            
            $result = $controller->getAllShipmentsWithDetails();
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Get Single Shipment with Details
        $group->get('/warehouse-staff/shipments/{id}', function ($request, $response, $args) {
            require_once CONTROLLER . 'shipment.controller.php';
            $controller = new ShipmentsController();
            
            $result = $controller->getShipmentWithDetails((int)$args['id']);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Create Shipment
        $group->post('/warehouse-staff/shipments', function ($request, $response) {
            require_once CONTROLLER . 'shipment.controller.php';
            $controller = new ShipmentsController();
            
            $data = $request->getParsedBody();
            $result = $controller->createShipment($data);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Update Shipment
        $group->patch('/warehouse-staff/shipments/{id}', function ($request, $response, $args) {
            require_once CONTROLLER . 'shipment.controller.php';
            $controller = new ShipmentsController();
            
            $data = $request->getParsedBody();
            $result = $controller->updateShipment((int)$args['id'], $data);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Update Shipment Status
        $group->patch('/warehouse-staff/shipments/{id}/status', function ($request, $response, $args) {
            require_once CONTROLLER . 'shipment.controller.php';
            $controller = new ShipmentsController();
            
            $data = $request->getParsedBody();
            $result = $controller->updateShipmentStatus((int)$args['id'], $data['status']);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Delete Shipment
        $group->delete('/warehouse-staff/shipments/{id}', function ($request, $response, $args) {
            require_once CONTROLLER . 'shipment.controller.php';
            $controller = new ShipmentsController();
            
            $result = $controller->deleteShipment((int)$args['id']);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        });
    });
};