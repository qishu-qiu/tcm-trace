<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\BillingService;

final class BillingServiceTest extends CIUnitTestCase
{
    protected BillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = new BillingService();
    }

    public function testGetPlans(): void
    {
        $plans = $this->billingService->getPlans();
        
        $this->assertNotNull($plans);
        $this->assertIsArray($plans);
        $this->assertCount(4, $plans);
        $this->assertArrayHasKey('free', $plans);
        $this->assertArrayHasKey('basic', $plans);
        $this->assertArrayHasKey('pro', $plans);
        $this->assertArrayHasKey('enterprise', $plans);
    }

    public function testGetPlan(): void
    {
        $plan = $this->billingService->getPlan('free');
        
        $this->assertNotNull($plan);
        $this->assertEquals('免费版', $plan['name']);
        $this->assertEquals(0, $plan['price']);
        $this->assertEquals(10, $plan['products']);
        $this->assertEquals(200, $plan['qrcodes']);
        $this->assertEquals(3, $plan['users']);
    }

    public function testGetPlanWithInvalidKey(): void
    {
        $plan = $this->billingService->getPlan('invalid');
        
        $this->assertNull($plan);
    }

    public function testGetPlanBasic(): void
    {
        $plan = $this->billingService->getPlan('basic');
        
        $this->assertNotNull($plan);
        $this->assertEquals('基础版', $plan['name']);
        $this->assertEquals(99, $plan['price']);
    }

    public function testGetPlanPro(): void
    {
        $plan = $this->billingService->getPlan('pro');
        
        $this->assertNotNull($plan);
        $this->assertEquals('专业版', $plan['name']);
        $this->assertEquals(299, $plan['price']);
    }

    public function testGetPlanEnterprise(): void
    {
        $plan = $this->billingService->getPlan('enterprise');
        
        $this->assertNotNull($plan);
        $this->assertEquals('企业版', $plan['name']);
        $this->assertEquals(899, $plan['price']);
        $this->assertEquals(-1, $plan['products']);
        $this->assertEquals(-1, $plan['users']);
    }

    public function testCalculatePriceUpgrade(): void
    {
        $result = $this->billingService->calculatePrice('free', 'basic');
        
        $this->assertNotNull($result);
        $this->assertEquals('free', $result['currentPlan']);
        $this->assertEquals('basic', $result['newPlan']);
        $this->assertEquals(0, $result['currentPrice']);
        $this->assertEquals(99, $result['newPrice']);
        $this->assertEquals(99, $result['priceDiff']);
        $this->assertTrue($result['isUpgrade']);
    }

    public function testCalculatePriceDowngrade(): void
    {
        $result = $this->billingService->calculatePrice('pro', 'basic');
        
        $this->assertNotNull($result);
        $this->assertEquals(-200, $result['priceDiff']);
        $this->assertFalse($result['isUpgrade']);
    }

    public function testCalculatePriceSamePlan(): void
    {
        $result = $this->billingService->calculatePrice('basic', 'basic');
        
        $this->assertNotNull($result);
        $this->assertEquals(0, $result['priceDiff']);
        $this->assertFalse($result['isUpgrade']);
    }

    public function testCalculatePriceWithInvalidPlan(): void
    {
        $result = $this->billingService->calculatePrice('invalid', 'basic');
        
        $this->assertNotNull($result);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetCurrentUsageWithInvalidResourceType(): void
    {
        $usage = $this->billingService->getCurrentUsage(1, 'invalid');
        
        $this->assertEquals(0, $usage);
    }
}