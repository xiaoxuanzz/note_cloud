<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录且是管理员角色
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

$id = $_GET['id'] ?? null;

// 检查用户是否存在
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("用户不存在");
}

// 删除用户
try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: users.php");
    exit();
} catch (Exception $e) {
    error_log("Failed to delete user: " . $e->getMessage());
    die("删除用户失败");
}