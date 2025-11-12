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

// 获取自动填充数据
$auto_fill_title = isset($_POST['chat_title']) ? $_POST['chat_title'] : '';
$auto_fill_content = isset($_POST['chat_content']) ? $_POST['chat_content'] : '';

// 处理创建笔记
$create_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['chat_title'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = $_POST['category_id'] ?? null;

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
                json_encode([]),
                json_encode([])
            ]);
            header("Location: index.php");  // ✅ 修复：直接跳转到同级index.php
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ✅ 修复：使用相对路径或移除 -->
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
        .main-content {
            margin-left: 200px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-200px); }
            .main-content { margin-left: 0; }
            .sidebar.active { transform: translateX(0); }
            .main-content.active { margin-left: 200px; }
        }
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
    <!-- ✅ 修复：添加 container-fluid -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item active">
                        <!-- ✅ 修复：改为 index.php -->
                        <a class="nav-link" href="index.php">知识库</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../recycle.php">回收站</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../favorites.php">收藏</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php" target="_blank">AI智能笔记</a>
                    </li>
                   <li class="nav-item">
                         <a class="nav-link" href="../logout.php">退出登录</a>
                     </li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <h2>创建笔记</h2>
                
                <!-- ✅ 自动填充提示 -->
                <?php if (!empty($auto_fill_title)): ?>
                    <div class="auto-fill-notice">
                        ✨ 已智能从笔记助手中自动填充对话内容
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($create_error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($create_error); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="mt-4">
                    <div class="mb-3">
                        <label for="title" class="form-label">标题 *</label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($auto_fill_title); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">内容 *</label>
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
                    <button type="submit" class="btn btn-primary">创建笔记</button>
                    <a href="index.php" class="btn btn-secondary">返回</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 移动端侧边栏交互（简化版）
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            
            // 点击链接收起侧边栏
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                        document.querySelector('.main-content').classList.remove('active');
                    }
                });
            });
        });

        // 简化切换函数
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        }
    </script>
</body>
</html>