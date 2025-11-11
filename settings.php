<?php
session_start();
include('includes/config.php');

// 检查用户是否登录且是管理员角色
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 获取未审批用户
$unapprovedUsers = [];
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE approved = 0 ORDER BY created_at");
    $unapprovedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to fetch unapproved users: " . $e->getMessage());
}

// 处理用户审批
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_user'])) {
    $userId = $_POST['user_id'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET approved = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        header("Location: settings.php");
        exit();
    } catch (Exception $e) {
        error_log("Failed to approve user: " . $e->getMessage());
    }
}

// 获取所有用户
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to fetch users: " . $e->getMessage());
}

// 获取所有笔记
$notes = [];
try {
    $stmt = $pdo->query("SELECT * FROM knowledge_notes ORDER BY created_at");
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link href="css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="http://10.92.169.234:8800/admin/down.php/a6c2ff793b37e00796a7f0d1624131ad.ico  )" type="image/x-icon">
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
            .section-header {
                font-size: 1.5rem;
                margin-top: 20px;
                margin-bottom: 15px;
                border-bottom: 1px solid #dee2e6;
                padding-bottom: 5px;
            }
            .table-container {
                overflow-x: auto;
                margin-bottom: 15px;
            }
            table {
                min-width: 600px;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
            }
            .form-group select,
            .form-group input {
                width: 100%;
                padding: 8px;
                box-sizing: border-box;
            }
            .btn-group {
                margin-top: 10px;
            }
            .btn {
                padding: 8px 12px;
                margin-right: 5px;
                margin-bottom: 5px;
            }
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
                    <li class="nav-item active">
                        <a class="nav-link" href="recycle.php">回收站</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="favorites.php">收藏</a>
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
                        <a class="nav-link" href="../logout.php">退出登录</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-10 main-content">
                <h2>系统设置</h2>
				<button class="toggle-sidebar d-lg-none" onclick="toggleSidebar()" style="left: 85%;" id="an">☰</button>
				<br/>
                <!-- 用户审批区域 -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h3 class="section-header">用户审批</h3>
                        <div class="table-container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>用户名</th>
                                        <th>邮箱</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unapprovedUsers as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo $user['created_at']; ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="approve_user" value="1">
                                                    <button type="submit" class="btn btn-sm btn-primary">审批</button>
                                                </form>
                                                <form method="POST" action="admin/delete_user.php?id=<?php echo $user['id']; ?>" style="display: inline;">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('确定拒绝此用户吗？')">拒绝</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 用户管理区域 -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h3 class="section-header">用户管理</h3>
                        <div class="table-container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>用户名</th>
                                        <th>邮箱</th>
                                        <th>角色</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo $user['role']; ?></td>
                                            <td><?php echo $user['created_at']; ?></td>
                                            <td>
                                                <a href="admin/edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <a href="admin/delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定删除此用户吗？')">删除</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 笔记管理区域 -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h3 class="section-header">笔记管理</h3>
                        <div class="table-container">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>标题</th>
                                        <th>内容</th>
                                        <th>图片路径</th>
                                        <th>文件路径</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notes as $note): ?>
                                        <tr>
                                            <td><?php echo $note['id']; ?></td>
                                            <td><?php echo htmlspecialchars($note['title']); ?></td>
                                            <td><?php echo htmlspecialchars($note['content']); ?></td>
                                            <td><?php echo $note['image_path']; ?></td>
                                            <td><?php echo $note['file_path']; ?></td>
                                            <td><?php echo $note['created_at']; ?></td>
                                            <td>
                                                <a href="admin/edit_note.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                                                <form method="POST" action="admin/delete_note.php?id=<?php echo $note['id']; ?>" style="display: inline;">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除此笔记吗？')">删除</button>
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
	
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
       function isMobile() {
         return /Mobi|Android|iPhone/i.test(navigator.userAgent);
       }
       isMobile()
       if(isMobile()==true){
       	document.querySelector('#an').style.display = 'block'
       }else{
       	document.querySelector('#an').style.display = 'none'
       }
       //这里结束
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