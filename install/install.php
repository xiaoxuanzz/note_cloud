<?php
/**
 * Note Cloud 安装程序 v2.6（修复版）
 * 修复清单：
 * 1. 修复 heredoc 结束标记缩进问题（第534行）
 * 2. 修复数组元素间缺少逗号问题
 * 3. 密码明文存储（不推荐生产环境）
 */

// ✅ 核心修复：必须在第一行开启输出缓冲
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ==================== 核心配置 ====================
define('BASE_PATH', dirname(__DIR__));
define('INSTALL_PATH', __DIR__);
define('CONFIG_DIR', BASE_PATH . '/includes/');
define('CONFIG_PATH', CONFIG_DIR . 'config.php');
define('LOCK_PATH', BASE_PATH . '/install.lock');

// 检查是否已安装
if (file_exists(LOCK_PATH)) {
    exit('
    <div style="text-align:center; padding:50px; font-family:sans-serif; background:#f8f9fa; border-radius:10px; max-width:600px; margin:50px auto;">
        <h2>⚠️ 系统已安装</h2>
        <p>如需重新安装，请删除网站根目录下的 <code>install.lock</code> 文件</p>
        <a href="../login.php" class="btn btn-primary">返回首页</a>
    </div>');
}

$step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1, 'max_range' => 4]]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note Cloud - 安装向导</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .container { max-width: 850px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .step-header { background: linear-gradient(45deg, #007bff, #0056b3); color: white; padding: 30px; text-align: center; }
        .step-header h2 { margin: 0; font-size: 28px; font-weight: bold; }
        .step-header p { margin: 10px 0 0; opacity: 0.9; }
        .step-indicator { display: flex; justify-content: space-between; padding: 20px 30px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
        .step-item { flex: 1; text-align: center; padding: 10px; margin: 0 5px; background: #e9ecef; border-radius: 5px; font-weight: 500; }
        .step-item.active { background: #007bff; color: white; }
        .step-content { padding: 40px; }
        .form-section { background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 30px; border-left: 5px solid #007bff; }
        .form-title { color: #007bff; font-weight: bold; font-size: 18px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; margin-bottom: 8px; color: #495057; }
        .form-control { padding: 12px; border-radius: 8px; border: 2px solid #dee2e6; transition: all 0.3s; }
        .form-control:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); outline: none; }
        .btn-primary, .btn-success, .btn-info { padding: 12px 30px; font-size: 16px; font-weight: 600; border-radius: 8px; transition: all 0.3s; }
        .btn-primary:hover, .btn-success:hover, .btn-info:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-block { width: 100%; padding: 15px; font-size: 18px; }
        .alert { border-radius: 8px; border: none; }
        .validation-message { font-size: 13px; margin-top: 5px; padding: 8px; border-radius: 5px; display: none; }
        .validation-success { color: #155724; background: #d4edda; display: block !important; }
        .validation-error { color: #721c24; background: #f8d7da; display: block !important; }
        .install-progress { padding: 20px 0; }
        .log-container { min-height: 50px; max-height: 400px; overflow-y: auto; }
        .log-container .alert { margin: 8px 0; padding: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="step-indicator">
            <div class="step-item <?= ($step >= 1 ? 'active' : '') ?>">1. 环境检测</div>
            <div class="step-item <?= ($step >= 2 ? 'active' : '') ?>">2. 配置信息</div>
            <div class="step-item <?= ($step >= 3 ? 'active' : '') ?>">3. 数据库安装</div>
            <div class="step-item <?= ($step >= 4 ? 'active' : '') ?>">4. 安装完成</div>
        </div>

        <div class="step-content">
            <?php
            // ==================== 步骤1：环境检测 ====================
            if ($step == 1):
                $check = [];
                $install = true;

                if (version_compare(PHP_VERSION, '7.1.0', '<')) {
                    $check['php'] = ['status' => 'danger', 'msg' => 'PHP版本过低（' . PHP_VERSION . '），需要7.1+'];
                    $install = false;
                } else {
                    $check['php'] = ['status' => 'success', 'msg' => 'PHP版本 ' . PHP_VERSION];
                }

                if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
                    $check['pdo'] = ['status' => 'success', 'msg' => 'PDO_MYSQL 支持'];
                } else {
                    $check['pdo'] = ['status' => 'danger', 'msg' => 'PDO_MYSQL 未安装'];
                    $install = false;
                }

                $dirs_to_check = [
                    CONFIG_DIR => 'includes目录（配置文件）',
                ];
                
                foreach ($dirs_to_check as $dir => $name) {
                    if (!is_dir($dir)) {
                        if (!@mkdir($dir, 0755, true)) {
                            $check['dir_' . md5($dir)] = ['status' => 'danger', 'msg' => "$name 不存在且无法自动创建"];
                            $install = false;
                        } else {
                            $check['dir_' . md5($dir)] = ['status' => 'success', 'msg' => "$name 已自动创建"];
                        }
                    } elseif (is_writable($dir)) {
                        $check['dir_' . md5($dir)] = ['status' => 'success', 'msg' => "$name 可写"];
                    } else {
                        $check['dir_' . md5($dir)] = ['status' => 'warning', 'msg' => "$name 不可写（请手动设置755权限）"];
                    }
                }
            ?>

                <h3 class="mb-4"><i class="fas fa-cogs"></i> 环境检测</h3>
                <p class="text-muted mb-4">请确保以下环境要求已满足</p>

                <ul class="list-group mb-4">
                    <?php foreach ($check as $item): ?>
                    <li class="list-group-item">
                        <?= htmlspecialchars($item['msg']) ?>
                        <span class="badge badge-<?= $item['status'] ?> badge-pill">
                            <?= $item['status'] == 'success' ? '✓' : ($item['status'] == 'warning' ? '!' : '✗') ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($install): ?>
                    <a href="?step=2" class="btn btn-success btn-block">检测通过，开始配置</a>
                <?php else: ?>
                    <div class="alert alert-danger">请解决以上问题后刷新页面重试</div>
                <?php endif; ?>

            <?php
            // ==================== 步骤2：配置表单 ====================
            elseif ($step == 2):
                $db = $_SESSION['db'] ?? ['host' => 'localhost', 'port' => '3306', 'name' => 'note_cloud'];
                $admin = $_SESSION['admin'] ?? ['username' => 'admin'];
            ?>

                <h3 class="mb-4"><i class="fas fa-wrench"></i> 配置信息</h3>
                <p class="text-muted mb-4">请填写数据库和管理员信息</p>

                <?php if (isset($_SESSION['install_error'])): ?>
                    <div class="alert alert-danger">
                        <strong>错误：</strong> <?= htmlspecialchars($_SESSION['install_error']) ?>
                    </div>
                    <?php unset($_SESSION['install_error']); ?>
                <?php endif; ?>

                <form method="post" action="?step=3&run=1" id="installForm">
                    <!-- 数据库配置 -->
                    <div class="form-section">
                        <div class="form-title">📊 数据库设置</div>
                        <div class="form-group">
                            <label>数据库地址</label>
                            <input type="text" class="form-control" name="db_host" value="<?= htmlspecialchars($db['host']) ?>" required placeholder="localhost">
                        </div>
                        <div class="form-group">
                            <label>数据库端口</label>
                            <input type="text" class="form-control" name="db_port" value="<?= htmlspecialchars($db['port']) ?>" required placeholder="3306">
                        </div>
                        <div class="form-group">
                            <label>数据库用户名</label>
                            <input type="text" class="form-control" name="db_user" value="<?= htmlspecialchars($db['user']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>数据库密码</label>
                            <input type="password" class="form-control" name="db_pass" value="<?= htmlspecialchars($db['pass'] ?? '') ?>" required>
                            <div class="alert alert-warning" style="margin-top: 10px;">
                                <strong>MySQL 8.0 用户注意：</strong><br>
                                如果出现 2054 错误，请在安装前执行 SQL：<br>
                                <code>ALTER USER '<?=htmlspecialchars($_POST['db_user']??'root')?>'@'localhost' IDENTIFIED WITH mysql_native_password BY '你的密码'; FLUSH PRIVILEGES;</code>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>数据库名称</label>
                            <input type="text" class="form-control" name="db_name" value="<?= htmlspecialchars($db['name']) ?>" required placeholder="将自动创建">
                        </div>
                    </div>

                    <!-- 管理员配置 -->
                    <div class="form-section">
                        <div class="form-title">👑 管理员账号</div>
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" class="form-control" name="admin_user" value="<?= htmlspecialchars($admin['username']) ?>" required pattern="[a-zA-Z0-9_]{3,20}">
                            <small class="text-muted">3-20位字母、数字或下划线</small>
                        </div>
                        <div class="form-group">
                            <label>密码</label>
                            <input type="password" class="form-control" name="admin_pass" id="adminPass" required minlength="6">
                            <div class="validation-message" id="passwordMessage">至少6位字符</div>
                        </div>
                        <div class="form-group">
                            <label>确认密码</label>
                            <input type="password" class="form-control" name="admin_pass2" id="adminPass2" required minlength="6">
                            <div class="validation-message" id="password2Message"></div>
                        </div>
                        <div class="form-group">
                            <label>邮箱</label>
                            <input type="email" class="form-control" name="admin_email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-block" id="submitBtn">
                        ▶ 开始安装
                    </button>
                </form>

                <script>
                // 密码验证脚本
                const password = document.getElementById('adminPass');
                const password2 = document.getElementById('adminPass2');
                const passwordMessage = document.getElementById('passwordMessage');
                const password2Message = document.getElementById('password2Message');
                const submitBtn = document.getElementById('submitBtn');

                function validateForm() {
                    const pwd1 = password.value;
                    const pwd2 = password2.value;
                    let isValid = true;

                    // 密码长度
                    if (pwd1.length > 0 && pwd1.length < 6) {
                        passwordMessage.textContent = '❌ 密码长度至少6位';
                        passwordMessage.className = 'validation-message validation-error';
                        isValid = false;
                    } else if (pwd1.length >= 6) {
                        passwordMessage.textContent = '✓ 密码强度符合要求';
                        passwordMessage.className = 'validation-message validation-success';
                    } else {
                        passwordMessage.style.display = 'none';
                    }

                    // 密码匹配
                    if (pwd2.length > 0 && pwd1 !== pwd2) {
                        password2Message.textContent = '❌ 两次密码不一致';
                        password2Message.className = 'validation-message validation-error';
                        isValid = false;
                    } else if (pwd2.length > 0 && pwd1 === pwd2) {
                        password2Message.textContent = '✓ 密码匹配';
                        password2Message.className = 'validation-message validation-success';
                    } else {
                        password2Message.style.display = 'none';
                    }

                    submitBtn.disabled = !isValid;
                    return isValid;
                }

                password.addEventListener('input', validateForm);
                password2.addEventListener('input', validateForm);

                document.getElementById('installForm').addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        alert('请检查密码设置是否正确！');
                        return false;
                    }
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '⏳ 安装中，请稍候...';
                });
                </script>

            <?php
            // ==================== 步骤3：执行安装 ====================
            elseif ($step == 3):
                // ✅ 先显示加载界面
                ?>
                <div class="install-progress">
                    <h3 class="mb-4"><i class="fas fa-spinner fa-spin"></i> 正在安装系统...</h3>
                    <div class="progress mb-3" style="height:30px; border-radius:8px;">
                        <div id="installProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             style="width:5%; font-size:14px; font-weight:bold;">
                            准备中...
                        </div>
                    </div>
                    <div id="installLog" class="log-container"></div>
                </div>
                <?php
                
                // ✅ 安全的 $log 函数定义
                $log = function($msg, $type = 'info') {
                    $icon = $type == 'success' ? '✓' : ($type == 'error' ? '✗' : '→');
                    $safeMsg = json_encode($icon . ' ' . $msg, JSON_UNESCAPED_UNICODE);
                    echo "<script>
                        try {
                            var div = document.createElement('div');
                            div.className = 'alert alert-{$type}';
                            div.style.margin = '8px 0';
                            div.style.padding = '10px';
                            div.style.fontSize = '14px';
                            div.innerHTML = {$safeMsg};
                            var logContainer = document.getElementById('installLog');
                            if(logContainer) {
                                logContainer.appendChild(div);
                                logContainer.scrollTop = logContainer.scrollHeight;
                            }
                        } catch(e) { console.error('日志错误:', e); }
                    </script>";
                    ob_flush(); flush();
                };

                // ✅ 实际安装逻辑
                if (isset($_GET['run']) && $_GET['run'] == '1') {
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        header('Location: ?step=2');
                        exit;
                    }

                    // 验证密码
                    if ($_POST['admin_pass'] !== $_POST['admin_pass2']) {
                        $log('❌ 两次密码不一致', 'error');
                        echo '<a href="?step=2" class="btn btn-info btn-block mt-4">返回修改</a>';
                        exit;
                    }

                    try {
                        // ✅ MySQL 8.0 认证插件
                        $dsn = "mysql:host={$_POST['db_host']};port={$_POST['db_port']};charset=utf8mb4;auth_plugin=mysql_native_password";
                        $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);

                        $dbName = $_POST['db_name'];
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $log('✓ 数据库创建成功', 'success');

                        $pdo->beginTransaction();
                        $pdo->exec("USE `$dbName`");
                        
                        // 创建数据表
                        try {
                            createTables($pdo, $log);
                        } catch (Exception $e) {
                            throw new Exception("数据表创建失败: " . $e->getMessage());
                        }

                        // ✅ 创建管理员（明文密码）
                        $plainPassword = $_POST['admin_pass'];
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, status, approved, created_at) VALUES (?, ?, ?, 'admin', 1, 1, NOW())");
                        $stmt->execute([$_POST['admin_user'], $plainPassword, $_POST['admin_email']]);
                        $log('✓ 管理员账号创建成功', 'success');

                        $pdo->commit();

                        // ✅ 生成包含 PDO 的配置文件
                        if (!is_dir(CONFIG_DIR)) {
                            mkdir(CONFIG_DIR, 0755, true);
                        }
                        
                        $configContent = '<?php' . "\n";
                        $configContent .= '/* Note Cloud 数据库配置 - 自动生成于 ' . date('Y-m-d H:i:s') . ' */' . "\n\n";
                        $configContent .= '$dbconfig = array(' . "\n";
                        $configContent .= "    'host' => '" . addslashes($_POST['db_host']) . "',\n";
                        $configContent .= "    'port' => " . intval($_POST['db_port']) . ",\n";
                        $configContent .= "    'user' => '" . addslashes($_POST['db_user']) . "',\n";
                        $configContent .= "    'pwd' => '" . addslashes($_POST['db_pass']) . "',\n";
                        $configContent .= "    'dbname' => '" . addslashes($_POST['db_name']) . "'\n";
                        $configContent .= ');' . "\n\n";
                        $configContent .= <<<'PDO_CODE'
// 自动创建 PDO 数据库连接（所有页面直接使用 $pdo）
try {
    $pdo = new PDO(
        "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4",
        $dbconfig['user'],
        $dbconfig['pwd'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    error_log("PDO Connection Error: " . $e->getMessage());
    die('<div style="text-align:center; padding:50px;"><h2>⚠️ 系统维护中</h2><p>数据库连接出现问题</p></div>');
}
PDO_CODE;
                        
                        if (file_put_contents(CONFIG_PATH, $configContent) === false) {
                            throw new Exception('配置文件写入失败');
                        }
                        chmod(CONFIG_PATH, 0644);
                        $log('✓ 配置文件创建成功', 'success');

                        // ✅ 创建包含安装信息的锁文件
                        $installInfo = [
                            'installed' => true,
                            'version' => '1.0.0',
                            'installed_at' => date('Y-m-d H:i:s'),
                            'installed_by' => $_SERVER['REMOTE_ADDR'],
                            'db_name' => $_POST['db_name'],
                            'admin_user' => $_POST['admin_user']
                        ];
                        $lockContent = '<?php return ' . var_export($installInfo, true) . ';';
                        if (file_put_contents(LOCK_PATH, $lockContent) === false) {
                            throw new Exception('无法创建安装锁文件');
                        }
                        $log('✓ 安装锁创建成功', 'success');

                        // ✅ 关键修复：清空缓冲区再跳转
                        ob_end_clean();
                        echo "<script>window.location.href='?step=4';</script>";  // ✅ JS跳转，不受输出限制
                        exit;

                    } catch (PDOException $e) {
                        if (isset($pdo) && $pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $log('✗ 数据库错误: ' . $e->getMessage(), 'error');
                        echo '<a href="?step=2" class="btn btn-info btn-block mt-4">返回修改配置</a>';
                        exit;
                    } catch (Exception $e) {
                        $log('✗ 安装失败: ' . $e->getMessage(), 'error');
                        echo '<a href="?step=2" class="btn btn-info btn-block mt-4">返回修改配置</a>';
                        exit;
                    }
                }
            ?>

            <?php
            // ==================== 步骤4：完成 ====================
            elseif ($step == 4):
                if (!file_exists(LOCK_PATH)) {
                    header('Location: ?step=1');
                    exit;
                }
            
                // ✅ 双重保险读取安装信息
                $dbName = $_SESSION['db']['name'] ?? '未知';
                $adminUser = $_SESSION['admin']['username'] ?? '未知';
                $adminEmail = $_SESSION['admin']['email'] ?? '未知';
                
                // ✅ 从锁文件读取
                $installInfo = @include(LOCK_PATH);
                if (is_array($installInfo)) {
                    $dbName = $installInfo['db_name'] ?? $dbName;
                    $adminUser = $installInfo['admin_user'] ?? $adminUser;
                }
                ?>
            
                <h3 class="mb-4"><i class="fas fa-check-circle"></i> 🎉 安装完成</h3>
                
                <div class="alert alert-success">
                    <h4>系统已成功安装！</h4>
                    <ul class="mb-0">
                        <li>数据库：<strong><?= htmlspecialchars($dbName) ?></strong></li>
                        <li>管理员：<strong><?= htmlspecialchars($adminUser) ?></strong>（已自动审批）</li>
                        <li>管理员邮箱：<strong><?= htmlspecialchars($adminEmail) ?></strong></li>
                    </ul>
                </div>
            
                <div class="card border-warning mb-4">
                    <div class="card-header bg-warning text-white font-weight-bold">⚠️ 安全提醒</div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li>请妥善保管管理员密码</li>
                            <li><strong>建议立即删除 install/ 目录</strong></li>
                            <li>配置文件已生成：<code>includes/config.php</code></li>
                            <li>安装锁文件：<code><?= LOCK_PATH ?></code></li>
                        </ol>
                    </div>
                </div>
            
                <div class="row">
                    <div class="col-md-6">
                        <a href="../index.php" class="btn btn-primary btn-block">进入系统</a>
                    </div>
                    <div class="col-md-6">
                        <a href="../login.php" class="btn btn-success btn-block">前往登录</a>
                    </div>
                </div>
            
                <?php
                // ✅ 关键修复：在显示完成后再清空 session
                unset($_SESSION['db'], $_SESSION['admin']);
                ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * ✅ 创建所有数据表（明文密码版本）
 */
function createTables(PDO $pdo, callable $log) {
    $tables = [
        // 用户表
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            role VARCHAR(50) NOT NULL DEFAULT 'user',
            status TINYINT(1) NOT NULL DEFAULT 1,
            approved TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status),
            INDEX idx_approved (approved)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
		
		"CREATE TABLE IF NOT EXISTS favorites (
		        user_id INT NOT NULL,
		        note_id INT NOT NULL,
		        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		        PRIMARY KEY (user_id, note_id),
		        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
		        FOREIGN KEY (note_id) REFERENCES knowledge_notes(id) ON DELETE CASCADE,
		        INDEX idx_user_id (user_id),
		        INDEX idx_note_id (note_id),
		        INDEX idx_created_at (created_at)
		    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 知识分类表
        "CREATE TABLE IF NOT EXISTS knowledge_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 标签表
        "CREATE TABLE IF NOT EXISTS note_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_name (name),
            UNIQUE KEY uk_user_tag (user_id, name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 知识标签表
        "CREATE TABLE IF NOT EXISTS knowledge_note_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_name (name),
            UNIQUE KEY uk_user_tag (user_id, name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 笔记表
        "CREATE TABLE IF NOT EXISTS notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            category_id INT DEFAULT NULL,
            status TINYINT(1) DEFAULT 1,
            is_deleted TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES knowledge_categories(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_category_id (category_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            FULLTEXT idx_title_content (title, content)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 知识笔记表
        "CREATE TABLE IF NOT EXISTS knowledge_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            images JSON DEFAULT NULL,           -- 原来是 image_path VARCHAR(500)
            files JSON DEFAULT NULL,            -- 原来是 file_path VARCHAR(500)
            status TINYINT(1) DEFAULT 1,
            view_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES knowledge_categories(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_category_id (category_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            FULLTEXT idx_title_content (title, content)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            if (preg_match('/CREATE TABLE IF NOT EXISTS\s+`?(\w+)`?/i', $sql, $matches)) {
                $log("✓ 表 {$matches[1]} 创建成功", 'success');
            }
        } catch (PDOException $e) {
            throw new Exception("创建表失败: " . $e->getMessage());
        }
    }
}