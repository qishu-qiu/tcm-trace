<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQrcodes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'batch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'qr_serial' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'unique'     => true,
            ],
            'qr_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'qr_image_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'scan_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'first_scan_at' => [
                'type'   => 'DATETIME',
                'null'   => true,
            ],
            'first_scan_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'last_scan_at' => [
                'type'   => 'DATETIME',
                'null'   => true,
            ],
            'is_disabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '0-已生成 1-已打印 2-已激活 3-已失效',
            ],
            'print_batch_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('qr_serial');
        $this->forge->addKey('batch_id');
        $this->forge->createTable('qrcodes');
    }

    public function down()
    {
        $this->forge->dropTable('qrcodes');
    }
}
