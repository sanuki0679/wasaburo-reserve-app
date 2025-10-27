<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>そば茶屋 和さぶろ | 予約</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="color-scheme" content="light dark">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
  <header class="border-b bg-white">
    <div class="mx-auto max-w-4xl px-4 py-4 flex items-center justify-between">
      <a href="/public/public/index.php" class="font-bold text-lg">
        そば茶屋 <span class="text-emerald-600">和さぶろ</span>
      </a>
      <nav class="flex gap-4 text-sm">
        <a class="hover:text-emerald-700" href="index.php">トップ</a>
        <a class="hover:text-emerald-700" href="reserve_form.php">予約する</a>
        <a class="hover:text-emerald-700" href="my_reservations.php">マイ予約</a>
      </nav>
    </div>
  </header>
  <main class="mx-auto max-w-4xl px-4 py-8">
