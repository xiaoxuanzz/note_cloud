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

// ===== 读取网站访问统计（从knowledge/index.php生成的文件）=====
$visit_file = '../data/visits.json';
$visits = ['total' => 0, 'today' => 0];
if (file_exists($visit_file)) {
    $visits = json_decode(file_get_contents($visit_file), true) ?? $visits;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 修复侧边栏层级问题 */
        .sidebar{
            width:250px;
            background:#343a40;
            min-height:100vh;
            position:fixed;
            padding:20px 0;
            transition:transform .3s;
            z-index:1050; /* 提高z-index确保在最上层 */
            transform:translateX(-250px);
        }
        .sidebar.active{transform:translateX(0);}
        .sidebar .nav-link{color:#dfe6e9;padding:12px 20px;display:block;transition:all .3s;}
        .sidebar .nav-link:hover{background:#485460;color:white;}
        .sidebar .nav-link.active{background:#6c7ae0;color:white;}
        .main-content{margin-left:0;padding:30px;transition:margin-left .3s;}
        .main-content.active{margin-left:250px;}
        .stat-card{transition:transform .3s;}.stat-card:hover{transform:translateY(-5px);}
        
        /* 修复按钮样式 */
        .toggle-sidebar{
            position:fixed;
            top:10px;
            right:10px;
            z-index:1060; /* 按钮在侧边栏之上 */
            background:#343a40;
            color:white;
            border:none;
            width:40px;
            height:40px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
            box-shadow:0 2px 5px rgba(0,0,0,.2);
        }
        
        /* 移动端遮罩层 */
        .sidebar-overlay{
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,0.5);
            z-index:1040;
            display:none;
        }
        .sidebar-overlay.active{display:block;}
        
        @media (min-width:769px){
            .sidebar{transform:translateX(0);}
            .main-content{margin-left:250px;}
            .toggle-sidebar{display:none!important;}
        }
        @media (max-width:768px){
            .toggle-sidebar{display:flex!important;}
        }
    </style>
</head>
<body>

<!-- 添加遮罩层 -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

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
    <!-- 按钮移到main-content内部 -->
    <button class="toggle-sidebar d-lg-none" onclick="toggleSidebar()" style="left:85%;top:10px;">☰</button>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>管理面板</h1>
        <span class="badge bg-primary">管理员: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-primary text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $user_count; ?></h2><p>总用户数</p></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-success text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $note_count; ?></h2><p>总笔记数</p></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-warning text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $pending_users; ?></h2><p>等待审核</p></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-info text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo $today_notes; ?></h2><p>今日新增</p></div></div></div>
        
        <!-- ===== 新增：显示网站访问统计 ===== -->
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-dark text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo number_format($visits['total']); ?></h2><p>网站总访问量</p></div></div></div>
        <div class="col-md-6 col-lg-3"><div class="card stat-card bg-secondary text-white text-center"><div class="card-body"><h2 class="display-6"><?php echo number_format($visits['today']); ?></h2><p>今日访问量</p></div></div></div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar(){
    const s=document.getElementById('sidebar'), m=document.getElementById('mainContent'), o=document.getElementById('sidebarOverlay');
    s.classList.toggle('active'); 
    m.classList.toggle('active');
    o.classList.toggle('active');
}

// 重写事件处理逻辑
document.addEventListener('DOMContentLoaded',()=>{
    const s=document.getElementById('sidebar'), m=document.getElementById('mainContent'), b=document.querySelector('.toggle-sidebar'), o=document.getElementById('sidebarOverlay');
    
    // 点击遮罩层关闭
    o.addEventListener('click',()=>{
        s.classList.remove('active'); m.classList.remove('active'); o.classList.remove('active');
    });
    
    // 点击空白处关闭
    document.addEventListener('click',e=>{
        if(window.innerWidth<=768 && !s.contains(e.target) && !b.contains(e.target) && s.classList.contains('active')){
            s.classList.remove('active'); m.classList.remove('active'); o.classList.remove('active');
        }
    });
    
    // 点击导航链接关闭
    document.querySelectorAll('.sidebar .nav-link').forEach(l=>{
        l.addEventListener('click',()=>{
            if(window.innerWidth<=768){ 
                s.classList.remove('active'); m.classList.remove('active'); o.classList.remove('active');
            }
        });
    });
    
    // 触摸滑动关闭
    let touchStartX = 0;
    document.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; });
    document.addEventListener('touchend', e => {
        if(window.innerWidth<=768 && s.classList.contains('active')){
            const touchEndX = e.changedTouches[0].screenX;
            if(Math.abs(touchEndX - touchStartX) > 50){
                s.classList.remove('active'); m.classList.remove('active'); o.classList.remove('active');
            }
        }
    });
});
</script>
</body>
</html>