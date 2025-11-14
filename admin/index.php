<?php
session_start();
include('../includes/config.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php"); exit();
}
// 统计
try{
    $user_count   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $note_count   = $pdo->query("SELECT COUNT(*) FROM knowledge_notes")->fetchColumn();
    $pending_users= $pdo->query("SELECT COUNT(*) FROM users WHERE approved = 0")->fetchColumn();
    $today_notes  = $pdo->query("SELECT COUNT(*) FROM knowledge_notes WHERE DATE(created_at) = CURDATE()")->fetchColumn();
}catch(Exception $e){ $user_count=$note_count=$pending_users=$today_notes=0; }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar{width:250px;background:#343a40;min-height:100vh;position:fixed;padding:20px 0;transition:transform .3s;z-index:1000;}
        .sidebar.collapsed{transform:translateX(-250px);}
        .sidebar .nav-link{color:#dfe6e9;padding:12px 20px;display:block;transition:all .3s;}
        .sidebar .nav-link:hover{background:#485460;color:white;}
        .sidebar .nav-link.active{background:#6c7ae0;color:white;}
        .main-content{margin-left:250px;padding:30px;transition:margin-left .3s;}
        .main-content.collapsed{margin-left:0;}
        .stat-card{transition:transform .3s;}.stat-card:hover{transform:translateY(-5px);}
        /* ===== 三按钮：默认隐藏 + 手机右侧 ===== */
        .toggle-sidebar{position:fixed;top:10px;right:10px;z-index:1001;background:#343a40;color:white;border:none;width:40px;height:40px;border-radius:50%;display:none;align-items:center;justify-content:center;font-size:20px;box-shadow:0 2px 5px rgba(0,0,0,.2);}
        @media (max-width:768px){
            .toggle-sidebar{display:flex!important;}
            .sidebar{transform:translateX(-250px);}
            .sidebar.active{transform:translateX(0);}
            .main-content{margin-left:0;}
            .main-content.active{margin-left:250px;}
        }
    </style>
</head>
<body>
<button class="toggle-sidebar" onclick="toggleSidebar()">☰</button>

<div class="sidebar" id="sidebar">
    <h4 class="text-white text-center mb-4">PZIOT 管理系统</h4>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link active" href="index.php">📊 数据统计</a></li>
        <li class="nav-item"><a class="nav-link" href="users.php">👥 用户管理</a></li>
        <li class="nav-item"><a class="nav-link" href="notes.php">📝 笔记管理</a></li>
        <li class="nav-item"><a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a></li>
    </ul>
</div>

<div class="main-content" id="mainContent">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>管理面板</h1>
        <span class="badge bg-primary">管理员: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-primary text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $user_count; ?></h2><p>总用户数</p></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-success text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $note_count; ?></h2><p>总笔记数</p></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-warning text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $pending_users; ?></h2><p>等待审核</p></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-info text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $today_notes; ?></h2><p>今日新增</p></div></div></div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar(){
    const s=document.getElementById('sidebar'), m=document.getElementById('mainContent');
    s.classList.toggle('active'); m.classList.toggle('active');
}
document.addEventListener('DOMContentLoaded',()=>{
    document.addEventListener('click',e=>{
        const s=document.getElementById('sidebar'), b=document.querySelector('.toggle-sidebar');
        if(window.innerWidth<=768 && !s.contains(e.target) && !b.contains(e.target) && s.classList.contains('active')){
            s.classList.remove('active'); m.classList.remove('active');
        }
    });
});
document.querySelectorAll('.sidebar .nav-link').forEach(l=>{
    l.addEventListener('click',()=>{
        if(window.innerWidth<=768){ s.classList.remove('active'); m.classList.remove('active'); }
    })
});
</script>
</body>
</html>
