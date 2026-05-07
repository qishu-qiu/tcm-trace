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
        
        $this->assertIsArray($plans);
        $this->assertArrayHasKey('free', $plans);
        $this->assertArrayHasKey('basic', $plans);
        $this->assertArrayHasKey('pro', $plans);
        $this->assertArrayHasKey('enterprise', $plans);
    }

    public function testGetPlanValid(): void
    {
        $plan = $this->billingService->getPlan('basic');
        
        $this->assertNotNull($plan);
        $this->assertEquals('基础版', $plan['name']);
        $this->assertEquals(99, $plan['price']);
        $this->assertEquals(50, $plan['products']);
        $this->assertEquals(2000, $plan['qrcodes']);
        $this->assertEquals(10, $plan['users']);
    }

    public function testGetPlanInvalid(): void
    {
        $plan = $this->billingService->getPlan('nonexistent');
        
        $this->assertNull($plan);
    }

    public function testGetPlanFree(): void
    {
        $plan = $this->billingService->getPlan('free');
        
        $this->assertNotNull($plan);
        $this->assertEquals('免费版', $plan['name']);
        $this->assertEquals(0, $plan['price']);
        $this->assertEquals(10, $plan['products']);
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
        $result = $this->billingService->calculatePrice('free', 'pro');
        
        $this->assertArrayHasKey('currentPlan', $result);
        $this->assertArrayHasKey('newPlan', $result);
        $this->assertArrayHasKey('priceDiff', $result);
        $this->assertArrayHasKey('isUpgrade', $result);
        
        $this->assertEquals('free', $result['currentPlan']);
        $this->assertEquals('pro', $result['newPlan']);
        $this->assertEquals(299, $result['priceDiff']);
        $this->assertTrue($result['isUpgrade']);
    }

    public function testCalculatePriceDowngrade(): void
    {
        $result = $this->billingService->calculatePrice('pro', 'basic');
        
        $this->assertEquals(-200, $result['priceDiff']);
        $this->assertFalse($result['isUpgrade']);
    }

    public function testCalculatePriceSame(): void
    {
        $result = $this->billingService->calculatePrice('basic', 'basic');
        
        $this->assertEquals(0, $result['priceDiff']);
        $this->assertFalse($result['isUpgrade']);
    }

    public function testCalculatePriceInvalidPlan(): void
    {
        $result = $this->billingService->calculatePrice('invalid', 'basic');
        
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('无效的套餐', $result['error']);
    }

    public function testCalculatePriceInvalidNewPlan(): void
    {
        $result = $this->billingService->calculatePrice('basic', 'invalid');
        
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('无效的套餐', $result['error']);
    }

    public function testPlanFeatures(): void
    {
        $freePlan = $this->billingService->getPlan('free');
        $proPlan = $this->billingService->getPlan('pro');
        
        $this->assertIsArray($freePlan['features']);
        $this->assertIsArray($proPlan['features']);
        $this->assertContains('基础溯源', $freePlan['features']);
        $this->assertContains('API接口', $proPlan['features']);
        $this->assertNotContains('专属客服', $freePlan['features']);
    }

    public function testPlanLimits(): void
    {
        $plans = $this->billingService->getPlans();
        
        foreach ($plans as $key => $plan) {
            $this->assertArrayHasKey('products', $plan);
            $this->assertArrayHasKey('qrcodes', $plan);
            $this->assertArrayHasKey('users', $plan);
            $this->assertArrayHasKey('apiLimit', $plan);
        }
    }
}