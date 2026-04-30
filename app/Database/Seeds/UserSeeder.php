<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'tenant_id' => 1,
                'username' => 'admin',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'real_name' => '系统管理员',
                'phone' => '13800138001',
                'email' => 'admin@tcm-trace.com',
                'role' => 'admin',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'username' => 'manager',
                'password_hash' => password_hash('manager123', PASSWORD_DEFAULT),
                'real_name' => '张经理',
                'phone' => '13800138002',
                'email' => 'manager@tcm-trace.com',
                'role' => 'admin',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'username' => 'operator',
                'password_hash' => password_hash('operator123', PASSWORD_DEFAULT),
                'real_name' => '李操作员',
                'phone' => '13800138003',
                'email' => 'operator@tcm-trace.com',
                'role' => 'staff',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'username' => 'viewer',
                'password_hash' => password_hash('viewer123', PASSWORD_DEFAULT),
                'real_name' => '王查看员',
                'phone' => '13800138004',
                'email' => 'viewer@tcm-trace.com',
                'role' => 'viewer',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 2,
                'username' => 'trt_admin',
                'password_hash' => password_hash('trt123', PASSWORD_DEFAULT),
                'real_name' => '同仁堂管理员',
                'phone' => '13900139001',
                'email' => 'admin@tongrentang.com',
                'role' => 'admin',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 3,
                'username' => 'ynby_admin',
                'password_hash' => password_hash('ynby123', PASSWORD_DEFAULT),
                'real_name' => '云南白药管理员',
                'phone' => '13700137001',
                'email' => 'admin@yunnanbaiyao.com',
                'role' => 'admin',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($data as $item) {
            $this->db->table('users')->insert($item);
        }
    }
}
