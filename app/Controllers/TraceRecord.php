<?php

namespace App\Controllers;

use App\Models\TraceRecordModel;
use App\Models\BatchModel;
use App\Libraries\AuditService;

class TraceRecord extends BaseController
{
    protected TraceRecordModel $traceRecordModel;
    protected BatchModel $batchModel;
    protected AuditService $auditService;

    protected array $stageFields = [
        'plant' => [
            'seedSource', 'plantMethod', 'fertilizer', 'pesticide', 'irrigation',
        ],
        'harvest' => [
            'harvestMethod', 'weather', 'yieldAmount',
        ],
        'process' => [
            'processType', 'temperature', 'duration', 'equipment',
        ],
        'inspect' => [
            'standard', 'items',
        ],
        'store' => [
            'warehouseName', 'temperature', 'humidity',
        ],
        'transport' => [
            'vehicleNo', 'driver', 'route', 'temperature',
        ],
        'sale' => [
            'buyer', 'quantity', 'price', 'invoiceNo',
        ],
    ];

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->traceRecordModel = new TraceRecordModel();
        $this->traceRecordModel->setTenantId($this->tenantId);
        $this->batchModel = new BatchModel();
        $this->batchModel->setTenantId($this->tenantId);
        $this->auditService = new AuditService();
    }

    public function byBatch(int $batchId)
    {
        $batch = $this->batchModel->find($batchId);

        if (!$batch) {
            return $this->notFound('批次不存在');
        }

        $records = $this->traceRecordModel->getByBatchGrouped($batchId);

        return $this->success($records);
    }

    public function create(int $batchId)
    {
        $batch = $this->batchModel->find($batchId);

        if (!$batch) {
            return $this->notFound('批次不存在');
        }

        $data = $this->request->getJSON(true);

        $rules = [
            'stage'        => 'required|in_list[plant,harvest,process,inspect,store,transport,sale]',
            'operateTime'  => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $stage = $data['stage'];
        $detail = $data['detail'] ?? [];

        $validationError = $this->validateDetail($stage, $detail);
        if ($validationError) {
            return $this->error($validationError, 400);
        }

        $attachments = [];
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            if (count($data['attachments']) > 9) {
                return $this->error('附件最多上传9张', 400);
            }
            foreach ($data['attachments'] as $base64Image) {
                $url = $this->saveBase64Image($base64Image);
                if ($url) {
                    $attachments[] = $url;
                }
            }
        }

        $user = $this->getCurrentUser();

        $recordData = [
            'batch_id'      => $batchId,
            'stage'         => $stage,
            'operator_id'   => $this->userId,
            'operator_name' => $user['realName'] ?? $user['username'],
            'operate_time'  => $data['operateTime'],
            'detail'        => $detail,
            'attachments'   => $attachments,
            'remark'        => $data['remark'] ?? null,
            'latitude'      => $data['latitude'] ?? null,
            'longitude'     => $data['longitude'] ?? null,
            'location_desc' => $data['locationDesc'] ?? null,
        ];

        $recordId = $this->traceRecordModel->insert($recordData);

        if (!$recordId) {
            return $this->error('创建失败', 500);
        }

        $this->auditService->logCreate('trace_record', (string) $recordId, $recordData, $this->tenantId, $this->userId, $user['realName'] ?? null);

        return $this->success([
            'id' => $recordId,
            ...$recordData,
        ], '创建成功');
    }

    public function update(int $id)
    {
        $record = $this->traceRecordModel->find($id);

        if (!$record) {
            return $this->notFound('溯源记录不存在');
        }

        $data = $this->request->getJSON(true);

        $updateData = [];

        if (isset($data['stage'])) {
            if (!in_array($data['stage'], ['plant', 'harvest', 'process', 'inspect', 'store', 'transport', 'sale'])) {
                return $this->error('无效的环节类型', 400);
            }
            $updateData['stage'] = $data['stage'];
        }

        if (isset($data['operateTime'])) {
            $updateData['operate_time'] = $data['operateTime'];
        }

        if (isset($data['detail'])) {
            $stage = $updateData['stage'] ?? $record['stage'];
            $validationError = $this->validateDetail($stage, $data['detail']);
            if ($validationError) {
                return $this->error($validationError, 400);
            }
            $updateData['detail'] = $data['detail'];
        }

        if (isset($data['remark'])) {
            $updateData['remark'] = $data['remark'];
        }

        if (isset($data['latitude'])) {
            $updateData['latitude'] = $data['latitude'];
        }

        if (isset($data['longitude'])) {
            $updateData['longitude'] = $data['longitude'];
        }

        if (isset($data['locationDesc'])) {
            $updateData['location_desc'] = $data['locationDesc'];
        }

        if (empty($updateData)) {
            return $this->error('没有需要更新的数据', 400);
        }

        $result = $this->traceRecordModel->update($id, $updateData);

        if (!$result) {
            return $this->error('更新失败', 500);
        }

        $user = $this->getCurrentUser();
        $this->auditService->logUpdate('trace_record', (string) $id, $record, $updateData, $this->tenantId, $this->userId, $user['realName'] ?? null);

        return $this->success(null, '更新成功');
    }

    public function delete(int $id)
    {
        $record = $this->traceRecordModel->find($id);

        if (!$record) {
            return $this->notFound('溯源记录不存在');
        }

        $result = $this->traceRecordModel->delete($id);

        if (!$result) {
            return $this->error('删除失败', 500);
        }

        $user = $this->getCurrentUser();
        $this->auditService->logDelete('trace_record', (string) $id, $record, $this->tenantId, $this->userId, $user['realName'] ?? null);

        return $this->success(null, '删除成功');
    }

    protected function validateDetail(string $stage, array $detail): ?string
    {
        if (!isset($this->stageFields[$stage])) {
            return '无效的环节类型';
        }

        $requiredFields = $this->stageFields[$stage];

        foreach ($requiredFields as $field) {
            if (!isset($detail[$field])) {
                return "缺少必填字段: {$field}";
            }
        }

        if ($stage === 'inspect') {
            if (!is_array($detail['items'])) {
                return '检验项目必须是数组';
            }
            foreach ($detail['items'] as $item) {
                if (!isset($item['name'], $item['result'], $item['qualified'])) {
                    return '检验项目格式不正确';
                }
            }
        }

        return null;
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
        $uploadPath = FCPATH . 'uploads/reports/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $filepath = $uploadPath . $filename;

        if (!file_put_contents($filepath, $imageData)) {
            return null;
        }

        return '/uploads/reports/' . $filename;
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
