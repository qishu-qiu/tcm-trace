<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JwtService;

class AuthFilter implements FilterInterface
{
    protected array $publicRoutes = [
        'api/auth/login',
        'api/auth/register',
        'api/scan',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri()->getPath();
        $uri = ltrim($uri, '/');

        foreach ($this->publicRoutes as $publicRoute) {
            if (str_starts_with($uri, $publicRoute)) {
                return null;
            }
        }

        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'code'    => 401,
                    'message' => '未授权访问，请先登录',
                    'data'    => null,
                ]);
        }

        $token = substr($authHeader, 7);

        $jwtService = new JwtService();
        $payload = $jwtService->getTokenPayload($token);

        if ($payload === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'code'    => 401,
                    'message' => 'Token已过期或无效',
                    'data'    => null,
                ]);
        }

        $request->tenantId = $payload['tenantId'];
        $request->userId = $payload['userId'];
        $request->userRole = $payload['role'];

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
