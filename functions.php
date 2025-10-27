<?php
require_once __DIR__ . '/config.php';

// 接続処理を行う関数
function connect_db()
{
    try {
        return new PDO(
            DSN,
            USER,
            PASSWORD,
            [PDO::ATTR_ERRMODE =>
            PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }
}

// エスケープ処理を行う関数
function h($str)
{
    // ENT_QUOTES: シングルクオートとダブルクオートを共に変換する。
    // return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}


// 休業日を指定
function get_holidays() {

    // Define your logic to get holidays for the given year and month

    // For example, return an array of dates that are holidays

    return [

        "2024-12-25",
        "2025-01-01",
        "2025-01-02",
        "2025-01-03",
        "2025-02-19"

    ];

}

function csrf_token_issue(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_verify(string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect(string $url) {
    header('Location: '.$url);
    exit;
}
