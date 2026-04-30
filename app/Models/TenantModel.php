<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table = 'tenants';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'name', 'slug', 'license_no', 'contact_name', 'contact_phone',
        'address', 'logo', 'plan', 'status', 'max_products', 'max_qrcodes',
        'max_users', 'expires_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'permit_empty|max_length[50]|is_unique[tenants.slug]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;

    public function findBySlug(string $slug)
    {
        return $this->builder()
            ->where('slug', $slug)
            ->get()
            ->getRowArray();
    }

    public function getActiveTenants()
    {
        return $this->builder()
            ->where('status', 1)
            ->where('expires_at IS NULL OR expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getResultArray();
    }
}
