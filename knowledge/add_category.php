<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 处理添加分类
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    
    $userId = $_SESSION['user_id']; // 假设每个用户只能添加自己的分类

    try {
        // 插入分类信息到数据库
        $stmt = $pdo->prepare("INSERT INTO knowledge_categories (name, user_id) VALUES (?, ?)");
        $stmt->execute([$name, $userId]);
        
        // 添加成功后，重定向分类管理页面
        header("Location: ../knowledge/categories.php");
        exit();
    } catch (Exception $e) {
        error_log("Failed to add category: " . $e->getMessage());
        // 显示错误信息（在生产环境中可能需要更优雅地处理错误显示）
        echo "添加分类失败：" . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="http://10.92.169.234:8800/admin/down.php/a6c2ff793b37e00796a7f0d1624131ad.ico)" type="image/x-icon">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">添加新分类</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">分类名称</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">添加</button>
                            <a href="categories.php" class="btn btn-secondary">返回</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>