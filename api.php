
<?php
include('includes/config.php');

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => '未授权']);
    exit();
}

// 获取请求方法和路径
$request_method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 路由处理
switch ($path) {
    case '/api/notes':
        if ($request_method === 'GET') {
            // 获取笔记列表
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($notes);
        } elseif ($request_method === 'POST') {
            // 创建笔记
            $data = json_decode(file_get_contents('php://input'), true);
            $title = $data['title'];
            $content = $data['content'];
            
            $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $content]);
            
            echo json_encode(['id' => $pdo->lastInsertId()]);
        }
        break;
    case '/api/notes/' . end(explode('/', trim($path, '/'))):
        $id = end(explode('/', trim($path, '/')));
        if ($request_method === 'GET') {
            // 获取单个笔记
            $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$note) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(['error' => '笔记不存在']);
            } else {
                echo json_encode($note);
            }
        } elseif ($request_method === 'PUT') {
            // 更新笔记
            $data = json_decode(file_get_contents('php://input'), true);
            $title = $data['title'];
            $content = $data['content'];
            
            $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $id, $_SESSION['user_id']]);
            
            echo json_encode(['status' => '更新成功']);
        } elseif ($request_method === 'DELETE') {
            // 删除笔记
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            
            echo json_encode(['status' => '删除成功']);
        }
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        echo json_encode(['error' => 'API未找到']);
}