<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TcmTraceSeeder extends Seeder
{
    public function run()
    {
        $this->call('TenantSeeder');
        $this->call('UserSeeder');
        $this->call('ProductSeeder');
        $this->call('BatchSeeder');
        $this->call('TraceRecordSeeder');
        $this->call('QrcodeSeeder');
        $this->call('ScanLogSeeder');
        $this->call('AuditLogSeeder');
    }
}
