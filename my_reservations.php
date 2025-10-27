<?php
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// CSRF トークンがない場合は生成
if (empty($_SESSION['csrf'])) {
  try {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  } catch (Exception $e) {
    $_SESSION['csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
  }
}

// POST: キャンセル処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'cancel')) {
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $post_name = isset($_POST['name']) ? trim($_POST['name']) : '';
  $post_tel  = isset($_POST['tel']) ? trim($_POST['tel']) : '';
  $csrf = $_POST['csrf'] ?? '';

  // CSRF チェック
  if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    $_SESSION['flash_error'] = '不正なリクエストです。';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query(['name' => $post_name, 'tel' => $post_tel]));
    exit;
  }

  if ($id && $post_name !== '' && $post_tel !== '') {
    try {
      $dbh = connect_db();
      $sql = "UPDATE reservations
                    SET status = 'canceled', canceled_at = NOW()
                    WHERE id = :id AND name = :name AND phone = :phone AND status = 'active'";
      $stmt = $dbh->prepare($sql);
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->bindValue(':name', $post_name, PDO::PARAM_STR);
      $stmt->bindValue(':phone', $post_tel, PDO::PARAM_STR);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {
        $_SESSION['flash'] = '予約をキャンセルしました。';
      } else {
        $_SESSION['flash_error'] = 'キャンセルできませんでした（条件不一致または既にキャンセル済み）。';
      }
    } catch (Exception $e) {
      $_SESSION['flash_error'] = '内部エラーが発生しました。';
    }
  }
  // PRG
  header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query(['name' => $post_name, 'tel' => $post_tel]));
  exit;
}

include __DIR__ . '/partials/header.php';
require_once __DIR__ . '/functions.php';



$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$phone = isset($_GET['tel']) ? trim($_GET['tel']) : '';
$reservations = [];

if ($name !== '' && $phone !== '') {
  $dbh = connect_db();
  $today = date('Y-m-d');
  // canceled の予約は表示しない
  $sql = "SELECT * FROM reservations
          WHERE name = :name
            AND phone = :phone
            AND datetime >= :today
            AND (status IS NULL OR status <> 'canceled')
          ORDER BY datetime DESC";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':name', $name, PDO::PARAM_STR);
  $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
  $stmt->bindValue(':today', $today, PDO::PARAM_STR);
  $stmt->execute();
  $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<style>
  html,
  body {
    color-scheme: only light;
    background: #f5efe7 !important;
  }
</style>
<section class="grid gap-6">
  <div>
    <h1 class="text-xl font-bold">マイ予約</h1>

    <form method="get" class="mb-4 flex gap-2 flex-wrap items-end">
      <div>
        <label class="block text-xs text-slate-600 mb-1" for="name">名前</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($name, ENT_QUOTES) ?>" class="rounded border px-2 py-1" required>
      </div>
      <div>
        <label class="block text-xs text-slate-600 mb-1" for="tel">電話番号</label>
        <input type="text" name="tel" id="tel" value="<?= htmlspecialchars($phone, ENT_QUOTES) ?>" class="rounded border px-2 py-1" required>
      </div>
      <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white">照会</button>
    </form>

    <?php if ($name === '' || $phone === ''): ?>
      <p class="text-sm text-slate-600">名前と電話番号を入力してください。</p>
    <?php elseif (empty($reservations)): ?>
      <p class="text-sm text-red-600">該当する予約が見つかりませんでした。</p>
    <?php else: ?>
      <p class="text-sm text-slate-600">予約情報を表示しています。</p>
    <?php endif; ?>
  </div>

  <ul class="space-y-3">
    <?php foreach ($reservations as $r): ?>
      <li class="rounded-lg border bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="text-sm">
            <div class="font-mono text-slate-700"><?= htmlspecialchars($r['id'] ?? $r['no'] ?? '', ENT_QUOTES) ?></div>
            <div class="text-slate-600">
              <?= htmlspecialchars(date('Y年n月j日 H:i', strtotime($r['datetime'])), ENT_QUOTES) ?>
              / <?= (int)$r['guests'] ?>名
              / <?= htmlspecialchars($r['details'], ENT_QUOTES) ?>
              <?php if (isset($r['course_count']) && $r['course_count'] !== ''): ?>
                / <?= (int)$r['course_count'] ?>人前
              <?php endif; ?>
            </div>
          </div>
            <div class="flex gap-2">
            <a href="/public/public/reserve_edit.php?id=<?= urlencode($r['id'] ?? $r['no'] ?? '') ?>"
              class="rounded-lg border px-4 py-2 text-sm hover:bg-slate-100">変更</a>

            <!-- 変更: POSTフォームでキャンセル（status='canceled' に更新） -->
            <form method="post" onsubmit="return confirm('予約をキャンセルしますか？');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES) ?>">
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="id" value="<?= htmlspecialchars($r['id'] ?? $r['no'] ?? '', ENT_QUOTES) ?>">
              <input type="hidden" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES) ?>">
              <input type="hidden" name="tel" value="<?= htmlspecialchars($phone, ENT_QUOTES) ?>">
              <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">キャンセル</button>
            </form>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="pt-2">
    <a href="/public/public/reserve_form.php" class="inline-flex rounded-lg bg-emerald-600 px-5 py-3 font-medium text-white hover:bg-emerald-700">
      新しく予約する
    </a>
  </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
