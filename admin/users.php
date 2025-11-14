<?php
session_start();
include('../includes/config.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php"); exit();
}
$users=[];
try{ $users=$pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(); }
catch(Exception $e){ error_log("Fetch users failed: ".$e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - PZIOT笔记网</title>
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
        <li class="nav-item"><a class="nav-link active" href="users.php">👥 用户管理</a></li>
        <li class="nav-item"><a class="nav-link" href="notes.php">📝 笔记管理</a></li>
        <li class="nav-item"><a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a></li>
    </ul>
</div>

<div class="main-content" id="mainContent">
    <h2 class="mb-4">用户管理</h2>
    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead class="table-dark"><tr><th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead>
            <tbody>
            <?php foreach($users as $u):
                $status=$u['approved']?'<span class="badge bg-success">已审批</span>':'<span class="badge bg-warning text-dark">待审核</span>'; ?>
                <tr>
                    <td><?=$u['id']?></td><td><?=htmlspecialchars($u['username'])?></td><td><?=htmlspecialchars($u['email'])?></td>
                    <td><?=$u['role']==='admin'?'<span class="badge bg-danger">管理员</span>':'<span class="badge bg-secondary">普通用户</span>'?></td>
                    <td><?=$status?></td><td><?=date('Y-m-d H:i',strtotime($u['created_at']))?></td>
                    <td>
                        <a href="edit_user.php?id=<?=$u['id']?>" class="btn btn-sm btn-outline-primary">编辑</a>
                        <?php if($u['username']!=='admin' && $u['id']!=$_SESSION['user_id']):?>
                            <a href="delete_user.php?id=<?=$u['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定删除此用户吗？此操作不可恢复！')">删除</a>
                        <?php endif;?>
                    </td>
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
