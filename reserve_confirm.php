<?php
include __DIR__ . '/partials/header.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$mode = ($_GET['mode'] ?? 'new') === 'edit' ? 'edit' : 'new';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;

/* ✨ まず最初に values を用意してから出力に進む */
$values = [
  'name' => '',
  'phone' => '',
  'guests' => 2,
  'children' => 0,
  'date' => '',
  'time' => '',
  'details' => '席のみ',
  'course_count' => 0,
  'memo' => '',
  'updated_at' => ''
];

// POST値があれば（フォーム送信直後）、POST値を優先して$valuesにセット
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($values as $k => $v) {
    if (isset($_POST[$k])) {
      $values[$k] = $_POST[$k];
    }
  }
  // 編集時はid, updated_atもPOSTから
  if (isset($_POST['id'])) $values['id'] = $_POST['id'];
  if (isset($_POST['updated_at'])) $values['updated_at'] = $_POST['updated_at'];
  // 型・整合性の正規化
  $values['course_count'] = isset($values['course_count']) ? (int)$values['course_count'] : 0;
  if (($values['details'] ?? '席のみ') === '席のみ') {
    // 席のみの場合は強制的に 0 人前に統一（POST上で改ざんされても無視）
    $values['course_count'] = 0;
  }
}

// course_count fallback for edit mode (if not posted, try to parse from DB or details)
if ($mode === 'edit' && !isset($_POST['course_count'])) {
  if (isset($values['course_count'])) {
    $values['course_count'] = (int)$values['course_count'];
  } else if (isset($values['details']) && preg_match('/(\\d+)人前/', $values['details'], $m)) {
    $values['course_count'] = (int)$m[1];
  } else {
    $values['course_count'] = 0;
  }
}

if ($mode === 'edit' && $id) {
  try {
    $dbh = connect_db();
    $sql = <<<SQL
      SELECT id, name, phone, guests, children, datetime, details, course_count, memo, updated_at
      FROM reservations WHERE id = :id
    SQL;
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($row);
    if (!$row) {
      http_response_code(404);
      echo '予約が見つかりません';
      include __DIR__ . '/partials/footer.php';
      exit;
    }

    // datetime → date, time（HH:MMに正規化）

    if (!empty($row['datetime'])) {
      $dt = explode(' ', trim($row['datetime']));
      $row['date'] = $dt[0] ?? '';
      // ここが重要: 11:30:00 → 11:30 に揃える
      $row['time'] = isset($dt[1]) ? substr($dt[1], 0, 5) : '';
      unset($row['datetime']);
    }

    // details の正規化（候補に無い/空なら「席のみ」）
    $allowedDetails = [
      '席のみ',
      '和コース',
      '和コース（飲み放題付き）',
      '和さぶろ弁当',
      '天ざる定食'
    ];
    $row['details'] = isset($row['details']) ? trim($row['details']) : '';

    if (!in_array($row['details'], $allowedDetails, true)) {
      $row['details'] = '席のみ';
    }

    $values = array_merge($values, $row);
  } catch (Exception $e) {
    http_response_code(500);
    echo 'データベースエラー: ' . htmlspecialchars($e->getMessage());
    include __DIR__ . '/partials/footer.php';
    exit;
  }
}

// 値の正規化（$values は前段で受け取っている想定）
$values = $values ?? [];

// 整形・デフォルト
$values['mode'] = $values['mode'] ?? ($_POST['mode'] ?? 'new');
$values['id'] = isset($values['id']) ? (int)$values['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$values['updated_at'] = $values['updated_at'] ?? ($_POST['updated_at'] ?? '');
$values['details'] = $values['details'] ?? ($_POST['details'] ?? '');
$values['course_count'] = isset($values['course_count']) ? (int)$values['course_count'] : (isset($_POST['course_count']) ? (int)$_POST['course_count'] : 0);

// 「席のみ」の場合は人数を0に固定（クライアント改ざん防止）
if (trim($values['details']) === '席のみ') {
  $values['course_count'] = 0;
}
?>

<!-- ダークモードを強制的に無効化（任意） -->
<style>
  html,
  body {
    color-scheme: only light;
    background: #f5efe7 !important;
  }

  body {
    background:
      radial-gradient(1px 1px at 20px 30px, rgba(0, 0, 0, .06) 0, transparent 1px),
      radial-gradient(1px 1px at 180px 120px, rgba(0, 0, 0, .05) 0, transparent 1px),
      radial-gradient(1px 1px at 60px 160px, rgba(0, 0, 0, .05) 0, transparent 1px),
      linear-gradient(#f5efe7, #f1eadf);
    background-size: 200px 200px, 220px 220px, 260px 260px, 100% 100%;
  }
</style>

<!-- ✅ action の二重 /public を修正 -->
<form action="/public/public/reserve_complete.php" method="post">
  <!-- CSRF / 編集フラグ / ID 等を必ず送る -->
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($values['csrf'] ?? $_SESSION['csrf'] ?? '', ENT_QUOTES) ?>">
  <input type="hidden" name="mode" value="<?= htmlspecialchars($values['mode'], ENT_QUOTES) ?>">
  <input type="hidden" name="id" value="<?= (int)$values['id'] ?>">
  <input type="hidden" name="updated_at" value="<?= htmlspecialchars($values['updated_at'], ENT_QUOTES) ?>">

  <!-- 画面で表示しているすべての値を hidden で再送 -->
  <input type="hidden" name="name" value="<?= htmlspecialchars($values['name'] ?? ($_POST['name'] ?? ''), ENT_QUOTES) ?>">
  <input type="hidden" name="phone" value="<?= htmlspecialchars($values['phone'] ?? ($_POST['phone'] ?? ''), ENT_QUOTES) ?>">
  <input type="hidden" name="date" value="<?= htmlspecialchars($values['date'] ?? ($_POST['date'] ?? ''), ENT_QUOTES) ?>">
  <input type="hidden" name="time" value="<?= htmlspecialchars($values['time'] ?? ($_POST['time'] ?? ''), ENT_QUOTES) ?>">
  <input type="hidden" name="guests" value="<?= (int)($values['guests'] ?? ($_POST['guests'] ?? 0)) ?>">
  <input type="hidden" name="children" value="<?= (int)($values['children'] ?? ($_POST['children'] ?? 0)) ?>">
  <input type="hidden" name="details" value="<?= htmlspecialchars($values['details'], ENT_QUOTES) ?>">
  <input type="hidden" name="course_count" value="<?= (int)$values['course_count'] ?>">
  <input type="hidden" name="memo" value="<?= htmlspecialchars($values['memo'] ?? ($_POST['memo'] ?? ''), ENT_QUOTES) ?>">

  <!-- 確定ボタン -->
  <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-3 font-medium text-white">
    確定する
  </button>
</form>

<!-- 予約内容の確認表示 -->
<div class="max-w-lg mx-auto my-8 bg-white rounded-lg shadow p-6">
  <h2 class="text-xl font-bold mb-4">ご予約内容の確認</h2>
  <dl class="space-y-2">
    <div class="flex">
      <dt class="w-32 font-semibold">お名前</dt>
      <dd><?= htmlspecialchars($values['name'], ENT_QUOTES) ?></dd>
    </div>
    <div class="flex">
      <dt class="w-32 font-semibold">電話番号</dt>
      <dd><?= htmlspecialchars($values['phone'], ENT_QUOTES) ?></dd>
    </div>
    <div class="flex">
      <dt class="w-32 font-semibold">日付</dt>
      <dd><?= htmlspecialchars($values['date'], ENT_QUOTES) ?></dd>
    </div>
    <div class="flex">
      <dt class="w-32 font-semibold">時間</dt>
      <dd><?= htmlspecialchars($values['time'], ENT_QUOTES) ?></dd>
    </div>
    <div class="flex">
      <dt class="w-32 font-semibold">大人</dt>
      <dd><?= (int)$values['guests'] ?> 名</dd>
    </div>
    <div class="flex">
      <dt class="w-32 font-semibold">子ども</dt>
      <dd><?= (int)$values['children'] ?> 名</dd>
    </div>
    <div class="flex">
      <dt class="w-32 font-semibold">コース</dt>
      <dd>
        <?= htmlspecialchars($values['details'], ENT_QUOTES) ?>
        <?php if ($values['details'] !== '席のみ' && (int)$values['course_count'] > 0): ?>
          （<?= (int)$values['course_count'] ?>人前）
        <?php endif; ?>
      </dd>
    </div>
    <div class="flex">
      <dt class="w-32 font-semibold">メモ</dt>
      <dd><?= nl2br(htmlspecialchars($values['memo'], ENT_QUOTES)) ?></dd>
    </div>
  </dl>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
