<?php
session_start();
include('includes/config.php');

// 检查用户是否登录且是管理员角色
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // 未授权用户跳转到登录页
    header("Location: ../login.php");
    exit();
}

// 验证通过，直接跳转到管理员首页
header("Location: admin/index.php");
exit();