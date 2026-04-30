<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name' => '中药材溯源科技有限公司',
                'slug' => 'tcm-trace',
                'license_no' => 'TCM-2024-0001',
                'contact_name' => '张经理',
                'contact_phone' => '13800138000',
                'address' => '北京市海淀区中关村科技园',
                'logo' => '',
                'plan' => 'pro',
                'status' => 1,
                'max_products' => 100,
                'max_qrcodes' => 10000,
                'max_users' => 50,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => '同仁堂中药材有限公司',
                'slug' => 'tongrentang',
                'license_no' => 'TRT-2024-0001',
                'contact_name' => '李总',
                'contact_phone' => '13900139000',
                'address' => '上海市浦东新区张江科技园',
                'logo' => '',
                'plan' => 'enterprise',
                'status' => 1,
                'max_products' => 500,
                'max_qrcodes' => 100000,
                'max_users' => 200,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => '云南白药溯源中心',
                'slug' => 'yunnanbaiyao',
                'license_no' => 'YNBY-2024-0001',
                'contact_name' => '王主任',
                'contact_phone' => '13700137000',
                'address' => '云南省昆明市高新技术开发区',
                'logo' => '',
                'plan' => 'basic',
                'status' => 1,
                'max_products' => 50,
                'max_qrcodes' => 5000,
                'max_users' => 20,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($data as $item) {
            $this->db->table('tenants')->insert($item);
        }
    }
}
