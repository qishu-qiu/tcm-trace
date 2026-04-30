<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Libraries\AuditService;

class Product extends BaseController
{
    protected ProductModel $productModel;
    protected AuditService $auditService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->productModel = new ProductModel();
        $this->productModel->setTenantId($this->tenantId);
        $this->auditService = new AuditService();
    }

    public function index()
    {
        $page = (int) $this->request->getGet('page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
        $pageSize = (int) $this->request->getGet('pageSize', FILTER_SANITIZE_NUMBER_INT) ?: 20;
        $keyword = $this->request->getGet('keyword');
        $category = $this->request->getGet('category');

        $result = $this->productModel->getList($page, $pageSize, $keyword, $category);

        return $this->paginated($result['list'], $result['total'], $page, $pageSize);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'name'           => 'required|max_length[100]',
            'alias'          => 'permit_empty|max_length[100]',
            'origin'         => 'permit_empty|max_length[100]',
            'category'       => 'permit_empty|max_length[50]',
            'specification'  => 'permit_empty|max_length[200]',
            'qualityGrade'   => 'permit_empty|max_length[20]',
            'description'    => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $tenant = $this->getCurrentTenant();
        if ($tenant) {
            $currentCount = $this->productModel->countProducts();
            if ($currentCount >= $tenant['max_products']) {
                return $this->error('产品数量已达到套餐上限', 400);
            }
        }

        $imageUrl = null;
        if (isset($data['image']) && !empty($data['image'])) {
            $imageUrl = $this->saveBase64Image($data['image']);
            if (!$imageUrl) {
                return $this->error('图片上传失败', 500);
            }
        }

        $productData = [
            'name'           => $data['name'],
            'alias'          => $data['alias'] ?? null,
            'origin'         => $data['origin'] ?? null,
            'category'       => $data['category'] ?? null,
            'specification'  => $data['specification'] ?? null,
            'quality_grade'  => $data['qualityGrade'] ?? null,
            'image_url'      => $imageUrl,
            'description'    => $data['description'] ?? null,
            'status'         => 1,
        ];

        $productId = $this->productModel->insert($productData);

        if (!$productId) {
            return $this->error('创建失败', 500);
        }

        $this->auditService->logCreate('product', (string) $productId, $productData, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success([
            'id' => $productId,
            ...$productData,
        ], '创建成功');
    }

    public function show(int $id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->notFound('产品不存在');
        }

        return $this->success($product);
    }

    public function update(int $id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->notFound('产品不存在');
        }

        $data = $this->request->getJSON(true);

        $rules = [
            'name'           => 'permit_empty|max_length[100]',
            'alias'          => 'permit_empty|max_length[100]',
            'origin'         => 'permit_empty|max_length[100]',
            'category'       => 'permit_empty|max_length[50]',
            'specification'  => 'permit_empty|max_length[200]',
            'qualityGrade'   => 'permit_empty|max_length[20]',
            'description'    => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['alias'])) $updateData['alias'] = $data['alias'];
        if (isset($data['origin'])) $updateData['origin'] = $data['origin'];
        if (isset($data['category'])) $updateData['category'] = $data['category'];
        if (isset($data['specification'])) $updateData['specification'] = $data['specification'];
        if (isset($data['qualityGrade'])) $updateData['quality_grade'] = $data['qualityGrade'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];

        if (isset($data['image']) && !empty($data['image'])) {
            $imageUrl = $this->saveBase64Image($data['image']);
            if ($imageUrl) {
                $updateData['image_url'] = $imageUrl;
            }
        }

        $result = $this->productModel->update($id, $updateData);

        if (!$result) {
            return $this->error('更新失败', 500);
        }

        $this->auditService->logUpdate('product', (string) $id, $product, $updateData, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '更新成功');
    }

    public function delete(int $id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->notFound('产品不存在');
        }

        $result = $this->productModel->update($id, ['status' => 0]);

        if (!$result) {
            return $this->error('删除失败', 500);
        }

        $this->auditService->logDelete('product', (string) $id, $product, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '删除成功');
    }

    public function categories()
    {
        $categories = $this->productModel->getCategories();
        return $this->success($categories);
    }

    protected function saveBase64Image(string $base64Image): ?string
    {
        $matches = [];
        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64Image, $matches)) {
            return null;
        }

        $extension = $matches[1];
        $imageData = base64_decode($matches[2]);

        if ($imageData === false) {
            return null;
        }

        $filename = uniqid() . '.' . $extension;
        $uploadPath = FCPATH . 'uploads/images/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $filepath = $uploadPath . $filename;

        if (!file_put_contents($filepath, $imageData)) {
            return null;
        }

        return '/uploads/images/' . $filename;
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
