<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogs extends Migration
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
            'actor_type' => [
                'type'       => 'ENUM',
                'constraint' => ['user', 'system', 'api_key'],
            ],
            'actor_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'actor_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'resource_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'resource_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'before_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'after_data' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'source_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'geo_location' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'occurred_at' => [
                'type' => 'DATETIME',
            ],
            'trace_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'request_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'result' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'success',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'occurred_at']);
        $this->forge->addKey(['actor_id', 'occurred_at']);
        $this->forge->addKey(['resource_type', 'resource_id']);
        $this->forge->createTable('audit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs');
    }
}
