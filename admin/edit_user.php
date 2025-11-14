<?php
session_start();
include('../includes/config.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php"); exit();
}
$id=$_GET['id']??null; if(!$id) die('无效的用户ID');
try{
    $stmt=$pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$id]); $user=$stmt->fetch();
    if(!$user) die('用户不存在');
}catch(Exception $e){ die('获取用户信息失败: '.$e->getMessage()); }

$update_error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username=trim($_POST['username']??''); $email=trim($_POST['email']??''); $role=$_POST['role']??'user'; $approved=isset($_POST['approved'])?1:0;
    if(empty($username)||empty($email)) $update_error='用户名和邮箱不能为空';
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) $update_error='邮箱格式不正确';
    else{
        try{
            $stmt=$pdo->prepare("SELECT COUNT(*) FROM users WHERE username=? AND id!=?");
            $stmt->execute([$username,$id]);
            if($stmt->fetchColumn()>0) $update_error='用户名已存在';
            else{
                $stmt=$pdo->prepare("UPDATE users SET username=?,email=?,role=?,approved=? WHERE id=?");
                $stmt->execute([$username,$email,$role,$approved,$id]);
                $_SESSION['message']='用户更新成功！';
                header("Location: users.php"); exit();
            }
        }catch(Exception $e){ $update_error='更新失败: '.$e->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑用户 - PZIOT笔记网</title>
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
        <li class="nav-item"><a class="nav-link" href="notes.php">📝 笔记管理</a></li>
        <li class="nav-item"><a class="nav-link" href="../knowledge/index.php">🚪️ 返回主页</a></li>
    </ul>
</div>

<div class="main-content" id="mainContent">
    <h2>编辑用户</h2>
    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3"><label for="username" class="form-label">用户名 *</label><input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                <div class="mb-3"><label for="email" class="form-label">邮箱 *</label><input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                <div class="mb-3">
                    <label for="role" class="form-label">角色</label>
                    <select class="form-select" id="role" name="role">
                        <option value="user" <?=$user['role']==='user'?'selected':''?>>普通用户</option>
                        <option value="admin" <?=$user['role']==='admin'?'selected':''?>>管理员</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="approved" name="approved" <?=$user['approved']?'checked':''?>><label class="form-check-label" for="approved">已审批（允许登录）</label></div>
                </div>
                <button type="submit" class="btn btn-primary">💾 更新用户</button>
                <a href="users.php" class="btn btn-secondary">取消</a>
                <?php if(!empty($update_error)):?><div class="alert alert-danger mt-3"><?=$update_error?></div><?php endif;?>
            </form>
        </div>
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
