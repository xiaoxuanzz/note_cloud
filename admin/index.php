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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 250px;
            background-color: #343a40;
            min-height: 100vh;
            position: fixed;
            padding: 20px 0;
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
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="sidebar">
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

    <div class="main-content">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>