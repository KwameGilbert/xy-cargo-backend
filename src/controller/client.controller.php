<?php

declare(strict_types=1);

require_once MODEL . 'client.model.php';

/**
 * ClientsController
 *
 * Handles client CRUD and authentication.
 * Works with ClientsModel that aligns with the clients table schema.
 */
class ClientsController
{
    protected ClientsModel $clientModel;

    public function __construct()
    {
        $this->clientModel = new ClientsModel();
    }

    /**
     * Get all clients
     */
    public function getAllClients(): string
    {
        if($clients = $this->clientModel->getAllClients()){
            return json_encode([
                'status' => 'success',
                'code' => 200,
                'clients' => $clients,
                'message' => null
            ], JSON_PRETTY_PRINT);
        }
        return json_encode([
            'status' => 'error',
            'code' => 404,
            'message' => 'No clients found'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get client by ID
     * @param int $clientId
     * @return string
     */
    public function getClientById(int $clientId): string
    {
        if(!$clientId){
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid client ID'
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientById($clientId);
        if (!$client) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => 'Client not found'
            ], JSON_PRETTY_PRINT);
        }

        return json_encode([
            'status' => 'success',
            'code' => 200,
            'client' => $client,
            'message' => null
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get client by email
     * @param string $email
     * @return string
     */
    public function getClientByEmail(string $email): string
    {
        if (!$email) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'message' => 'Email is required',
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientByEmail($email);
        if ($client) {
            return json_encode([
                'status' => 'success',
                'code' => 200,
                'client' => $client,
                'message' => null,
            ], JSON_PRETTY_PRINT);
        }
        return json_encode([
            'status' => 'error',
            'code' => 404,
            'client' => null,
            'message' => 'Client not found with this email',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get client by phone number
     * @param string $phone
     * @return string
     */
    public function getClientByPhone(string $phone): string
    {
        if (!$phone) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'message' => 'Phone number is required',
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientByPhone($phone);
        if ($client) {
            return json_encode([
                'status' => 'success',
                'code' => 200,
                'client' => $client,
                'message' => null,
            ], JSON_PRETTY_PRINT);
        }
        return json_encode([
            'status' => 'error',
            'code' => 404,
            'client' => null,
            'message' => 'Client not found with this phone number',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Create a new client
     * Expected data: firstName, lastName, email, password, phone(optional), address(optional), company(optional)
     */
    public function createClient(array $data): string
    {
        $required = ['firstName', 'lastName', 'email', 'password'];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'message' => 'Missing required fields: ' . implode(', ', $missing),
            ], JSON_PRETTY_PRINT);
        }

        if ($client = $this->clientModel->getClientByEmail($data['email'])) {
            return json_encode([
                'status' => 'error',
                'code' => 409,
                'field' => 'email',
                'message' => 'Email already in use by another account',
            ], JSON_PRETTY_PRINT);
        }

        $violation = $this->checkUniqueConstraints($data, null);
        if ($violation) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'field' => $violation['field'],
                'message' => $violation['message'],
            ], JSON_PRETTY_PRINT);
        }

        // Hash the password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);

        $clientId = $this->clientModel->createClient($data);
        if ($clientId === false) {
            return json_encode([
                'status' => 'error',
                'code' => 500,
                'client' => null,
                'message' => 'Failed to create client: ' . $this->clientModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientById((int) $clientId);
        return json_encode([
            'status' => 'success',
            'code' => 201,
            'client' => $client,
            'message' => 'Client created successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update an existing client
     * Allowed fields: firstName, lastName, email, phone, address, company
     */
    public function updateClient(int $id, array $data): string
    {
        $existing = $this->clientModel->getClientById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'client' => null,
                'message' => 'Client not found',
            ], JSON_PRETTY_PRINT);
        }

        $violation = $this->checkUniqueConstraints($data, $id);
        if ($violation) {
            return json_encode([
                'status' => 'error',
                'code' => 400,
                'client' => null,
                'field' => $violation['field'],
                'message' => $violation['message'],
            ], JSON_PRETTY_PRINT);
        }

        $updated = $this->clientModel->updateClient($id, $data);
        if (!$updated) {
            return json_encode([
                'status' => 'error',
                'code' => 500,
                'client' => null,
                'message' => 'Failed to update client: ' . $this->clientModel->getLastError(),
            ], JSON_PRETTY_PRINT);
        }

        $client = $this->clientModel->getClientById($id);
        return json_encode([
            'status' => 'success',
            'code' => 200,
            'client' => $client,
            'message' => 'Client updated successfully',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Delete a client by ID
     * @param int $id
     * @return string
     */
    public function deleteClient(int $id): string
    {
        $existing = $this->clientModel->getClientById($id);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'client' => null,
                'message' => 'Client not found',
            ], JSON_PRETTY_PRINT);
        }

        $deleted = $this->clientModel->deleteClient($id);
        return json_encode([
            'status' => $deleted ? 'success' : 'error',
            'code' => $deleted ? 200 : 500,
            'client' => null,
            'message' => $deleted ? 'Client deleted successfully' : ('Failed to delete client: ' . $this->clientModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Login with email and password
     */
    public function login(string $email, string $password): string
    {
        $client = $this->clientModel->clientLogin($email, $password);
        return json_encode([
            'status' => $client ? 'success' : 'error',
            'code' => $client ? 200 : 401,
            'client' => $client,
            'message' => $client ? 'Login successful' : ('Login failed: ' . $this->clientModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Update password for a client
     */
    public function updatePassword(int $clientId, string $newPassword): string
    {
        $existing = $this->clientModel->getClientById($clientId);
        if (!$existing) {
            return json_encode([
                'status' => 'error',
                'code' => 404,
                'message' => 'Client not found',
            ], JSON_PRETTY_PRINT);
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updated = $this->clientModel->updateClientPassword($clientId, $hashedPassword);
        return json_encode([
            'status' => $updated ? 'success' : 'error',
            'code' => $updated ? 200 : 500,
            'client' => null,
            'message' => $updated ? 'Password updated successfully' : ('Failed to update password: ' . $this->clientModel->getLastError()),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Enforce unique email. $currentClientId excludes that client when updating.
     * @return array{field:string,message:string}|null
     */
    private function checkUniqueConstraints(array $data, ?int $currentClientId = null): ?array
    {
        if (!empty($data['email'])) {
            $existing = $this->clientModel->getClientByEmail($data['email']);
            if ($existing && (!isset($existing['client_id']) || (int) $existing['client_id'] !== (int) ($currentClientId ?? -1))) {
                return ['field' => 'email', 'message' => 'Email already in use by another account'];
            }
        }

        return null;
    }
}
