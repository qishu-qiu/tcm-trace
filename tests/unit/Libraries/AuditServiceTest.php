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
        $this->assertNotEmpty($traceId);
        $this->assertStringStartsWith('trace_', $traceId);
    }

    public function testGetTraceIdIsUnique(): void
    {
        $service1 = new AuditService();
        $service2 = new AuditService();
        
        $traceId1 = $service1->getTraceId();
        $traceId2 = $service2->getTraceId();
        
        $this->assertNotEquals($traceId1, $traceId2);
    }
}