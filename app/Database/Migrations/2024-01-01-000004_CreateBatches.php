<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBatches extends Migration
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
            'product_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'batch_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'farm_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'farm_address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'farm_area' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'plant_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'harvest_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'harvest_qty' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'grow_years' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'process_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'process_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'process_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'inspect_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'inspect_result' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'inspect_report' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'inspect_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'package_spec' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'package_qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'warehouse_info' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'comment'    => '0-草稿 1-在库 2-已售出',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'product_id']);
        $this->forge->addUniqueKey(['tenant_id', 'batch_no'], 'uk_tenant_batch');
        $this->forge->createTable('batches');
    }

    public function down()
    {
        $this->forge->dropTable('batches');
    }
}
