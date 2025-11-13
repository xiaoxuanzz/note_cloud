<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

// 获取所有笔记
$notes = [];
try {
    $stmt = $pdo->query("SELECT n.*, u.username as author 
                        FROM knowledge_notes n 
                        LEFT JOIN users u ON n.user_id = u.id 
                        ORDER BY n.created_at DESC");
    $notes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to fetch notes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>笔记管理 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { width: 250px; background-color: #343a40; min-height: 100vh; position: fixed; padding: 20px 0; transition: transform 0.3s ease; transform: translateX(0); z-index: 1000; }
        .sidebar.collapsed { transform: translateX(-250px); }
        .sidebar .nav-link { color: #dfe6e9; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar .nav-link:hover { background-color: #485460; color: white; }
        .sidebar .nav-link.active { background-color: #6c7ae0; color: white; }
        .main-content { margin-left: 250px; padding: 30px; transition: margin-left 0.3s ease; }
        .main-content.collapsed { margin-left: 0; }
        .toggle-sidebar {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
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
    <button class="toggle-sidebar d-md-none" onclick="toggleSidebar()">☰</button>
    
    <div class="sidebar" id="sidebar">
        <h4 class="text-white text-center mb-4">PZIOT 管理系统</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="index.php">📊 数据统计</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php">👥 用户管理</a></li>
            <li class="nav-item"><a class="nav-link active" href="notes.php">📝 笔记管理</a></li>
            <li class="nav-item"><a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a></li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>笔记管理</h2>
            <a href="../knowledge/create.php" class="btn btn-primary">➕ 新建笔记</a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>标题</th>
                        <th>作者</th>
                        <th>创建时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notes as $note): ?>
                        <tr>
                            <td><?php echo $note['id']; ?></td>
                            <td><?php echo htmlspecialchars($note['title']); ?></td>
                            <td><?php echo htmlspecialchars($note['author'] ?? '未知'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($note['created_at'])); ?></td>
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