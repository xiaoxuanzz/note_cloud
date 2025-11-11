<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? null;

// 检查笔记是否存在
$stmt = $pdo->prepare("SELECT * FROM knowledge_notes WHERE id = ?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    die("笔记不存在");
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看笔记 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="http://10.92.169.234:8800/admin/down.php/a6c2ff793b37e00796a7f0d1624131ad.ico)" type="image/x-icon">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h1>查看笔记</h1>
                <a href="../favorites.php" class="btn btn-secondary">返回收藏夹</a>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><?php echo htmlspecialchars($note['title']); ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars($note['content']); ?></p>
                        <?php if (!empty($note['images'])): ?>
                            <?php foreach (json_decode($note['images'], true) as $image): ?>
                                <img src="<?php echo $image; ?>" alt="笔记图片" class="img-fluid rounded mb-2" style="max-height: 500px;">
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($note['files'])): ?>
                            <div class="mt-2">
                                <?php foreach (json_decode($note['files'], true) as $file): ?>
                                    <a href="uploads/files/<?php echo basename($file); ?>" class="btn btn-sm btn-outline-primary" download>下载文件</a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">创建者: 
                                <?php
                                $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                                $userStmt->execute([$note['user_id']]);
                                $user = $userStmt->fetch();
                                echo htmlspecialchars($user['username']);
                                ?>
                            </small>
                            <div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>