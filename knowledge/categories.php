<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 获取分类
$categories = [];
try {
    if (isset($_SESSION['is_xiaoxuan']) && $_SESSION['is_xiaoxuan']) {
    $stmt = $pdo->query("SELECT * FROM knowledge_categories ORDER BY name ASC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM knowledge_categories WHERE user_id = ? ORDER BY name ASC");
    $stmt->execute([$_SESSION['user_id']]);
}

$categories = $stmt->fetchAll();
}
 catch (Exception $e) {
    error_log("Failed to fetch categories: " . $e->getMessage());
}

// 处理删除分类
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_category'])) {
    $categoryId = $_POST['category_id'];
    try {
        if (isset($_SESSION['is_xiaoxuan']) && $_SESSION['is_xiaoxuan']) {
    $stmt = $pdo->prepare("DELETE FROM knowledge_categories WHERE id = ?");
    $stmt->execute([$categoryId]);
} else {
    $stmt = $pdo->prepare("DELETE FROM knowledge_categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$categoryId, $_SESSION['user_id']]);
}
        header("Location: categories.php");
        exit();
    }
     catch (Exception $e) {
        error_log("Failed to delete category: " . $e->getMessage());
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
    <style>
        .justify-content-between {
            display:flex;
            justify-content: space-between !important;
        }
        .width{
            width:20%
        }
		
    </style> 
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-">管理分类</h3>
                        <div class="justify-content-between ">
                            <a href="index.php" class="btn btn-primary">返回</a>
                            <a href="add_category.php" class="btn btn-primary">添加分类</a>
                        </div>
                        
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>名称</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <input type="hidden" name="delete_category" value="1">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定删除此分类吗？')">删除</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>