<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? null;

// 查询笔记
$stmt = $pdo->prepare("SELECT * FROM knowledge_notes WHERE id = ?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    die("笔记不存在");
}

// 权限验证
$isAdmin = $_SESSION['role'] === 'admin';
$isOwner = $note['user_id'] == $_SESSION['user_id'];

if (!$isAdmin && !$isOwner) {
    header("HTTP/1.1 403 Forbidden");
    die("您无权访问此笔记");
}

// 获取知识库分类
$categories = [];
$content = isset($_POST['content']) ? nl2br($_POST['content']) : '';
try {
    $stmt = $pdo->query("SELECT * FROM knowledge_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to fetch knowledge categories: " . $e->getMessage());
}

// 处理更新笔记的逻辑
$update_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    // 获取已有的图片和文件
    $existing_images = json_decode($note['images'], true) ?? [];
    $existing_files = json_decode($note['files'], true) ?? [];

    // 处理图片删除
    if (isset($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $imageIndex) {
            if (isset($existing_images[$imageIndex])) {
                // 删除本地文件
                if (file_exists($existing_images[$imageIndex])) {
                    unlink($existing_images[$imageIndex]);
                }
                // 从数组中移除
                unset($existing_images[$imageIndex]);
            }
        }
        $existing_images = array_values($existing_images);
    }

    // 处理文件删除
    if (isset($_POST['delete_files'])) {
        foreach ($_POST['delete_files'] as $fileIndex) {
            if (isset($existing_files[$fileIndex])) {
                // 删除本地文件
                if (file_exists($existing_files[$fileIndex])) {
                    unlink($existing_files[$fileIndex]);
                }
                // 从数组中移除
                unset($existing_files[$fileIndex]);
            }
        }
        $existing_files = array_values($existing_files);
    }

    // 处理图片上传
    $images = [];
    if (!empty($_FILES['images']['name'])) {
        $target_dir = "uploads/images/"; // 直接存储在uploads/images/
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        foreach ($_FILES['images']['name'] as $index => $name) {
            $target_file = $target_dir . basename($name);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowed_types)) {
                if (move_uploaded_file($_FILES["images"]["tmp_name"][$index], $target_file)) {
                    $images[] = $target_file;
                }
            }
        }
    }

    // 处理文件上传
    $files = [];
    if (!empty($_FILES['files']['name'])) {
        $target_dir = "uploads/files/"; // 直接存储在uploads/files/
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        foreach ($_FILES['files']['name'] as $index => $name) {
            $target_file = $target_dir . basename($name);
            if (move_uploaded_file($_FILES["files"]["tmp_name"][$index], $target_file)) {
                $files[] = $target_file;
            }
        }
    }

    // 合并已有的和新上传的图片和文件
    $images = array_merge($existing_images, $images);
    $files = array_merge($existing_files, $files);

    if (empty($title)) {
        $update_error = '标题不能为空';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE knowledge_notes SET title = ?, content = ?, category_id = ?, images = ?, files = ? WHERE id = ?");
            $stmt->execute([$title, $content, $category_id, json_encode($images), json_encode($files), $id]);

            header("Location: view.php?id=$id");
            exit();
        } catch (Exception $e) {
            $update_error = '更新笔记时出错: ' . $e->getMessage();
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
    <link href="../css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="http://10.92.169.234:8800/admin/down.php/a6c2ff793b37e00796a7f0d1624131ad.ico" type="image/x-icon">
    <style>
        .sidebar {
            width: 200px;
            background-color: #343a40;
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
            transition: transform 0.3s ease;
            transform: translateX(0);
            z-index: 1000;
        }
        .sidebar.collapsed {
            transform: translateX(-200px);
        }
        .sidebar .nav-link {
            color: #dfe6e9;
            padding: 10px 20px;
            display: block;
        }
        .sidebar .nav-link:hover {
            background-color: #485460;
        }
        .sidebar .nav-link.active {
            background-color: #6c7ae0;
        }
        .main-content {
            margin-left: 200px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        .main-content.collapsed {
            margin-left: 0;
        }
        .toggle-sidebar {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            display: none;
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
                transform: translateX(-200px);
            }
            .main-content {
                margin-left: 0;
            }
            .toggle-sidebar {
                display: block;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content.active {
                margin-left: 200px;
            }
        }
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-bottom: 10px;
        }
        .delete-btn {
            margin-left: 10px;
        }
        .grayed-out {
            opacity: 0.5;
            filter: grayscale(100%);
        }
    </style>
</head>
<body>
        <div class="row">
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item active">
                        <a class="nav-link" href="index.php">知识库</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../recycle.php">回收站</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../favorites.php">收藏</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <h2>编辑笔记</h2>
				<button class="toggle-sidebar d-lg-none" onclick="toggleSidebar()" style="left: 85%;" id="an">☰</button>
				<br/>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <?php if (!empty($update_error)): ?>
                            <div class="alert alert-danger"><?php echo $update_error; ?></div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data" id="editForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">标题</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($note['title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">内容</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($note['content']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="category_id" class="form-label">分类</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $note['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="images" class="form-label">上传图片（多张）</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple>
                                <div id="imagePreviewContainer" class="mt-2"></div>
                                <div class="mt-2" id="existingImagesContainer">
                                    <?php if (!empty($note['images'])): ?>
                                        <?php $images = json_decode($note['images'], true); ?>
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="d-flex align-items-center" id="image_<?php echo $index; ?>">
                                                <img src="<?php echo $image; ?>" alt="现有图片" class="img-fluid image-preview">
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" onclick="markImageForDeletion(<?php echo $index; ?>)">删除</button>
                                                <input type="checkbox" name="delete_images[]" value="<?php echo $index; ?>" id="deleteImage_<?php echo $index; ?>" style="display: none;">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="files" class="form-label">上传文件（多个）</label>
                                <input type="file" class="form-control" id="files" name="files[]" multiple>
                                <div id="filePreviewContainer" class="mt-2"></div>
                                <div class="mt-2" id="existingFilesContainer">
                                    <?php if (!empty($note['files'])): ?>
                                        <?php $files = json_decode($note['files'], true); ?>
                                        <?php foreach ($files as $index => $file): ?>
                                            <div class="d-flex align-items-center" id="file_<?php echo $index; ?>">
                                                <a href="<?php echo $file; ?>" class="btn btn-sm btn-outline-primary" download>下载文件</a>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" onclick="markFileForDeletion(<?php echo $index; ?>)">删除</button>
                                                <input type="checkbox" name="delete_files[]" value="<?php echo $index; ?>" id="deleteFile_<?php echo $index; ?>" style="display: none;">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">保存更新</button>
							<a href="index.php" class="btn btn-primary">返回</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('active');
        }

        document.getElementById('images').addEventListener('change', function(e) {
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            imagePreviewContainer.innerHTML = '';
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-fluid image-preview';
                        img.alt = '预览图片';
                        imagePreviewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
        function markImageForDeletion(index) {
            document.getElementById('deleteImage_' + index).checked = true;
            document.getElementById('image_' + index).classList.add('grayed-out');
        }
        function markFileForDeletion(index) {
            document.getElementById('deleteFile_' + index).checked = true;
            document.getElementById('file_' + index).classList.add('grayed-out');
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const mainContent = document.querySelector('.main-content');
                const toggleButton = document.querySelector('.toggle-sidebar');
                if (!sidebar.contains(event.target) && !toggleButton.contains(event.target) && window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('active');
                }
            });
        });
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    const sidebar = document.querySelector('.sidebar');
                    const mainContent = document.querySelector('.main-content');
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('active');
                }
            });
        });
        let touchStartX = 0;
        let touchEndX = 0;
        document.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        document.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
        function handleSwipe() {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const mainContent = document.querySelector('.main-content');
                const swipeThreshold = 50;
                const difference = Math.abs(touchEndX - touchStartX);
                if (difference > swipeThreshold && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('active');
                }
            }
        }
		
        // 按钮的JS
        function isMobile() {
            return /Mobi|Android|iPhone/i.test(navigator.userAgent);
        }
        if (isMobile()) {
            document.querySelector('.toggle-sidebar').style.display = 'block';
        } else {
            document.querySelector('.toggle-sidebar').style.display = 'none';
        }
    </script>
</body>
</html>