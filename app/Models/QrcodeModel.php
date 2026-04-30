<?php

namespace App\Models;

use CodeIgniter\Model;

class QrcodeModel extends Model
{
    protected $table = 'qrcodes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id', 'batch_id', 'qr_serial', 'qr_url', 'qr_image_url',
        'scan_count', 'first_scan_at', 'first_scan_ip', 'last_scan_at',
        'is_disabled', 'status', 'print_batch_no',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = false;
    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'batch_id'   => 'required|integer',
        'qr_serial'  => 'required|max_length[32]|is_unique[qrcodes.qr_serial]',
        'qr_url'     => 'required|max_length[500]',
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

    public function getList(int $page = 1, int $pageSize = 20, ?int $batchId = null, ?int $status = null)
    {
        $builder = $this->builder();
        
        $builder->select('qrcodes.*, batches.batch_no, products.name as product_name, products.origin, products.specification');
        $builder->join('batches', 'batches.id = qrcodes.batch_id', 'left');
        $builder->join('products', 'products.id = batches.product_id', 'left');
        
        if ($this->tenantId !== null) {
            $builder->where('qrcodes.tenant_id', $this->tenantId);
        }
        
        if ($batchId !== null) {
            $builder->where('qrcodes.batch_id', $batchId);
        }
        
        if ($status !== null) {
            $builder->where('qrcodes.status', $status);
        }
        
        $builder->orderBy('qrcodes.created_at', 'DESC');
        
        $total = $builder->countAllResults(false);
        
        $builder->limit($pageSize, ($page - 1) * $pageSize);
        $list = $builder->get()->getResultArray();
        
        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    public function getDetail(int $id)
    {
        $builder = $this->builder();
        
        $builder->select('qrcodes.*, batches.batch_no, batches.harvest_date, products.name as product_name, products.alias, products.origin, products.specification, products.quality_grade');
        $builder->join('batches', 'batches.id = qrcodes.batch_id', 'left');
        $builder->join('products', 'products.id = batches.product_id', 'left');
        
        if ($this->tenantId !== null) {
            $builder->where('qrcodes.tenant_id', $this->tenantId);
        }
        
        $builder->where('qrcodes.id', $id);
        
        return $builder->get()->getRowArray();
    }

    public function getByBatch(int $batchId)
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        $builder->where('batch_id', $batchId);
        $builder->orderBy('created_at', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    public function getBySerial(string $serial)
    {
        return $this->builder()
            ->where('qr_serial', $serial)
            ->get()
            ->getRowArray();
    }

    public function countQrcodes()
    {
        $builder = $this->builder();
        
        if ($this->tenantId !== null) {
            $builder->where('tenant_id', $this->tenantId);
        }
        
        return $builder->countAllResults();
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

    public function getForPrint(array $ids)
    {
        $builder = $this->builder();
        
        $builder->select('qrcodes.*, batches.batch_no, products.name as product_name, products.origin, products.specification');
        $builder->join('batches', 'batches.id = qrcodes.batch_id', 'left');
        $builder->join('products', 'products.id = batches.product_id', 'left');
        
        if ($this->tenantId !== null) {
            $builder->where('qrcodes.tenant_id', $this->tenantId);
        }
        
        $builder->whereIn('qrcodes.id', $ids);
        
        return $builder->get()->getResultArray();
    }

    public function markAsPrinted(array $ids, string $printBatchNo): bool
    {
        return $this->builder()
            ->whereIn('id', $ids)
            ->update([
                'status' => 1,
                'print_batch_no' => $printBatchNo,
            ]);
    }
}
