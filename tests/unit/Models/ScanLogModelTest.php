<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\ScanLogModel;

final class ScanLogModelTest extends CIUnitTestCase
{
    protected ScanLogModel $scanLogModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanLogModel = new ScanLogModel();
    }

    public function testFindWithNullId(): void
    {
        $result = $this->scanLogModel->find(null);
        
        $this->assertNull($result);
    }

    public function testDecodeJsonFieldsWithNull(): void
    {
        $result = $this->scanLogModel->decodeJsonFields(null);
        
        $this->assertNull($result);
    }

    public function testDecodeJsonFieldsWithValidJson(): void
    {
        $record = [
            'scan_location' => json_encode(['city' => 'Shanghai']),
        ];
        
        $result = $this->scanLogModel->decodeJsonFields($record);
        
        $this->assertNotNull($result);
        $this->assertEquals(['city' => 'Shanghai'], $result['scan_location']);
    }

    public function testDecodeJsonFieldsWithInvalidJson(): void
    {
        $record = [
            'scan_location' => 'invalid json {',
        ];
        
        $result = $this->scanLogModel->decodeJsonFields($record);
        
        $this->assertNotNull($result);
        $this->assertEquals('invalid json {', $result['scan_location']);
    }

    public function testDecodeJsonFieldsWithEmptyString(): void
    {
        $record = [
            'scan_location' => '',
        ];
        
        $result = $this->scanLogModel->decodeJsonFields($record);
        
        $this->assertNotNull($result);
        $this->assertEquals('', $result['scan_location']);
    }
}