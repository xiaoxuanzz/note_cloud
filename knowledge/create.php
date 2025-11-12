<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 获取知识库分类
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM knowledge_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to fetch knowledge categories: " . $e->getMessage());
}

// 处理创建笔记的逻辑
$create_error = '';
$content = isset($_POST['content']) ? nl2br($_POST['content']) : '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category_id = $_POST['category_id'] ?? null;

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

    if (empty($title)) {
        $create_error = '标题不能为空';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO knowledge_notes (user_id, category_id, title, content, images, files) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $category_id,
                $title,
                $content,
                json_encode($images),
                json_encode($files)
            ]);
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $create_error = '创建笔记时出错: ' . $e->getMessage();
        }
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
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-200px);
            }
            .main-content {
                margin-left: 0;
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
					<li class="nav-item">
					    <a class="nav-link" href="desp/index.php" target="_blank" rel="noopener noreferrer">AI智能笔记</a>
					</li>
                   <li class="nav-item">
                         <a class="nav-link" href="../logout.php">退出登录</a>
                     </li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <h2>创建笔记</h2>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <?php if (!empty($create_error)): ?>
                            <div class="alert alert-danger"><?php echo $create_error; ?></div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">标题</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">内容</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="category_id" class="form-label">分类</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="images" class="form-label">上传图片（多张）</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple>
                                <div id="imagePreviewContainer" class="mt-2"></div>
                            </div>
                            <div class="mb-3">
                                <label for="files" class="form-label">上传文件（多个）</label>
                                <input type="file" class="form-control" id="files" name="files[]" multiple>
                            </div>
                            <button type="submit" class="btn btn-primary">创建笔记</button>
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
		

        // 图片预览功能
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
		let touchStartX = 0;
		let touchEndX = 0;
        // 点击页面其他地方收起侧边栏
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

        // 点击侧边栏中的链接时收起侧边栏
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
    </script>
</body>
</html>