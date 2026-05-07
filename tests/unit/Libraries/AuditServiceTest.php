<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\AuditService;

final class AuditServiceTest extends CIUnitTestCase
{
    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AuditService();
    }

    public function testGetTraceId(): void
    {
        $traceId = $this->auditService->getTraceId();
        
        $this->assertNotNull($traceId);
        $this->assertIsString($traceId);
        $this->assertStringStartsWith('trace_', $traceId);
        $this->assertNotEmpty($traceId);
    }

    public function testGetTraceIdUnique(): void
    {
        $service1 = new AuditService();
        $service2 = new AuditService();
        
        $traceId1 = $service1->getTraceId();
        $traceId2 = $service2->getTraceId();
        
        $this->assertNotEquals($traceId1, $traceId2);
    }

    public function testLogMethodExists(): void
    {
        $this->assertTrue(method_exists($this->auditService, 'log'));
    }

    public function testLogErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->auditService, 'logError'));
    }

    public function testLogLoginMethodExists(): void
    {
        $this->assertTrue(method_exists($this->auditService, 'logLogin'));
    }

    public function testLogLogoutMethodExists(): void
    {
        $this->assertTrue(method_exists($this->auditService, 'logLogout'));
    }

    public function testLogCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->auditService, 'logCreate'));
    }

    public function testLogUpdateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->auditService, 'logUpdate'));
    }

    public function testLogDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->auditService, 'logDelete'));
    }

    public function testAuditServiceInstance(): void
    {
        $this->assertInstanceOf(AuditService::class, $this->auditService);
    }

    public function testTraceIdFormat(): void
    {
        $traceId = $this->auditService->getTraceId();
        
        $pattern = '/^trace_[a-f0-9]+\.[a-f0-9]+$/';
        $this->assertMatchesRegularExpression($pattern, $traceId);
    }
}