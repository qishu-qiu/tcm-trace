<?php

namespace App\Controllers;

use App\Models\TenantModel;
use App\Models\ProductModel;
use App\Models\QrcodeModel;
use App\Models\UserModel;
use App\Libraries\AuditService;

class Tenant extends BaseController
{
    protected TenantModel $tenantModel;
    protected ProductModel $productModel;
    protected QrcodeModel $qrcodeModel;
    protected UserModel $userModel;
    protected AuditService $auditService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->tenantModel = new TenantModel();
        $this->productModel = new ProductModel();
        $this->productModel->setTenantId($this->tenantId);
        $this->qrcodeModel = new QrcodeModel();
        $this->qrcodeModel->setTenantId($this->tenantId);
        $this->userModel = new UserModel();
        $this->userModel->setTenantId($this->tenantId);
        $this->auditService = new AuditService();
    }

    public function index()
    {
        $tenant = $this->tenantModel->find($this->tenantId);

        if (!$tenant) {
            return $this->notFound('企业不存在');
        }

        return $this->success($tenant);
    }

    public function update()
    {
        $tenant = $this->tenantModel->find($this->tenantId);

        if (!$tenant) {
            return $this->notFound('企业不存在');
        }

        $data = $this->request->getJSON(true);

        $updateData = [];

        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['contactName'])) $updateData['contact_name'] = $data['contactName'];
        if (isset($data['contactPhone'])) $updateData['contact_phone'] = $data['contactPhone'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];
        if (isset($data['logo'])) $updateData['logo'] = $data['logo'];

        if (empty($updateData)) {
            return $this->error('没有需要更新的数据', 400);
        }

        $result = $this->tenantModel->update($this->tenantId, $updateData);

        if (!$result) {
            return $this->error('更新失败', 500);
        }

        $this->auditService->logUpdate('tenant', (string) $this->tenantId, $tenant, $updateData, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '更新成功');
    }

    public function usage()
    {
        $tenant = $this->tenantModel->find($this->tenantId);

        if (!$tenant) {
            return $this->notFound('企业不存在');
        }

        $usedProducts = $this->productModel->countProducts();
        $usedQrcodes = $this->qrcodeModel->countQrcodes();
        $usedUsers = $this->userModel->countUsers();

        return $this->success([
            'products' => [
                'used'  => $usedProducts,
                'limit' => $tenant['max_products'],
                'percent' => $tenant['max_products'] > 0 ? round($usedProducts / $tenant['max_products'] * 100, 1) : 0,
            ],
            'qrcodes' => [
                'used'  => $usedQrcodes,
                'limit' => $tenant['max_qrcodes'],
                'percent' => $tenant['max_qrcodes'] > 0 ? round($usedQrcodes / $tenant['max_qrcodes'] * 100, 1) : 0,
            ],
            'users' => [
                'used'  => $usedUsers,
                'limit' => $tenant['max_users'],
                'percent' => $tenant['max_users'] > 0 ? round($usedUsers / $tenant['max_users'] * 100, 1) : 0,
            ],
            'plan' => $tenant['plan'],
            'expiresAt' => $tenant['expires_at'],
        ]);
    }

    public function uploadLogo()
    {
        $file = $this->request->getFile('logo');

        if (!$file || !$file->isValid()) {
            return $this->error('请上传有效的图片文件', 400);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->error('仅支持 JPG、PNG、GIF、WEBP 格式', 400);
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            return $this->error('图片大小不能超过 2MB', 400);
        }

        $uploadPath = FCPATH . 'uploads/logos/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $filename = $this->tenantId . '_' . time() . '.' . $file->getExtension();

        if (!$file->move($uploadPath, $filename)) {
            return $this->error('上传失败', 500);
        }

        $logoUrl = '/uploads/logos/' . $filename;

        $this->tenantModel->update($this->tenantId, ['logo' => $logoUrl]);

        return $this->success([
            'url' => $logoUrl,
        ], '上传成功');
    }
}
