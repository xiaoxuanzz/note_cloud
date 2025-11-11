<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? null;

// 检查笔记是否存在并属于当前用户
$stmt = $pdo->prepare("SELECT * FROM knowledge_notes WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$note = $stmt->fetch();

if (!$note) {
    die("笔记不存在或您无权限操作");
}

// 将笔记移动到回收站
$stmt = $pdo->prepare("UPDATE knowledge_notes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php");
exit();