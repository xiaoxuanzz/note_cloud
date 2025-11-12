<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

// 这里可以添加系统设置逻辑
$settings = [
    'site_name' => 'PZIOT笔记网',
    'allow_registration' => true,
    'require_approval' => true,
    'max_file_size' => '5MB'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - PZIOT笔记网</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <li class="nav-item"><a class="nav-link" href="notes.php">📝 笔记管理</a></li>
            <li class="nav-item"><a class="nav-link active" href="settings.php">⚙️ 系统设置</a></li>
            <li class="nav-item">
                <a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <h2>系统设置</h2>
        <div class="card mt-3">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">网站名称</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($settings['site_name']); ?>" disabled>
                        <div class="form-text">如需修改，请联系系统管理员</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" <?php echo $settings['allow_registration'] ? 'checked' : ''; ?> disabled>
                            <label class="form-check-label">允许新用户注册</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" <?php echo $settings['require_approval'] ? 'checked' : ''; ?> disabled>
                            <label class="form-check-label">新用户需要审批</label>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" disabled>保存设置（演示）</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>