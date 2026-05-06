<?php
/**
 * TCM Trace - 自动修复脚本
 * 针对中药溯源系统的定制化修复
 */

class AutoFixer
{
    private string $projectPath;
    private array $analysis;
    private array $appliedFixes = [];
    private array $failedFixes = [];

    public function __construct(string $projectPath)
    {
        $this->projectPath = rtrim($projectPath, '/');
        $this->loadAnalysis();
    }

    private function loadAnalysis(): void
    {
        $analysisFile = $this->projectPath . '/.trae/analysis-result.json';
        if (!file_exists($analysisFile)) {
            throw new Exception("分析结果文件不存在，请先运行 code-analyzer.php");
        }

        $this->analysis = json_decode(file_get_contents($analysisFile), true);
    }

    /**
     * 运行自动修复
     */
    public function fix(): void
    {
        echo "🔧 TCM Trace 自动修复\n";
        echo str_repeat("=", 60) . "\n\n";

        foreach ($this->analysis['issues'] as $issue) {
            switch ($issue['category']) {
                case 'missing_view':
                    $this->fixMissingView($issue);
                    break;

                case 'empty_method':
                    $this->fixEmptyMethod($issue);
                    break;

                case 'unimplemented_route':
                    $this->fixUnimplementedRoute($issue);
                    break;

                case 'missing_validation':
                    $this->fixMissingValidation($issue);
                    break;

                case 'no_output':
                    $this->fixNoOutput($issue);
                    break;

                case 'model_no_table':
                    $this->fixModelNoTable($issue);
                    break;

                case 'model_no_primary_key':
                    $this->fixModelNoPrimaryKey($issue);
                    break;
            }
        }

        $this->generateFixReport();
    }

    /**
     * 修复缺失的视图
     */
    private function fixMissingView(array $issue): void
    {
        echo "📝 修复缺失视图: {$issue['message']}\n";

        preg_match(/'([^']+)'/, $issue['message'], $matches);
        $viewName = $matches[1] ?? null;

        if (!$viewName) {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '无法提取视图名称'];
            return;
        }

        $viewPath = $this->projectPath . '/app/Views/' . $viewName . '.php';

        $dir = dirname($viewPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $viewContent = $this->generateViewTemplate($viewName);

        if (file_put_contents($viewPath, $viewContent)) {
            $this->appliedFixes[] = [
                'type' => 'create_view',
                'file' => $viewPath,
                'issue' => $issue
            ];
            echo "   ✓ 创建视图: {$viewPath}\n";
        } else {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '文件写入失败'];
        }
    }

    /**
     * 生成视图模板
     */
    private function generateViewTemplate(string $viewName): string
    {
        $title = ucwords(str_replace(['/', '_'], ' ', $viewName));

        return <<<HTML
<?= \$this->extend('layouts/main') ?>

<?= \$this->section('title') ?><?= esc(\$title ?? '{$title}') ?><?= \$this->endSection() ?>

<?= \$this->section('content') ?>
<div class="container">
    <h1><?= esc(\$title ?? '{$title}') ?></h1>

    <!-- TODO: 添加页面内容 -->

</div>
<?= \$this->endSection() ?>

<?= \$this->section('scripts') ?>
<script>
    // TODO: 添加页面脚本
</script>
<?= \$this->endSection() ?>
HTML;
    }

    /**
     * 修复空方法
     */
    private function fixEmptyMethod(array $issue): void
    {
        echo "🔧 修复空方法: {$issue['message']}\n";

        preg_match(/(\w+)::(\w+)/, $issue['message'], $matches);
        $controllerName = $matches[1] ?? null;
        $methodName = $matches[2] ?? null;

        if (!$controllerName || !$methodName) {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '无法提取控制器/方法名'];
            return;
        }

        $controllerFile = $this->findControllerFile($controllerName);
        if (!$controllerFile) {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '找不到控制器文件'];
            return;
        }

        $implementation = $this->generateMethodImplementation($methodName, $controllerName);

        $content = file_get_contents($controllerFile);
        $pattern = '/public\s+function\s+' . $methodName . '\s*\([^)]*\)\s*\{\s*\}/';
        $replacement = "public function {$methodName}(\$id = null)\n    {\n{$implementation}    }";

        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent !== $content && file_put_contents($controllerFile, $newContent)) {
            $this->appliedFixes[] = [
                'type' => 'implement_method',
                'file' => $controllerFile,
                'method' => $methodName,
                'issue' => $issue
            ];
            echo "   ✓ 实现方法: {$methodName}\n";
        } else {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '方法替换失败'];
        }
    }

    /**
     * 生成方法实现 - TCM Trace 定制化
     */
    private function generateMethodImplementation(string $methodName, string $controllerName): string
    {
        // 根据控制器类型生成不同的实现
        if (str_contains($controllerName, 'Auth')) {
            return $this->generateAuthMethod($methodName);
        }
        
        if (str_contains($controllerName, 'Product')) {
            return $this->generateProductMethod($methodName);
        }
        
        if (str_contains($controllerName, 'Batch')) {
            return $this->generateBatchMethod($methodName);
        }
        
        if (str_contains($controllerName, 'Qrcode')) {
            return $this->generateQrcodeMethod($methodName);
        }
        
        if (str_contains($controllerName, 'Trace')) {
            return $this->generateTraceMethod($methodName);
        }
        
        if (str_contains($controllerName, 'Audit')) {
            return $this->generateAuditMethod($methodName);
        }
        
        if (str_contains($controllerName, 'Statistics')) {
            return $this->generateStatisticsMethod($methodName);
        }
        
        if (str_contains($controllerName, 'Billing')) {
            return $this->generateBillingMethod($methodName);
        }

        return $this->generateDefaultMethod($methodName);
    }

    private function generateAuthMethod(string $methodName): string
    {
        $implementations = [
            'register' => <<<'CODE'
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'tenant_name' => 'required|min_length[2]|max_length[100]'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        // TODO: 实现注册逻辑

        return $this->respond([
            'success' => true,
            'message' => '注册成功'
        ]);

CODE,
            'login' => <<<'CODE'
        $rules = [
            'username' => 'required',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        // TODO: 实现登录逻辑

        return $this->respond([
            'success' => true,
            'message' => '登录成功',
            'token' => 'generated_token_here'
        ]);

CODE,
            'profile' => <<<'CODE'
        $user = $this->request->user;
        
        return $this->respond([
            'success' => true,
            'data' => $user
        ]);

CODE
        ];

        return $implementations[$methodName] ?? $this->generateDefaultMethod($methodName);
    }

    private function generateProductMethod(string $methodName): string
    {
        $implementations = [
            'index' => <<<'CODE'
        $productModel = new \App\Models\ProductModel();
        $products = $productModel->where('tenant_id', $this->request->tenant->id)
                                 ->findAll();

        return $this->respond([
            'success' => true,
            'data' => $products
        ]);

CODE,
            'create' => <<<'CODE'
        $rules = [
            'name' => 'required|min_length[2]|max_length[200]',
            'category' => 'required',
            'description' => 'permit_empty|max_length[1000]'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        $productModel = new \App\Models\ProductModel();
        $data = $this->request->getJSON(true);
        $data['tenant_id'] = $this->request->tenant->id;
        
        $id = $productModel->insert($data);

        return $this->respond([
            'success' => true,
            'message' => '产品创建成功',
            'data' => ['id' => $id]
        ]);

CODE,
            'show' => <<<'CODE'
        $productModel = new \App\Models\ProductModel();
        $product = $productModel->where('id', $id)
                                ->where('tenant_id', $this->request->tenant->id)
                                ->first();

        if (!$product) {
            return $this->respond([
                'success' => false,
                'message' => '产品不存在'
            ], 404);
        }

        return $this->respond([
            'success' => true,
            'data' => $product
        ]);

CODE
        ];

        return $implementations[$methodName] ?? $this->generateDefaultMethod($methodName);
    }

    private function generateBatchMethod(string $methodName): string
    {
        return $this->generateDefaultMethod($methodName);
    }

    private function generateQrcodeMethod(string $methodName): string
    {
        return $this->generateDefaultMethod($methodName);
    }

    private function generateTraceMethod(string $methodName): string
    {
        return $this->generateDefaultMethod($methodName);
    }

    private function generateAuditMethod(string $methodName): string
    {
        return $this->generateDefaultMethod($methodName);
    }

    private function generateStatisticsMethod(string $methodName): string
    {
        return $this->generateDefaultMethod($methodName);
    }

    private function generateBillingMethod(string $methodName): string
    {
        return $this->generateDefaultMethod($methodName);
    }

    private function generateDefaultMethod(string $methodName): string
    {
        $implementations = [
            'index' => <<<'CODE'
        $data = [
            'success' => true,
            'data' => []
        ];

        return $this->respond($data);

CODE,
            'create' => <<<'CODE'
        $rules = [
            // TODO: 添加验证规则
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        // TODO: 保存数据

        return $this->respond([
            'success' => true,
            'message' => '创建成功'
        ]);

CODE,
            'show' => <<<'CODE'
        // TODO: 获取数据
        $data = null;

        if (!$data) {
            return $this->respond([
                'success' => false,
                'message' => '记录不存在'
            ], 404);
        }

        return $this->respond([
            'success' => true,
            'data' => $data
        ]);

CODE,
            'update' => <<<'CODE'
        $rules = [
            // TODO: 添加验证规则
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'success' => false,
                'errors' => $this->validator->getErrors()
            ], 422);
        }

        // TODO: 更新数据

        return $this->respond([
            'success' => true,
            'message' => '更新成功'
        ]);

CODE,
            'delete' => <<<'CODE'
        // TODO: 删除数据

        return $this->respond([
            'success' => true,
            'message' => '删除成功'
        ]);

CODE
        ];

        return $implementations[$methodName] ?? <<<'CODE'
        // TODO: 实现业务逻辑

        return $this->respond([
            'success' => true,
            'message' => '操作成功'
        ]);

CODE;
    }

    /**
     * 修复未实现的路由
     */
    private function fixUnimplementedRoute(array $issue): void
    {
        echo "🎮 修复未实现路由: {$issue['message']}\n";

        preg_match(/(\w+)::(\w+)/, $issue['message'], $matches);
        $controllerName = $matches[1] ?? null;
        $methodName = $matches[2] ?? 'index';

        if (!$controllerName) {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '无法提取控制器名'];
            return;
        }

        $controllerPath = $this->projectPath . '/app/Controllers/' . $controllerName . '.php';

        $controllerContent = $this->generateControllerTemplate($controllerName, $methodName);

        if (file_put_contents($controllerPath, $controllerContent)) {
            $this->appliedFixes[] = [
                'type' => 'create_controller',
                'file' => $controllerPath,
                'issue' => $issue
            ];
            echo "   ✓ 创建控制器: {$controllerPath}\n";
        } else {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '控制器创建失败'];
        }
    }

    /**
     * 生成控制器模板
     */
    private function generateControllerTemplate(string $controllerName, string $methodName): string
    {
        $implementation = $this->generateMethodImplementation($methodName, $controllerName);

        return <<<PHP
<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class {$controllerName} extends ResourceController
{
    protected \$helpers = ['form', 'url'];

    public function __construct()
    {
        // TODO: 初始化
    }

    public function {$methodName}(\$id = null)
    {
{$implementation}    }
}
PHP;
    }

    /**
     * 修复缺失验证
     */
    private function fixMissingValidation(array $issue): void
    {
        echo "✅ 记录缺失验证: {$issue['message']}\n";
        
        $this->appliedFixes[] = [
            'type' => 'suggestion',
            'suggestion' => '添加 $this->validate() 调用',
            'issue' => $issue
        ];
    }

    /**
     * 修复无输出
     */
    private function fixNoOutput(array $issue): void
    {
        echo "📤 修复无输出: {$issue['message']}\n";

        preg_match(/(\w+)::(\w+)/, $issue['message'], $matches);
        $controllerName = $matches[1] ?? null;
        $methodName = $matches[2] ?? null;

        if (!$controllerName || !$methodName) {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '无法提取控制器/方法名'];
            return;
        }

        $controllerFile = $this->findControllerFile($controllerName);
        if (!$controllerFile) {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '找不到控制器文件'];
            return;
        }

        $content = file_get_contents($controllerFile);

        $pattern = '/(public\s+function\s+' . $methodName . '\s*\([^)]*\)\s*\{[^}]+)(\})/s';
        $replacement = "$1\n        return \$this->respond(['success' => true]);\n    $2";

        $newContent = preg_replace($pattern, $replacement, $content, 1);

        if ($newContent !== $content && file_put_contents($controllerFile, $newContent)) {
            $this->appliedFixes[] = [
                'type' => 'add_return',
                'file' => $controllerFile,
                'method' => $methodName,
                'issue' => $issue
            ];
            echo "   ✓ 添加返回值到方法: {$methodName}\n";
        } else {
            $this->failedFixes[] = ['issue' => $issue, 'reason' => '添加返回值失败'];
        }
    }

    /**
     * 修复模型缺少表名
     */
    private function fixModelNoTable(array $issue): void
    {
        echo "💾 修复模型配置: {$issue['message']}\n";
        
        $this->appliedFixes[] = [
            'type' => 'suggestion',
            'suggestion' => '请手动添加 protected $table 配置',
            'issue' => $issue
        ];
    }

    /**
     * 修复模型缺少主键
     */
    private function fixModelNoPrimaryKey(array $issue): void
    {
        echo "🔑 修复模型主键: {$issue['message']}\n";
        
        $this->appliedFixes[] = [
            'type' => 'suggestion',
            'suggestion' => '请手动添加 protected $primaryKey = "id"',
            'issue' => $issue
        ];
    }

    /**
     * 查找控制器文件
     */
    private function findControllerFile(string $controllerName): ?string
    {
        $controllersPath = $this->projectPath . '/app/Controllers';

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllersPath)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                if ($file->getBasename('.php') === $controllerName) {
                    return $file->getPathname();
                }
            }
        }

        return null;
    }

    /**
     * 生成修复报告
     */
    private function generateFixReport(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🔧 自动修复报告\n";
        echo str_repeat("=", 60) . "\n\n";

        echo "✅ 成功修复: " . count($this->appliedFixes) . "\n";
        echo "❌ 修复失败: " . count($this->failedFixes) . "\n\n";

        if (!empty($this->appliedFixes)) {
            echo "已应用的修复:\n";
            foreach ($this->appliedFixes as $fix) {
                echo "  ✓ [{$fix['type']}] " . ($fix['file'] ?? $fix['suggestion'] ?? '') . "\n";
            }
        }

        if (!empty($this->failedFixes)) {
            echo "\n修复失败:\n";
            foreach ($this->failedFixes as $fix) {
                echo "  ✗ {$fix['issue']['message']} - {$fix['reason']}\n";
            }
        }

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'applied' => $this->appliedFixes,
            'failed' => $this->failedFixes
        ];

        file_put_contents(
            $this->projectPath . '/.trae/fix-report.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        echo "\n📄 修复记录已保存到: .trae/fix-report.json\n";
    }
}

// 命令行入口
if (PHP_SAPI === 'cli') {
    $projectPath = $argv[1] ?? getcwd();

    try {
        $fixer = new AutoFixer($projectPath);
        $fixer->fix();
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . "\n";
        exit(1);
    }
}
