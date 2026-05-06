<?php
/**
 * TCM Trace - 自动化测试运行器
 */

class TestRunner
{
    private string $projectPath;
    private array $testResults = [];
    private array $errors = [];

    public function __construct(string $projectPath)
    {
        $this->projectPath = rtrim($projectPath, '/');
    }

    /**
     * 运行所有测试
     */
    public function run(): void
    {
        echo "🧪 TCM Trace 测试运行器\n";
        echo str_repeat("=", 60) . "\n\n";

        $this->testSyntax();
        $this->testAutoload();
        $this->testRoutes();
        $this->testControllers();
        $this->testModels();
        $this->testDatabase();

        $this->generateReport();
    }

    /**
     * 测试 PHP 语法
     */
    private function testSyntax(): void
    {
        echo "🔍 检查 PHP 语法...\n";

        $phpFiles = $this->getPhpFiles();
        $passed = 0;
        $failed = 0;

        foreach ($phpFiles as $file) {
            $output = [];
            $returnCode = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                $this->errors[] = [
                    'type' => 'syntax',
                    'file' => $file,
                    'message' => implode("\n", $output)
                ];
                $failed++;
            } else {
                $passed++;
            }
        }

        $this->testResults['syntax'] = [
            'name' => 'PHP 语法检查',
            'passed' => $passed,
            'failed' => $failed,
            'total' => count($phpFiles)
        ];

        echo "   ✓ 通过: {$passed}, ✗ 失败: {$failed}\n";
    }

    /**
     * 测试自动加载
     */
    private function testAutoload(): void
    {
        echo "📦 检查自动加载...\n";

        $autoloadFile = $this->projectPath . '/vendor/autoload.php';
        if (!file_exists($autoloadFile)) {
            $this->errors[] = [
                'type' => 'autoload',
                'message' => 'vendor/autoload.php 不存在，请运行 composer install'
            ];
            $this->testResults['autoload'] = [
                'name' => '自动加载检查',
                'passed' => 0,
                'failed' => 1,
                'total' => 1
            ];
            echo "   ✗ 自动加载文件不存在\n";
            return;
        }

        try {
            require_once $autoloadFile;
            $this->testResults['autoload'] = [
                'name' => '自动加载检查',
                'passed' => 1,
                'failed' => 0,
                'total' => 1
            ];
            echo "   ✓ 自动加载正常\n";
        } catch (Exception $e) {
            $this->errors[] = [
                'type' => 'autoload',
                'message' => $e->getMessage()
            ];
            $this->testResults['autoload'] = [
                'name' => '自动加载检查',
                'passed' => 0,
                'failed' => 1,
                'total' => 1
            ];
            echo "   ✗ 自动加载失败: " . $e->getMessage() . "\n";
        }
    }

    /**
     * 测试路由
     */
    private function testRoutes(): void
    {
        echo "🛣️  检查路由...\n";

        $routesFile = $this->projectPath . '/app/Config/Routes.php';
        if (!file_exists($routesFile)) {
            $this->errors[] = [
                'type' => 'routes',
                'message' => 'Routes.php 不存在'
            ];
            $this->testResults['routes'] = [
                'name' => '路由检查',
                'passed' => 0,
                'failed' => 1,
                'total' => 1
            ];
            return;
        }

        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($routesFile) . " 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $this->testResults['routes'] = [
                'name' => '路由检查',
                'passed' => 1,
                'failed' => 0,
                'total' => 1
            ];
            echo "   ✓ 路由配置正常\n";
        } else {
            $this->errors[] = [
                'type' => 'routes',
                'file' => $routesFile,
                'message' => implode("\n", $output)
            ];
            $this->testResults['routes'] = [
                'name' => '路由检查',
                'passed' => 0,
                'failed' => 1,
                'total' => 1
            ];
            echo "   ✗ 路由配置有语法错误\n";
        }
    }

    /**
     * 测试控制器
     */
    private function testControllers(): void
    {
        echo "🎮 检查控制器...\n";

        $controllersPath = $this->projectPath . '/app/Controllers';
        if (!is_dir($controllersPath)) {
            $this->testResults['controllers'] = [
                'name' => '控制器检查',
                'passed' => 0,
                'failed' => 0,
                'total' => 0
            ];
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllersPath)
        );

        $passed = 0;
        $failed = 0;

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());

                if (!preg_match('/class\s+\w+/', $content)) {
                    $this->errors[] = [
                        'type' => 'controller',
                        'file' => $file->getPathname(),
                        'message' => '缺少类定义'
                    ];
                    $failed++;
                    continue;
                }

                if (!preg_match('/namespace\s+App/', $content)) {
                    $this->errors[] = [
                        'type' => 'controller',
                        'file' => $file->getPathname(),
                        'message' => '命名空间不正确'
                    ];
                    $failed++;
                    continue;
                }

                $passed++;
            }
        }

        $this->testResults['controllers'] = [
            'name' => '控制器检查',
            'passed' => $passed,
            'failed' => $failed,
            'total' => $passed + $failed
        ];

        echo "   ✓ 通过: {$passed}, ✗ 失败: {$failed}\n";
    }

    /**
     * 测试模型
     */
    private function testModels(): void
    {
        echo "💾 检查模型...\n";

        $modelsPath = $this->projectPath . '/app/Models';
        if (!is_dir($modelsPath)) {
            $this->testResults['models'] = [
                'name' => '模型检查',
                'passed' => 0,
                'failed' => 0,
                'total' => 0
            ];
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modelsPath)
        );

        $passed = 0;
        $failed = 0;

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());

                if (!preg_match('/class\s+\w+\s+extends\s+Model/', $content)) {
                    $this->errors[] = [
                        'type' => 'model',
                        'file' => $file->getPathname(),
                        'message' => '未继承 Model 类'
                    ];
                    $failed++;
                    continue;
                }

                $passed++;
            }
        }

        $this->testResults['models'] = [
            'name' => '模型检查',
            'passed' => $passed,
            'failed' => $failed,
            'total' => $passed + $failed
        ];

        echo "   ✓ 通过: {$passed}, ✗ 失败: {$failed}\n";
    }

    /**
     * 测试数据库配置
     */
    private function testDatabase(): void
    {
        echo "💾 检查数据库配置...\n";

        $dbConfigFile = $this->projectPath . '/app/Config/Database.php';
        if (!file_exists($dbConfigFile)) {
            $this->testResults['database'] = [
                'name' => '数据库配置检查',
                'passed' => 0,
                'failed' => 1,
                'total' => 1
            ];
            $this->errors[] = [
                'type' => 'database',
                'message' => 'Database.php 不存在'
            ];
            echo "   ✗ 数据库配置文件不存在\n";
            return;
        }

        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($dbConfigFile) . " 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $this->testResults['database'] = [
                'name' => '数据库配置检查',
                'passed' => 1,
                'failed' => 0,
                'total' => 1
            ];
            echo "   ✓ 数据库配置正常\n";
        } else {
            $this->errors[] = [
                'type' => 'database',
                'file' => $dbConfigFile,
                'message' => implode("\n", $output)
            ];
            $this->testResults['database'] = [
                'name' => '数据库配置检查',
                'passed' => 0,
                'failed' => 1,
                'total' => 1
            ];
            echo "   ✗ 数据库配置有语法错误\n";
        }
    }

    /**
     * 获取所有 PHP 文件
     */
    private function getPhpFiles(): array
    {
        $files = [];
        $appPath = $this->projectPath . '/app';

        if (!is_dir($appPath)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($appPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 生成测试报告
     */
    private function generateReport(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🧪 测试报告\n";
        echo str_repeat("=", 60) . "\n\n";

        $totalPassed = 0;
        $totalFailed = 0;

        foreach ($this->testResults as $test) {
            $totalPassed += $test['passed'];
            $totalFailed += $test['failed'];

            $status = $test['failed'] === 0 ? '✅' : '⚠️';
            echo "{$status} {$test['name']}\n";
            echo "   通过: {$test['passed']}, 失败: {$test['failed']}, 总计: {$test['total']}\n\n";
        }

        echo str_repeat("-", 60) . "\n";
        echo "总计: 通过 {$totalPassed}, 失败 {$totalFailed}\n";

        if (!empty($this->errors)) {
            echo "\n❌ 发现的错误:\n";
            foreach ($this->errors as $error) {
                echo "\n  [{$error['type']}]\n";
                if (isset($error['file'])) {
                    echo "  文件: {$error['file']}\n";
                }
                echo "  错误: {$error['message']}\n";
            }
        }

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'passed' => $totalPassed,
                'failed' => $totalFailed,
                'total' => $totalPassed + $totalFailed
            ],
            'results' => $this->testResults,
            'errors' => $this->errors
        ];

        $reportFile = $this->projectPath . '/.trae/test-report.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo "\n📄 测试报告已保存: {$reportFile}\n";

        exit($totalFailed > 0 ? 1 : 0);
    }
}

// 命令行入口
if (PHP_SAPI === 'cli') {
    $projectPath = $argv[1] ?? getcwd();

    $runner = new TestRunner($projectPath);
    $runner->run();
}
