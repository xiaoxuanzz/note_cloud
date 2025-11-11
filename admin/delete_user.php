<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id || $id == $_SESSION['user_id']) {
    die("无法删除当前用户");
}

try {
    // 检查是否是admin用户
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if ($user && $user['username'] === 'admin') {
        die("不能删除超级管理员");
    }
    
    // 删除用户及其笔记
    $pdo->beginTransaction();
    
    // 删除用户的笔记及关联文件
    $stmt = $pdo->prepare("SELECT image_path, file_path FROM knowledge_notes WHERE user_id = ?");
    $stmt->execute([$id]);
    $notes = $stmt->fetchAll();
    
    foreach ($notes as $note) {
        if (!empty($note['image_path']) && file_exists($note['image_path'])) {
            unlink($note['image_path']);
        }
        if (!empty($note['file_path']) && file_exists($note['file_path'])) {
            unlink($note['file_path']);
        }
    }
    
    // 删除笔记
    $stmt = $pdo->prepare("DELETE FROM knowledge_notes WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // 删除用户
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    $_SESSION['message'] = '用户及其所有笔记已删除！';
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Failed to delete user: " . $e->getMessage());
    $_SESSION['error'] = '删除用户失败';
}

header("Location: users.php");
exit();