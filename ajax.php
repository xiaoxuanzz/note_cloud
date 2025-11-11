<?php
include('includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => '未授权']);
    exit();
}

// 获取AJAX请求的类型和数据
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save_note':
        $id = $_POST['id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        
        if ($id) {
            // 更新笔记
            $stmt = $pdo->prepare("UPDATE knowledge_notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $id, $_SESSION['user_id']]);
            echo json_encode(['status' => '更新成功']);
        } else {
            // 创建笔记
            $stmt = $pdo->prepare("INSERT INTO knowledge_notes (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $content]);
            echo json_encode(['status' => '创建成功', 'id' => $pdo->lastInsertId()]);
        }
        break;
    case 'delete_note':
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("UPDATE knowledge_notes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        echo json_encode(['status' => '删除成功']);
        break;
    case 'get_note':
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM knowledge_notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$note) {
            echo json_encode(['error' => '笔记不存在']);
        } else {
            echo json_encode(['title' => $note['title'], 'content' => $note['content'], 'image_path' => $note['image_path'], 'file_path' => $note['file_path']]);
        }
        break;
    case 'upload_image':
        if (!empty($_FILES['image']['name'])) {
            $target_dir = 'uploads/images/';
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // 允许的文件类型
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowed_types)) {
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    echo json_encode(['status' => '上传成功', 'image_path' => $target_file]);
                } else {
                    echo json_encode(['error' => '图片上传失败']);
                }
            } else {
                echo json_encode(['error' => '只允许 JPG, JPEG, PNG, GIF 格式']);
            }
        } else {
            echo json_encode(['error' => '未上传图片']);
        }
        break;
    case 'upload_file':
        if (!empty($_FILES['file']['name'])) {
            $target_dir = 'uploads/files/';
            $target_file = $target_dir . basename($_FILES["file"]["name"]);
            $file_type = pathinfo($target_file, PATHINFO_EXTENSION);

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                echo json_encode(['status' => '上传成功', 'file_path' => $target_file]);
            } else {
                echo json_encode(['error' => '文件上传失败']);
            }
        } else {
            echo json_encode(['error' => '未上传文件']);
        }
        break;
    default:
        echo json_encode(['error' => '未知操作']);
}