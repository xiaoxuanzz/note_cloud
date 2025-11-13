<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录且是管理员角色
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

// 获取统计信息
try {
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $note_count = $pdo->query("SELECT COUNT(*) FROM knowledge_notes")->fetchColumn();
    $pending_users = $pdo->query("SELECT COUNT(*) FROM users WHERE approved = 0")->fetchColumn();
    $today_notes = $pdo->query("SELECT COUNT(*) FROM knowledge_notes WHERE DATE(created_at) = CURDATE()")->fetchColumn();
} catch (Exception $e) {
    error_log("Failed to fetch statistics: " . $e->getMessage());
    $user_count = $note_count = $pending_users = $today_notes = 0;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 250px;
            background-color: #343a40;
            min-height: 100vh;
            position: fixed;
            padding: 20px 0;
            transition: transform 0.3s ease;
            transform: translateX(0);
            z-index: 1000;
        }
        .sidebar.collapsed {
            transform: translateX(-250px);
        }
        .sidebar .nav-link {
            color: #dfe6e9;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: #485460;
            color: white;
        }
        .sidebar .nav-link.active {
            background-color: #6c7ae0;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }
        .main-content.collapsed {
            margin-left: 0;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
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
            .sidebar {
                transform: translateX(-250px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.active {
                margin-left: 250px;
            }
            .toggle-sidebar {
                display: block;
            }
        }
    </style>
</head>
<body>
    <button class="toggle-sidebar d-md-none" onclick="toggleSidebar()">☰</button>
    
    <div class="sidebar" id="sidebar">
        <h4 class="text-white text-center mb-4">PZIOT 管理系统</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">📊 数据统计</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">👥 用户管理</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="notes.php">📝 笔记管理</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a>
            </li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>管理面板</h1>
            <div>
                <span class="badge bg-primary">管理员: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body text-center">
                        <h2 class="card-title display-6"><?php echo $user_count; ?></h2>
                        <p class="card-text">总用户数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body text-center">
                        <h2 class="card-title display-6"><?php echo $note_count; ?></h2>
                        <p class="card-text">总笔记数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body text-center">
                        <h2 class="card-title display-6"><?php echo $pending_users; ?></h2>
                        <p class="card-text">等待审核</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body text-center">
                        <h2 class="card-title display-6"><?php echo $today_notes; ?></h2>
                        <p class="card-text">今日新增</p>
                    </div>
                </div>
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