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
        'api/scan/verify',
        'api/scan/history',
    ];

    protected array $publicRoutePatterns = [
        'api/scan/verify/.*',
        'api/auth/register',
        'api/auth/login',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri()->getPath();
        $uri = ltrim($uri, '/');

        $normalizedUri = $this->normalizePath($uri);
        if ($normalizedUri !== $uri) {
            return service('response')
                ->setStatusCode(400)
                ->setJSON([
                    'code'    => 400,
                    'message' => '无效的请求路径',
                    'data'    => null,
                ]);
        }

        foreach ($this->publicRoutePatterns as $pattern) {
            if (preg_match('#^' . $pattern . '$#', $uri)) {
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

    protected function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $parts = explode('/', $path);
        $result = [];
        
        foreach ($parts as $part) {
            if ($part === '.' || $part === '') {
                continue;
            }
            if ($part === '..') {
                array_pop($result);
            } else {
                $result[] = $part;
            }
        }
        
        return implode('/', $result);
    }
}
