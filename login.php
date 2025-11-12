<?php
session_start();

// 检查配置文件
if (!file_exists('includes/config.php')) {
    die('
        <div style="text-align:center; padding:50px; font-family: system-ui;">
            <h2>⚠️ 配置文件不存在</h2>
            <p>请先运行 <a href="install/install.php" style="color: #0d6efd;">安装程序</a></p>
        </div>
    ');
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
    die('
        <div style="text-align:center; padding:50px; color:#dc3545; font-family: system-ui;">
            <h2>数据库连接失败</h2>
            <p>错误信息：' . htmlspecialchars($e->getMessage()) . '</p>
            <p>请检查 includes/config.php 配置是否正确</p>
        </div>
    ');
}

// 检查是否已登录
if (isset($_SESSION['user_id'])) {
    header("Location: knowledge/index.php");
    exit();
}

// 处理登录
$errors = [];
$formData = ['username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['username'] = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 验证输入
    if (empty($formData['username'])) {
        $errors[] = "请输入用户名";
    } elseif (empty($password)) {
        $errors[] = "请输入密码";
    } else {
        try {
            // 查询用户
            $stmt = $pdo->prepare("SELECT id, username, password, role, approved FROM users WHERE username = ?");
            $stmt->execute([$formData['username']]);
            $user = $stmt->fetch();
            
            if ($user && $password === $user['password']) {
                // 检查是否通过审批
                if ($user['approved'] == 1) {
                    // 登录成功
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // 特殊用户标记
                    if ($user['username'] === 'xiaoxuan') {
                        $_SESSION['is_xiaoxuan'] = true;
                    }
                    
                    header("Location: knowledge/index.php");
                    exit();
                } else {
                    $errors[] = "您的账号尚未通过审批，请等待管理员审核";
                }
            } else {
                $errors[] = "用户名或密码错误";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "系统错误，请稍后重试";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - PZIOT笔记网</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        :root {
            --primary: #0d6efd;
        }
        
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 12px;
            overflow: hidden;
            animation: fadeIn 0.4s ease-out;
        }
        
        .card:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            color: white;
            text-align: center;
            padding: 1.5rem;
            border: none;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }
        
        .btn-primary:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #842029;
        }
        
        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="card">
            <div class="card-header">
                <h3>🔐 账号登录</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0" style="padding-left: 1.25rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               required autofocus value="<?= htmlspecialchars($formData['username']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                        <span class="btn-text">登录</span>
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="register.php" class="btn btn-link">没有账号？立即注册</a>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // 防止重复提交
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }
            
            submitBtn.disabled = true;
            btnText.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 登录中...';
        });
    </script>
</body>
</html>