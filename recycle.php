<?php
session_start();
include('includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 获取回收站中的笔记（修复：添加错误处理和权限判断）
$recycleNotes = [];
try {
    // 如果是管理员，查看所有回收站笔记；否则只查看自己的
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $stmt = $pdo->query("SELECT * FROM knowledge_notes WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM knowledge_notes WHERE user_id = ? AND deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $recycleNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("回收站查询失败: " . $e->getMessage());
    $recycleNotes = [];
}

// 处理恢复笔记（修复：添加权限检查）
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore'])) {
    $noteId = $_POST['note_id'];
    try {
        if ($_SESSION['role'] === 'admin') {
            $stmt = $pdo->prepare("UPDATE knowledge_notes SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$noteId]);
        } else {
            $stmt = $pdo->prepare("UPDATE knowledge_notes SET deleted_at = NULL WHERE id = ? AND user_id = ?");
            $stmt->execute([$noteId, $_SESSION['user_id']]);
        }
        header("Location: recycle.php");
        exit();
    } catch (Exception $e) {
        error_log("恢复笔记失败: " . $e->getMessage());
    }
}

// 处理彻底删除笔记（修复：添加权限检查）
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete-permanent'])) {
    $noteId = $_POST['note_id'];
    try {
        if ($_SESSION['role'] === 'admin') {
            $stmt = $pdo->prepare("DELETE FROM knowledge_notes WHERE id = ?");
            $stmt->execute([$noteId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM knowledge_notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$noteId, $_SESSION['user_id']]);
        }
        header("Location: recycle.php");
        exit();
    } catch (Exception $e) {
        error_log("彻底删除失败: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>回收站 - PZIOT笔记网</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
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
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .toggle-sidebar {
                display: block;
            }
            .main-content.active {
                margin-left: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar" id="sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="knowledge/index.php">知识库</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link active" href="recycle.php">回收站</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="favorites.php">收藏</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="desp/index.html" target="_blank">AI智能笔记</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">设置</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">退出登录</a>
                    </li>
                </ul>
            </div>

            <div class="col-md-10 main-content" id="mainContent">
                <h2>回收站</h2>
                <button class="toggle-sidebar d-lg-none" onclick="toggleSidebar()" style="left: 85%;" id="an">☰</button>
                <br/>
                
                <div class="row mt-4">
                    <?php foreach ($recycleNotes as $note): ?>
                        <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($note['title']); ?></span>
                                    <small class="text-muted">删除时间: <?php echo $note['deleted_at']; ?></small>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo htmlspecialchars($note['content']); ?></p>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <a href="knowledge/recycle_view.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-secondary">查看</a>
                                        </div>
                                        <div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                                <button type="submit" name="restore" class="btn btn-sm btn-outline-primary">恢复</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                                <button type="submit" name="delete-permanent" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定永久删除此笔记吗？')">永久删除</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('active');
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