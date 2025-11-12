<?php
session_start();
include('../includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 开启错误显示（开发环境）
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;

if (!$id) {
    die("❌ 错误：无效的笔记ID");
}

// 检查笔记是否存在
$stmt = $pdo->prepare("SELECT * FROM knowledge_notes WHERE id = ?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    die("❌ 笔记不存在");
}

// 权限验证：必须是管理员或笔记所有者
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isOwner = $note['user_id'] == $_SESSION['user_id'];

if (!$isAdmin && !$isOwner) {
    die("❌ 您无权操作此笔记");
}

// 检查数据库字段是否存在
try {
    // 验证 deleted_at 字段是否存在
    $pdo->query("SELECT deleted_at FROM knowledge_notes LIMIT 1");
} catch (Exception $e) {
    die("❌ 数据库错误：deleted_at 字段不存在，请运行升级脚本");
}

// 将笔记移动到回收站
try {
    $stmt = $pdo->prepare("UPDATE knowledge_notes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$id]);
    
    // 检查是否更新成功
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = '✅ 笔记已移动到回收站';
    } else {
        $_SESSION['error'] = '❌ 操作失败，请重试';
    }
} catch (Exception $e) {
    error_log("回收站操作失败: " . $e->getMessage());
    die("❌ 系统错误: " . $e->getMessage());
}

header("Location: index.php");
exit();