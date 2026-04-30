<?php

namespace App\Controllers;

use App\Models\BatchModel;
use App\Models\ProductModel;
use App\Models\TraceRecordModel;
use App\Libraries\AuditService;

class Batch extends BaseController
{
    protected BatchModel $batchModel;
    protected ProductModel $productModel;
    protected TraceRecordModel $traceRecordModel;
    protected AuditService $auditService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->batchModel = new BatchModel();
        $this->batchModel->setTenantId($this->tenantId);
        $this->productModel = new ProductModel();
        $this->productModel->setTenantId($this->tenantId);
        $this->traceRecordModel = new TraceRecordModel();
        $this->traceRecordModel->setTenantId($this->tenantId);
        $this->auditService = new AuditService();
    }

    public function index()
    {
        $page = (int) $this->request->getGet('page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
        $pageSize = (int) $this->request->getGet('pageSize', FILTER_SANITIZE_NUMBER_INT) ?: 20;
        $productId = $this->request->getGet('productId') !== null ? (int) $this->request->getGet('productId') : null;
        $status = $this->request->getGet('status') !== null ? (int) $this->request->getGet('status') : null;
        $keyword = $this->request->getGet('keyword');

        $result = $this->batchModel->getList($page, $pageSize, $productId, $status, $keyword);

        return $this->paginated($result['list'], $result['total'], $page, $pageSize);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'productId' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $product = $this->productModel->find($data['productId']);

        if (!$product) {
            return $this->error('产品不存在', 400);
        }

        $sequence = $this->batchModel->getNextSequence($product['name']);
        $batchNo = generate_batch_no($product['name'], $sequence);

        $batchData = [
            'product_id'      => $data['productId'],
            'batch_no'        => $batchNo,
            'farm_name'       => $data['farmName'] ?? null,
            'farm_address'    => $data['farmAddress'] ?? null,
            'farm_area'       => $data['farmArea'] ?? null,
            'plant_date'      => $data['plantDate'] ?? null,
            'harvest_date'    => $data['harvestDate'] ?? null,
            'harvest_qty'     => $data['harvestQty'] ?? null,
            'grow_years'      => $data['growYears'] ?? null,
            'process_method'  => $data['processMethod'] ?? null,
            'process_date'    => $data['processDate'] ?? null,
            'process_unit'    => $data['processUnit'] ?? null,
            'inspect_date'    => $data['inspectDate'] ?? null,
            'inspect_result'  => $data['inspectResult'] ?? null,
            'inspect_report'  => $data['inspectReport'] ?? null,
            'inspect_unit'    => $data['inspectUnit'] ?? null,
            'package_spec'    => $data['packageSpec'] ?? null,
            'package_qty'     => $data['packageQty'] ?? null,
            'warehouse_info'  => $data['warehouseInfo'] ?? null,
            'status'          => $data['status'] ?? 1,
        ];

        $batchId = $this->batchModel->insert($batchData);

        if (!$batchId) {
            return $this->error('创建失败', 500);
        }

        $this->auditService->logCreate('batch', (string) $batchId, $batchData, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success([
            'id' => $batchId,
            ...$batchData,
        ], '创建成功');
    }

    public function show(int $id)
    {
        $batch = $this->batchModel->getDetail($id);

        if (!$batch) {
            return $this->notFound('批次不存在');
        }

        $traceRecords = $this->traceRecordModel->getByBatchGrouped($id);
        $batch['traceRecords'] = $traceRecords;

        return $this->success($batch);
    }

    public function update(int $id)
    {
        $batch = $this->batchModel->find($id);

        if (!$batch) {
            return $this->notFound('批次不存在');
        }

        $data = $this->request->getJSON(true);

        $updateData = [];
        if (isset($data['farmName'])) $updateData['farm_name'] = $data['farmName'];
        if (isset($data['farmAddress'])) $updateData['farm_address'] = $data['farmAddress'];
        if (isset($data['farmArea'])) $updateData['farm_area'] = $data['farmArea'];
        if (isset($data['plantDate'])) $updateData['plant_date'] = $data['plantDate'];
        if (isset($data['harvestDate'])) $updateData['harvest_date'] = $data['harvestDate'];
        if (isset($data['harvestQty'])) $updateData['harvest_qty'] = $data['harvestQty'];
        if (isset($data['growYears'])) $updateData['grow_years'] = $data['growYears'];
        if (isset($data['processMethod'])) $updateData['process_method'] = $data['processMethod'];
        if (isset($data['processDate'])) $updateData['process_date'] = $data['processDate'];
        if (isset($data['processUnit'])) $updateData['process_unit'] = $data['processUnit'];
        if (isset($data['inspectDate'])) $updateData['inspect_date'] = $data['inspectDate'];
        if (isset($data['inspectResult'])) $updateData['inspect_result'] = $data['inspectResult'];
        if (isset($data['inspectReport'])) $updateData['inspect_report'] = $data['inspectReport'];
        if (isset($data['inspectUnit'])) $updateData['inspect_unit'] = $data['inspectUnit'];
        if (isset($data['packageSpec'])) $updateData['package_spec'] = $data['packageSpec'];
        if (isset($data['packageQty'])) $updateData['package_qty'] = $data['packageQty'];
        if (isset($data['warehouseInfo'])) $updateData['warehouse_info'] = $data['warehouseInfo'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];

        $result = $this->batchModel->update($id, $updateData);

        if (!$result) {
            return $this->error('更新失败', 500);
        }

        $this->auditService->logUpdate('batch', (string) $id, $batch, $updateData, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '更新成功');
    }

    public function delete(int $id)
    {
        $batch = $this->batchModel->find($id);

        if (!$batch) {
            return $this->notFound('批次不存在');
        }

        $result = $this->batchModel->update($id, ['status' => 0]);

        if (!$result) {
            return $this->error('删除失败', 500);
        }

        $this->auditService->logDelete('batch', (string) $id, $batch, $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '删除成功');
    }
}
