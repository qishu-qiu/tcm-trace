<?php

namespace App\Libraries;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QrcodeService
{
    protected $db;
    protected string $qrcodePath;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->qrcodePath = FCPATH . 'uploads/qrcodes/';
    }

    public function generateQrSerial(int $tenantId): string
    {
        $maxAttempts = 10;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $tenantPart = strtoupper(base_convert((string) $tenantId, 10, 36));
            $tenantPart = str_pad($tenantPart, 4, '0', STR_PAD_LEFT);
            
            $randomPart = bin2hex(random_bytes(8));
            
            $timePart = strtoupper(base_convert((string) time(), 10, 36));
            
            $serial = $tenantPart . '-' . $randomPart . '-' . $timePart;

            $exists = $this->db->table('qrcodes')
                ->where('qr_serial', $serial)
                ->countAllResults();

            if ($exists === 0) {
                return $serial;
            }

            $attempts++;
        }

        throw new \RuntimeException('无法生成唯一序列号');
    }

    public function generateQrImage(string $qrUrl, int $tenantId, string $qrSerial, int $size = 300): string
    {
        $options = new QROptions([
            'version'      => -1,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_M,
            'scale'        => (int) ($size / 25),
            'margin'       => 2,
            'imageBase64'  => false,
        ]);

        $qrcode = new QRCode($options);
        $imageData = $qrcode->render($qrUrl);

        $tenantDir = $this->qrcodePath . $tenantId . '/';
        
        if (!is_dir($tenantDir)) {
            mkdir($tenantDir, 0755, true);
        }

        $filename = $qrSerial . '.png';
        $filepath = $tenantDir . $filename;

        file_put_contents($filepath, $imageData);

        return '/uploads/qrcodes/' . $tenantId . '/' . $filename;
    }

    public function generateStyledQrImage(string $qrUrl, int $tenantId, string $qrSerial, ?string $logoPath = null, int $size = 300): string
    {
        $tenantDir = $this->qrcodePath . $tenantId . '/';
        
        if (!is_dir($tenantDir)) {
            mkdir($tenantDir, 0755, true);
        }

        $filename = $qrSerial . '.png';
        $filepath = $tenantDir . $filename;

        $builder = Builder::create()
            ->data($qrUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->writer(new PngWriter());

        if ($logoPath && file_exists($logoPath)) {
            $builder->logoPath($logoPath)
                ->logoResizeToWidth((int) ($size * 0.2))
                ->logoPunchoutBackground(true);
        }

        $result = $builder->build();
        $result->saveToFile($filepath);

        return '/uploads/qrcodes/' . $tenantId . '/' . $filename;
    }

    public function generateBatchQrcodes(int $tenantId, int $batchId, int $quantity): array
    {
        if ($quantity < 1 || $quantity > 100) {
            throw new \InvalidArgumentException('数量必须在1-100之间');
        }

        $batch = $this->db->table('batches')
            ->where('id', $batchId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->getRowArray();

        if (!$batch) {
            throw new \RuntimeException('批次不存在');
        }

        $generatedIds = [];
        $generatedImages = [];

        $this->db->transStart();

        try {
            for ($i = 0; $i < $quantity; $i++) {
                $qrSerial = $this->generateQrSerial($tenantId);
                $qrUrl = base_url('verify/' . $qrSerial);
                $qrImageUrl = $this->generateQrImage($qrUrl, $tenantId, $qrSerial);
                $generatedImages[] = $qrImageUrl;

                $data = [
                    'tenant_id'     => $tenantId,
                    'batch_id'      => $batchId,
                    'qr_serial'     => $qrSerial,
                    'qr_url'        => $qrUrl,
                    'qr_image_url'  => $qrImageUrl,
                    'scan_count'    => 0,
                    'is_disabled'   => 0,
                    'status'        => 0,
                    'created_at'    => date('Y-m-d H:i:s'),
                ];

                $this->db->table('qrcodes')->insert($data);
                $generatedIds[] = $this->db->insertID();

                if ($i % 10 === 0) {
                    usleep(10000);
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                foreach ($generatedImages as $imageUrl) {
                    $filepath = FCPATH . ltrim($imageUrl, '/');
                    @unlink($filepath);
                }
                throw new \RuntimeException('批量生成二维码失败');
            }

            return $generatedIds;

        } catch (\Exception $e) {
            $this->db->transRollback();
            foreach ($generatedImages as $imageUrl) {
                $filepath = FCPATH . ltrim($imageUrl, '/');
                @unlink($filepath);
            }
            throw $e;
        }
    }

    public function getQrcodeBySerial(string $serial): ?array
    {
        return $this->db->table('qrcodes')
            ->where('qr_serial', $serial)
            ->get()
            ->getRowArray();
    }

    public function getQrcodeById(int $id, int $tenantId): ?array
    {
        return $this->db->table('qrcodes')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->get()
            ->getRowArray();
    }

    public function updateScanInfo(int $qrId, string $ip): array
    {
        $now = date('Y-m-d H:i:s');
        
        $result = $this->db->table('qrcodes')
            ->set('scan_count', 'scan_count + 1', false)
            ->set('last_scan_at', $now)
            ->where('id', $qrId)
            ->where('first_scan_at IS NULL')
            ->update([
                'first_scan_at' => $now,
                'first_scan_ip' => $ip,
            ]);

        if ($result > 0) {
            return [
                'is_first_scan' => true,
                'scan_count' => $this->getScanCount($qrId),
                'first_scan_at' => $now,
            ];
        }

        $this->db->table('qrcodes')
            ->set('scan_count', 'scan_count + 1', false)
            ->set('last_scan_at', $now)
            ->where('id', $qrId)
            ->update();

        $qrcode = $this->db->table('qrcodes')
            ->select('scan_count, first_scan_at')
            ->where('id', $qrId)
            ->get()
            ->getRowArray();

        return [
            'is_first_scan' => false,
            'scan_count' => $qrcode['scan_count'] ?? 0,
            'first_scan_at' => $qrcode['first_scan_at'] ?? null,
        ];
    }

    public function getScanCount(int $qrId): int
    {
        $qrcode = $this->db->table('qrcodes')
            ->select('scan_count')
            ->where('id', $qrId)
            ->get()
            ->getRowArray();

        return $qrcode['scan_count'] ?? 0;
    }

    public function disableQrcode(int $id, int $tenantId): bool
    {
        return $this->db->table('qrcodes')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['is_disabled' => 1]);
    }

    public function updateStatus(int $id, int $tenantId, int $status): bool
    {
        return $this->db->table('qrcodes')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['status' => $status]);
    }

    public function countQrcodes(int $tenantId): int
    {
        return $this->db->table('qrcodes')
            ->where('tenant_id', $tenantId)
            ->countAllResults();
    }

    public function generatePrintHtml(array $qrcodes): string
    {
        $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>二维码标签打印</title>
    <style>
        @page {
            size: 60mm 40mm;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "Microsoft YaHei", sans-serif;
            font-size: 10px;
        }
        .label {
            width: 60mm;
            height: 40mm;
            padding: 2mm;
            box-sizing: border-box;
            page-break-after: always;
            display: flex;
            align-items: center;
            border: 1px dashed #ccc;
        }
        .qr-image {
            width: 30mm;
            height: 30mm;
            flex-shrink: 0;
        }
        .qr-image img {
            width: 100%;
            height: 100%;
        }
        .info {
            margin-left: 2mm;
            flex: 1;
            overflow: hidden;
        }
        .info-row {
            margin-bottom: 1mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-name {
            font-size: 12px;
            font-weight: bold;
        }
        .batch-no {
            font-size: 9px;
            color: #666;
        }
        @media print {
            .label {
                border: none;
            }
        }
    </style>
</head>
<body>';

        foreach ($qrcodes as $qr) {
            $html .= '
    <div class="label">
        <div class="qr-image">
            <img src="' . base_url(ltrim($qr['qr_image_url'], '/')) . '" alt="QR Code">
        </div>
        <div class="info">
            <div class="info-row product-name">' . htmlspecialchars($qr['product_name'] ?? '') . '</div>
            <div class="info-row batch-no">批次: ' . htmlspecialchars($qr['batch_no'] ?? '') . '</div>
            <div class="info-row">产地: ' . htmlspecialchars($qr['origin'] ?? '') . '</div>
            <div class="info-row">规格: ' . htmlspecialchars($qr['specification'] ?? '') . '</div>
        </div>
    </div>';
        }

        $html .= '
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';

        return $html;
    }

    public function cleanOldImages(int $days = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $oldQrcodes = $this->db->table('qrcodes')
            ->where('created_at <', $cutoffDate)
            ->where('scan_count', 0)
            ->get()
            ->getResultArray();

        $cleaned = 0;
        foreach ($oldQrcodes as $qr) {
            if (!empty($qr['qr_image_url'])) {
                $filepath = FCPATH . ltrim($qr['qr_image_url'], '/');
                if (file_exists($filepath)) {
                    unlink($filepath);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}
