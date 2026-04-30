<?php

namespace App\Controllers;

use App\Libraries\JwtService;
use App\Libraries\AuditService;

class Auth extends BaseController
{
    protected $db;
    protected JwtService $jwtService;
    protected AuditService $auditService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->jwtService = new JwtService();
        $this->auditService = new AuditService();
    }

    public function register()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'name'         => 'required|max_length[100]',
            'slug'         => 'required|alpha_dash|max_length[50]',
            'contactName'  => 'permit_empty|max_length[50]',
            'contactPhone' => 'permit_empty|max_length[20]',
            'username'     => 'required|alpha_dash|max_length[50]',
            'password'     => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $existingSlug = $this->db->table('tenants')
            ->where('slug', $data['slug'])
            ->countAllResults();

        if ($existingSlug > 0) {
            return $this->error('租户标识已被使用', 400);
        }

        $this->db->transStart();

        try {
            $tenantData = [
                'name'          => $data['name'],
                'slug'          => $data['slug'],
                'contact_name'  => $data['contactName'] ?? null,
                'contact_phone' => $data['contactPhone'] ?? null,
                'plan'          => 'free',
                'status'        => 1,
                'max_products'  => 100,
                'max_qrcodes'   => 1000,
                'max_users'     => 10,
                'created_at'    => date('Y-m-d H:i:s'),
            ];

            $this->db->table('tenants')->insert($tenantData);
            $tenantId = $this->db->insertID();

            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

            $userData = [
                'tenant_id'      => $tenantId,
                'username'       => $data['username'],
                'password_hash'  => $passwordHash,
                'real_name'      => $data['contactName'] ?? null,
                'phone'          => $data['contactPhone'] ?? null,
                'role'           => 'admin',
                'status'         => 1,
                'created_at'     => date('Y-m-d H:i:s'),
            ];

            $this->db->table('users')->insert($userData);
            $userId = $this->db->insertID();

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->error('注册失败，请稍后重试', 500);
            }

            $token = $this->jwtService->generateToken($userId, $tenantId, 'admin');

            $this->auditService->logCreate('tenant', (string) $tenantId, $tenantData, $tenantId, $userId, $data['contactName'] ?? null);

            return $this->success([
                'token' => $token,
                'user'  => [
                    'id'       => $userId,
                    'username' => $data['username'],
                    'realName' => $data['contactName'] ?? null,
                    'role'     => 'admin',
                ],
                'tenant' => [
                    'id'   => $tenantId,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                ],
            ], '注册成功');

        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->error('注册失败：' . $e->getMessage(), 500);
        }
    }

    public function login()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $username = $data['username'];
        $password = $data['password'];
        $tenantSlug = $data['tenantSlug'] ?? null;

        $tenantQuery = $this->db->table('tenants');

        if ($tenantSlug) {
            $tenantQuery->where('slug', $tenantSlug);
        }

        $tenant = $tenantQuery->where('status', 1)->get()->getRowArray();

        if (!$tenant) {
            $this->auditService->logError('login', 'user', '租户不存在或已禁用', null, null, null, null, $username);
            return $this->error('租户不存在或已禁用', 400);
        }

        $user = $this->db->table('users')
            ->where('tenant_id', $tenant['id'])
            ->where('username', $username)
            ->where('status', 1)
            ->get()
            ->getRowArray();

        if (!$user) {
            $this->auditService->logError('login', 'user', '用户名或密码错误', null, null, $tenant['id'], null, $username);
            return $this->error('用户名或密码错误', 400);
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->auditService->logError('login', 'user', '用户名或密码错误', null, null, $tenant['id'], $user['id'], $username);
            return $this->error('用户名或密码错误', 400);
        }

        if ($tenant['expires_at'] !== null && strtotime($tenant['expires_at']) < time()) {
            return $this->error('租户订阅已过期', 403);
        }

        $this->db->table('users')
            ->where('id', $user['id'])
            ->update([
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $this->getClientIp(),
            ]);

        $token = $this->jwtService->generateToken(
            (int) $user['id'],
            (int) $tenant['id'],
            $user['role']
        );

        $this->auditService->logLogin((int) $user['id'], $user['real_name'] ?? $user['username'], (int) $tenant['id'], true);

        return $this->success([
            'token' => $token,
            'user'  => [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'realName' => $user['real_name'],
                'role'     => $user['role'],
            ],
            'tenant' => [
                'id'   => (int) $tenant['id'],
                'name' => $tenant['name'],
                'slug' => $tenant['slug'],
                'plan' => $tenant['plan'],
            ],
        ], '登录成功');
    }

    public function profile()
    {
        if ($this->userId === null) {
            return $this->unauthorized();
        }

        $user = $this->db->table('users')
            ->select('id, username, real_name, phone, email, role, status, last_login_at, created_at')
            ->where('id', $this->userId)
            ->get()
            ->getRowArray();

        if (!$user) {
            return $this->notFound('用户不存在');
        }

        $tenant = $this->getCurrentTenant();

        return $this->success([
            'user' => [
                'id'          => (int) $user['id'],
                'username'    => $user['username'],
                'realName'    => $user['real_name'],
                'phone'       => $user['phone'],
                'email'       => $user['email'],
                'role'        => $user['role'],
                'lastLoginAt' => $user['last_login_at'],
                'createdAt'   => $user['created_at'],
            ],
            'tenant' => $tenant ? [
                'id'          => (int) $tenant['id'],
                'name'        => $tenant['name'],
                'slug'        => $tenant['slug'],
                'plan'        => $tenant['plan'],
                'maxProducts' => (int) $tenant['max_products'],
                'maxQrcodes'  => (int) $tenant['max_qrcodes'],
                'maxUsers'    => (int) $tenant['max_users'],
                'expiresAt'   => $tenant['expires_at'],
            ] : null,
        ]);
    }

    public function changePassword()
    {
        if ($this->userId === null) {
            return $this->unauthorized();
        }

        $data = $this->request->getJSON(true);

        $rules = [
            'oldPassword' => 'required',
            'newPassword' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $user = $this->db->table('users')
            ->where('id', $this->userId)
            ->get()
            ->getRowArray();

        if (!$user) {
            return $this->notFound('用户不存在');
        }

        if (!password_verify($data['oldPassword'], $user['password_hash'])) {
            return $this->error('原密码错误', 400);
        }

        $newPasswordHash = password_hash($data['newPassword'], PASSWORD_BCRYPT);

        $this->db->table('users')
            ->where('id', $this->userId)
            ->update([
                'password_hash' => $newPasswordHash,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

        $this->auditService->log('change_password', 'user', (string) $this->userId, null, null, $this->tenantId, $this->userId, $user['real_name'] ?? $user['username']);

        return $this->success(null, '密码修改成功');
    }

    public function logout()
    {
        if ($this->userId !== null) {
            $user = $this->db->table('users')
                ->select('username, real_name')
                ->where('id', $this->userId)
                ->get()
                ->getRowArray();

            if ($user) {
                $this->auditService->logLogout($this->userId, $user['real_name'] ?? $user['username'], $this->tenantId);
            }
        }

        return $this->success(null, '退出成功');
    }

    public function refreshToken()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('未授权访问');
        }

        $token = substr($authHeader, 7);

        $newToken = $this->jwtService->refresh($token);

        if ($newToken === null) {
            return $this->unauthorized('Token无效');
        }

        return $this->success([
            'token' => $newToken,
        ], 'Token刷新成功');
    }
}
