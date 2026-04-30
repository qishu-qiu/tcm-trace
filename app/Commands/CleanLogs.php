<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanLogs extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'clean:logs';
    protected $description = '清理过期日志和无效二维码图片';

    protected $db;

    public function run(array $params)
    {
        $this->db = \Config\Database::connect();

        CLI::write('开始清理过期数据...', 'yellow');

        $scanLogsCleaned = $this->cleanScanLogs();
        CLI::write("清理扫码日志: {$scanLogsCleaned} 条", 'green');

        $auditLogsCleaned = $this->cleanAuditLogs();
        CLI::write("清理审计日志: {$auditLogsCleaned} 条", 'green');

        $qrcodeImagesCleaned = $this->cleanQrcodeImages();
        CLI::write("清理二维码图片: {$qrcodeImagesCleaned} 个", 'green');

        $archivedLogs = $this->archiveAuditLogs();
        CLI::write("归档审计日志: {$archivedLogs} 条", 'green');

        CLI::write('清理完成!', 'green');
    }

    protected function cleanScanLogs(): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-90 days'));

        $count = $this->db->table('scan_logs')
            ->where('scan_time <', $cutoffDate)
            ->countAllResults();

        if ($count > 0) {
            $this->db->table('scan_logs')
                ->where('scan_time <', $cutoffDate)
                ->delete();
        }

        return $count;
    }

    protected function cleanAuditLogs(): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-180 days'));

        $count = $this->db->table('audit_logs')
            ->where('occurred_at <', $cutoffDate)
            ->countAllResults();

        if ($count > 0) {
            $this->db->table('audit_logs')
                ->where('occurred_at <', $cutoffDate)
                ->delete();
        }

        return $count;
    }

    protected function cleanQrcodeImages(): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-30 days'));

        $qrcodes = $this->db->table('qrcodes')
            ->where('status', 3)
            ->where('updated_at <', $cutoffDate)
            ->where('qr_image_url IS NOT NULL')
            ->get()
            ->getResultArray();

        $cleaned = 0;
        foreach ($qrcodes as $qr) {
            if (!empty($qr['qr_image_url'])) {
                $filepath = FCPATH . ltrim($qr['qr_image_url'], '/');
                if (file_exists($filepath)) {
                    if (unlink($filepath)) {
                        $cleaned++;
                    }
                }
            }
        }

        return $cleaned;
    }

    protected function archiveAuditLogs(): int
    {
        $archiveDate = date('Y-m-d H:i:s', strtotime('-90 days'));

        $logs = $this->db->table('audit_logs')
            ->where('occurred_at <', $archiveDate)
            ->limit(1000)
            ->get()
            ->getResultArray();

        if (empty($logs)) {
            return 0;
        }

        $this->createArchiveTableIfNotExists();

        $inserted = 0;
        foreach ($logs as $log) {
            $this->db->table('audit_logs_archive')->insert($log);
            $this->db->table('audit_logs')->where('id', $log['id'])->delete();
            $inserted++;
        }

        return $inserted;
    }

    protected function createArchiveTableIfNotExists(): void
    {
        $forge = \Config\Database::forge();

        if (!$this->db->tableExists('audit_logs_archive')) {
            $forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'actor_type'    => ['type' => 'ENUM', 'constraint' => ['user', 'system', 'api_key']],
                'actor_id'      => ['type' => 'VARCHAR', 'constraint' => 50],
                'actor_name'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'action'        => ['type' => 'VARCHAR', 'constraint' => 50],
                'resource_type' => ['type' => 'VARCHAR', 'constraint' => 50],
                'resource_id'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'before_data'   => ['type' => 'JSON', 'null' => true],
                'after_data'    => ['type' => 'JSON', 'null' => true],
                'source_ip'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'user_agent'    => ['type' => 'TEXT', 'null' => true],
                'geo_location'  => ['type' => 'JSON', 'null' => true],
                'occurred_at'   => ['type' => 'DATETIME'],
                'trace_id'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'request_id'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'result'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'success'],
                'error_message' => ['type' => 'TEXT', 'null' => true],
            ]);

            $forge->addKey('id', true);
            $forge->addKey('tenant_id');
            $forge->addKey('occurred_at');
            $forge->createTable('audit_logs_archive');
        }
    }
}
