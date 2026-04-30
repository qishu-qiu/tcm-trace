<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tenantId = $request->tenantId ?? null;

        if ($tenantId === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'code'    => 401,
                    'message' => '租户信息缺失',
                    'data'    => null,
                ]);
        }

        $db = \Config\Database::connect();
        $tenant = $db->table('tenants')
            ->where('id', $tenantId)
            ->get()
            ->getRowArray();

        if (!$tenant) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'code'    => 403,
                    'message' => '租户不存在',
                    'data'    => null,
                ]);
        }

        if ((int) $tenant['status'] !== 1) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'code'    => 403,
                    'message' => '租户已被禁用',
                    'data'    => null,
                ]);
        }

        if ($tenant['expires_at'] !== null) {
            $expiresAt = strtotime($tenant['expires_at']);
            if ($expiresAt < time()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON([
                        'code'    => 403,
                        'message' => '租户订阅已过期',
                        'data'    => null,
                    ]);
            }
        }

        $request->tenant = $tenant;

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
