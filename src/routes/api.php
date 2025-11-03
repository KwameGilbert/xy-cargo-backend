<?php
return function ($app): void {
    // Define API routes here. This file is responsible for registering all API endpoints.
    // Get the request URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Map route prefixes to their router files
    $routeMap = [
        '/v1/auth' => ROUTES . '/v1/auth.routes.php',
        '/v1/clients' => ROUTES . '/v1/client.routes.php',
        'v1countries' => ROUTES . '/v1/country.route.php',
        '/v1/parcels' => ROUTES . '/v1/parcel.routes.php',
        '/v1/invoices' => ROUTES . '/v1/invoice.routes.php',
        '/v1/payments' => ROUTES . '/v1/payment.routes.php',
        '/v1/shipments' => ROUTES . '/v1/shipment.routes.php',
        '/v1/warehouses' => ROUTES . '/v1/warehouse.routes.php',
        '/v1/warehouse-staff' => ROUTES . '/v1/warehouse-staff.routes.php',
        '/v1/shipment-types' => ROUTES . '/v1/shipment-type.routes.php',
        '/v1/shipment-tracking-updates' => ROUTES . '/v1/shipment-tracking-update.routes.php',
        // Add more routes as needed
    ];

    $loaded = false;
    // Check if the request matches any of our defined prefixes
    foreach ($routeMap as $prefix => $routerFile) {
        if (strpos($requestUri, $prefix) === 0) {
            // Load only the matching router
            if (file_exists($routerFile)) {
                (require_once $routerFile)($app);
                $loaded = true;
            }
        }
    }

    // If no specific router was loaded, load all routers as fallback
    if (!$loaded) {
        foreach ($routeMap as $routerFile) {
            if (file_exists($routerFile)) {
                (require_once $routerFile)($app);
            }
        }
    };
};
