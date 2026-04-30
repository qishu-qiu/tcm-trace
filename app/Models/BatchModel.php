<?php

namespace App\Models;

use CodeIgniter\Model;

class BatchModel extends Model
{
    protected $table = 'batches';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id', 'product_id', 'batch_no', 'farm_name', 'farm_address', 'farm_area',
        'plant_date', 'harvest_date', 'harvest_qty', 'grow_years', 'process_method',
        'process_date', 'process_unit', 'inspect_date', 'inspect_result', 'inspect_report',
        'inspect_unit', 'package_spec', 'package_qty', 'warehouse_info', 'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'product_id'     => 'required|integer',
        'batch_no'       => 'required|max_length[50]',
        'farm_name'      => 'permit_empty|max_length[100]',
        'harvest_qty'    => 'permit_empty|decimal',
        'grow_years'     => 'permit_empty|integer',
        'inspect_result' => 'permit_empty|max_length[20]',
        'package_qty'    => 'permit_empty|integer',
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
        if ($data !== null && !isset($data['tenant_id']) && $this->tenantId !== null) {
            $data['tenant_id'] = $this->tenantId;
        }
        return parent::insert($data, $returnID);
    }

    public function update($id = null, array $data = null)
    {
        if ($data !== null && !isset($data['tenant_id']) && $this->tenantId !== null) {
            $data['tenant_id'] = $this->tenantId;
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
        return parent::find($id);
    }

    public function getList(int $page = 1, int $pageSize = 20, ?int $productId = null, ?int $status = null, ?string $keyword = null)
    {
        $builder = $this->builder();
        
        $builder->select('batches.*, products.name as product_name, products.alias as product_alias');
        $builder->join('products', 'products.id = batches.product_id', 'left');
        
        if ($this->tenantId !== null) {
            $builder->where('batches.tenant_id', $this->tenantId);
            $builder->where('products.tenant_id', $this->tenantId);
        }
        
        if ($productId !== null) {
            $builder->where('batches.product_id', $productId);
        }
        
        if ($status !== null) {
            $builder->where('batches.status', $status);
        }
        
        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('batches.batch_no', $keyword)
                ->orLike('products.name', $keyword)
                ->orLike('farm_name', $keyword)
                ->groupEnd();
        }
        
        $builder->orderBy('batches.created_at', 'DESC');
        
        $total = $builder->countAllResults(false);
        
        $builder->limit($pageSize, ($page - 1) * $pageSize);
        $list = $builder->get()->getResultArray();
        
        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    public function getDetail(int $batchId)
    {
        $builder = $this->builder();
        
        $builder->select('batches.*, products.name as product_name, products.alias as product_alias, products.origin as product_origin');
        $builder->join('products', 'products.id = batches.product_id', 'left');
        
        if ($this->tenantId !== null) {
            $builder->where('batches.tenant_id', $this->tenantId);
        }
        
        $builder->where('batches.id', $batchId);
        
        return $builder->get()->getRowArray();
    }

    public function getNextSequence(string $productName): int
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $prefix = substr(pinyin_first_letter($productName), 0, 4) . date('y');
        $builder->like('batch_no', $prefix, 'after');
        
        $maxNo = $builder->select('batch_no')->orderBy('batch_no', 'DESC')->limit(1)->get()->getRowArray();
        
        if (!$maxNo) {
            return 1;
        }
        
        $seq = substr($maxNo['batch_no'], -6);
        return (int) $seq + 1;
    }

    public function countBatches()
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        return $builder->countAllResults();
    }
}
