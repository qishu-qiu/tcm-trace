<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    protected int $maxRequests = 100;
    protected int $windowSeconds = 60;
    protected int $cleanupInterval = 3600;

    public function before(RequestInterface $request, $arguments = null)
    {
        $this->cleanupExpiredFiles();

        $tenantId = $request->tenantId ?? 0;
        $ip = $request->getIPAddress();
        
        $key = $tenantId > 0 ? "tenant_{$tenantId}" : "ip_" . md5($ip);
        $cacheKey = "rate_limit_{$key}";
        $cacheFile = WRITEPATH . "cache/" . md5($cacheKey) . ".php";

        $currentTime = time();
        $count = 0;

        if (file_exists($cacheFile)) {
            $data = @include $cacheFile;
            if (is_array($data) && isset($data['expires']) && $data['expires'] > $currentTime) {
                $count = $data['count'] ?? 0;
            } else {
                @unlink($cacheFile);
            }
        }

        $count++;

        $cacheData = [
            'count' => $count,
            'expires' => $currentTime + $this->windowSeconds,
        ];

        $cacheContent = "<?php\nreturn " . var_export($cacheData, true) . ";\n";
        @file_put_contents($cacheFile, $cacheContent);

        if ($count > $this->maxRequests) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'code'    => 429,
                    'message' => '请求过于频繁，请稍后再试',
                    'data'    => [
                        'retry_after' => $this->windowSeconds,
                    ],
                ]);
        }

        return null;
    }

    protected function cleanupExpiredFiles(): void
    {
        static $lastCleanup = 0;
        $currentTime = time();

        if ($currentTime - $lastCleanup < $this->cleanupInterval) {
            return;
        }

        $lastCleanup = $currentTime;
        $cacheDir = WRITEPATH . "cache/";

        if (!is_dir($cacheDir)) {
            return;
        }

        $files = glob($cacheDir . "*.php");
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                $data = @include $file;
                if (is_array($data) && isset($data['expires']) && $data['expires'] <= $currentTime) {
                    @unlink($file);
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
