<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\RiskService;

final class RiskServiceTest extends CIUnitTestCase
{
    protected RiskService $riskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->riskService = new RiskService();
    }

    public function testGetStageName(): void
    {
        $this->assertEquals('种植', $this->riskService->getStageName('plant'));
        $this->assertEquals('采收', $this->riskService->getStageName('harvest'));
        $this->assertEquals('加工', $this->riskService->getStageName('process'));
        $this->assertEquals('检验', $this->riskService->getStageName('inspect'));
        $this->assertEquals('仓储', $this->riskService->getStageName('store'));
        $this->assertEquals('运输', $this->riskService->getStageName('transport'));
        $this->assertEquals('销售', $this->riskService->getStageName('sale'));
    }

    public function testGetStageNameWithUnknownStage(): void
    {
        $this->assertEquals('unknown', $this->riskService->getStageName('unknown'));
        $this->assertEquals('custom', $this->riskService->getStageName('custom'));
    }

    public function testFormatTraceTimelineWithEmptyRecords(): void
    {
        $result = $this->riskService->formatTraceTimeline([]);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFormatTraceTimelineWithValidRecords(): void
    {
        $traceRecords = [
            [
                'stage' => 'plant',
                'operate_time' => '2024-01-15 10:30:00',
                'detail' => json_encode(['location' => '云南', 'farmer' => '张三']),
                'attachments' => json_encode(['/uploads/images/1.jpg']),
            ],
            [
                'stage' => 'harvest',
                'operate_time' => '2024-03-20 14:00:00',
                'detail' => '手工采收',
                'attachments' => null,
            ],
        ];

        $result = $this->riskService->formatTraceTimeline($traceRecords);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        
        $this->assertEquals('plant', $result[0]['stage']);
        $this->assertEquals('种植', $result[0]['stageName']);
        $this->assertEquals('2024-01-15', $result[0]['time']);
        $this->assertEquals(['location' => '云南', 'farmer' => '张三'], $result[0]['detail']);
        $this->assertNotEmpty($result[0]['attachments']);
        
        $this->assertEquals('harvest', $result[1]['stage']);
        $this->assertEquals('采收', $result[1]['stageName']);
        $this->assertEquals('2024-03-20', $result[1]['time']);
        $this->assertEquals('手工采收', $result[1]['detail']);
    }

    public function testFormatTraceTimelineWithInvalidJson(): void
    {
        $traceRecords = [
            [
                'stage' => 'process',
                'operate_time' => '2024-04-01 09:00:00',
                'detail' => 'invalid json {',
                'attachments' => 'invalid json',
            ],
        ];

        $result = $this->riskService->formatTraceTimeline($traceRecords);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('invalid json {', $result[0]['detail']);
        $this->assertEquals([], $result[0]['attachments']);
    }

    public function testFormatTraceTimelineWithNullDetail(): void
    {
        $traceRecords = [
            [
                'stage' => 'inspect',
                'operate_time' => '2024-05-10 11:00:00',
                'detail' => null,
                'attachments' => null,
            ],
        ];

        $result = $this->riskService->formatTraceTimeline($traceRecords);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]['detail']);
        $this->assertEquals([], $result[0]['attachments']);
    }

    public function testGenerateDeviceFingerprint(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test)';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'zh-CN';
        
        $fingerprint = $this->riskService->generateDeviceFingerprint();
        
        $this->assertNotNull($fingerprint);
        $this->assertIsString($fingerprint);
        $this->assertEquals(16, strlen($fingerprint));
        
        $fingerprint2 = $this->riskService->generateDeviceFingerprint();
        $this->assertEquals($fingerprint, $fingerprint2);
    }

    public function testGenerateDeviceFingerprintWithMissingServerVars(): void
    {
        $originalAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $originalLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        $fingerprint = $this->riskService->generateDeviceFingerprint();
        
        $this->assertNotNull($fingerprint);
        $this->assertIsString($fingerprint);
        $this->assertEquals(16, strlen($fingerprint));
        
        if ($originalAgent !== null) {
            $_SERVER['HTTP_USER_AGENT'] = $originalAgent;
        }
        if ($originalLang !== null) {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $originalLang;
        }
    }
}