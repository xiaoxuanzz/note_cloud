<?php
session_start();
// 登录检查：未登录则跳转到登录页
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
// 已登录则继续渲染页面，不执行任何跳转
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>笔记助手</title>

    <!-- 代码高亮 -->
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <script src="../js/bootstrap.bundle.min.js"></script>
    
    <!-- Prism.js 代码高亮 -->
    <link href="../css/prism.css" rel="stylesheet" />
    <script src="../js/prism.js"></script>
    <script src="../js/prism-javascript.min.js"></script>

    <style>
        /* ========== 基础重置 & 布局 ========== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            touch-action: pan-y;
        }

        body {
            background: #f5f5f5;
        }

        /* 主容器 */
        .app-container {
            display: flex;
            width: 100%;
            height: 100%;
            position: relative;
        }

        /* ========== 侧边栏 ========== */
        .sidebar {
            width: 320px;
            background: #2c3e50;
            border-right: 1px solid #34495e;
            display: flex;
            flex-direction: column;
            color: #ecf0f1;
            flex-shrink: 0;
            transition: transform 0.3s ease;
            transform: translateX(0);
            z-index: 1000;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
        }
        .sidebar.collapsed {
            transform: translateX(-320px);
        }
        .sidebar-header {
            padding: 20px;
            background: #34495e;
            border-bottom: 1px solid #4a5f7a;
            flex-shrink: 0;
        }
        .sidebar-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #fff;
        }
        .new-chat-btn {
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            font-weight: 500;
            transition: background .2s;
        }
        .new-chat-btn:hover {
            background: #2980b9;
        }
        .storage-info {
            padding: 10px 20px;
            background: #34495e;
            font-size: 12px;
            color: #bdc3c7;
            border-bottom: 1px solid #4a5f7a;
            transition: all .3s ease;
            flex-shrink: 0;
        }
        .storage-info.updating {
            color: #3498db;
            font-weight: bold;
        }
        .history-panel {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            -webkit-overflow-scrolling: touch;
        }
        .history-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-left: 5px;
            color: #bdc3c7;
        }
        .history-item {
            background: #34495e;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid transparent;
        }
        .history-item:hover {
            background: #4a5f7a;
            border-color: #3498db;
        }
        .history-item.active {
            background: #3498db;
            border-color: #2980b9;
        }
        .history-item-content {
            flex: 1;
            overflow: hidden;
        }
        .history-item-title {
            font-weight: 500;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
        }
        .history-item-time {
            font-size: 11px;
            color: #95a5a6;
        }
        .history-item.active .history-item-time {
            color: #ecf0f1;
        }
        .delete-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 11px;
            margin-left: 10px;
            transition: background .2s;
            flex-shrink: 0;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .sidebar-footer {
            padding: 15px;
            background: #34495e;
            border-top: 1px solid #4a5f7a;
            flex-shrink: 0;
        }
        .back-btn {
            background: #95a5a6;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
            font-weight: 500;
            transition: background .2s;
        }
        .back-btn:hover {
            background: #7f8c8d;
        }

        /* ========== 聊天面板 ========== */
        .chat-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: #fff;
            min-width: 0;
            margin-left: 320px;
            width: calc(100% - 320px);
            transition: margin-left 0.3s ease; /* 主内容区域动画 */
        }
        .chat-panel.collapsed {
            margin-left: 0;
        }
        .chat-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #ecf0f1;
            display: flex;
            flex-direction: column;
            gap: 15px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        .chat-input-area {
            display: flex;
            padding: 15px;
            background: #fff;
            border-top: 1px solid #ddd;
            align-items: center;
            flex-shrink: 0;
        }
        .chat-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-right: 10px;
            resize: none;
            font-size: 14px;
            transition: border-color .2s;
            min-height: 50px;
            max-height: 150px;
            font-family: inherit;
        }
        .chat-input:focus {
            outline: none;
            border-color: #3498db;
        }
        .send-button {
            padding: 10px 25px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background .2s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .send-button:hover {
            background: #2980b9;
        }
        .stop-thinking-btn {
            margin-left: 8px;
            padding: 6px 12px;
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background .2s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .stop-thinking-btn:hover {
            background: #c0392b;
        }

        /* ========== 消息气泡 ========== */
        .message {
            margin: 8px 0;
            display: flex;
            align-items: flex-start;
            animation: fadeIn .3s ease-in;
        }
        .user-message {
            justify-content: flex-end;
        }
        .bot-message {
            justify-content: flex-start;
        }
        .message-content {
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 18px;
            line-height: 1.5;
            word-break: break-word;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
            transition: all .2s ease;
        }
        .user-message .message-content {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .bot-message .message-content {
            background: #fff;
            color: #2c3e50;
            border: 1px solid #e5e5e5;
            border-bottom-left-radius: 4px;
        }

        /* ========== 特殊消息类型 ========== */
        .typing-indicator {
            color: #7f8c8d;
            font-style: italic;
            display: inline-flex;
            align-items: center;
        }
        .typing-indicator::after {
            content: '';
            animation: typing 1.5s infinite;
        }
        @keyframes typing {
            0% { content: '.'; }
            33% { content: '..'; }
            66% { content: '...'; }
            100% { content: '.'; }
        }
        .empty-state {
            text-align: center;
            color: #95a5a6;
            padding: 40px;
            font-style: italic;
        }
        .error-message {
            background: #fee !important;
            border-color: #fcc !important;
            color: #c33 !important;
        }

        /* ========== 代码块样式 ========== */
        .code-block-wrapper {
            position: relative;
            margin: 8px 0;
            max-width: calc(100% - 4px);
            box-sizing: border-box;
        }
        pre[class*="language-"] {
            margin: 0;
            border-radius: 6px;
            width: 100%;
            overflow-x: auto;
            white-space: pre;
            word-break: normal;
            background: #2d2d2d;
        }

        /* ========== 操作按钮 ========== */
        .message-actions {
            text-align: right;
            margin-top: 5px;
        }
        .bot-message .message-actions {
            text-align: left;
        }
        .create-note-btn {
            background: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: background .2s;
            margin-top: 8px;
        }
        .create-note-btn:hover {
            background: #27ae60;
        }
        
        /* ========== 移动端侧边栏控制 ========== */
        .toggle-sidebar {
            position: absolute; /* 关键：在chat-panel内绝对定位 */
            top: 10px;
            left: 85%; /* 右上角位置 */
            z-index: 1001;
            background-color: #2c3e50;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none; /* 默认隐藏，移动端显示 */
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-320px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .chat-panel {
                margin-left: 0;
                width: 100%;
            }
            .chat-panel.active {
                margin-left: 320px; /* 主内容向右移动 */
            }
            .toggle-sidebar {
                display: block; /* 仅在移动端显示 */
            }
            
            /* 移动端优化 */
            .message-content {
                max-width: 85%;
                font-size: 13px;
            }
            .chat-input-area {
                padding: 10px;
            }
            .chat-input {
                font-size: 13px;
                padding: 10px;
                min-height: 40px;
            }
            .send-button, .stop-thinking-btn {
                padding: 8px 15px;
                font-size: 13px;
            }
            .sidebar-title {
                font-size: 18px;
            }
            .new-chat-btn {
                padding: 8px 12px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">📝 笔记助手</div>
                <button class="new-chat-btn" onclick="newChat()">+ 新建笔记</button>
            </div>
            <div class="storage-info" id="storage-info" title="浏览器存储限制：通常至少 500MB，取决于可用磁盘空间">存储使用: 计算中...</div>
            <div class="history-panel">
                <div class="history-title">历史笔记</div>
                <div id="history-list"></div>
            </div>
            <div class="sidebar-footer">
                <button class="back-btn" onclick="goBack()">← 返回知识库</button>
            </div>
        </div>

        <div class="chat-panel" id="chatPanel">
            <!-- 按钮放在 chat-panel 内部，右上角 -->
            <button class="toggle-sidebar d-md-none" onclick="toggleSidebar()" style="position: absolute; top: 10px; left: 85%;">☰</button>
            
            <div class="chat-area" id="chat-area">
                <div class="message bot-message">
                    <div class="message-content">您好！我是您的专属笔记助手，让我们一起创作和管理您的笔记吧。有什么想法想记录下来吗？</div>
                </div>
            </div>
            <div class="chat-input-area">
                <textarea class="chat-input" id="userInput" placeholder="输入您的笔记内容，或向我提问..."></textarea>
                <button class="send-button" onclick="sendMessage()">发送</button>
                <button class="stop-thinking-btn" id="stopBtn" style="display:none;" onclick="stopThinking()">停止思考</button>
            </div>
        </div>
    </div>

    <script>
        /* ========== 基础数据 & 初始化 ========== */
        const dbName = 'KimiNotesDB';
        const storeName = 'chats';
        let db;
        let currentChat = {
            messages: [],
            id: Date.now(),
            title: "新笔记"
        };
        let chatHistory = [];
        
        const SYSTEM_PROMPT = "你是融合知识管理与编程能力的专业助手，擅长将灵感转化为结构化笔记或可执行代码。你拒绝一切涉及恐怖主义、种族歧视、黄色暴力等问题的回答。Moonshot AI 为专有名词，不可翻译成其他语言。";
        
        let isPrinting = false;
        let printSaveTimer = null;
        let printInterval = null;
        let thinkingIndex = -1;

        function initDatabase() {
            const req = indexedDB.open(dbName, 1);
            req.onupgradeneeded = e => {
                db = e.target.result;
                if (!db.objectStoreNames.contains(storeName)) {
                    db.createObjectStore(storeName, { keyPath: 'id' });
                }
            };
            req.onsuccess = e => {
                db = e.target.result;
                loadChatHistoryFromDB();
                updateStorageInfo();
            };
            req.onerror = e => console.error('DB open error:', e);
        }
        initDatabase();

        /* ========== IndexedDB 工具 ========== */
        function saveChatToDB(chat, updateStorage = true) {
            return new Promise((res, rej) => {
                const tx = db.transaction([storeName], 'readwrite');
                const req = tx.objectStore(storeName).put(chat);
                req.onsuccess = () => {
                    if (updateStorage && !isPrinting) updateStorageInfo();
                    res();
                };
                req.onerror = e => rej(e.target.error);
            });
        }

        function deleteChatFromDB(id) {
            return new Promise((res, rej) => {
                const tx = db.transaction([storeName], 'readwrite');
                const req = tx.objectStore(storeName).delete(id);
                req.onsuccess = () => {
                    updateStorageInfo();
                    res();
                };
                req.onerror = e => rej(e.target.error);
            });
        }

        function loadChatHistoryFromDB() {
            const tx = db.transaction([storeName], 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = e => {
                chatHistory = e.target.result || [];
                updateHistoryList();
            };
        }

        /* ========== 历史列表 & 删除 ========== */
        function updateHistoryList() {
            const list = document.getElementById('history-list');
            if (!chatHistory.length) {
                list.innerHTML = '<div class="empty-state">暂无历史记录</div>';
                return;
            }
            
            const sorted = [...chatHistory].sort((a, b) => 
                new Date(b.messages[0]?.timestamp || 0) - new Date(a.messages[0]?.timestamp || 0)
            );
            
            list.innerHTML = sorted.map(chat => {
                const idx = chatHistory.findIndex(c => c.id === chat.id);
                const first = chat.messages.find(m => m.role === 'user');
                const preview = first ? (first.content.substring(0, 30) + '...') : '空笔记';
                const time = new Date(chat.messages[0]?.timestamp || Date.now());
                
                return `
                    <div class="history-item ${currentChat.id === chat.id ? 'active' : ''}" onclick="loadChatFromHistory(${idx})">
                        <div class="history-item-content">
                            <div class="history-item-title">${escapeHtml(chat.title || '未命名笔记')}</div>
                            <div class="history-item-time">${time.toLocaleDateString()} ${time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                            <div style="font-size:11px;color:#95a5a6;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(preview)}</div>
                        </div>
                        <button class="delete-btn" onclick="event.stopPropagation();deleteChat(${idx})" title="删除笔记">删除</button>
                    </div>`;
            }).join('');
        }

        async function deleteChat(index) {
            const id = chatHistory[index].id;
            await deleteChatFromDB(id);
            chatHistory.splice(index, 1);
            
            if (chatHistory.length) {
                loadChatFromHistory(chatHistory.length - 1);
            } else {
                currentChat = {
                    messages: [{
                        role: 'bot',
                        content: '您好！我是您的专属笔记助手，让我们一起创作和管理您的笔记吧。有什么想法想记录下来吗？',
                        timestamp: new Date().toISOString()
                    }],
                    id: Date.now(),
                    title: "新笔记"
                };
                await saveChatToDB(currentChat);
            }
            
            updateHistoryList();
            updateChatArea();
        }

        function loadChatFromHistory(index) {
            currentChat = JSON.parse(JSON.stringify(chatHistory[index]));
            updateHistoryList();
            updateChatArea();
        }

        /* ========== 新建笔记 ========== */
        async function newChat() {
            if (currentChat.messages.length > 1) {
                await saveChatToDB(currentChat);
            }
            
            currentChat = {
                messages: [{
                    role: 'bot',
                    content: '您好！我是您的专属笔记助手，让我们一起创作和管理您的笔记吧。有什么想法想记录下来吗？',
                    timestamp: new Date().toISOString()
                }],
                id: Date.now(),
                title: "新笔记"
            };
            
            await saveChatToDB(currentChat);
            updateChatArea();
            loadChatHistoryFromDB();
        }

        /* ========== 返回知识库 ========== */
        function goBack() {
            saveChatToDB(currentChat)
                .then(() => location.href = '../knowledge/index.php')
                .catch(() => location.href = '../knowledge/index.php');
        }

        /* ========== 存储占用 ========== */
        function updateStorageInfo() {
            if (isPrinting || !db) return;
            
            const tx = db.transaction([storeName], 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = e => {
                const chats = e.target.result || [];
                let size = 0;
                chats.forEach(c => size += new Blob([JSON.stringify(c)]).size);
                const mb = (size / 1048576).toFixed(2);
                const el = document.getElementById('storage-info');
                el.textContent = `笔记数量: ${chats.length} | 存储使用: ${mb} MB`;
                el.classList.remove('updating');
            };
        }

        /* ========== 发送消息 ========== */
        async function sendMessage() {
            const input = document.getElementById('userInput');
            const text = input.value.trim();
            if (!text) return;
            
            if (currentChat.messages.length <= 1) {
                currentChat.title = text.substring(0, 20) || '新笔记';
            }
            
            currentChat.messages.push({
                role: 'user',
                content: text,
                timestamp: new Date().toISOString()
            });
            
            input.value = '';
            await saveChatToDB(currentChat);
            updateChatArea();
            updateHistoryList();

            currentChat.messages.push({
                role: 'bot',
                content: '思考中...',
                timestamp: new Date().toISOString(),
                isLoading: true
            });
            
            await saveChatToDB(currentChat);
            updateChatArea();

            try {
                const reply = await callKimiAPIWithRetry();
                currentChat.messages.pop(); // 移除"思考中"
                currentChat.messages.push({
                    role: 'bot',
                    content: '',
                    timestamp: new Date().toISOString()
                });
                await saveChatToDB(currentChat);
                await typeWriterEffect(reply, currentChat.messages.length - 1);
            } catch (err) {
                currentChat.messages.pop();
                
                let msg = '抱歉，遇到了未知错误。';
                if (err.message.includes('Failed to fetch')) msg = '网络连接失败，请检查网络或API地址是否正确。';
                else if (err.message.includes('401')) msg = 'API Key 无效或已过期，请检查配置。';
                else if (err.message.includes('429')) msg = '请求过于频繁，请稍后再试。';
                else if (err.message.includes('500') || err.message.includes('503')) msg = '服务器内部错误，请稍后再试。';
                
                currentChat.messages.push({
                    role: 'bot',
                    content: msg,
                    timestamp: new Date().toISOString(),
                    isError: true
                });
                
                await saveChatToDB(currentChat);
                updateChatArea();
            }
        }

        /* ========== 调用 Kimi API ========== */
        async function callKimiAPIWithRetry(retryCount = 1) {
            const apiKey = 'YOU_KIMI_API_KEY'; // 请替换
            const url = 'https://api.moonshot.cn/v1/chat/completions';
            
            const messages = [{
                role: 'system',
                content: SYSTEM_PROMPT
            }];
            
            const relevant = currentChat.messages.filter(m => !m.isLoading);
            relevant.forEach(m => {
                messages.push({
                    role: m.role === 'bot' ? 'assistant' : m.role,
                    content: m.content
                });
            });
            
            const body = {
                model: 'kimi-k2-turbo-preview',
                messages,
                temperature: 0.3,
                max_tokens: 8192,
                top_p: 0.95
            };
            
            const ctrl = new AbortController();
            const t = setTimeout(() => ctrl.abort(), 120000);
            
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${apiKey.trim()}`
                    },
                    body: JSON.stringify(body),
                    signal: ctrl.signal
                });
                
                clearTimeout(t);
                if (!res.ok) throw new Error(`API ${res.status} ${await res.text()}`);
                const json = await res.json();
                return json.choices?.[0]?.message?.content || '暂无回答';
            } catch (e) {
                clearTimeout(t);
                if (e.name === 'AbortError') throw new Error('请求超时，请检查网络或稍后重试');
                if (retryCount > 0 && e.message.includes('Failed to fetch')) {
                    await new Promise(r => setTimeout(r, 2000));
                    return callKimiAPIWithRetry(retryCount - 1);
                }
                throw e;
            }
        }

        /* ========== 打字机效果（最快版本） ========== */
        async function typeWriterEffect(text, msgIdx) {
            isPrinting = true;
            thinkingIndex = msgIdx;
            
            const stopBtn = document.getElementById('stopBtn');
            if (stopBtn) stopBtn.style.display = 'inline-block';

            const storageInfo = document.getElementById('storage-info');
            storageInfo.classList.add('updating');
            storageInfo.textContent = '正在输出...';

            let idx = 0;
            return new Promise((resolve) => {
                printInterval = setInterval(() => {
                    if (idx < text.length) {
                        currentChat.messages[msgIdx].content += text[idx++];
                        updateChatArea();
                        
                        if (idx % 7 === 0 || idx === text.length) {
                            if (printSaveTimer) clearTimeout(printSaveTimer);
                            printSaveTimer = setTimeout(() => saveChatToDB(currentChat, false), 500);
                        }
                    } else {
                        clearInterval(printInterval);
                        isPrinting = false;
                        thinkingIndex = -1;
                        
                        if (stopBtn) stopBtn.style.display = 'none';
                        if (printSaveTimer) clearTimeout(printSaveTimer);
                        
                        saveChatToDB(currentChat).then(() => {
                            storageInfo.classList.remove('updating');
                            updateStorageInfo();
                        });
                        
                        resolve();
                    }
                }, 5);
            });
        }

        /* ========== 停止思考 ========== */
        function stopThinking() {
            if (printInterval) {
                clearInterval(printInterval);
                isPrinting = false;
                thinkingIndex = -1;
                document.getElementById('stopBtn').style.display = 'none';
                if (printSaveTimer) clearTimeout(printSaveTimer);
                saveChatToDB(currentChat);
                updateStorageInfo();
            }
        }

        /* ========== 更新聊天区域 ========== */
        function updateChatArea() {
            const area = document.getElementById('chat-area');
            area.innerHTML = '';
            
            if (!currentChat.messages.length) {
                area.innerHTML = '<div class="empty-state">开始记录您的第一个想法吧...</div>';
                return;
            }

            currentChat.messages.forEach((m, i) => {
                const msgDiv = document.createElement('div');
                msgDiv.className = `message ${m.role === 'user' ? 'user-message' : 'bot-message'}`;

                // 特殊标记处理
                if (m.isLoading) {
                    msgDiv.innerHTML = `<div class="message-content"><span class="typing-indicator">${escapeHtml(m.content)}</span></div>`;
                    area.appendChild(msgDiv);
                    return;
                }
                
                if (m.isError) {
                    msgDiv.innerHTML = `<div class="message-content error-message">${escapeHtml(m.content)}</div>`;
                    area.appendChild(msgDiv);
                    return;
                }

                // 提取代码块（健壮匹配Markdown格式）
                let raw = m.content;
                const codeBlocks = [];
                
                // 修复：使用正确的正则匹配代码块
                const codeBlockRegex = /^```(\w+)?\s*\n([\s\S]*?)\n```$/gm;
                raw = raw.replace(codeBlockRegex, (_, lang, code) => {
                    const id = `code-${currentChat.id}-${i}-${codeBlocks.length}`;
                    codeBlocks.push({
                        id,
                        lang: lang || 'javascript',
                        code: code.trim()
                    });
                    return `{{CODE_BLOCK_${codeBlocks.length - 1}}}`;
                });

                // 对剩余文本做转义 + 换行
                raw = escapeHtml(raw).replace(/\n/g, '<br>');

                // 倒序还原代码块（无复制按钮）
                for (let idx = codeBlocks.length - 1; idx >= 0; idx--) {
                    const b = codeBlocks[idx];
                    const escapedCode = escapeHtml(b.code);
                    raw = raw.replace(`{{CODE_BLOCK_${idx}}}`,
                        `<div class="code-block-wrapper">
                            <pre><code id="${b.id}" class="language-${b.lang}">${escapedCode}</code></pre>
                        </div>`
                    );
                }

                // 拼入普通内容容器
                let html = `<div class="message-content">${raw}</div>`;

                // 最后一条AI消息追加"创建笔记"按钮（确保在最下面）
                if (m.role === 'bot' && i === currentChat.messages.length - 1 && !m.isLoading) {
                    html += `<div class="message-actions"><button class="create-note-btn" onclick="createNoteFromChat('${currentChat.id}')">📄 摘要为笔记</button></div>`;
                }
                
                msgDiv.innerHTML = html;
                area.appendChild(msgDiv);
            });

            // 自动平滑滚动到底部
            area.scrollTop = area.scrollHeight;
            
            // 重新高亮代码
            Prism.highlightAll();
        }

        /* ========== 新增：智能摘要生成 ========== */
        async function summarizeChat(messages) {
            const apiKey = 'YOU_KIMI_API_KEY'; // 请使用你的API Key
            const url = 'https://api.moonshot.cn/v1/chat/completions';
            
            // 提取有效对话内容（排除加载中和错误消息）
            const validMessages = messages.filter(m => !m.isLoading && !m.isError);
            
            const chatText = validMessages
                .filter(m => m.role === 'user' || m.role === 'bot')
                .map(m => `${m.role === 'user' ? '用户' : '助手'}: ${m.content}`)
                .join('\n\n');
            
            // 如果内容太短，直接返回
            if (chatText.length < 50) {
                return {
                    title: validMessages.find(m => m.role === 'user')?.content?.substring(0, 20) || '新笔记',
                    summary: chatText
                };
            }
            
            const summaryPrompt = `请对以下对话进行智能摘要，要求：
1. 标题：提取最核心主题，不超过50个字，简洁明确
2. 内容：大部分保留对话内容，不要过于缩减，关键部分可以缩减一点点

对话内容：
${chatText}

请直接返回JSON格式，不要附加任何说明：
{"title": "摘要标题", "summary": "详细摘要内容"}`;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${apiKey.trim()}`
                    },
                    body: JSON.stringify({
                        model: 'kimi-k2-turbo-preview',
                        messages: [{ role: 'user', content: summaryPrompt }],
                        temperature: 0.3,
                        max_tokens: 1000
                    })
                });
                
                if (!res.ok) throw new Error(`API ${res.status}`);
                const json = await res.json();
                const result = json.choices?.[0]?.message?.content || '';
                
                // 尝试解析JSON，如果失败则使用回退方案
                try {
                    return JSON.parse(result);
                } catch {
                    // 解析失败，使用简单摘要
                    const lastUser = validMessages.filter(m => m.role === 'user').pop();
                    return {
                        title: lastUser?.content?.substring(0, 20) || '新笔记',
                        summary: result // 直接返回原始摘要
                    };
                }
            } catch (e) {
                console.error('摘要生成失败:', e);
                // 回退到简单摘要方案
                const lastUser = validMessages.filter(m => m.role === 'user').pop();
                const firstBot = validMessages.filter(m => m.role === 'bot').pop();
                return {
                    title: lastUser?.content?.substring(0, 20) || '新笔记',
                    summary: firstBot?.content?.substring(0, 500) || '摘要生成失败，请手动编辑。'
                };
            }
        }
        
        /* ========== 创建笔记（带自动摘要） ========== */
        async function createNoteFromChat(chatId) {
            if (isPrinting) {
                alert('请等待当前消息输出完成后再创建笔记');
                return;
            }

            // 获取当前对话
            const chat = currentChat;
            if (!chat || !chat.messages || chat.messages.length <= 1) {
                alert('对话内容太少，无法生成摘要');
                return;
            }

            // 显示加载状态
            const btn = document.querySelector('.create-note-btn');
            const originalText = btn.textContent;
            btn.textContent = '📝 正在生成摘要...';
            btn.disabled = true;

            try {
                // 调用API生成摘要
                const summary = await summarizeChat(chat.messages);
                
                // 动态创建隐藏表单并提交
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'create.php';
                form.style.display = 'none';
                
                // 添加标题字段
                const titleInput = document.createElement('input');
                titleInput.type = 'hidden';
                titleInput.name = 'chat_title';
                titleInput.value = summary.title;
                form.appendChild(titleInput);
                
                // 添加内容字段
                const contentInput = document.createElement('input');
                contentInput.type = 'hidden';
                contentInput.name = 'chat_content';
                contentInput.value = summary.summary;
                form.appendChild(contentInput);
                
                // 添加到页面并提交
                document.body.appendChild(form);
                form.submit();
                
            } catch (error) {
                console.error('创建笔记失败:', error);
                alert('生成摘要失败，请重试');
                
                // 恢复按钮状态
                btn.textContent = originalText;
                btn.disabled = false;
            }
        }

        /* ========== 小工具 ========== */
        function escapeHtml(str) {
            return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
        }

        // 回车发送事件
        document.getElementById('userInput').addEventListener('keypress', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // 页面可见性变化时更新存储信息
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !isPrinting) updateStorageInfo();
        });

        /* ========== 移动端侧边栏控制 ========== */
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const chatPanel = document.getElementById('chatPanel');
            sidebar.classList.toggle('active');
            chatPanel.classList.toggle('active'); // 关键：主内容也移动
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 重新高亮代码
            Prism.highlightAll();
            
            // 点击外部关闭侧边栏
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const toggleButton = document.querySelector('.toggle-sidebar');
                
                if (window.innerWidth <= 768 && 
                    !sidebar.contains(event.target) && 
                    !toggleButton.contains(event.target) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    document.getElementById('chatPanel').classList.remove('active');
                }
            });
        });

        document.querySelectorAll('.sidebar .nav-link, .new-chat-btn, .back-btn').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('chatPanel').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>