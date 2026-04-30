<?php

namespace App\Controllers;

use App\Libraries\BillingService;
use App\Libraries\AuditService;

class Billing extends BaseController
{
    protected BillingService $billingService;
    protected AuditService $auditService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->billingService = new BillingService();
        $this->auditService = new AuditService();
    }

    public function index()
    {
        return $this->plans();
    }

    public function plans()
    {
        $plans = $this->billingService->getPlans();

        $planList = [];
        foreach ($plans as $key => $plan) {
            $planList[] = [
                'key'       => $key,
                'name'      => $plan['name'],
                'price'     => $plan['price'],
                'products'  => $plan['products'],
                'qrcodes'   => $plan['qrcodes'],
                'users'     => $plan['users'],
                'apiLimit'  => $plan['apiLimit'],
                'features'  => $plan['features'],
            ];
        }

        return $this->success($planList);
    }

    public function current()
    {
        $usage = $this->billingService->getUsage($this->tenantId);

        if (empty($usage)) {
            return $this->error('获取订阅信息失败', 500);
        }

        return $this->success($usage);
    }

    public function upgrade()
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限操作');
        }

        $data = $this->request->getJSON(true);

        if (!isset($data['plan'])) {
            return $this->error('请选择套餐', 400);
        }

        $newPlan = $data['plan'];
        $plan = $this->billingService->getPlan($newPlan);

        if (!$plan) {
            return $this->error('无效的套餐', 400);
        }

        $db = \Config\Database::connect();
        $tenant = $db->table('tenants')
            ->where('id', $this->tenantId)
            ->get()
            ->getRowArray();

        if (!$tenant) {
            return $this->error('企业不存在', 400);
        }

        if ($tenant['plan'] === $newPlan) {
            return $this->error('您已是该套餐', 400);
        }

        $priceCalc = $this->billingService->calculatePrice($tenant['plan'], $newPlan);
        if (isset($priceCalc['error'])) {
            return $this->error($priceCalc['error'], 400);
        }

        $paymentRequired = $plan['price'] > 0;

        if ($paymentRequired && !isset($data['paymentConfirmed'])) {
            return $this->success([
                'requirePayment' => true,
                'priceCalc'      => $priceCalc,
                'message'        => '请确认支付',
            ], '需要支付');
        }

        $result = $this->billingService->upgradePlan($this->tenantId, $newPlan);

        if (!$result) {
            return $this->error('升级失败', 500);
        }

        $this->auditService->log(
            'upgrade_plan',
            'tenant',
            $tenant['plan'],
            (string) $this->tenantId,
            ['new_plan' => $newPlan, 'price' => $plan['price']],
            $this->tenantId,
            $this->userId,
            $this->getCurrentUser()['realName'] ?? null
        );

        return $this->success([
            'plan'      => $newPlan,
            'planName'  => $plan['name'],
            'expiresAt' => $newPlan !== 'free' ? date('Y-m-d H:i:s', strtotime('+1 year')) : null,
        ], '升级成功');
    }

    public function checkExpiration()
    {
        $result = $this->billingService->checkExpiration();

        return $this->success($result);
    }

    protected function getCurrentUser(): array
    {
        $db = \Config\Database::connect();
        $user = $db->table('users')
            ->select('real_name, username')
            ->where('id', $this->userId)
            ->get()
            ->getRowArray();
        
        return $user ?: ['realName' => null, 'username' => null];
    }
}
