<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

// 获取所有用户
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to fetch users: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { width: 250px; background-color: #343a40; min-height: 100vh; position: fixed; padding: 20px 0; transition: transform 0.3s ease; transform: translateX(0); z-index: 1000; }
        .sidebar.collapsed { transform: translateX(-250px); }
        .sidebar .nav-link { color: #dfe6e9; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar .nav-link:hover { background-color: #485460; color: white; }
        .sidebar .nav-link.active { background-color: #6c7ae0; color: white; }
        .main-content { margin-left: 250px; padding: 30px; transition: margin-left 0.3s ease; }
        .main-content.collapsed { margin-left: 0; }
        
        /* ========== 复制回收站示例的按钮样式 ========== */
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
            .sidebar { transform: translateX(-250px); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .main-content.active { margin-left: 250px; }
            .toggle-sidebar { display: block; }
        }
    </style>
</head>
<body>
    <!-- ========== 修复：移除d-md-none，添加style="left: 85%;" ========== -->
    <button class="toggle-sidebar" onclick="toggleSidebar()" style="left: 85%;">☰</button>
    
    <div class="sidebar" id="sidebar">
        <h4 class="text-white text-center mb-4">PZIOT 管理系统</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="index.php">📊 数据统计</a></li>
            <li class="nav-item"><a class="nav-link active" href="users.php">👥 用户管理</a></li>
            <li class="nav-item"><a class="nav-link" href="notes.php">📝 笔记管理</a></li>
            <li class="nav-item"><a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a></li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <!-- ✅ 标题 -->
        <h2 class="mb-4">用户管理</h2>
        
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th>角色</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $status = $user['approved'] ? '<span class="badge bg-success">已审批</span>' : '<span class="badge bg-warning text-dark">待审核</span>';
                    ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['role'] === 'admin' ? '<span class="badge bg-danger">管理员</span>' : '<span class="badge bg-secondary">普通用户</span>'; ?></td>
                            <td><?php echo $status; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                                <?php if ($user['username'] !== 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除此用户吗？此操作不可恢复！')">删除</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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