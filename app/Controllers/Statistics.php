<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\BatchModel;
use App\Models\QrcodeModel;
use App\Models\ScanLogModel;

class Statistics extends BaseController
{
    protected ProductModel $productModel;
    protected BatchModel $batchModel;
    protected QrcodeModel $qrcodeModel;
    protected ScanLogModel $scanLogModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->productModel = new ProductModel();
        $this->productModel->setTenantId($this->tenantId);
        $this->batchModel = new BatchModel();
        $this->batchModel->setTenantId($this->tenantId);
        $this->qrcodeModel = new QrcodeModel();
        $this->qrcodeModel->setTenantId($this->tenantId);
        $this->scanLogModel = new ScanLogModel();
    }

    public function index()
    {
        $tenantId = $this->tenantId;

        $totalProducts = $this->productModel->countProducts();
        $totalBatches = $this->batchModel->countBatches();
        $totalQrcodes = $this->qrcodeModel->countQrcodes();

        $totalScans = $this->scanLogModel->where('tenant_id', $tenantId)->countAllResults();

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        $todayScans = $this->scanLogModel->where('tenant_id', $tenantId)
            ->where('scan_time >=', $todayStart)
            ->where('scan_time <=', $todayEnd)
            ->countAllResults();

        $weekStart = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $weekEnd = date('Y-m-d 23:59:59');
        $weekScans = $this->scanLogModel->where('tenant_id', $tenantId)
            ->where('scan_time >=', $weekStart)
            ->where('scan_time <=', $weekEnd)
            ->countAllResults();

        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-d 23:59:59');
        $monthScans = $this->scanLogModel->where('tenant_id', $tenantId)
            ->where('scan_time >=', $monthStart)
            ->where('scan_time <=', $monthEnd)
            ->countAllResults();

        return $this->success([
            'totalProducts' => (int) $totalProducts,
            'totalBatches' => (int) $totalBatches,
            'totalQrcodes' => (int) $totalQrcodes,
            'totalScans' => (int) $totalScans,
            'todayScans' => (int) $todayScans,
            'weekScans' => (int) $weekScans,
            'monthScans' => (int) $monthScans,
        ]);
    }

    public function scan()
    {
        $tenantId = $this->tenantId;
        $period = $this->request->getGet('period') ?: 'day';
        $startDate = $this->request->getGet('startDate');
        $endDate = $this->request->getGet('endDate');

        $db = \Config\Database::connect();
        $builder = $db->table('scan_logs');

        switch ($period) {
            case 'week':
                $limit = 12;
                $builder->select('DATE(DATE_SUB(scan_time, INTERVAL WEEKDAY(scan_time) DAY)) as date, COUNT(*) as count');
                $builder->groupBy('DATE(DATE_SUB(scan_time, INTERVAL WEEKDAY(scan_time) DAY))');
                $builder->orderBy('date', 'DESC');
                break;
            case 'month':
                $limit = 12;
                $builder->select('DATE_FORMAT(scan_time, "%Y-%m") as date, COUNT(*) as count');
                $builder->groupBy('DATE_FORMAT(scan_time, "%Y-%m")');
                $builder->orderBy('date', 'DESC');
                break;
            default:
                $limit = 30;
                $builder->select('DATE(scan_time) as date, COUNT(*) as count');
                $builder->groupBy('DATE(scan_time)');
                $builder->orderBy('date', 'DESC');
                break;
        }

        $builder->where('tenant_id', $tenantId);

        if ($startDate) {
            $builder->where('DATE(scan_time) >=', $startDate);
        }
        if ($endDate) {
            $builder->where('DATE(scan_time) <=', $endDate);
        }

        $builder->limit($limit);

        $results = $builder->get()->getResultArray();

        $results = array_reverse($results);

        return $this->success($results);
    }

    public function product()
    {
        $tenantId = $this->tenantId;
        $results = $this->scanLogModel->getProductRanking($tenantId, 10);

        $data = array_map(function ($item) {
            return [
                'productName' => $item['product_name'] ?? '未知产品',
                'scanCount' => (int) $item['scan_count'],
            ];
        }, $results);

        return $this->success($data);
    }

    public function risk()
    {
        $tenantId = $this->tenantId;

        $db = \Config\Database::connect();
        $builder = $db->table('scan_logs');

        $builder->select('scan_logs.qr_serial, products.name as productName, batches.batch_no as batchNo, scan_logs.scan_time, scan_logs.scan_ip, scan_logs.risk_level, scan_logs.risk_reason');
        $builder->join('qrcodes', 'qrcodes.id = scan_logs.qr_id', 'left');
        $builder->join('batches', 'batches.id = qrcodes.batch_id', 'left');
        $builder->join('products', 'products.id = batches.product_id', 'left');
        $builder->where('scan_logs.tenant_id', $tenantId);
        $builder->where('scan_logs.risk_level >=', 1);
        $builder->orderBy('scan_logs.scan_time', 'DESC');
        $builder->limit(20);

        $results = $builder->get()->getResultArray();

        return $this->success($results);
    }
}
