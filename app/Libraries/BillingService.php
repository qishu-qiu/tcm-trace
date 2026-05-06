<?php

namespace App\Libraries;

class BillingService
{
    protected $db;

    public const PLANS = [
        'free' => [
            'name'       => '免费版',
            'price'      => 0,
            'products'   => 10,
            'qrcodes'    => 200,
            'users'      => 3,
            'apiLimit'   => 100,
            'features'   => ['基础溯源', '二维码生成', '扫码验证'],
        ],
        'basic' => [
            'name'       => '基础版',
            'price'      => 99,
            'products'   => 50,
            'qrcodes'    => 2000,
            'users'      => 10,
            'apiLimit'   => 300,
            'features'   => ['基础溯源', '二维码生成', '扫码验证', '数据统计', '批量操作'],
        ],
        'pro' => [
            'name'       => '专业版',
            'price'      => 299,
            'products'   => 200,
            'qrcodes'    => 10000,
            'users'      => 30,
            'apiLimit'   => 600,
            'features'   => ['基础溯源', '二维码生成', '扫码验证', '数据统计', '批量操作', 'API接口', '优先支持'],
        ],
        'enterprise' => [
            'name'       => '企业版',
            'price'      => 899,
            'products'   => -1,
            'qrcodes'    => 50000,
            'users'      => -1,
            'apiLimit'   => -1,
            'features'   => ['基础溯源', '二维码生成', '扫码验证', '数据统计', '批量操作', 'API接口', '优先支持', '专属客服', '定制开发'],
        ],
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getPlans(): array
    {
        return self::PLANS;
    }

    public function getPlan(string $planKey): ?array
    {
        return self::PLANS[$planKey] ?? null;
    }

    public function checkQuota(int $tenantId, string $resourceType): bool
    {
        $tenant = $this->db->table('tenants')
            ->where('id', $tenantId)
            ->get()
            ->getRowArray();

        if (!$tenant) {
            return false;
        }

        $plan = $this->getPlan($tenant['plan']);
        if (!$plan) {
            return false;
        }

        $limitField = [
            'product' => 'products',
            'qrcode'  => 'qrcodes',
            'user'    => 'users',
        ];

        $fieldName = $limitField[$resourceType] ?? null;
        if (!$fieldName) {
            return false;
        }

        $limit = $plan[$fieldName];
        if ($limit === -1) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($tenantId, $resourceType);

        return $currentUsage < $limit;
    }

    public function getCurrentUsage(int $tenantId, string $resourceType): int
    {
        switch ($resourceType) {
            case 'product':
                return $this->db->table('products')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 1)
                    ->countAllResults();

            case 'qrcode':
                return $this->db->table('qrcodes')
                    ->where('tenant_id', $tenantId)
                    ->countAllResults();

            case 'user':
                return $this->db->table('users')
                    ->where('tenant_id', $tenantId)
                    ->countAllResults();

            default:
                return 0;
        }
    }

    public function getUsage(int $tenantId): array
    {
        $tenant = $this->db->table('tenants')
            ->where('id', $tenantId)
            ->get()
            ->getRowArray();

        if (!$tenant) {
            return [];
        }

        $plan = $this->getPlan($tenant['plan']);
        if (!$plan) {
            return [];
        }

        $productUsage = $this->getCurrentUsage($tenantId, 'product');
        $qrcodeUsage = $this->getCurrentUsage($tenantId, 'qrcode');
        $userUsage = $this->getCurrentUsage($tenantId, 'user');

        return [
            'plan'      => $tenant['plan'],
            'planName'  => $plan['name'],
            'expiresAt' => $tenant['expires_at'],
            'products'  => [
                'used'    => $productUsage,
                'limit'   => $plan['products'],
                'percent' => $plan['products'] > 0 ? round($productUsage / $plan['products'] * 100, 1) : 0,
            ],
            'qrcodes'   => [
                'used'    => $qrcodeUsage,
                'limit'   => $plan['qrcodes'],
                'percent' => $plan['qrcodes'] > 0 ? round($qrcodeUsage / $plan['qrcodes'] * 100, 1) : 0,
            ],
            'users'     => [
                'used'    => $userUsage,
                'limit'   => $plan['users'],
                'percent' => $plan['users'] > 0 ? round($userUsage / $plan['users'] * 100, 1) : 0,
            ],
        ];
    }

    public function upgradePlan(int $tenantId, string $newPlan): bool
    {
        $plan = $this->getPlan($newPlan);
        if (!$plan) {
            return false;
        }

        $tenant = $this->db->table('tenants')
            ->where('id', $tenantId)
            ->get()
            ->getRowArray();

        if (!$tenant) {
            return false;
        }

        $expiresAt = null;
        if ($newPlan !== 'free') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
        }

        $updateData = [
            'plan'         => $newPlan,
            'max_products' => $plan['products'] === -1 ? 999999 : $plan['products'],
            'max_qrcodes'  => $plan['qrcodes'] === -1 ? 999999 : $plan['qrcodes'],
            'max_users'    => $plan['users'] === -1 ? 999999 : $plan['users'],
            'expires_at'   => $expiresAt,
            'status'       => 1,
        ];

        $result = $this->db->table('tenants')
            ->where('id', $tenantId)
            ->update($updateData);

        if ($result) {
            $auditService = new AuditService();
            $auditService->log(
                'upgrade_plan',
                'tenant',
                (string) $tenantId,
                ['old_plan' => $tenant['plan']],
                ['new_plan' => $newPlan, 'expires_at' => $expiresAt],
                $tenantId,
                null,
                'system'
            );
        }

        return $result;
    }

    public function checkExpiration(): array
    {
        $now = date('Y-m-d H:i:s');
        $in7Days = date('Y-m-d H:i:s', strtotime('+7 days'));
        $in3Days = date('Y-m-d H:i:s', strtotime('+3 days'));
        $in1Day = date('Y-m-d H:i:s', strtotime('+1 day'));

        $expiring7Days = $this->db->table('tenants')
            ->where('expires_at <=', $in7Days)
            ->where('expires_at >', $now)
            ->where('status', 1)
            ->get()
            ->getResultArray();

        $expiring3Days = $this->db->table('tenants')
            ->where('expires_at <=', $in3Days)
            ->where('expires_at >', $now)
            ->where('status', 1)
            ->get()
            ->getResultArray();

        $expiring1Day = $this->db->table('tenants')
            ->where('expires_at <=', $in1Day)
            ->where('expires_at >', $now)
            ->where('status', 1)
            ->get()
            ->getResultArray();

        $expired = $this->db->table('tenants')
            ->where('expires_at <=', $now)
            ->where('plan !=', 'free')
            ->get()
            ->getResultArray();

        foreach ($expired as $tenant) {
            $this->downgradeToFree($tenant['id']);
        }

        return [
            'expiring_7_days' => count($expiring7Days),
            'expiring_3_days' => count($expiring3Days),
            'expiring_1_day'  => count($expiring1Day),
            'expired'         => count($expired),
        ];
    }

    protected function downgradeToFree(int $tenantId): bool
    {
        return $this->db->table('tenants')
            ->where('id', $tenantId)
            ->update([
                'plan'         => 'free',
                'max_products' => 10,
                'max_qrcodes'  => 200,
                'max_users'    => 3,
                'status'       => 2,
            ]);
    }

    public function calculatePrice(string $currentPlan, string $newPlan): array
    {
        $current = $this->getPlan($currentPlan);
        $new = $this->getPlan($newPlan);

        if (!$current || !$new) {
            return ['error' => '无效的套餐'];
        }

        $priceDiff = $new['price'] - $current['price'];

        return [
            'currentPlan'    => $currentPlan,
            'newPlan'        => $newPlan,
            'currentPrice'   => $current['price'],
            'newPrice'       => $new['price'],
            'priceDiff'      => $priceDiff,
            'isUpgrade'      => $priceDiff > 0,
        ];
    }
}
