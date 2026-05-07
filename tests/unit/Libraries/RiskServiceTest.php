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

    public function testGetStageNameValid(): void
    {
        $stageNames = [
            'plant'     => '种植',
            'harvest'   => '采收',
            'process'   => '加工',
            'inspect'   => '检验',
            'store'     => '仓储',
            'transport' => '运输',
            'sale'      => '销售',
        ];

        foreach ($stageNames as $stage => $expectedName) {
            $result = $this->riskService->getStageName($stage);
            $this->assertEquals($expectedName, $result);
        }
    }

    public function testGetStageNameInvalid(): void
    {
        $result = $this->riskService->getStageName('invalid_stage');
        $this->assertEquals('invalid_stage', $result);
    }

    public function testGetStageNameEmpty(): void
    {
        $result = $this->riskService->getStageName('');
        $this->assertEquals('', $result);
    }

    public function testFormatTraceTimelineEmpty(): void
    {
        $result = $this->riskService->formatTraceTimeline([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFormatTraceTimelineWithValidData(): void
    {
        $traceRecords = [
            [
                'stage'       => 'plant',
                'operate_time' => '2024-01-01 10:00:00',
                'detail'      => '{"location": "农场A", "temperature": "25"}',
                'attachments' => '["/images/1.jpg", "/images/2.jpg"]',
            ],
            [
                'stage'       => 'harvest',
                'operate_time' => '2024-01-15 14:30:00',
                'detail'      => ['worker' => '张三', 'quantity' => 100],
                'attachments' => [],
            ],
        ];

        $result = $this->riskService->formatTraceTimeline($traceRecords);

        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        
        $this->assertEquals('种植', $result[0]['stageName']);
        $this->assertEquals('2024-01-01', $result[0]['time']);
        $this->assertEquals(['location' => '农场A', 'temperature' => '25'], $result[0]['detail']);
        $this->assertIsArray($result[0]['attachments']);
        
        $this->assertEquals('采收', $result[1]['stageName']);
        $this->assertEquals(['worker' => '张三', 'quantity' => 100], $result[1]['detail']);
    }

    public function testFormatTraceTimelineWithInvalidJson(): void
    {
        $traceRecords = [
            [
                'stage'       => 'process',
                'operate_time' => '2024-02-01 09:00:00',
                'detail'      => 'invalid json',
                'attachments' => 'invalid json',
            ],
        ];

        $result = $this->riskService->formatTraceTimeline($traceRecords);

        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        $this->assertEquals([], $result[0]['detail']);
        $this->assertEquals([], $result[0]['attachments']);
    }

    public function testGenerateDeviceFingerprint(): void
    {
        $fingerprint = $this->riskService->generateDeviceFingerprint();
        
        $this->assertNotNull($fingerprint);
        $this->assertIsString($fingerprint);
        $this->assertEquals(16, strlen($fingerprint));
    }

    public function testGenerateDeviceFingerprintWithServerVars(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Test)';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'zh-CN';
        
        $fingerprint = $this->riskService->generateDeviceFingerprint();
        
        $this->assertNotNull($fingerprint);
        $this->assertEquals(16, strlen($fingerprint));
        
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    public function testFormatTraceTimelineWithNullAttachments(): void
    {
        $traceRecords = [
            [
                'stage'       => 'sale',
                'operate_time' => '2024-03-01 16:00:00',
                'detail'      => ['customer' => '李四'],
                'attachments' => null,
            ],
        ];

        $result = $this->riskService->formatTraceTimeline($traceRecords);

        $this->assertEquals([], $result[0]['attachments']);
    }

    public function testStageNamesComplete(): void
    {
        $stageNames = [
            'plant', 'harvest', 'process', 'inspect', 'store', 'transport', 'sale'
        ];

        foreach ($stageNames as $stage) {
            $result = $this->riskService->getStageName($stage);
            $this->assertNotEmpty($result);
            $this->assertNotEquals($stage, $result);
        }
    }
}