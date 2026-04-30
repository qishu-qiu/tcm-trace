<?php

namespace App\Models;

use CodeIgniter\Model;

class TraceRecordModel extends Model
{
    protected $table = 'trace_records';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id', 'batch_id', 'stage', 'operator_id', 'operator_name',
        'operate_time', 'detail', 'attachments', 'remark', 'latitude',
        'longitude', 'location_desc',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'batch_id'      => 'required|integer',
        'stage'         => 'required|in_list[plant,harvest,process,inspect,store,transport,sale]',
        'operate_time'  => 'required|valid_date',
        'detail'        => 'permit_empty',
        'attachments'   => 'permit_empty',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;

    protected $tenantId;

    public function setTenantId(int $tenantId)
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function insert(array $data = null, bool $returnID = true)
    {
        if ($data !== null) {
            if (!isset($data['tenant_id']) && $this->tenantId !== null) {
                $data['tenant_id'] = $this->tenantId;
            }
            if (isset($data['detail']) && is_array($data['detail'])) {
                $data['detail'] = json_encode($data['detail'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                $data['attachments'] = json_encode($data['attachments'], JSON_UNESCAPED_UNICODE);
            }
        }
        return parent::insert($data, $returnID);
    }

    public function update($id = null, array $data = null)
    {
        if ($data !== null) {
            if (!isset($data['tenant_id']) && $this->tenantId !== null) {
                $data['tenant_id'] = $this->tenantId;
            }
            if (isset($data['detail']) && is_array($data['detail'])) {
                $data['detail'] = json_encode($data['detail'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                $data['attachments'] = json_encode($data['attachments'], JSON_UNESCAPED_UNICODE);
            }
        }
        return parent::update($id, $data);
    }

    public function find($id = null)
    {
        if ($id === null) {
            return null;
        }
        if ($this->tenantId !== null) {
            $this->where('tenant_id', $this->tenantId);
        }
        $record = parent::find($id);
        return $this->decodeJsonFields($record);
    }

    protected function decodeJsonFields(?array $record): ?array
    {
        if ($record === null) {
            return null;
        }
        if (isset($record['detail']) && !empty($record['detail'])) {
            $record['detail'] = json_decode($record['detail'], true) ?? $record['detail'];
        }
        if (isset($record['attachments']) && !empty($record['attachments'])) {
            $record['attachments'] = json_decode($record['attachments'], true) ?? $record['attachments'];
        }
        return $record;
    }

    public function getByBatch(int $batchId)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('batch_id', $batchId);
        $builder->orderBy('operate_time', 'ASC');
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }

    public function getByBatchGrouped(int $batchId)
    {
        $records = $this->getByBatch($batchId);
        
        $grouped = [
            'plant'     => [],
            'harvest'   => [],
            'process'   => [],
            'inspect'   => [],
            'store'     => [],
            'transport' => [],
            'sale'      => [],
        ];
        
        foreach ($records as $record) {
            $stage = $record['stage'];
            if (isset($grouped[$stage])) {
                $grouped[$stage][] = $record;
            }
        }
        
        return $grouped;
    }

    public function getByStage(int $batchId, string $stage)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('batch_id', $batchId);
        $builder->where('stage', $stage);
        $builder->orderBy('operate_time', 'ASC');
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }

    public function countByBatch(int $batchId)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('batch_id', $batchId);
        
        return $builder->countAllResults();
    }
}
