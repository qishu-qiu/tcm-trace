# 中药材溯源SaaS平台部署指南

## 环境要求

- **操作系统**: CentOS 7/8/9
- **Web服务器**: Apache 2.4+ 或 Nginx 1.20+
- **PHP版本**: 8.2
- **数据库**: MySQL 5.7+ 或 MariaDB 10.2+
- **PHP扩展**: pdo_mysql, json, mbstring, gd, fileinfo, openssl

---

## MySQL 5.7 兼容性说明

本系统完全兼容 MySQL 5.7，主要兼容性说明：

| 功能 | MySQL 5.7 版本要求 | 说明 |
|------|-------------------|------|
| utf8mb4 字符集 | 5.5.3+ | ✅ 完全支持 |
| JSON 类型 | 5.7.8+ | ✅ 用于溯源记录详情 |
| ENUM 类型 | 完全支持 | ✅ 用于状态/角色等字段 |
| DECIMAL 类型 | 完全支持 | ✅ 用于数量/面积等字段 |
| CURRENT_TIMESTAMP | 完全支持 | ✅ 用于时间戳字段 |

**注意事项**：
- 如果使用 `utf8mb4` 字符集，建议 MySQL 5.7.10+ 版本
- 如遇 JSON 类型错误，可将 JSON 字段改为 TEXT 类型
- 已提供兼容SQL脚本：`database/mysql57_schema.sql`

---

## 部署步骤

### 步骤1：上传代码到虚拟主机

将项目代码上传到虚拟主机的web目录，例如：
```
/var/www/tcm-trace/
```

**目录结构说明：**
- `public/` - 网站根目录，配置虚拟主机指向此目录
- `app/` - 应用核心代码
- `vendor/` - Composer依赖
- `writable/` - 可写目录（日志、缓存、上传文件）

---

### 步骤2：设置PHP版本为8.2

在虚拟主机控制面板中切换PHP版本为8.2：

**cPanel用户：**
1. 登录cPanel面板
2. 找到"选择PHP版本"或"MultiPHP Manager"
3. 选择PHP 8.2版本
4. 点击"应用"保存

**DirectAdmin用户：**
1. 登录DirectAdmin面板
2. 找到"PHP版本选择"
3. 选择PHP 8.2
4. 保存设置

---

### 步骤3：确保PHP扩展已启用

确保以下PHP扩展已启用：

| 扩展名称 | 说明 |
|---------|------|
| pdo_mysql | MySQL数据库驱动 |
| json | JSON处理 |
| mbstring | 多字节字符串处理 |
| gd | 图片处理（二维码生成） |
| fileinfo | 文件类型检测 |
| openssl | 加密功能 |

**在cPanel中启用扩展：**
1. 进入"选择PHP版本"
2. 点击"扩展"选项卡
3. 勾选所需扩展
4. 保存设置

---

### 步骤4：创建MySQL数据库并导入迁移

1. **创建数据库和用户**：
   - 数据库名：`tcm_trace`
   - 用户名：`your_db_username`
   - 字符集：`utf8mb4`

2. **方式一：使用迁移命令**（推荐 MySQL 5.7.8+）：
```bash
cd /var/www/tcm-trace
php spark migrate
```

3. **方式二：手动执行SQL**（如果JSON类型不支持）：
   - 直接导入 `database/mysql57_schema.sql` 文件
   - 如果MySQL版本低于5.7.8，需要将JSON字段改为TEXT类型

---

### 步骤5：配置 .env 文件

复制并编辑 `.env` 文件，配置数据库连接和JWT密钥：

```env
CI_ENVIRONMENT = production

app.baseURL = 'https://your-domain.com/'
app.indexPage = ''

database.default.hostname = localhost
database.default.database = tcm_trace
database.default.username = your_db_username
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.port = 3306

jwt.secret = '生成一个随机的长密钥'
jwt.expiresIn = 604800

uploads.path = 'uploads/'
qrcode.path = 'uploads/qrcodes/'
```

**生成JWT密钥：**
```bash
openssl rand -hex 32
```

---

### 步骤6：设置目录权限

设置 `writable/` 目录可写权限：

```bash
cd /var/www/tcm-trace
chmod -R 755 writable/
chown -R www-data:www-data writable/
```

**目录权限说明：**
- `writable/logs/` - 日志目录
- `writable/cache/` - 缓存目录
- `writable/session/` - 会话目录
- `public/uploads/` - 上传文件目录

---

### 步骤7：测试登录

访问管理后台登录页面：
```
https://your-domain.com/admin/login.html
```

默认管理员账号（首次部署后需创建）：
- 用户名：admin
- 密码：需通过数据库初始化或注册接口创建

---

### 步骤8：配置Cron任务

设置日志清理Cron任务，每天凌晨执行：

```bash
0 0 * * * /usr/bin/php /var/www/tcm-trace/spark clean:logs >> /var/log/tcm-trace-cron.log 2>&1
```

**添加Cron任务：**
```bash
crontab -e
```
然后添加上述行

---

### 步骤9：配置SSL证书（HTTPS）

#### 使用Let's Encrypt免费证书：

```bash
# 安装Certbot
yum install certbot python3-certbot-nginx -y

# 获取证书（Nginx）
certbot --nginx -d your-domain.com

# 自动续期测试
certbot renew --dry-run
```

#### 手动配置SSL：

将SSL证书文件上传到服务器：
- 证书文件：`/etc/ssl/certs/your-domain.crt`
- 私钥文件：`/etc/ssl/private/your-domain.key`

---

## 安全加固

### 1. 禁止PHP错误输出

在 `.env` 中确保：
```env
CI_ENVIRONMENT = production
```

### 2. 保护敏感文件

`.htaccess` 和 `nginx.conf` 已配置禁止访问：
- `.env` - 环境配置文件
- `writable/` - 可写目录
- `app/` - 应用代码
- `vendor/` - 依赖目录

### 3. 上传文件类型限制

系统仅允许上传以下文件类型：
- jpg, jpeg, png, gif, pdf

上传文件大小限制：5MB

### 4. SQL注入防护

- 使用CodeIgniter Query Builder参数绑定
- 禁止直接拼接SQL语句

### 5. XSS防护

- 输出时使用 `htmlspecialchars()` 转义
- 使用CodeIgniter的安全类处理用户输入

### 6. CSRF保护

CodeIgniter内置CSRF保护已启用：
```env
security.csrfProtection = 'cookie'
security.tokenRandomize = true
```

---

## 配置文件说明

### Apache配置 (.htaccess)

路径：`public/.htaccess`

包含以下配置：
- URL重写规则（所有请求指向index.php）
- 静态资源直接访问（不经过PHP）
- 二维码图片缓存头（Cache-Control: max-age=604800）
- Gzip压缩（mod_deflate）
- 安全头（X-Frame-Options, X-Content-Type-Options）
- 禁止访问敏感文件

### Nginx配置

路径：`nginx.conf.example`

包含以下配置：
- HTTP重定向到HTTPS
- 反向代理到PHP-FPM
- 静态资源缓存
- Gzip压缩
- SSL配置

---

## 故障排除

### 常见问题

1. **页面显示空白**
   - 检查PHP版本是否正确
   - 检查 `writable/logs/` 目录权限
   - 查看PHP错误日志

2. **数据库连接失败**
   - 检查 `.env` 数据库配置
   - 确认数据库用户权限
   - 检查数据库服务是否运行

3. **二维码无法生成**
   - 确认GD扩展已启用
   - 检查 `public/uploads/qrcodes/` 目录权限

4. **404错误**
   - 确认URL重写规则已配置
   - 检查Apache `mod_rewrite` 是否已启用

---

## 技术支持

如有部署问题，请联系技术支持团队。