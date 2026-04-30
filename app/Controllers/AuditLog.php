<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AuditLog extends BaseController
{
    protected AuditLogModel $auditLogModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->auditLogModel = new AuditLogModel();
        $this->auditLogModel->setTenantId($this->tenantId);
    }

    public function index()
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限访问');
        }

        $page = (int) $this->request->getGet('page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
        $pageSize = (int) $this->request->getGet('pageSize', FILTER_SANITIZE_NUMBER_INT) ?: 20;
        $actorId = $this->request->getGet('actorId');
        $action = $this->request->getGet('action');
        $resourceType = $this->request->getGet('resourceType');
        $startDate = $this->request->getGet('startDate');
        $endDate = $this->request->getGet('endDate');
        $result = $this->request->getGet('result');

        $query = $this->auditLogModel->builder();

        if ($actorId) {
            $query->where('actor_id', $actorId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($resourceType) {
            $query->where('resource_type', $resourceType);
        }

        if ($startDate) {
            $query->where('occurred_at >=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('occurred_at <=', $endDate . ' 23:59:59');
        }

        if ($result) {
            $query->where('result', $result);
        }

        $query->orderBy('occurred_at', 'DESC');

        $total = $query->countAllResults(false);

        $query->limit($pageSize, ($page - 1) * $pageSize);
        $list = $query->get()->getResultArray();

        foreach ($list as &$item) {
            $item['before_data'] = $item['before_data'] ? json_decode($item['before_data'], true) : null;
            $item['after_data'] = $item['after_data'] ? json_decode($item['after_data'], true) : null;
            $item['geo_location'] = $item['geo_location'] ? json_decode($item['geo_location'], true) : null;
        }

        return $this->paginated($list, $total, $page, $pageSize);
    }

    public function show(int $id)
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限访问');
        }

        $log = $this->auditLogModel->find($id);

        if (!$log) {
            return $this->notFound('日志不存在');
        }

        $log['before_data'] = $log['before_data'] ? json_decode($log['before_data'], true) : null;
        $log['after_data'] = $log['after_data'] ? json_decode($log['after_data'], true) : null;
        $log['geo_location'] = $log['geo_location'] ? json_decode($log['geo_location'], true) : null;

        return $this->success($log);
    }

    public function resource(string $resourceType, string $resourceId)
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限访问');
        }

        $page = (int) $this->request->getGet('page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
        $pageSize = (int) $this->request->getGet('pageSize', FILTER_SANITIZE_NUMBER_INT) ?: 20;

        $query = $this->auditLogModel->builder();
        $query->where('resource_type', $resourceType);
        $query->where('resource_id', $resourceId);
        $query->orderBy('occurred_at', 'DESC');

        $total = $query->countAllResults(false);

        $query->limit($pageSize, ($page - 1) * $pageSize);
        $list = $query->get()->getResultArray();

        foreach ($list as &$item) {
            $item['before_data'] = $item['before_data'] ? json_decode($item['before_data'], true) : null;
            $item['after_data'] = $item['after_data'] ? json_decode($item['after_data'], true) : null;
        }

        return $this->paginated($list, $total, $page, $pageSize);
    }

    public function export()
    {
        if ($this->userRole !== 'admin') {
            return $this->forbidden('无权限访问');
        }

        $actorId = $this->request->getGet('actorId');
        $action = $this->request->getGet('action');
        $resourceType = $this->request->getGet('resourceType');
        $startDate = $this->request->getGet('startDate');
        $endDate = $this->request->getGet('endDate');
        $result = $this->request->getGet('result');

        $query = $this->auditLogModel->builder();

        if ($actorId) {
            $query->where('actor_id', $actorId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($resourceType) {
            $query->where('resource_type', $resourceType);
        }

        if ($startDate) {
            $query->where('occurred_at >=', $startDate . ' 00:00:00');
        }

        if ($endDate) {
            $query->where('occurred_at <=', $endDate . ' 23:59:59');
        }

        if ($result) {
            $query->where('result', $result);
        }

        $query->orderBy('occurred_at', 'DESC');
        $query->limit(10000);

        $list = $query->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['时间', '操作人', '操作类型', '资源类型', '资源ID', 'IP地址', '结果'];
        $sheet->fromArray($headers, null, 'A1');

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2BA471');

        $row = 2;
        foreach ($list as $item) {
            $sheet->setCellValue('A' . $row, $item['occurred_at']);
            $sheet->setCellValue('B' . $row, $item['actor_name'] ?: $item['actor_id']);
            $sheet->setCellValue('C' . $row, $item['action']);
            $sheet->setCellValue('D' . $row, $item['resource_type']);
            $sheet->setCellValue('E' . $row, $item['resource_id']);
            $sheet->setCellValue('F' . $row, $item['source_ip']);
            $sheet->setCellValue('G' . $row, $item['result'] === 'success' ? '成功' : '失败');
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'audit_log_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
