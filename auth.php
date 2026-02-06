<?php
// auth.php - 统一管理 session 和角色检查

if(session_status() === PHP_SESSION_NONE){
    session_start(); // 只有 session 没启动才启动
}

// 检查是否登录
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// 检查角色
function check_role($role){
    if(!isset($_SESSION['role']) || $_SESSION['role'] !== $role){
        die("Access denied");
    }
}

// 获取当前用户 ID
function current_user_id(){
    return $_SESSION['user_id'];
}

// 获取当前用户角色
function current_user_role(){
    return $_SESSION['role'];
}
