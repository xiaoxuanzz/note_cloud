<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录且是管理员角色
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

// 获取用户统计
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$user_count = $stmt->fetchColumn();

// 获取笔记统计（修正表名为 knowledge_notes）
$stmt = $pdo->query("SELECT COUNT(*) FROM knowledge_notes");
$note_count = $stmt->fetchColumn();

// 获取未审批用户统计
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE approved = 0");
$pending_users = $stmt->fetchColumn();

// 获取今日新增笔记统计
$stmt = $pdo->query("SELECT COUNT(*) FROM knowledge_notes WHERE DATE(created_at) = CURDATE()");
$today_notes = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - PZIOT笔记网</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css " rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h1>管理面板</h1>
                <a href="settings.php" class="btn btn-primary mt-3">系统设置</a>
                <a href="../logout.php" class="btn btn-danger mt-3">退出登录</a>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $user_count; ?></h2>
                        <p class="card-text">总用户数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $note_count; ?></h2>
                        <p class="card-text">总笔记数</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $pending_users; ?></h2>
                        <p class="card-text">等待审核</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $today_notes; ?></h2>
                        <p class="card-text">今日新增</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>