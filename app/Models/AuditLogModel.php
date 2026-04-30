<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id', 'actor_type', 'actor_id', 'actor_name', 'action',
        'resource_type', 'resource_id', 'before_data', 'after_data',
        'source_ip', 'user_agent', 'geo_location', 'occurred_at',
        'trace_id', 'request_id', 'result', 'error_message',
    ];
    protected $useTimestamps = false;
    protected $validationRules = [
        'tenant_id'     => 'required|integer',
        'actor_type'    => 'required|in_list[user,system,api_key]',
        'actor_id'      => 'required|max_length[50]',
        'action'        => 'required|max_length[50]',
        'resource_type' => 'required|max_length[50]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;

    protected $tenantId;

    protected function decodeJsonFields(?array $record): ?array
    {
        if ($record === null) {
            return null;
        }
        if (isset($record['before_data']) && !empty($record['before_data'])) {
            $decoded = json_decode($record['before_data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $record['before_data'] = $decoded;
            }
        }
        if (isset($record['after_data']) && !empty($record['after_data'])) {
            $decoded = json_decode($record['after_data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $record['after_data'] = $decoded;
            }
        }
        if (isset($record['geo_location']) && !empty($record['geo_location'])) {
            $decoded = json_decode($record['geo_location'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $record['geo_location'] = $decoded;
            }
        }
        return $record;
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

    public function findAll(int $limit = 0, int $offset = 0)
    {
        if ($this->tenantId !== null) {
            $this->where('tenant_id', $this->tenantId);
        }
        $records = parent::findAll($limit, $offset);
        foreach ($records as &$record) {
            $record = $this->decodeJsonFields($record);
        }
        return $records;
    }

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
            if (isset($data['before_data']) && is_array($data['before_data'])) {
                $data['before_data'] = json_encode($data['before_data'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['after_data']) && is_array($data['after_data'])) {
                $data['after_data'] = json_encode($data['after_data'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($data['geo_location']) && is_array($data['geo_location'])) {
                $data['geo_location'] = json_encode($data['geo_location'], JSON_UNESCAPED_UNICODE);
            }
        }
        return parent::insert($data, $returnID);
    }

    public function getLogs(?int $limit = 50, ?int $offset = 0)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->orderBy('occurred_at', 'DESC');
        $builder->limit($limit, $offset);
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }

    public function getByActor(string $actorId, ?int $limit = 50)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('actor_id', $actorId);
        $builder->orderBy('occurred_at', 'DESC');
        $builder->limit($limit);
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }

    public function getByResource(string $resourceType, string $resourceId, ?int $limit = 50)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('resource_type', $resourceType);
        $builder->where('resource_id', $resourceId);
        $builder->orderBy('occurred_at', 'DESC');
        $builder->limit($limit);
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }
}
