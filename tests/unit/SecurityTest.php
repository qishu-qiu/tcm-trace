<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Filters\AuthFilter;
use App\Libraries\JwtService;
use App\Libraries\QrcodeService;

class SecurityTest extends CIUnitTestCase
{
    public function testAuthFilterPathTraversal()
    {
        $filter = new AuthFilter();
        
        $mockRequest = $this->getMockBuilder(\CodeIgniter\HTTP\RequestInterface::class)
            ->getMock();
        
        $mockUri = $this->getMockBuilder(\CodeIgniter\HTTP\URI::class)
            ->getMock();
        $mockUri->method('getPath')->willReturn('/api/auth/login/../../admin/users');
        
        $mockRequest->method('getUri')->willReturn($mockUri);
        
        $result = $filter->before($mockRequest);
        
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testAuthFilterValidPublicRoute()
    {
        $filter = new AuthFilter();
        
        $mockRequest = $this->getMockBuilder(\CodeIgniter\HTTP\RequestInterface::class)
            ->getMock();
        
        $mockUri = $this->getMockBuilder(\CodeIgniter\HTTP\URI::class)
            ->getMock();
        $mockUri->method('getPath')->willReturn('/api/auth/login');
        
        $mockRequest->method('getUri')->willReturn($mockUri);
        
        $result = $filter->before($mockRequest);
        
        $this->assertNull($result);
    }

    public function testJwtServiceMissingSecret()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JWT secret is not configured');
        
        putenv('jwt.secret=');
        new JwtService();
    }

    public function testJwtServiceShortSecret()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JWT secret must be at least 32 characters');
        
        putenv('jwt.secret=short');
        new JwtService();
    }

    public function testQrcodeServiceUpdateScanInfoIsAtomic()
    {
        $service = new QrcodeService();
        
        $db = \Config\Database::connect();
        $db->table('qrcodes')->insert([
            'tenant_id' => 1,
            'batch_id' => 1,
            'qr_serial' => 'TEST-' . uniqid(),
            'qr_url' => 'http://test.com',
            'qr_image_url' => '/uploads/test.png',
            'scan_count' => 0,
            'is_disabled' => 0,
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $qrId = $db->insertID();
        
        $result = $service->updateScanInfo($qrId, '127.0.0.1');
        
        $this->assertArrayHasKey('is_first_scan', $result);
        $this->assertArrayHasKey('scan_count', $result);
        $this->assertTrue($result['is_first_scan']);
        $this->assertEquals(1, $result['scan_count']);
        
        $result2 = $service->updateScanInfo($qrId, '127.0.0.2');
        
        $this->assertFalse($result2['is_first_scan']);
        $this->assertEquals(2, $result2['scan_count']);
        
        $db->table('qrcodes')->where('id', $qrId)->delete();
    }

    public function testQrcodeServiceGenerateBatchWithTransaction()
    {
        $service = new QrcodeService();
        
        $db = \Config\Database::connect();
        
        $db->table('products')->insert([
            'tenant_id' => 1,
            'name' => 'Test Product',
            'alias' => 'TP',
            'origin' => 'Test Origin',
            'specification' => 'Test Spec',
            'quality_grade' => 'A',
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $productId = $db->insertID();
        
        $db->table('batches')->insert([
            'tenant_id' => 1,
            'product_id' => $productId,
            'batch_no' => 'TEST-' . uniqid(),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $batchId = $db->insertID();
        
        try {
            $ids = $service->generateBatchQrcodes(1, $batchId, 5);
            
            $this->assertCount(5, $ids);
            
            foreach ($ids as $id) {
                $db->table('qrcodes')->where('id', $id)->delete();
            }
        } finally {
            $db->table('batches')->where('id', $batchId)->delete();
            $db->table('products')->where('id', $productId)->delete();
        }
    }
}