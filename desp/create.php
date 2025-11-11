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

// 检查是否有来自Kimi笔记助手的自动填充数据（在$_POST['chat_title']存在时）
$auto_fill_title = '';
$auto_fill_content = '';
if (isset($_POST['chat_title'])) {
    $auto_fill_title = $_POST['chat_title'];
    $auto_fill_content = isset($_POST['chat_content']) ? $_POST['chat_content'] : '';
}

// 处理创建笔记的逻辑（仅在正常提交时）
$create_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['chat_title'])) {
    // 安全地获取所有POST数据
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? $_POST['category_id'] : null;

    // 删除图片和文件上传功能，但保留空数组以兼容数据库
    $images = [];
    $files = [];

    if (empty($title)) {
        $create_error = '标题不能为空';
    } else {
        try {
            // 保留原有插入语句，空数组会自动转为JSON
            $stmt = $pdo->prepare("INSERT INTO knowledge_notes (user_id, category_id, title, content, images, files) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $category_id,
                $title,
                $content,
                json_encode($images),
                json_encode($files)
            ]);
            // 修复：跳转到上级目录的index.php
            header("Location: ../knowledge/index.php");
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
    <title>PZIOT笔记网 - 创建笔记</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="http://10.92.169.234:8800/admin/down.php/a6c2ff793b37e00796a7f0d1624131ad.ico " type="image/x-icon">
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
        .delete-btn {
            margin-left: 10px;
        }
        /* 添加自动填充提示样式 */
        .auto-fill-notice {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
        <div class="row">
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item active">
                        <!-- 修复：跳转到上级目录的index.php -->
                        <a class="nav-link" href="index.html">知识库</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../recycle.php">回收站</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../favorites.php">收藏</a>
                    </li>
					<li class="nav-item">
					    <a class="nav-link" href="index.html" target="_blank" rel="noopener noreferrer">AI智能笔记</a>
					</li>
                    <?php if (isset($_SESSION['is_xiaoxuan']) && $_SESSION['is_xiaoxuan']): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../settings.php">设置</a>
                        </li>
                    <?php endif; ?>
                   <li class="nav-item">
                         <a class="nav-link" href="../logout.php">退出登录</a>
                     </li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <h2>创建笔记</h2>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <!-- 显示自动填充提示 -->
                        <?php if (!empty($auto_fill_title)): ?>
                            <div class="auto-fill-notice">
                                ✨ 已智能从笔记助手中自动填充对话内容，请检查并完善信息后提交。
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($create_error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($create_error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">标题</label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?php echo htmlspecialchars($auto_fill_title); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">内容</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required><?php 
                                    echo htmlspecialchars($auto_fill_content); 
                                ?></textarea>
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
                            <!-- 删除图片上传功能 -->
                            <!--
                            <div class="mb-3">
                                <label for="images" class="form-label">上传图片（多张）</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple>
                                <div id="imagePreviewContainer" class="mt-2"></div>
                            </div>
                            -->
                            <!-- 删除文件上传功能 -->
                            <!--
                            <div class="mb-3">
                                <label for="files" class="form-label">上传文件（多个）</label>
                                <input type="file" class="form-control" id="files" name="files[]" multiple>
                            </div>
                            -->
                            <button type="submit" class="btn btn-primary">创建笔记</button>
                            <!-- 修复：跳转到上级目录的index.php -->
                            <a href="index.html" class="btn btn-primary">返回</a>
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