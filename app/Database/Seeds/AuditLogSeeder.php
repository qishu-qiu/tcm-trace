<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run()
    {
        $actions = ['create', 'update', 'delete', 'login', 'logout'];
        $resourceTypes = ['product', 'batch', 'qrcode', 'user', 'tenant', 'trace_record'];
        $resourceNames = ['产品', '批次', '二维码', '用户', '租户', '溯源记录'];

        $data = [];

        for ($i = 0; $i < 50; $i++) {
            $index = array_rand($actions);
            $resourceIndex = array_rand($resourceTypes);
            $tenantId = mt_rand(1, 3);

            $data[] = [
                'tenant_id' => $tenantId,
                'actor_type' => 'user',
                'actor_id' => (string)mt_rand(1, 3),
                'actor_name' => '系统管理员',
                'action' => $actions[$index],
                'resource_type' => $resourceTypes[$resourceIndex],
                'resource_id' => (string)mt_rand(1, 20),
                'before_data' => json_encode(['old_value' => '测试数据', 'time' => date('Y-m-d H:i:s', strtotime('-1 hour'))]),
                'after_data' => json_encode(['new_value' => '更新后数据', 'time' => date('Y-m-d H:i:s')]),
                'source_ip' => '192.168.1.' . mt_rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'geo_location' => json_encode(['city' => 'Beijing', 'province' => 'Beijing', 'country' => 'China']),
                'occurred_at' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(0, 24 * 30) . ' hours')),
                'trace_id' => substr(md5(uniqid(rand(), true)), 0, 32),
                'request_id' => substr(md5(uniqid(rand(), true)), 0, 32),
                'result' => 'success',
                'error_message' => null
            ];
        }

        foreach ($data as $item) {
            $this->db->table('audit_logs')->insert($item);
        }
    }
}
