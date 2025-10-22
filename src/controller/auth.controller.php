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

class AuthController{
    protected ClientsModel $clientModel;
    protected WarehouseStaffModel $warehouseStaffModel;

    public function __construct()
    {
        $this->clientModel = new ClientsModel();
        $this->warehouseStaffModel = new WarehouseStaffModel();
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
        $payload =['data' => $client];
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
        // Validate required fields
        $requiredFields = ['firstName', 'lastName', 'email', 'phone', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return json_encode([
                    'code' => 400,
                    'status' => 'error',
                    'message' => "Field '{$field}' is required."
                ], JSON_PRETTY_PRINT);
            }
        }
          
        // Create the new client
        $newClient = $this->clientModel->createClient($data);
        if ($newClient === false) {
            return json_encode([
                'code' => 500,
                'status' => 'error',
                'client' => null,
                'message' => 'Failed to create client: ' . $this->clientModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }
        $newClient = $this->clientModel->getClientById((int)$newClient);
        return json_encode([
            'code' => 201,
            'status' => 'success',
            'client' => $newClient,
            'message' => 'Client created successfully',
        ], JSON_PRETTY_PRINT);
        }
};