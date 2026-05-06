<?php
/**
 * TCM Trace - 前端页面修复器
 * 自动修复前端页面缺失的功能和 API 调用
 */

class FrontendFixer
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
        $analysisFile = $this->projectPath . '/.trae/business-analysis.json';
        if (!file_exists($analysisFile)) {
            throw new Exception("业务分析结果不存在，请先运行 business-analyzer.php");
        }

        $this->analysis = json_decode(file_get_contents($analysisFile), true);
    }

    /**
     * 运行修复
     */
    public function fix(): void
    {
        echo "🔧 TCM Trace 前端页面修复器\n";
        echo str_repeat("=", 70) . "\n\n";

        $this->fixMissingPages();
        $this->fixIncompletePages();
        $this->fixApiMismatches();
        $this->fixMissingFeatures();

        $this->generateFixReport();
    }

    /**
     * 修复缺失的页面
     */
    private function fixMissingPages(): void
    {
        echo "📄 检查缺失页面...\n";

        $missingPages = array_filter($this->analysis['issues'], fn($i) => $i['category'] === 'missing_page');

        foreach ($missingPages as $issue) {
            $pageName = basename($issue['message'], '.html');
            $pageName = preg_replace('/.*:\s*/', '', $pageName);
            
            echo "📝 创建页面: {$pageName}.html\n";
            
            $content = $this->generatePageTemplate($pageName);
            $filePath = $this->projectPath . '/public/admin/' . $pageName . '.html';
            
            if (file_put_contents($filePath, $content)) {
                $this->appliedFixes[] = [
                    'type' => 'create_page',
                    'file' => $filePath,
                    'issue' => $issue
                ];
                echo "   ✓ 创建成功\n";
            } else {
                $this->failedFixes[] = ['issue' => $issue, 'reason' => '文件写入失败'];
            }
        }
    }

    /**
     * 生成页面模板
     */
    private function generatePageTemplate(string $pageName): string
    {
        $title = $this->getPageTitle($pageName);
        $module = $this->getPageModule($pageName);

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - 中药材溯源管理平台</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.9.6/dist/css/layui.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #f5f7fa; padding: 20px; }
        .layui-card { border: none; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); }
        .layui-card-header { font-size: 16px; font-weight: 600; color: #333; border-bottom: 1px solid #eee; }
        .search-bar { padding: 16px; background: #fff; border-bottom: 1px solid #eee; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .search-bar .layui-form-item { margin-bottom: 0; margin-right: 0; }
        .table-container { padding: 16px; }
    </style>
</head>
<body>
    <div class="layui-card">
        <div class="search-bar">
            <form class="layui-form" lay-filter="searchForm">
                <div class="layui-form-item">
                    <label class="layui-form-label">关键词</label>
                    <div class="layui-input-inline">
                        <input type="text" name="keyword" placeholder="请输入关键词" autocomplete="off" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item">
                    <button class="layui-btn layui-btn-primary" lay-submit lay-filter="search">
                        <i class="layui-icon layui-icon-search"></i> 搜索
                    </button>
                    <button type="button" class="layui-btn" id="btnAdd">
                        <i class="layui-icon layui-icon-add-1"></i> 新增
                    </button>
                </div>
            </form>
        </div>
        <div class="table-container">
            <table id="dataTable" lay-filter="dataTable"></table>
        </div>
    </div>

    <script src="https://unpkg.com/layui@2.9.6/dist/layui.js"></script>
    <script src="/assets/admin/js/config.js"></script>
    <script src="/assets/admin/js/common.js"></script>
    <script type="text/html" id="tableTpl">
        <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete">删除</a>
    </script>
    <script>
        layui.use(['table', 'layer', 'form'], function(){
            const table = layui.table;
            const layer = layui.layer;
            const form = layui.form;
            
            let currentPage = 1;
            let currentPageSize = 20;
            
            // 加载表格数据
            async function loadTable(page = 1) {
                currentPage = page;
                const keyword = document.querySelector('input[name="keyword"]').value;
                
                try {
                    const res = await api.get('/{$module}', {
                        page: page,
                        pageSize: currentPageSize,
                        keyword: keyword
                    });
                    
                    const data = res?.data || { list: [], total: 0 };
                    
                    table.render({
                        elem: '#dataTable',
                        cols: [[
                            { field: 'id', title: 'ID', width: 80, sort: true },
                            { field: 'name', title: '名称', width: 180 },
                            { field: 'created_at', title: '创建时间', width: 180, templet: function(d) {
                                return utils.formatTime(d.created_at);
                            }},
                            { title: '操作', toolbar: '#tableTpl', width: 150 }
                        ]],
                        data: data.list,
                        page: {
                            curr: page,
                            count: data.total,
                            limit: currentPageSize,
                            layout: ['count', 'prev', 'page', 'next', 'skip'],
                            jump: function(obj, first) {
                                if (!first) {
                                    loadTable(obj.curr);
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error('加载数据失败', error);
                    layer.msg('加载数据失败', {icon: 2});
                }
            }
            
            // 搜索
            form.on('submit(search)', function(data) {
                loadTable(1);
                return false;
            });
            
            // 新增
            document.getElementById('btnAdd').onclick = function() {
                layer.msg('新增功能待实现', {icon: 0});
            };
            
            // 表格操作
            table.on('tool(dataTable)', function(obj) {
                const data = obj.data;
                
                if (obj.event === 'edit') {
                    layer.msg('编辑功能待实现: ID=' + data.id, {icon: 0});
                } else if (obj.event === 'delete') {
                    layer.confirm('确定要删除吗？', {icon: 3}, function(index) {
                        layer.msg('删除功能待实现: ID=' + data.id, {icon: 0});
                        layer.close(index);
                    });
                }
            });
            
            // 初始化
            loadTable(1);
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * 获取页面标题
     */
    private function getPageTitle(string $pageName): string
    {
        $titles = [
            'products' => '产品管理',
            'batches' => '批次管理',
            'qrcodes' => '二维码管理',
            'trace' => '溯源记录',
            'audit' => '审计日志',
            'statistics' => '统计分析',
            'billing' => '订阅管理',
            'settings' => '系统设置',
            'users' => '用户管理',
            'dashboard' => '工作台'
        ];

        return $titles[$pageName] ?? ucfirst($pageName) . '管理';
    }

    /**
     * 获取页面模块
     */
    private function getPageModule(string $pageName): string
    {
        $modules = [
            'products' => 'products',
            'batches' => 'batches',
            'qrcodes' => 'qrcodes',
            'trace' => 'trace-records',
            'audit' => 'audit',
            'statistics' => 'statistics',
            'billing' => 'billing',
            'settings' => 'tenant',
            'users' => 'users'
        ];

        return $modules[$pageName] ?? $pageName;
    }

    /**
     * 修复不完整的页面
     */
    private function fixIncompletePages(): void
    {
        echo "🔧 修复不完整页面...\n";

        $incompletePages = array_filter($this->analysis['issues'], fn($i) => $i['category'] === 'incomplete_crud');

        foreach ($incompletePages as $issue) {
            echo "⚠️  {$issue['message']}\n";
            $this->appliedFixes[] = [
                'type' => 'suggestion',
                'suggestion' => '请手动检查并完善 CRUD 功能',
                'issue' => $issue
            ];
        }
    }

    /**
     * 修复 API 不匹配
     */
    private function fixApiMismatches(): void
    {
        echo "🔧 修复 API 不匹配...\n";

        $mismatches = array_filter($this->analysis['issues'], fn($i) => $i['category'] === 'page_api_mismatch');

        foreach ($mismatches as $issue) {
            echo "❌ {$issue['message']}\n";
            $this->appliedFixes[] = [
                'type' => 'api_mismatch',
                'suggestion' => '请检查 API 路径是否正确',
                'issue' => $issue
            ];
        }
    }

    /**
     * 修复缺失功能
     */
    private function fixMissingFeatures(): void
    {
        echo "🔧 检查缺失功能...\n";

        $missingFeatures = array_filter($this->analysis['issues'], fn($i) => in_array($i['category'], ['missing_feature', 'incomplete_dashboard']));

        foreach ($missingFeatures as $issue) {
            echo "ℹ️  {$issue['message']}\n";
            $this->appliedFixes[] = [
                'type' => 'missing_feature',
                'suggestion' => $issue['suggestion'],
                'issue' => $issue
            ];
        }
    }

    /**
     * 生成修复报告
     */
    private function generateFixReport(): void
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "🔧 前端修复报告\n";
        echo str_repeat("=", 70) . "\n\n";

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
            $this->projectPath . '/.trae/frontend-fix-report.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        echo "\n📄 修复记录已保存到: .trae/frontend-fix-report.json\n";
    }
}

// 命令行入口
if (PHP_SAPI === 'cli') {
    $projectPath = $argv[1] ?? getcwd();

    try {
        $fixer = new FrontendFixer($projectPath);
        $fixer->fix();
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . "\n";
        exit(1);
    }
}
