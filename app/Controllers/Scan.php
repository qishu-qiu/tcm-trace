<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\ScanLogModel;
use App\Libraries\QrcodeService;
use App\Libraries\RiskService;
use App\Libraries\AuditService;

class Scan extends BaseController
{
    protected $db;
    protected ScanLogModel $scanLogModel;
    protected QrcodeService $qrcodeService;
    protected RiskService $riskService;
    protected AuditService $auditService;
    protected $cache;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->scanLogModel = new ScanLogModel();
        $this->qrcodeService = new QrcodeService();
        $this->riskService = new RiskService();
        $this->auditService = new AuditService();
        
        $cacheConfig = new \Config\Cache();
        $this->cache = \Config\Services::cache($cacheConfig);
    }

    public function verify(string $qrSerial)
    {
        $request = service('request');
        
        $isHtmlRequest = !$request->isAJAX() && strpos($request->getUserAgent()->getAgentString(), 'Mozilla') !== false;
        if ($isHtmlRequest) {
            return $this->showVerifyPage($qrSerial);
        }

        return $this->getVerifyData($qrSerial);
    }

    public function showVerifyPage(string $qrSerial)
    {
        $baseUrl = base_url();
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>中药材溯源验证</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.global.prod.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;background:#f5f5f5;min-height:100vh}
        .app{max-width:600px;margin:0 auto;background:#fff;min-height:100vh}
        .header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 20px;text-align:center;color:#fff}
        .logo{font-size:28px;font-weight:700;margin-bottom:8px}
        .subtitle{font-size:14px;opacity:.9}
        .loading{padding:60px 20px;text-align:center;color:#666}
        .loading .spinner{width:40px;height:40px;border:3px solid #eee;border-top-color:#667eea;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 20px}
        @keyframes spin{to{transform:rotate(360deg)}}
        .result{padding:20px}
        .status-card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.08);text-align:center;margin-bottom:20px}
        .status-valid{background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%)}
        .status-invalid{background:linear-gradient(135deg,#eb3349 0%,#f45c43 100%)}
        .status-valid .icon{width:80px;height:80px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:48px}
        .status-invalid .icon{width:80px;height:80px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:48px}
        .status-title{font-size:22px;font-weight:700;color:#fff;margin-bottom:8px}
        .status-message{font-size:14px;color:rgba(255,255,255,.9)}
        .product-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:16px}
        .card-title{font-size:18px;font-weight:600;color:#333;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #eee}
        .info-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f5f5f5}
        .info-row:last-child{border-bottom:0}
        .info-label{color:#666;font-size:14px}
        .info-value{color:#333;font-size:14px;font-weight:500;text-align:right;max-width:60%;word-break:break-word}
        .scan-info{background:#fff8e1;border-radius:12px;padding:16px;margin-bottom:16px}
        .scan-info .title{font-size:16px;font-weight:600;color:#d68910;margin-bottom:12px}
        .scan-info .row{display:flex;justify-content:space-between;padding:8px 0}
        .timeline{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:20px}
        .timeline-item{display:flex;gap:16px;padding:16px 0;border-bottom:1px solid #f5f5f5}
        .timeline-item:last-child{border-bottom:0}
        .timeline-dot{width:12px;height:12px;border-radius:50%;background:#667eea;margin-top:4px;flex-shrink:0}
        .timeline-content{flex:1}
        .timeline-stage{font-size:16px;font-weight:600;color:#333;margin-bottom:4px}
        .timeline-time{font-size:12px;color:#999;margin-bottom:8px}
        .timeline-detail{font-size:14px;color:#666;line-height:1.6}
        .timeline-attachments{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
        .timeline-attachments img{width:80px;height:80px;object-fit:cover;border-radius:8px}
        .risk-alert{background:#fff3f3;border:1px solid #ffc9c9;border-radius:8px;padding:16px;margin-bottom:16px}
        .risk-alert .alert-title{font-size:14px;font-weight:600;color:#c92a2a;margin-bottom:8px}
        .risk-alert .alert-text{font-size:14px;color:#666}
        .footer{text-align:center;padding:30px 20px;color:#999;font-size:12px}
    </style>
</head>
<body>
    <div id="app" class="app">
        <div class="header">
            <div class="logo">中药材溯源</div>
            <div class="subtitle">中药材全流程溯源查询平台</div>
        </div>
        
        <div v-if="loading" class="loading">
            <div class="spinner"></div>
            <div>正在查询...</div>
        </div>
        
        <template v-else-if="error">
            <div class="result">
                <div class="status-card status-invalid">
                    <div class="icon">❌</div>
                    <div class="status-title">查询失败</div>
                    <div class="status-message">{{ error }}</div>
                </div>
            </div>
        </template>
        
        <template v-else-if="result">
            <div class="result">
                <div v-if="result.riskLevel === 2" class="risk-alert">
                    <div class="alert-title">⚠️ 风险提示</div>
                    <div class="alert-text">{{ result.riskReason }}</div>
                </div>
                
                <div :class="['status-card', result.valid ? 'status-valid' : 'status-invalid']">
                    <div class="icon">{{ result.valid ? '✅' : '❌' }}</div>
                    <div class="status-title">{{ result.valid ? '正品认证' : '无效溯源码' }}</div>
                    <div class="status-message">{{ result.message || '该产品信息已通过审核' }}</div>
                </div>
                
                <template v-if="result.valid">
                    <div class="product-card">
                        <div class="card-title">产品信息</div>
                        <div class="info-row"><span class="info-label">产品名称</span><span class="info-value">{{ result.productName }}</span></div>
                        <div class="info-row" v-if="result.batchNo"><span class="info-label">批次号</span><span class="info-value">{{ result.batchNo }}</span></div>
                        <div class="info-row" v-if="result.origin"><span class="info-label">产地</span><span class="info-value">{{ result.origin }}</span></div>
                        <div class="info-row" v-if="result.enterprise"><span class="info-label">生产企业</span><span class="info-value">{{ result.enterprise }}</span></div>
                        <div class="info-row" v-if="result.productionDate"><span class="info-label">生产日期</span><span class="info-value">{{ result.productionDate }}</span></div>
                        <div class="info-row" v-if="result.specification"><span class="info-label">规格</span><span class="info-value">{{ result.specification }}</span></div>
                        <div class="info-row" v-if="result.inspectResult"><span class="info-label">检验结果</span><span class="info-value">{{ result.inspectResult }}</span></div>
                        <div class="info-row" v-if="result.inspectReport">
                            <span class="info-label">检验报告</span>
                            <span class="info-value"><a :href="result.inspectReport" target="_blank" style="color:#667eea;text-decoration:none">查看报告</a></span>
                        </div>
                    </div>
                    
                    <div class="scan-info">
                        <div class="title">扫码信息</div>
                        <div class="row"><span>首次扫码</span><span>{{ result.scanInfo.isFirstScan ? '是' : '否' }}</span></div>
                        <div class="row"><span>累计查询</span><span>{{ result.scanInfo.totalScans }}次</span></div>
                        <div class="row" v-if="result.scanInfo.firstScanTime"><span>首次查询</span><span>{{ result.scanInfo.firstScanTime }}</span></div>
                        <div v-if="result.scanInfo.riskLevel > 0" class="row" style="color:#c92a2a">
                            <span>风险等级</span>
                            <span>{{ result.scanInfo.riskLevel === 1 ? '低风险' : '高风险' }}</span>
                        </div>
                    </div>
                    
                    <div class="timeline" v-if="result.traceTimeline && result.traceTimeline.length">
                        <div class="card-title">溯源时间线</div>
                        <div class="timeline-item" v-for="item in result.traceTimeline" :key="item.stage">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-stage">{{ item.stageName }}</div>
                                <div class="timeline-time">{{ item.time }}</div>
                                <div class="timeline-detail" v-if="item.detail">
                                    <div v-for="(value, key) in item.detail" :key="key">{{ key }}: {{ value }}</div>
                                </div>
                                <div class="timeline-attachments" v-if="item.attachments && item.attachments.length">
                                    <img v-for="(img, i) in item.attachments" :key="i" :src="img" :alt="'附件' + (i + 1)">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        
        <div class="footer">© 2025 中药材溯源平台 · 安全可信赖</div>
    </div>

    <script>
        const { createApp, ref, onMounted } = Vue;
        
        createApp({
            setup() {
                const loading = ref(true);
                const error = ref('');
                const result = ref(null);
                
                const qrSerial = window.location.pathname.split('/').filter(Boolean).pop() || '';
                
                const fetchData = async () => {
                    try {
                        const response = await fetch('{$baseUrl}api/scan/' + qrSerial);
                        const data = await response.json();
                        
                        if (data.code === 200 && data.data) {
                            result.value = data.data;
                        } else {
                            error.value = data.message || '查询失败';
                        }
                    } catch (err) {
                        error.value = '网络请求失败，请稍后重试';
                    } finally {
                        loading.value = false;
                    }
                };
                
                onMounted(() => {
                    fetchData();
                });
                
                return { loading, error, result };
            }
        }).mount('#app');
    </script>
</body>
</html>
HTML;

        return $this->response->setBody($html)->setHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function getVerifyData(string $qrSerial): ResponseInterface
    {
        $cacheKey = 'scan_result_' . $qrSerial;
        
        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult) {
            return $this->response->setJSON([
                'code' => 200,
                'data' => $cachedResult,
            ]);
        }

        $qrcode = $this->db->table('qrcodes')
            ->where('qr_serial', $qrSerial)
            ->get()
            ->getRowArray();

        if (!$qrcode) {
            return $this->response->setJSON([
                'code' => 200,
                'data' => [
                    'valid' => false,
                    'message' => '无效溯源码',
                ],
            ]);
        }

        if ($qrcode['is_disabled'] == 1) {
            return $this->response->setJSON([
                'code' => 200,
                'data' => [
                    'valid' => false,
                    'message' => '该溯源码已被禁用',
                ],
            ]);
        }

        $batch = $this->db->table('batches')
            ->select('batches.*, products.name as product_name, products.alias, products.origin, products.specification, products.quality_grade, tenants.name as enterprise_name')
            ->join('products', 'products.id = batches.product_id')
            ->join('tenants', 'tenants.id = batches.tenant_id')
            ->where('batches.id', $qrcode['batch_id'])
            ->get()
            ->getRowArray();

        if (!$batch) {
            return $this->response->setJSON([
                'code' => 200,
                'data' => [
                    'valid' => false,
                    'message' => '批次信息不存在',
                ],
            ]);
        }

        $traceRecords = $this->db->table('trace_records')
            ->where('batch_id', $qrcode['batch_id'])
            ->orderBy('operate_time', 'ASC')
            ->get()
            ->getResultArray();

        $scanIp = $this->request->getIPAddress();
        $riskResult = $this->riskService->detectFraud($qrcode['id'], $scanIp);

        $isFirstScan = $qrcode['scan_count'] == 0;
        $this->qrcodeService->updateScanInfo($qrcode['id'], $scanIp);

        $scanLogData = [
            'qr_id'             => $qrcode['id'],
            'tenant_id'         => $qrcode['tenant_id'],
            'scan_time'         => date('Y-m-d H:i:s'),
            'scan_ip'           => $scanIp,
            'user_agent'        => $this->request->getUserAgent()->getAgentString(),
            'device_fingerprint' => $this->riskService->generateDeviceFingerprint(),
            'is_first_scan'     => $isFirstScan ? 1 : 0,
            'risk_level'        => $riskResult['riskLevel'],
            'risk_reason'       => $riskResult['riskReason'],
        ];
        $this->scanLogModel->logScan($scanLogData);

        $timeline = $this->riskService->formatTraceTimeline($traceRecords);

        $resultData = [
            'valid'           => true,
            'productName'     => $batch['product_name'],
            'batchNo'         => $batch['batch_no'],
            'origin'          => $batch['origin'],
            'enterprise'      => $batch['enterprise_name'],
            'productionDate'  => $batch['plant_date'],
            'specification'   => $batch['specification'],
            'inspectResult'   => $batch['inspect_result'],
            'inspectReport'   => $batch['inspect_report'] ? $this->formatUrl($batch['inspect_report']) : null,
            'scanInfo'        => [
                'isFirstScan'    => $isFirstScan,
                'totalScans'     => $qrcode['scan_count'] + 1,
                'firstScanTime'  => $qrcode['first_scan_at'],
                'riskLevel'      => $riskResult['riskLevel'],
                'riskReason'     => $riskResult['riskReason'],
            ],
            'traceTimeline'   => $timeline,
            'riskLevel'       => $riskResult['riskLevel'],
            'riskReason'      => $riskResult['riskReason'],
        ];

        if ($riskResult['riskLevel'] === 0) {
            $this->cache->save($cacheKey, $resultData, 3600);
        }

        return $this->response->setJSON([
            'code' => 200,
            'data' => $resultData,
        ]);
    }

    public function history(int $batchId)
    {
        $scanLogs = $this->scanLogModel->getByQrId($batchId, 100);
        
        return $this->response->setJSON([
            'code' => 200,
            'data' => $scanLogs,
        ]);
    }

    protected function formatUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        
        if (strpos($url, '/') === 0) {
            return base_url(ltrim($url, '/'));
        }
        
        return base_url($url);
    }
}
