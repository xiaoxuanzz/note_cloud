<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../knowledge/index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("无效的笔记ID");
}

try {
    // 先获取笔记信息以删除文件
    $stmt = $pdo->prepare("SELECT image_path, file_path FROM knowledge_notes WHERE id = ?");
    $stmt->execute([$id]);
    $note = $stmt->fetch();
    
    if ($note) {
        // 删除关联的文件
        if (!empty($note['image_path']) && file_exists($note['image_path'])) {
            unlink($note['image_path']);
        }
        if (!empty($note['file_path']) && file_exists($note['file_path'])) {
            unlink($note['file_path']);
        }
        
        // 删除笔记记录
        $stmt = $pdo->prepare("DELETE FROM knowledge_notes WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = '笔记删除成功！';
    }
} catch (Exception $e) {
    error_log("Failed to delete note: " . $e->getMessage());
    $_SESSION['error'] = '删除笔记失败';
}

header("Location: notes.php");
exit();