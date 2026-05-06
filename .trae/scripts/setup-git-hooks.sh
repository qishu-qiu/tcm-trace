#!/bin/bash
# TCM Trace - Git 预提交钩子安装脚本

set -e

PROJECT_PATH="${1:-$(pwd)}"
TRAEDIR="$PROJECT_PATH/.trae"
HOOK_FILE="$PROJECT_PATH/.git/hooks/pre-commit"

echo "🚀 安装 TCM Trace 自动化代码检查钩子..."
echo "项目路径: $PROJECT_PATH"

# 检查是否是 git 仓库
if [ ! -d "$PROJECT_PATH/.git" ]; then
    echo "❌ 错误: 当前目录不是 Git 仓库"
    echo "请先运行: git init"
    exit 1
fi

# 创建 .trae 目录结构
mkdir -p "$TRAEDIR/scripts"
mkdir -p "$TRAEDIR/prompts"
mkdir -p "$TRAEDIR/reports"

# 复制脚本文件（如果它们不存在）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -f "$SCRIPT_DIR/code-analyzer.php" ]; then
    cp "$SCRIPT_DIR/code-analyzer.php" "$TRAEDIR/scripts/"
    echo "✓ 复制 code-analyzer.php"
fi

if [ -f "$SCRIPT_DIR/auto-fixer.php" ]; then
    cp "$SCRIPT_DIR/auto-fixer.php" "$TRAEDIR/scripts/"
    echo "✓ 复制 auto-fixer.php"
fi

if [ -f "$SCRIPT_DIR/test-runner.php" ]; then
    cp "$SCRIPT_DIR/test-runner.php" "$TRAEDIR/scripts/"
    echo "✓ 复制 test-runner.php"
fi

# 创建预提交钩子
cat > "$HOOK_FILE" << 'HOOK'
#!/bin/bash
# TCM Trace Git 预提交钩子 - 自动代码检查

set -e

echo "🔍 TCM Trace 预提交代码检查..."

PROJECT_PATH="$(git rev-parse --show-toplevel)"
TRAEDIR="$PROJECT_PATH/.trae"
PHP_BIN="$(which php)"

# 检查 PHP 是否可用
if [ -z "$PHP_BIN" ]; then
    echo "⚠️  警告: 未找到 PHP，跳过代码检查"
    exit 0
fi

# 运行代码分析
echo "📋 正在分析代码..."
if "$PHP_BIN" "$TRAEDIR/scripts/code-analyzer.php" "$PROJECT_PATH"; then
    echo "✅ 代码检查通过"
    exit 0
else
    echo ""
    echo "❌ 代码检查发现问题"
    echo ""
    
    # 读取分析结果
    if [ -f "$TRAEDIR/analysis-result.json" ]; then
        echo "📊 分析报告已生成: $TRAEDIR/analysis-result.json"
        echo ""
        
        # 统计问题数量
        ERRORS=$(grep -o '"type": "error"' "$TRAEDIR/analysis-result.json" | wc -l)
        WARNINGS=$(grep -o '"type": "warning"' "$TRAEDIR/analysis-result.json" | wc -l)
        
        echo "问题统计:"
        echo "  ❌ 错误: $ERRORS"
        echo "  ⚠️  警告: $WARNINGS"
        echo ""
    fi
    
    # 询问是否继续提交
    echo "选项:"
    echo "  1. 修复问题后重新提交 (推荐)"
    echo "  2. 强制提交 (跳过检查)"
    echo ""
    
    # 如果有错误，默认阻止提交
    if [ "$ERRORS" -gt 0 ]; then
        echo "⚠️  检测到错误，建议先修复问题"
        echo ""
        echo "自动修复命令:"
        echo "  php $TRAEDIR/scripts/auto-fixer.php $PROJECT_PATH"
        echo ""
        echo "或者使用 Trae AI 修复:"
        echo "  1. 在 Trae 中打开项目"
        echo "  2. 使用提示词模板进行修复"
        echo ""
        
        # 提供强制提交的选项
        read -p "是否强制提交? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo "⚠️  强制提交，跳过代码检查"
            exit 0
        else
            echo "❌ 提交已取消，请修复问题后重试"
            exit 1
        fi
    else
        # 只有警告，允许提交但提醒
        echo "ℹ️  只有警告，允许提交"
        exit 0
    fi
fi
HOOK

# 设置执行权限
chmod +x "$HOOK_FILE"
echo "✓ 创建 pre-commit 钩子"

# 创建配置文件
cat > "$TRAEDIR/config.json" << 'CONFIG'
{
    "version": "1.0.0",
    "project": "TCM Trace",
    "description": "中药溯源系统",
    "php_version": "8.2",
    "framework": "codeigniter4",
    "checks": {
        "syntax": true,
        "routes": true,
        "controllers": true,
        "views": true,
        "models": true,
        "filters": true,
        "services": true,
        "duplicates": true,
        "security": true,
        "api_consistency": true,
        "business_logic": true
    },
    "auto_fix": {
        "enabled": false,
        "create_missing_views": true,
        "implement_empty_methods": false,
        "add_validation": false
    },
    "ignore": [
        "app/ThirdParty/**",
        "app/Views/errors/**",
        "system/**",
        "vendor/**",
        "writable/**"
    ]
}
CONFIG

echo "✓ 创建配置文件"

# 创建 README
cat > "$TRAEDIR/README.md" << 'README'
# TCM Trace 自动化代码检查工具

## 功能

- 🔍 自动检测代码问题
- 🐛 识别接口-页面对应关系
- 📝 自动补全缺失的视图和方法
- 🛡️ 检查安全性和最佳实践
- 🔄 Git 预提交钩子集成
- 📊 中药溯源业务逻辑检查

## 使用方法

### 1. 手动运行代码分析

```bash
php .trae/scripts/code-analyzer.php
```

### 2. 自动修复问题

```bash
php .trae/scripts/auto-fixer.php
```

### 3. 运行测试

```bash
php .trae/scripts/test-runner.php
```

### 4. Git 提交时自动检查

提交代码时会自动运行检查：

```bash
git add .
git commit -m "your message"
# 自动触发代码检查
```

### 5. 跳过检查（紧急情况下）

```bash
git commit -m "your message" --no-verify
```

## 配置文件

编辑 `.trae/config.json` 自定义检查规则：

```json
{
    "checks": {
        "syntax": true,
        "routes": true,
        "controllers": true,
        "models": true,
        "security": true,
        "api_consistency": true
    }
}
```

## 报告文件

- `analysis-result.json` - 分析结果（JSON 格式）
- `fix-report.json` - 修复报告
- `test-report.json` - 测试报告
- `reports/` - 历史报告目录

## 问题反馈

如遇到问题，请检查：
1. PHP 版本 >= 8.2
2. CodeIgniter 4 项目结构完整
3. 文件权限正确

## TCM Trace 特定检查

- ✅ 中药产品模型完整性
- ✅ 溯源记录逻辑检查
- ✅ 二维码生成检查
- ✅ 审计日志完整性
- ✅ 租户权限验证
- ✅ 统计报表逻辑
README

echo "✓ 创建说明文档"

echo ""
echo "✅ 安装完成！"
echo ""
echo "使用方法:"
echo "  1. 手动分析: php .trae/scripts/code-analyzer.php"
echo "  2. 自动修复: php .trae/scripts/auto-fixer.php"
echo "  3. 运行测试: php .trae/scripts/test-runner.php"
echo "  4. Git 提交时将自动运行检查"
echo ""
echo "配置文件: .trae/config.json"
