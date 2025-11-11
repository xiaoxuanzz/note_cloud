<?php
session_start();
include('includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 处理收藏和取消收藏的请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    $noteId = $_POST['note_id'];
    if (isset($_POST['action']) && $_POST['action'] === 'add_favorite') {
        try {
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, note_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $noteId]);
        } catch (Exception $e) {
            error_log("Failed to add favorite: " . $e->getMessage());
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
        try {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND note_id = ?");
            $stmt->execute([$_SESSION['user_id'], $noteId]);
        } catch (Exception $e) {
            error_log("Failed to remove favorite: " . $e->getMessage());
        }
    }
    header("Location: favorites.php");
    exit();
}

// 获取收藏夹中的笔记
$favoriteNotes = [];
try {
    $stmt = $pdo->prepare("SELECT kn.* FROM knowledge_notes kn 
                           INNER JOIN favorites f ON kn.id = f.note_id 
                           WHERE f.user_id = ? 
                           ORDER BY kn.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $favoriteNotes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to fetch favorite notes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>收藏夹</title>
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
        .note-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .note-card:hover {
            transform: translateY(-5px);
        }
        .note-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .note-card .card-body {
            padding: 20px;
        }
    </style>
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="knowledge/index.php">知识库</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recycle.php">回收站</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link active" href="favorites.php">收藏</a>
                    </li>
					<li class="nav-item">
					    <a class="nav-link" href="desp/index.html" target="_blank" rel="noopener noreferrer">AI智能笔记</a>
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
            <div class="col-md-10 main-content">
				<h2>收藏夹</h2>
				<!-- 按钮控件-->
				<button class="toggle-sidebar d-lg-none" onclick="toggleSidebar()" style="left: 85%;" id="an">☰</button>
				<br/>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="mb-0">收藏的笔记</h3>
                            </div>
                            <div class="card-body">
                                <div class="row" id="notesContainer">
                                    <?php if (empty($favoriteNotes)): ?>
                                        <div class="col-md-12">
                                            <div class="card note-card">
                                                <div class="card-body text-center">
                                                    <p class="card-text">还没有收藏的笔记哦</p>
                                                    <p class="card-text">浏览知识库并收藏您喜欢的笔记~</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($favoriteNotes as $note): ?>
                                            <div class="col-md-12 mb-4">
                                                <div class="card note-card">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <span><?php echo htmlspecialchars($note['title']); ?></span>
                                                        <small class="text-muted"><?php echo $note['created_at']; ?></small>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="card-text"><?php echo htmlspecialchars($note['content']); ?></p>
                                                        <?php
                                                        $hasImages = !empty($note['images']) && json_decode($note['images'], true);
                                                        $hasFiles = !empty($note['files']) && json_decode($note['files'], true);
                                                        if ($hasImages || $hasFiles): ?>
                                                            <p class="text-muted">（含图片或文件）</p>
                                                        <?php endif; ?>
                                                        <div class="d-flex justify-content-between mt-2">
                                                            <div>
                                                                <a href="knowledge/favories_view.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-secondary">浏览</a>
                                                                <form method="POST" action="favorites.php" style="display: inline;">
                                                                    <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                                                    <input type="hidden" name="action" value="remove_favorite">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">取消收藏</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
    </script>
</body>
</html>