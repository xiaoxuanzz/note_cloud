<?php
/**
 * PZIOT笔记网 - 入口文件
 * 功能：安装检测 → 登录验证 → 自动跳转
 * 
 * 修复说明：
 * 1. INSTALL_LOCK 路径修复：BASE_PATH . '/install.lock'（必须带斜杠）
 * 2. 所有 header Location 改为绝对路径（带 /）避免路径混淆
 */

// ==================== 1. 安装检测 ====================
define('BASE_PATH', __DIR__);
define('INSTALL_LOCK', BASE_PATH . '/install/install.lock');  // ✅ 修复：添加斜杠

// 检查是否已安装
if (!file_exists(INSTALL_LOCK)) {
    // 未安装 -> 跳转到安装页面
    header('Location: /install/install.php');  // ✅ 使用绝对路径
    exit();
}

// ==================== 2. 会话初始化 ====================
session_start();

// ==================== 3. 登录状态验证 ====================
// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    // 未登录 -> 跳转到登录页
    header('Location: /login.php');  // ✅ 使用绝对路径
    exit();
}

// ==================== 4. 已登录 -> 跳转到知识库首页 ====================
// 301永久重定向，避免SEO问题
header('HTTP/1.1 301 Moved Permanently');
header('Location: /knowledge/index.php');  // ✅ 使用绝对路径
exit();