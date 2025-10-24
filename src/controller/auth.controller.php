<?php

declare(strict_types=1);

/**
 * Clients and Warehouse Staff Auth API Routes
*
* These routes handle client and warehouse staff authentication.
* Clients have fields: firstName, lastName, email, phone, address, company, password_hash.
* Warehouse Staff have fields: staff_id, warehouse_id, firstName, lastName, email, phone, password_hash, role, status, profile_picture.
*/
require_once MODEL . '/client.model.php';
require_once MODEL . '/warehouse-staff.model.php';
require_once HELPER . '/JwtHelper.php';
require_once CONTROLLER . '/client.controller.php';

class AuthController{
    protected ClientsModel $clientModel;
    protected WarehouseStaffModel $warehouseStaffModel;
    protected ClientsController $clientsController;

    public function __construct()
    {
        $this->clientModel = new ClientsModel();
        $this->warehouseStaffModel = new WarehouseStaffModel();
        $this->clientsController = new ClientsController();
    }

    /**
     * Authenticate client
     * @param string $email
     * @param string $password
     * @return string
     */
    public function authenticateClient(string $email, string $password): string {
        if (!$email) {
            return json_encode([
                'code' => 400,
                'status' => 'error',
                'message' => 'Email is required for authentication.'
            ], JSON_PRETTY_PRINT);
        }
        
        if (!$password) {
            return json_encode([
                'code' => 400,
                'status' => 'error',
                'message' => 'Password is required for authentication.'
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->clientLogin($email, $password);
        if (!$client) {
            return json_encode([
                'code' => 401,
                'status' => 'error',
                'message' => 'Invalid email or password.'
            ], JSON_PRETTY_PRINT);
        }

        require_once HELPER . '/JwtHelper.php';
        // Generate a JWT token for the authenticated client
        $payload = ['data' => $client];
        $token = JwtHelper::generateToken($payload);
        return json_encode([
            'code' => 200,
            'status' => 'success',
            'data' => $client,
            'token' => $token
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Authenticate warehouse staff
     * @param string $email
     * @param string $password
     * @return string
     */
    public function authenticateWarehouseStaff(string $email, string $password): string {
        if (!$email) {
            return json_encode([
                'code' => 400,
                'status' => 'error',
                'message' => 'Email is required for authentication.'
            ], JSON_PRETTY_PRINT);
        }

        if (!$password) {
            return json_encode([
                'code' => 400,
                'status' => 'error',
                'message' => 'Password is required for authentication.'
            ], JSON_PRETTY_PRINT);
        }

        $staff = $this->warehouseStaffModel->warehouseStaffLogin($email, $password);
        if (!$staff) {
            return json_encode([
                'code' => 401,
                'status' => 'error',
                'message' => 'Invalid email or password.'
            ], JSON_PRETTY_PRINT);
        }

        // require_once HELPER . '/JwtHelper.php';
        // Generate a JWT token for the authenticated warehouse staff
        $payload =['data' => $staff, 'user_type' => 'warehouse_staff'];
        $token = JwtHelper::generateToken($payload);
        return json_encode([
            'code' => 200,
            'status' => 'success',
            'data' => $staff,
            'user_type' => 'warehouse_staff',
            'token' => $token
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Sign Up Client
     */
    public function clientSignUp($data): string {
        // Delegate to ClientsController::createClient
        return $this->clientsController->createClient($data);
    }
};