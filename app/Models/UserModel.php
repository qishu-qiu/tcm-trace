<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id', 'username', 'password_hash', 'real_name', 'phone',
        'email', 'role', 'status', 'last_login_at', 'last_login_ip',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'username'      => 'required|max_length[50]',
        'password_hash' => 'required|max_length[255]',
        'role'          => 'permit_empty|in_list[admin,staff,viewer]',
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

    public function getList(int $page = 1, int $pageSize = 20, ?string $keyword = null, ?string $role = null, ?int $status = null)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('username', $keyword)
                ->orLike('real_name', $keyword)
                ->orLike('phone', $keyword)
                ->groupEnd();
        }
        
        if (!empty($role)) {
            $builder->where('role', $role);
        }
        
        if ($status !== null) {
            $builder->where('status', $status);
        }
        
        $builder->orderBy('created_at', 'DESC');
        
        $total = $builder->countAllResults(false);
        
        $builder->limit($pageSize, ($page - 1) * $pageSize);
        $list = $builder->get()->getResultArray();
        
        foreach ($list as &$item) {
            unset($item['password_hash']);
        }
        
        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    public function countUsers()
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        return $builder->countAllResults();
    }

    public function findByUsername(string $username, int $tenantId)
    {
        return $this->builder()
            ->where('username', $username)
            ->where('tenant_id', $tenantId)
            ->get()
            ->getRowArray();
    }
}
