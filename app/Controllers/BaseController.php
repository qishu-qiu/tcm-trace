<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected ?int $tenantId = null;
    protected ?int $userId = null;
    protected ?string $userRole = null;
    protected ?array $tenant = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->tenantId = $request->tenantId ?? null;
        $this->userId = $request->userId ?? null;
        $this->userRole = $request->userRole ?? null;
        $this->tenant = $request->tenant ?? null;
    }

    protected function success($data = null, string $message = '操作成功'): ResponseInterface
    {
        return $this->response->setJSON([
            'code' => 200,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function error(string $message = '操作失败', int $code = 400): ResponseInterface
    {
        return $this->response->setStatusCode($code >= 400 ? $code : 400)->setJSON([
            'code' => $code,
            'message' => $message,
            'data' => null,
        ]);
    }

    protected function paginated(array $list, int $total, int $page = 1, int $pageSize = 20): ResponseInterface
    {
        return $this->response->setJSON([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => (int) ceil($total / $pageSize),
            ],
        ]);
    }

    protected function unauthorized(string $message = '未授权访问'): ResponseInterface
    {
        return $this->response->setStatusCode(401)->setJSON([
            'code' => 401,
            'message' => $message,
            'data' => null,
        ]);
    }

    protected function forbidden(string $message = '禁止访问'): ResponseInterface
    {
        return $this->response->setStatusCode(403)->setJSON([
            'code' => 403,
            'message' => $message,
            'data' => null,
        ]);
    }

    protected function notFound(string $message = '资源不存在'): ResponseInterface
    {
        return $this->response->setStatusCode(404)->setJSON([
            'code' => 404,
            'message' => $message,
            'data' => null,
        ]);
    }

    protected function validationError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'code' => 422,
            'message' => '数据验证失败',
            'data' => $errors,
        ]);
    }

    protected function getCurrentUserId(): ?int
    {
        return $this->userId;
    }

    protected function getCurrentUser(): ?array
    {
        if (!$this->userId) {
            return null;
        }
        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($this->userId);
        if ($user) {
            unset($user['password_hash']);
        }
        return $user;
    }

    protected function getCurrentTenantId(): ?int
    {
        return $this->tenantId;
    }

    protected function getCurrentUserRole(): ?string
    {
        return $this->userRole;
    }

    protected function getCurrentTenant(): ?array
    {
        return $this->tenant;
    }

    protected function isAdmin(): bool
    {
        return $this->userRole === 'admin';
    }

    protected function isStaff(): bool
    {
        return $this->userRole === 'staff';
    }

    protected function isViewer(): bool
    {
        return $this->userRole === 'viewer';
    }

    protected function getClientIp(): string
    {
        return $this->request->getIPAddress();
    }

    protected function getUserAgent(): string
    {
        $userAgent = $this->request->getUserAgent();
        if ($userAgent === null) {
            return '';
        }
        $agentString = $userAgent->getAgentString();
        return $agentString ?? '';
    }
}
