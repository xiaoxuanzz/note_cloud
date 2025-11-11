<?php
session_start();

// 检查配置文件
if (!file_exists('includes/config.php')) {
    die('<div style="text-align:center; padding:50px;">
            <h2>⚠️ 配置文件不存在</h2>
            <p>请先运行 <a href="install/install.php">安装程序</a></p>
        </div>');
}

include('includes/config.php');

// ✅ 创建 PDO 数据库连接
try {
    $pdo = new PDO(
        "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4",
        $dbconfig['user'],
        $dbconfig['pwd'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('<div style="text-align:center; padding:50px; color:red;">
            <h2>数据库连接失败</h2>
            <p>错误信息：' . htmlspecialchars($e->getMessage()) . '</p>
            <p>请检查 includes/config.php 配置是否正确</p>
        </div>');
}

// 检查是否已登录
if (isset($_SESSION['user_id'])) {
    header("Location: knowledge/index.php");
    exit();
}

// 处理注册
$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // 验证输入
    if (empty($username) || empty($email) || empty($password)) {
        $register_error = "所有字段都必须填写";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $register_error = "用户名长度必须在3-20位之间";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "邮箱格式不正确";
    } elseif (strlen($password) < 6) {
        $register_error = "密码长度至少6位";
    } elseif ($password !== $password_confirm) {
        $register_error = "两次密码不一致";
    } else {
        try {
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $register_error = "用户名已存在";
            } else {
                // 检查邮箱是否已存在
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $register_error = "邮箱已被注册";
                } else {
                    // ✅ 插入新用户（approved 默认为 0，需要审批）
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role, status, approved, created_at) VALUES (?, ?, ?, 'user', 1, 0, NOW())");
                    $stmt->execute([$username, $passwordHash, $email]);
                    
                    $register_success = "注册成功！您的账号需要管理员审批，请耐心等待";
                }
            }
        } catch (PDOException $e) {
            $register_error = "系统错误，请稍后重试";
            error_log("Register error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PZIOT笔记网 - 注册</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-container {
            max-width: 500px;
            margin: 0 auto;
        }
        .card {
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border-radius: 15px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            text-align: center;
            padding: 20px;
            border: none;
        }
        .card-header h3 {
            margin: 0;
            font-weight: bold;
        }
        .card-body {
            padding: 30px;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <h3>📝 账号注册</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($register_error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($register_error) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($register_success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($register_success) ?></div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-success">前往登录</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus
                                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                            <small class="text-muted">3-20位字母、数字或下划线</small>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <small class="text-muted">至少6位字符</small>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">确认密码</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-success w-100">注册账号</button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="btn btn-link">已有账号？立即登录</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>