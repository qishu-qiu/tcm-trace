<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ScanLogSeeder extends Seeder
{
    public function run()
    {
        $data = [];

        $qrIds = range(1, 350);

        foreach ($qrIds as $qrId) {
            $tenantId = 1;
            if ($qrId >= 181 && $qrId <= 290) $tenantId = 2;
            if ($qrId >= 291) $tenantId = 3;

            if (mt_rand(0, 100) < 40) {
                $scanCount = mt_rand(1, 5);
                for ($j = 0; $j < $scanCount; $j++) {
                    $scanDate = date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 60) . ' days'));
                    $data[] = [
                        'qr_id' => $qrId,
                        'tenant_id' => $tenantId,
                        'scan_time' => $scanDate,
                        'scan_ip' => '192.168.' . mt_rand(1, 254) . '.' . mt_rand(1, 254),
                        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
                        'device_fingerprint' => substr(md5(uniqid()), 0, 32),
                        'is_first_scan' => $j === 0 ? 1 : 0,
                        'risk_level' => mt_rand(0, 100) < 95 ? 0 : (mt_rand(0, 100) < 90 ? 1 : 2),
                        'risk_reason' => mt_rand(0, 100) < 95 ? null : (mt_rand(0, 100) < 80 ? '重复扫码频率较高' : '设备指纹异常')
                    ];
                }
            }
        }

        foreach ($data as $item) {
            $this->db->table('scan_logs')->insert($item);
        }
    }
}
