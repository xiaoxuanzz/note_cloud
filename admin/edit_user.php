<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("无效的用户ID");
}

// 获取用户信息
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("用户不存在");
    }
} catch (Exception $e) {
    die("获取用户信息失败: " . $e->getMessage());
}

// 处理更新
$update_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $approved = isset($_POST['approved']) ? 1 : 0;

    // 验证输入
    if (empty($username) || empty($email)) {
        $update_error = '用户名和邮箱不能为空';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_error = '邮箱格式不正确';
    } else {
        // 检查用户名是否已存在
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            
            if ($stmt->fetchColumn() > 0) {
                $update_error = "用户名已存在";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, approved = ? WHERE id = ?");
                $stmt->execute([$username, $email, $role, $approved, $id]);
                
                $_SESSION['message'] = '用户更新成功！';
                header("Location: users.php");
                exit();
            }
        } catch (Exception $e) {
            $update_error = '更新失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑用户 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { width: 250px; background-color: #343a40; min-height: 100vh; position: fixed; padding: 20px 0; }
        .sidebar .nav-link { color: #dfe6e9; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar .nav-link:hover { background-color: #485460; color: white; }
        .sidebar .nav-link.active { background-color: #6c7ae0; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
        
        /* ========== 移动端样式（与回收站一致） ========== */
        .toggle-sidebar {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            display: none;
            background-color: #343a40;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-250px); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; transition: margin-left 0.3s ease; }
            .main-content.active { margin-left: 250px; }
            .toggle-sidebar { display: block; }
        }
    </style>
</head>
<body>
    <!-- ========== 修复：添加按钮和容器结构 ========== -->
    <button class="toggle-sidebar" onclick="toggleSidebar()" style="left: 85%;">☰</button>
    
    <div class="sidebar" id="sidebar">
        <h4 class="text-white text-center mb-4">PZIOT 管理系统</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="index.php">📊 数据统计</a></li>
            <li class="nav-item"><a class="nav-link active" href="users.php">👥 用户管理</a></li>
            <li class="nav-item"><a class="nav-link" href="notes.php">📝 笔记管理</a></li>
            <li class="nav-item">
                <a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a>
            </li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <h2>编辑用户</h2>
        <div class="card mt-3">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名 *</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">邮箱 *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">角色</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>普通用户</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理员</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="approved" name="approved" <?php echo $user['approved'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="approved">已审批（允许登录）</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 更新用户</button>
                    <a href="users.php" class="btn btn-secondary">取消</a>
                    
                    <?php if (!empty($update_error)): ?>
                        <div class="alert alert-danger mt-3"><?php echo $update_error; ?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const toggleButton = document.querySelector('.toggle-sidebar');
                
                if (window.innerWidth <= 768 && 
                    !sidebar.contains(event.target) && 
                    !toggleButton.contains(event.target) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    document.getElementById('mainContent').classList.remove('active');
                }
            });
        });

        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('mainContent').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>