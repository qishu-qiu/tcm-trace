<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class QrcodeSeeder extends Seeder
{
    public function run()
    {
        $data = [];
        $qrcodeCount = 0;

        $batchData = [
            ['batch_id' => 1, 'qty' => 100],
            ['batch_id' => 2, 'qty' => 80],
            ['batch_id' => 3, 'qty' => 120],
            ['batch_id' => 4, 'qty' => 90],
            ['batch_id' => 5, 'qty' => 60],
            ['batch_id' => 6, 'qty' => 50],
            ['batch_id' => 7, 'qty' => 150]
        ];

        foreach ($batchData as $bd) {
            $batchId = $bd['batch_id'];
            $tenantId = 1;
            if ($batchId >= 5 && $batchId <= 6) $tenantId = 2;
            if ($batchId >= 7) $tenantId = 3;

            for ($i = 0; $i < $bd['qty']; $i++) {
                $qrcodeCount++;
                $qrSerial = strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
                $isFirstScan = mt_rand(0, 100) < 40;

                $data[] = [
                    'tenant_id' => $tenantId,
                    'batch_id' => $batchId,
                    'qr_serial' => $qrSerial,
                    'qr_url' => 'http://localhost:8000/scan/' . $qrSerial,
                    'qr_image_url' => '',
                    'scan_count' => $isFirstScan ? mt_rand(1, 10) : 0,
                    'first_scan_at' => $isFirstScan ? date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 30) . ' days')) : null,
                    'first_scan_ip' => $isFirstScan ? '192.168.1.' . mt_rand(1, 255) : null,
                    'last_scan_at' => $isFirstScan ? date('Y-m-d H:i:s', strtotime('-' . mt_rand(0, 10) . ' days')) : null,
                    'is_disabled' => 0,
                    'status' => 1,
                    'print_batch_no' => 'PB' . $batchId . '-' . date('Ymd'),
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        foreach ($data as $item) {
            $this->db->table('qrcodes')->insert($item);
        }
    }
}
