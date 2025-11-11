<?php
session_start();
include('includes/config.php');

// 检查用户是否已登录
if (isset($_SESSION['user_id'])) {
    header("Location: knowledge/index.php");
    exit();
}

// 处理用户登录
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 使用 $pdo 查询数据库
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $password === $user['password']) {
        // 检查是否通过审批
        if ($user['approved'] == 1) {
            // 登录成功，设置会话变量
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // 检查是否是特定用户 xiaoxuan
            if ($user['username'] === 'xiaoxuan') {
                $_SESSION['is_xiaoxuan'] = true;
            }
            
            header("Location: knowledge/index.php");
            exit();
        } else {
            $login_error = "您的账号尚未通过审批，请等待管理员审核";
        }
    } else {
        $login_error = "用户名或密码错误";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PZIOT笔记网 - 登录</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="http://10.92.169.234:8800/admin/down.php/a6c2ff793b37e00796a7f0d1624131ad.ico)" type="image/x-icon">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-sm-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">登录到PZIOT笔记网</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">用户名</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">密码</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">登录</button>
                            <?php if (!empty($login_error)): ?>
                                <div class="alert alert-danger mt-3"><?php echo $login_error; ?></div>
                            <?php endif; ?>
                        </form>
                        <a href="register.php" class="btn btn-link text-center mt-3">没有账号？立即注册</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>