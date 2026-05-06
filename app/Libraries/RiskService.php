<?php

namespace App\Libraries;

use App\Models\ScanLogModel;
use App\Libraries\AuditService;

class RiskService
{
    protected $db;
    protected ScanLogModel $scanLogModel;
    protected AuditService $auditService;

    protected array $stageNames = [
        'plant'     => '种植',
        'harvest'   => '采收',
        'process'   => '加工',
        'inspect'   => '检验',
        'store'     => '仓储',
        'transport' => '运输',
        'sale'      => '销售',
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->scanLogModel = new ScanLogModel();
        $this->auditService = new AuditService();
    }

    public function detectFraud(int $qrId, string $scanIp): array
    {
        $qrcode = $this->db->table('qrcodes')
            ->where('id', $qrId)
            ->get()
            ->getRowArray();

        if (!$qrcode) {
            return [
                'riskLevel' => 2,
                'riskReason' => '溯源码不存在',
            ];
        }

        $riskLevel = 0;
        $riskReason = null;

        if ($qrcode['is_disabled'] == 1) {
            $riskLevel = 2;
            $riskReason = '该溯源码已被禁用';
            return [
                'riskLevel' => $riskLevel,
                'riskReason' => $riskReason,
            ];
        }

        $scanCount = $this->scanLogModel->countByQrIdInWindow($qrId, 1);
        if ($scanCount >= 20) {
            $riskLevel = 2;
            $riskReason = '短时间内查询次数异常';
        }

        if ($riskLevel === 0) {
            $ipCount = $this->scanLogModel->countDistinctIpsByQrIdInWindow($qrId, 1);
            if ($ipCount >= 10) {
                $riskLevel = 2;
                $riskReason = '短时间内查询来源异常';
            }
        }

        if ($riskLevel === 0) {
            $qrCount = $this->scanLogModel->countDistinctQrsByIpInWindow($scanIp, 1);
            if ($qrCount >= 50) {
                $riskLevel = 1;
                $riskReason = '短时间内大量查询不同溯源码';
            }
        }

        if ($riskLevel === 2) {
            $this->disableQrcode($qrId, $qrcode['tenant_id'], $riskReason);
        }

        return [
            'riskLevel' => $riskLevel,
            'riskReason' => $riskReason,
        ];
    }

    protected function disableQrcode(int $qrId, int $tenantId, string $reason): void
    {
        $this->db->table('qrcodes')
            ->where('id', $qrId)
            ->update(['is_disabled' => 1]);

        $this->auditService->logError(
            'fraud_detected',
            'qrcode',
            $reason,
            (string) $qrId,
            ['action' => 'disable'],
            $tenantId,
            null,
            'system'
        );
    }

    public function getStageName(string $stage): string
    {
        return $this->stageNames[$stage] ?? $stage;
    }

    public function formatTraceTimeline(array $traceRecords): array
    {
        $timeline = [];
        
        foreach ($traceRecords as $record) {
            $detail = $record['detail'];
            if (is_string($detail)) {
                $decoded = json_decode($detail, true);
                $detail = $decoded !== null ? $decoded : $detail;
            }
            
            $attachments = $record['attachments'];
            if (is_string($attachments)) {
                $attachments = json_decode($attachments, true) ?? [];
            }
            
            $timeline[] = [
                'stage'      => $record['stage'],
                'stageName'  => $this->getStageName($record['stage']),
                'time'       => substr($record['operate_time'], 0, 10),
                'detail'     => $detail,
                'attachments' => array_map(function($url) {
                    if (strpos($url, 'http') !== 0 && strpos($url, '/') === 0) {
                        return base_url(ltrim($url, '/'));
                    }
                    return $url;
                }, (array) $attachments),
            ];
        }
        
        return $timeline;
    }

    public function generateDeviceFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $timezone = date_default_timezone_get();
        
        $raw = $userAgent . $acceptLanguage . $timezone;
        return substr(md5($raw), 0, 16);
    }
}
