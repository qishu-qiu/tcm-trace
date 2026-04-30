<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id', 'name', 'alias', 'origin', 'category', 'specification',
        'quality_grade', 'image_url', 'description', 'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'name'           => 'required|max_length[100]',
        'alias'          => 'permit_empty|max_length[100]',
        'origin'         => 'permit_empty|max_length[100]',
        'category'       => 'permit_empty|max_length[50]',
        'specification'  => 'permit_empty|max_length[200]',
        'quality_grade'  => 'permit_empty|max_length[20]',
        'description'    => 'permit_empty',
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

    public function findAll(int $limit = 0, int $offset = 0)
    {
        if ($this->tenantId !== null) {
            $this->where('tenant_id', $this->tenantId);
        }
        return parent::findAll($limit, $offset);
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

    public function getList(int $page = 1, int $pageSize = 20, ?string $keyword = null, ?string $category = null)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('name', $keyword)
                ->orLike('alias', $keyword)
                ->orLike('origin', $keyword)
                ->groupEnd();
        }
        
        if (!empty($category)) {
            $builder->where('category', $category);
        }
        
        $builder->where('status', 1);
        $builder->orderBy('created_at', 'DESC');
        
        $total = $builder->countAllResults(false);
        
        $builder->limit($pageSize, ($page - 1) * $pageSize);
        $list = $builder->get()->getResultArray();
        
        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    public function getCategories()
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('status', 1);
        $builder->select('category');
        $builder->distinct();
        
        $result = $builder->get()->getResultArray();
        return array_column($result, 'category');
    }

    public function countProducts()
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('status', 1);
        
        return $builder->countAllResults();
    }
}
