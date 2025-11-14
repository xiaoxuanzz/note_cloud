<?php
session_start();
include('../includes/config.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php"); exit();
}
$notes=[];
try{
    $notes=$pdo->query("SELECT n.*, u.username AS author FROM knowledge_notes n LEFT JOIN users u ON n.user_id=u.id ORDER BY n.created_at DESC")->fetchAll();
}catch(Exception $e){ error_log("Fetch notes failed: ".$e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>笔记管理 - PZIOT笔记网</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar{width:250px;background:#343a40;min-height:100vh;position:fixed;padding:20px 0;transition:transform .3s;z-index:1000;}
        .sidebar.collapsed{transform:translateX(-250px);}
        .sidebar .nav-link{color:#dfe6e9;padding:12px 20px;display:block;transition:all .3s;}
        .sidebar .nav-link:hover{background:#485460;color:white;}
        .sidebar .nav-link.active{background:#6c7ae0;color:white;}
        .main-content{margin-left:250px;padding:30px;transition:margin-left .3s;}
        .main-content.collapsed{margin-left:0;}
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
        <li class="nav-item"><a class="nav-link" href="index.php">📊 数据统计</a></li>
        <li class="nav-item"><a class="nav-link" href="users.php">👥 用户管理</a></li>
        <li class="nav-item"><a class="nav-link active" href="notes.php">📝 笔记管理</a></li>
        <li class="nav-item"><a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a></li>
    </ul>
</div>

<div class="main-content" id="mainContent">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>笔记管理</h2>
        <a href="../knowledge/create.php" class="btn btn-primary">➕ 新建笔记</a>
    </div>

    <?php if(isset($_SESSION['message'])):?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?=$_SESSION['message']?><?php unset($_SESSION['message']);?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif;?>

    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead class="table-dark"><tr><th>ID</th><th>标题</th><th>作者</th><th>创建时间</th></tr></thead>
            <tbody>
            <?php foreach($notes as $n):?>
                <tr>
                    <td><?=$n['id']?></td><td><?=htmlspecialchars($n['title'])?></td><td><?=htmlspecialchars($n['author']??'未知')?></td><td><?=date('Y-m-d H:i',strtotime($n['created_at']))?></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
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
