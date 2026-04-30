<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateScanLogs extends Migration
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
            'qr_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'scan_time' => [
                'type'    => 'DATETIME',
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'scan_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'scan_location' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'device_fingerprint' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'is_first_scan' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'risk_level' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '0-正常 1-可疑 2-高风险',
            ],
            'risk_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('qr_id');
        $this->forge->addKey(['tenant_id', 'scan_time']);
        $this->forge->createTable('scan_logs');
    }

    public function down()
    {
        $this->forge->dropTable('scan_logs');
    }
}
