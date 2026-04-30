<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TraceRecordSeeder extends Seeder
{
    public function run()
    {
        $stages = ['plant', 'harvest', 'process', 'inspect', 'store', 'transport', 'sale'];
        $stageNames = [
            'plant' => '种植环节',
            'harvest' => '采收环节',
            'process' => '加工环节',
            'inspect' => '检测环节',
            'store' => '仓储环节',
            'transport' => '运输环节',
            'sale' => '销售环节'
        ];

        $records = [];
        $batchIds = [1, 2, 3, 4, 5, 6, 7];

        foreach ($batchIds as $batchId) {
            $tenantId = 1;
            if ($batchId >= 5 && $batchId <= 6) $tenantId = 2;
            if ($batchId >= 7) $tenantId = 3;

            foreach ($stages as $index => $stage) {
                $date = date('Y-m-d H:i:s', strtotime("-$index days"));
                $records[] = [
                    'tenant_id' => $tenantId,
                    'batch_id' => $batchId,
                    'stage' => $stage,
                    'operator_id' => 2,
                    'operator_name' => '张经理',
                    'operate_time' => $date,
                    'detail' => $stageNames[$stage] . '操作完成，符合质量标准。',
                    'remark' => '正常记录',
                    'latitude' => 39.9042 + (mt_rand(-1000, 1000) / 10000),
                    'longitude' => 116.4074 + (mt_rand(-1000, 1000) / 10000),
                    'location_desc' => '北京市海淀区',
                    'created_at' => $date
                ];
            }
        }

        foreach ($records as $item) {
            $this->db->table('trace_records')->insert($item);
        }
    }
}
