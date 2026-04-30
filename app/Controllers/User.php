<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\TenantModel;
use App\Libraries\AuditService;
use App\Libraries\JwtService;

class User extends BaseController
{
    protected UserModel $userModel;
    protected TenantModel $tenantModel;
    protected AuditService $auditService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->userModel = new UserModel();
        $this->userModel->setTenantId($this->tenantId);
        $this->tenantModel = new TenantModel();
        $this->auditService = new AuditService();
    }

    public function index()
    {
        $page = (int) $this->request->getGet('page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
        $pageSize = (int) $this->request->getGet('pageSize', FILTER_SANITIZE_NUMBER_INT) ?: 20;
        $keyword = $this->request->getGet('keyword');
        $role = $this->request->getGet('role');
        $status = $this->request->getGet('status');

        $result = $this->userModel->getList($page, $pageSize, $keyword, $role, $status);

        return $this->paginated($result['list'], $result['total'], $page, $pageSize);
    }

    public function create()
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限操作');
        }

        $data = $this->request->getJSON(true);

        $rules = [
            'username'  => 'required|max_length[50]',
            'password'  => 'required|min_length[6]',
            'realName'  => 'permit_empty|max_length[50]',
            'role'      => 'permit_empty|in_list[admin,staff,viewer]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $existingUser = $this->userModel->builder()
            ->where('tenant_id', $this->tenantId)
            ->where('username', $data['username'])
            ->get()
            ->getRowArray();

        if ($existingUser) {
            return $this->error('用户名已存在', 400);
        }

        $tenant = $this->getCurrentTenant();
        if ($tenant) {
            $currentCount = $this->userModel->countUsers();
            if ($currentCount >= $tenant['max_users']) {
                return $this->error('用户数量已达到套餐上限', 400);
            }
        }

        $userData = [
            'username'       => $data['username'],
            'password_hash'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'real_name'      => $data['realName'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'email'          => $data['email'] ?? null,
            'role'           => $data['role'] ?? 'staff',
            'status'         => 1,
        ];

        $userId = $this->userModel->insert($userData);

        if (!$userId) {
            return $this->error('创建失败', 500);
        }

        $this->auditService->logCreate('user', (string) $userId, $userData, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success([
            'id' => $userId,
            ...$userData,
            'password_hash' => '[protected]',
        ], '创建成功');
    }

    public function show(int $id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->notFound('用户不存在');
        }

        unset($user['password_hash']);

        return $this->success($user);
    }

    public function update(int $id)
    {
        if ($this->userRole !== 'admin' && $id !== $this->userId) {
            return $this->forbidden('无权限操作');
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->notFound('用户不存在');
        }

        $data = $this->request->getJSON(true);

        $updateData = [];

        if (isset($data['realName'])) $updateData['real_name'] = $data['realName'];
        if (isset($data['phone'])) $updateData['phone'] = $data['phone'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];

        if ($this->userRole === 'admin' && isset($data['role'])) {
            if (in_array($data['role'], ['admin', 'staff', 'viewer'])) {
                $updateData['role'] = $data['role'];
            }
        }

        if (empty($updateData)) {
            return $this->error('没有需要更新的数据', 400);
        }

        $result = $this->userModel->update($id, $updateData);

        if (!$result) {
            return $this->error('更新失败', 500);
        }

        $this->auditService->logUpdate('user', (string) $id, $user, $updateData, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '更新成功');
    }

    public function updateStatus(int $id)
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限操作');
        }

        if ($id === $this->userId) {
            return $this->error('不能禁用自己', 400);
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->notFound('用户不存在');
        }

        $data = $this->request->getJSON(true);

        if (!isset($data['status']) || !in_array($data['status'], [0, 1])) {
            return $this->error('无效的状态值', 400);
        }

        $result = $this->userModel->update($id, ['status' => $data['status']]);

        if (!$result) {
            return $this->error('更新失败', 500);
        }

        $this->auditService->logUpdate('user', (string) $id, $user, ['status' => $data['status']], $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, $data['status'] === 1 ? '已启用' : '已禁用');
    }

    public function resetPassword(int $id)
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限操作');
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->notFound('用户不存在');
        }

        $newPassword = $this->generateRandomPassword();
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $result = $this->userModel->update($id, ['password_hash' => $passwordHash]);

        if (!$result) {
            return $this->error('重置失败', 500);
        }

        $this->auditService->log('reset_password', 'user', null, (string) $id, [
            'action' => 'password_reset',
        ], $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success([
            'newPassword' => $newPassword,
        ], '密码已重置');
    }

    public function delete(int $id)
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限操作');
        }

        if ($id === $this->userId) {
            return $this->error('不能删除自己', 400);
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            return $this->notFound('用户不存在');
        }

        if ($user['role'] === 'admin') {
            return $this->error('不能删除管理员', 400);
        }

        $result = $this->userModel->delete($id);

        if (!$result) {
            return $this->error('删除失败', 500);
        }

        $this->auditService->logDelete('user', (string) $id, $user, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '删除成功');
    }

    protected function generateRandomPassword(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
