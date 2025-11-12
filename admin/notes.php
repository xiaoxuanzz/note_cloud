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
        .sidebar { width: 250px; background-color: #343a40; min-height: 100vh; position: fixed; padding: 20px 0; }
        .sidebar .nav-link { color: #dfe6e9; padding: 12px 20px; display: block; transition: all 0.3s; }
        .sidebar .nav-link:hover { background-color: #485460; color: white; }
        .sidebar .nav-link.active { background-color: #6c7ae0; color: white; }
        .main-content { margin-left: 250px; padding: 30px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white text-center mb-4">PZIOT 管理系统</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="index.php">📊 数据统计</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php">👥 用户管理</a></li>
            <li class="nav-item"><a class="nav-link active" href="notes.php">📝 笔记管理</a></li>
            <li class="nav-item">
                <a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a>
            </li>
        </ul>
    </div>

    <div class="main-content">
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
</body>
</html>