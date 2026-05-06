<?php

namespace App\Controllers;

use App\Models\QrcodeModel;
use App\Models\BatchModel;
use App\Libraries\QrcodeService;
use App\Libraries\AuditService;

class Qrcode extends BaseController
{
    protected QrcodeModel $qrcodeModel;
    protected BatchModel $batchModel;
    protected QrcodeService $qrcodeService;
    protected AuditService $auditService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->qrcodeModel = new QrcodeModel();
        $this->qrcodeModel->setTenantId($this->tenantId);
        $this->batchModel = new BatchModel();
        $this->batchModel->setTenantId($this->tenantId);
        $this->qrcodeService = new QrcodeService();
        $this->auditService = new AuditService();
    }

    public function index()
    {
        $page = (int) $this->request->getGet('page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
        $pageSize = (int) $this->request->getGet('pageSize', FILTER_SANITIZE_NUMBER_INT) ?: 20;
        $batchId = $this->request->getGet('batchId') !== null ? (int) $this->request->getGet('batchId') : null;
        $status = $this->request->getGet('status') !== null ? (int) $this->request->getGet('status') : null;

        $result = $this->qrcodeModel->getList($page, $pageSize, $batchId, $status);

        return $this->paginated($result['list'], $result['total'], $page, $pageSize);
    }

    public function generate()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'batchId' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $batchId = (int) $data['batchId'];
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : 1;

        if ($quantity < 1 || $quantity > 100) {
            return $this->error('数量必须在1-100之间', 400);
        }

        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return $this->error('批次不存在', 400);
        }

        $tenant = $this->getCurrentTenant();
        if ($tenant) {
            $currentCount = $this->qrcodeModel->countQrcodes();
            if ($currentCount + $quantity > $tenant['max_qrcodes']) {
                return $this->error('二维码数量将超过套餐上限', 400);
            }
        }

        try {
            $generatedIds = $this->qrcodeService->generateBatchQrcodes($this->tenantId, $batchId, $quantity);
        } catch (\Exception $e) {
            return $this->error('生成失败: ' . $e->getMessage(), 500);
        }

        $this->auditService->logCreate('qrcode', implode(',', $generatedIds), [
            'batch_id' => $batchId,
            'quantity' => $quantity,
            'ids' => $generatedIds,
        ], $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success([
            'generated' => count($generatedIds),
            'ids' => $generatedIds,
        ], '生成成功');
    }

    public function show(int $id)
    {
        $qrcode = $this->qrcodeModel->getDetail($id);

        if (!$qrcode) {
            return $this->notFound('二维码不存在');
        }

        return $this->success($qrcode);
    }

    public function updateStatus(int $id)
    {
        $qrcode = $this->qrcodeModel->find($id);

        if (!$qrcode) {
            return $this->notFound('二维码不存在');
        }

        $data = $this->request->getJSON(true);

        if (!isset($data['status']) || !in_array($data['status'], [0, 1, 2, 3])) {
            return $this->error('无效的状态值', 400);
        }

        $result = $this->qrcodeService->updateStatus($id, $this->tenantId, (int) $data['status']);

        if (!$result) {
            return $this->error('更新失败', 500);
        }

        $this->auditService->logUpdate('qrcode', (string) $id, $qrcode, ['status' => $data['status']], $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '更新成功');
    }

    public function disable(int $id)
    {
        $qrcode = $this->qrcodeModel->find($id);

        if (!$qrcode) {
            return $this->notFound('二维码不存在');
        }

        $result = $this->qrcodeService->disableQrcode($id, $this->tenantId);

        if (!$result) {
            return $this->error('禁用失败', 500);
        }

        $this->auditService->logUpdate('qrcode', (string) $id, $qrcode, ['is_disabled' => 1], $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success(null, '禁用成功');
    }

    public function byBatch(int $batchId)
    {
        $batch = $this->batchModel->find($batchId);

        if (!$batch) {
            return $this->notFound('批次不存在');
        }

        $qrcodes = $this->qrcodeModel->getByBatch($batchId);

        return $this->success($qrcodes);
    }

    public function print()
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['qrIds']) || !is_array($data['qrIds']) || empty($data['qrIds'])) {
            return $this->error('请选择要打印的二维码', 400);
        }

        if (count($data['qrIds']) > 100) {
            return $this->error('单次最多打印100个', 400);
        }

        $qrcodes = $this->qrcodeModel->getForPrint($data['qrIds']);

        if (empty($qrcodes)) {
            return $this->error('未找到有效的二维码', 400);
        }

        $printBatchNo = 'P' . date('YmdHis') . sprintf('%04d', rand(0, 9999));

        $this->qrcodeModel->markAsPrinted(array_column($qrcodes, 'id'), $printBatchNo);

        $html = $this->qrcodeService->generatePrintHtml($qrcodes);

        $this->auditService->log('print', 'qrcode', implode(',', $data['qrIds']), null, [
            'print_batch_no' => $printBatchNo,
            'count' => count($qrcodes),
        ], $this->tenantId, $this->userId, $this->getCurrentUser()['realName'] ?? null);

        return $this->success([
            'html' => $html,
            'printBatchNo' => $printBatchNo,
            'count' => count($qrcodes),
        ]);
    }

    public function download(int $id)
    {
        $qrcode = $this->qrcodeModel->find($id);

        if (!$qrcode) {
            return $this->notFound('二维码不存在');
        }

        if (empty($qrcode['qr_image_url'])) {
            return $this->error('二维码图片不存在', 404);
        }

        $allowedDir = realpath(FCPATH . 'uploads/qrcodes');
        $filepath = FCPATH . ltrim($qrcode['qr_image_url'], '/');
        $realFilePath = realpath($filepath);

        if (!$realFilePath || strpos($realFilePath, $allowedDir) !== 0) {
            return $this->error('无权访问该文件', 403);
        }

        $extension = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
            return $this->error('不支持的文件类型', 400);
        }

        if (!file_exists($realFilePath)) {
            $qrUrl = $qrcode['qr_url'];
            $qrSerial = $qrcode['qr_serial'];
            $this->qrcodeService->generateQrImage($qrUrl, $this->tenantId, $qrSerial);
            $realFilePath = realpath($filepath);
        }

        if (!file_exists($realFilePath)) {
            return $this->error('文件不存在', 404);
        }

        $filename = $qrcode['qr_serial'] . '.' . $extension;

        return $this->response
            ->setHeader('Content-Type', $this->getMimeType($extension))
            ->setHeader('Content-Disposition', 'attachment; filename="' . htmlspecialchars($filename, ENT_QUOTES) . '"')
            ->setBody(file_get_contents($realFilePath));
    }

    protected function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
