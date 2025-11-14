<?php
session_start();
include('../includes/config.php');

// ===== 网站访问统计（移到主入口）=====
$visit_file = '../data/visits.json';
if (!file_exists(dirname($visit_file))) {
    mkdir(dirname($visit_file), 0755, true);
}

// 读取统计
$visits = ['total' => 0, 'today' => 0, 'last_date' => date('Y-m-d')];
if (file_exists($visit_file)) {
    $visits = json_decode(file_get_contents($visit_file), true) ?? $visits;
}

// 更新统计
$today = date('Y-m-d');
if ($visits['last_date'] !== $today) {
    $visits['today'] = 0;
    $visits['last_date'] = $today;
}

// 防止重复计数（基于session）
if (!isset($_SESSION['visited_today'])) {
    $visits['total']++;
    $visits['today']++;
    $_SESSION['visited_today'] = true;
    file_put_contents($visit_file, json_encode($visits));
}

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

// 获取当前分类ID
$categoryId = $_GET['category_id'] ?? null;

// 获取搜索关键词
$searchKeyword = $_GET['search'] ?? '';

// 获取笔记
$notes = [];
try {
    $query = "SELECT * FROM knowledge_notes";
    $params = [];
    $conditions = [];

    if ($categoryId) {
        $conditions[] = "category_id = ?";
        $params[] = $categoryId;
    }

    if (!empty($searchKeyword)) {
        $conditions[] = "title LIKE ? OR content LIKE ?";
        $params[] = "%{$searchKeyword}%";
        $params[] = "%{$searchKeyword}%";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $notes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to fetch notes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
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
        
        /* ========== 完全复制回收站的按钮样式 ========== */
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
            /* 完全复制回收站的媒体查询 */
            .toggle-sidebar {
                display: block;
            }
            .main-content.active {
                margin-left: 200px;
            }
        }
		/* 添加访问统计提示 */
		        .visit-stats {
		            position: fixed;
		            bottom: 10px;
		            right: 10px;
		            background: rgba(0,0,0,0.7);
		            color: white;
		            padding: 5px 10px;
		            border-radius: 5px;
		            font-size: 12px;
		            z-index: 1000;
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
        .note-image {
            max-height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .note-file {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar" id="sidebar">
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
                        <a class="nav-link" href="../desp/index.php" onclick="openAIAndClose()">AI智能笔记</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../settings.php">设置</a>
                        </li>
                    <?php endif; ?>
                     <li class="nav-item">
                         <a class="nav-link" href="../logout.php">退出登录</a>
                     </li>
                </ul>
            </div>
            <div class="col-md-10 main-content" id="mainContent">
                <h2>知识库</h2>
                
                <!-- ========== 按钮移到main-content内部，使用left: 85% ========== -->
                <button class="toggle-sidebar d-lg-none" onclick="toggleSidebar()" style="left: 85%;" id="an">☰</button>
                <br/>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="mb-0">所有笔记</h3>
                                <div class="btn-group">
                                    <a href="create.php" class="btn btn-primary">新建笔记</a>
                                    <a href="categories.php" class="btn btn-secondary">管理分类</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <form method="GET" action="index.php" class="d-flex">
                                        <input type="text" class="form-control me-2" name="search" placeholder="输入关键词，关键内容等待1秒自动搜索" value="<?php echo htmlspecialchars($searchKeyword); ?>" id="searchInput">
                                    </form>
                                </div>
                                <div class="mb-3">
                                    <label for="categoryFilter" class="form-label">选择分类</label>
                                    <select class="form-select" id="categoryFilter" onchange="filterNotes()">
                                        <option value="">全部分类</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo isset($categoryId) && $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row" id="notesContainer">
                                    <?php foreach ($notes as $note): ?>
                                        <div class="col-md-12 mb-4">
                                            <div class="card note-card">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <span><?php echo htmlspecialchars($note['title']); ?></span>
                                                    <small class="text-muted"><?php echo $note['created_at']; ?></small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text"><?php echo htmlspecialchars($note['content']); ?></p>
                                                    <?php if (!empty($note['images'])): ?>
                                                        <?php foreach (json_decode($note['images'], true) as $image): ?>
                                                            <img src="<?php echo $image; ?>" alt="笔记图片" class="img-fluid note-image">
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($note['files'])): ?>
                                                        <div class="mt-2">
                                                            <?php foreach (json_decode($note['files'], true) as $file): ?>
                                                                <a href="<?php echo htmlspecialchars($file); ?>" class="btn btn-sm btn-outline-primary note-file" download>下载文件</a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between mt-2">
                                                        <small class="text-muted">创建者: 
                                                            <?php
                                                            if (isset($note['user_id'])) {
                                                                $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                                                                $userStmt->execute([$note['user_id']]);
                                                                $user = $userStmt->fetch();
                                                                echo htmlspecialchars($user['username']);
                                                            } else {
                                                                echo "未知";
                                                            }
                                                            ?>
                                                        </small>
                                                        <div>
                                                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $note['user_id']): ?>
                                                                <a href="edit.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                                                                <a href="delete.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除此笔记吗？')">移入回收站</a>
                                                            <?php endif; ?>
                                                            <a href="view.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-secondary">浏览</a>
                                                            <!-- 添加收藏按钮 -->
                                                            <form method="POST" action="../favorites.php" style="display: inline;">
                                                                <input type="hidden" name="note_id" value="<?php echo $note['id']; ?>">
                                                                <?php
                                                                // 检查当前笔记是否已被收藏
                                                                $stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND note_id = ?");
                                                                $stmt->execute([$_SESSION['user_id'], $note['id']]);
                                                                $isFavorite = $stmt->fetch();
                                                                ?>
                                                                <input type="hidden" name="action" value="<?php echo $isFavorite ? 'remove_favorite' : 'add_favorite'; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <?php echo $isFavorite ? '取消收藏' : '收藏'; ?>
                                                                </button>
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
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // AI链接点击处理函数
        function openAIAndClose() {
            // 在新标签页中打开AI界面
            window.open('../desp/index.php', '_blank');
            
            // 尝试关闭当前窗口
            window.opener = null;
            window.open('', '_self');
            window.close();
            
            return false; // 阻止默认链接行为
        }

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

        function filterNotes() {
            const categoryId = document.getElementById('categoryFilter').value;
            const searchKeyword = document.getElementById('searchInput')?.value ?? '';
            window.location.href = 'index.php?category_id=' + categoryId + '&search=' + encodeURIComponent($searchKeyword);
        }

        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchKeyword = this.value;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const categoryId = document.getElementById('categoryFilter').value;
                window.location.href = 'index.php?category_id=' + categoryId + '&search=' + encodeURIComponent(searchKeyword);
            }, 400);
        });
    </script>
	<!-- 显示访问统计（可选，仅管理员可见） -->
	<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
	<div class="visit-stats">
	    今日: <?php echo number_format($visits['today']); ?> | 总计: <?php echo number_format($visits['total']); ?>
	</div>
	<?php endif; ?>
</body>
</html>