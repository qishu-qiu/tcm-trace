<?php

namespace App\Models;

use CodeIgniter\Model;

class ScanLogModel extends Model
{
    protected $table = 'scan_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'qr_id', 'tenant_id', 'scan_time', 'scan_ip', 'scan_location',
        'user_agent', 'device_fingerprint', 'is_first_scan', 'risk_level',
        'risk_reason',
    ];
    protected $useTimestamps = false;
    protected $validationRules = [
        'qr_id'      => 'required|integer',
        'tenant_id'  => 'required|integer',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;

    public function decodeJsonFields(?array $record): ?array
    {
        if ($record === null) {
            return null;
        }
        if (isset($record['scan_location']) && !empty($record['scan_location'])) {
            $decoded = json_decode($record['scan_location'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $record['scan_location'] = $decoded;
            }
        }
        return $record;
    }

    public function find($id = null)
    {
        if ($id === null) {
            return null;
        }
        $record = parent::find($id);
        return $this->decodeJsonFields($record);
    }

    public function getByQrId(int $qrId, ?int $limit = null)
    {
        $builder = $this->builder();
        
        $builder->where('qr_id', $qrId);
        $builder->orderBy('scan_time', 'DESC');
        
        if ($limit !== null) {
            $builder->limit($limit);
        }
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }

    public function getByQrIdInWindow(int $qrId, int $hours = 1)
    {
        $builder = $this->builder();
        
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $builder->where('qr_id', $qrId);
        $builder->where('scan_time >=', $cutoffTime);
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }

    public function getByIpInWindow(string $ip, int $hours = 1)
    {
        $builder = $this->builder();
        
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $builder->where('scan_ip', $ip);
        $builder->where('scan_time >=', $cutoffTime);
        
        $results = $builder->get()->getResultArray();
        
        foreach ($results as &$result) {
            $result = $this->decodeJsonFields($result);
        }
        
        return $results;
    }

    public function countByQrIdInWindow(int $qrId, int $hours = 1): int
    {
        $builder = $this->builder();
        
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $builder->where('qr_id', $qrId);
        $builder->where('scan_time >=', $cutoffTime);
        
        return $builder->countAllResults();
    }

    public function countDistinctIpsByQrIdInWindow(int $qrId, int $hours = 1): int
    {
        $builder = $this->builder();
        
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $builder->where('qr_id', $qrId);
        $builder->where('scan_time >=', $cutoffTime);
        $builder->select('DISTINCT scan_ip');
        
        return $builder->countAllResults();
    }

    public function countDistinctQrsByIpInWindow(string $ip, int $hours = 1): int
    {
        $builder = $this->builder();
        
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $builder->where('scan_ip', $ip);
        $builder->where('scan_time >=', $cutoffTime);
        $builder->select('DISTINCT qr_id');
        
        return $builder->countAllResults();
    }

    public function getDailyStats(int $tenantId, ?int $days = 30): array
    {
        $builder = $this->builder();
        
        $builder->select('DATE(scan_time) as scan_date, COUNT(*) as scan_count');
        $builder->where('tenant_id', $tenantId);
        
        if ($days !== null) {
            $cutoff = date('Y-m-d', strtotime("-{$days} days"));
            $builder->where('DATE(scan_time) >=', $cutoff);
        }
        
        $builder->groupBy('DATE(scan_time)');
        $builder->orderBy('scan_date', 'DESC');
        
        $result = $builder->get()->getResultArray();
        
        $stats = [];
        foreach ($result as $row) {
            $stats[$row['scan_date']] = $row['scan_count'];
        }
        
        return $stats;
    }

    public function getProductRanking(int $tenantId, ?int $limit = 10): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('scan_logs');
        
        $builder->select('products.name as product_name, COUNT(scan_logs.id) as scan_count');
        $builder->join('qrcodes', 'qrcodes.id = scan_logs.qr_id');
        $builder->join('batches', 'batches.id = qrcodes.batch_id');
        $builder->join('products', 'products.id = batches.product_id');
        $builder->where('scan_logs.tenant_id', $tenantId);
        $builder->groupBy('products.id, products.name');
        $builder->orderBy('scan_count', 'DESC');
        
        if ($limit !== null) {
            $builder->limit($limit);
        }
        
        return $builder->get()->getResultArray();
    }

    public function logScan(array $data): bool
    {
        if (empty($data['scan_time'])) {
            $data['scan_time'] = date('Y-m-d H:i:s');
        }
        
        if (isset($data['scan_location']) && is_array($data['scan_location'])) {
            $data['scan_location'] = json_encode($data['scan_location'], JSON_UNESCAPED_UNICODE);
        }
        
        return $this->insert($data) !== false;
    }
}
