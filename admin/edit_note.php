<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("无效的笔记ID");
}

// 获取笔记信息
try {
    $stmt = $pdo->prepare("SELECT * FROM knowledge_notes WHERE id = ?");
    $stmt->execute([$id]);
    $note = $stmt->fetch();
    
    if (!$note) {
        die("笔记不存在");
    }
} catch (Exception $e) {
    die("获取笔记失败: " . $e->getMessage());
}

// 处理更新
$update_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_path = $note['image_path'];
    $file_path = $note['file_path'];

    // 验证标题和内容
    if (empty($title) || empty($content)) {
        $update_error = '标题和内容不能为空';
    } else {
        // 处理图片上传
        if (!empty($_FILES['image']['name'])) {
            $target_dir = '../uploads/images/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $file_name;
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower(pathinfo($target_file, PATHINFO_EXTENSION)), $allowed_types)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // 删除旧图片
                    if (!empty($note['image_path']) && file_exists($note['image_path'])) {
                        unlink($note['image_path']);
                    }
                    $image_path = $target_file;
                } else {
                    $update_error = "图片上传失败";
                }
            } else {
                $update_error = "只允许 JPG, JPEG, PNG, GIF 格式";
            }
        }

        // 处理文件上传
        if (empty($update_error) && !empty($_FILES['file']['name'])) {
            $target_dir = '../uploads/files/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES["file"]["name"]);
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                // 删除旧文件
                if (!empty($note['file_path']) && file_exists($note['file_path'])) {
                    unlink($note['file_path']);
                }
                $file_path = $target_file;
            } else {
                $update_error = "文件上传失败";
            }
        }

        // 更新数据库
        if (empty($update_error)) {
            try {
                $stmt = $pdo->prepare("UPDATE knowledge_notes SET title = ?, content = ?, image_path = ?, file_path = ? WHERE id = ?");
                $stmt->execute([$title, $content, $image_path, $file_path, $id]);
                
                $_SESSION['message'] = '笔记更新成功！';
                header("Location: notes.php");
                exit();
            } catch (Exception $e) {
                $update_error = '更新失败: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑笔记 - PZIOT笔记网</title>
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
            <li class="nav-item"><a class="nav-link active" href="notes.php">📝 笔记管理</a></li>
            <li class="nav-item">
                <a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a>
            </li>
        </ul>
    </div>

<div class="main-content">
    <h2>编辑笔记</h2>
    <div class="card mt-3">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">标题 *</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($note['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">内容 *</label>
                    <textarea class="form-control" id="content" name="content" rows="8" required><?php echo htmlspecialchars($note['content']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">上传图片 (JPG, PNG, GIF)</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <?php if (!empty($note['image_path']) && file_exists($note['image_path'])): ?>
                        <div class="mt-3">
                            <img src="<?php echo $note['image_path']; ?>" alt="当前图片" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">上传文件</label>
                    <input type="file" class="form-control" id="file" name="file">
                    <?php if (!empty($note['file_path']) && file_exists($note['file_path'])): ?>
                        <div class="mt-3">
                            <a href="<?php echo $note['file_path']; ?>" class="btn btn-sm btn-outline-primary" download>📎 下载当前文件</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- ✅ 修改：将提交按钮改为跳转链接 -->
                <a href="knowledge/create.php" class="btn btn-primary">➕ 创建新笔记</a>
                
                <a href="notes.php" class="btn btn-secondary">取消</a>
                
                <?php if (!empty($update_error)): ?>
                    <div class="alert alert-danger mt-3"><?php echo $update_error; ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>