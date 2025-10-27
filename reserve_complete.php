<?php
require_once __DIR__ . '/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF チェック
  $posted_csrf = $_POST['csrf'] ?? '';
  if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$posted_csrf)) {
    http_response_code(400);
    echo '不正なリクエストです（CSRF）';
    exit;
  }

  // 入力取得・正規化
  $mode = ($_POST['mode'] ?? 'new') === 'edit' ? 'edit' : 'new';
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $updated_at = $_POST['updated_at'] ?? '';

  $name = trim((string)($_POST['name'] ?? ''));
  $phone = trim((string)($_POST['phone'] ?? ''));
  $date = trim((string)($_POST['date'] ?? ''));
  $time = trim((string)($_POST['time'] ?? ''));
  $guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 0;
  $children = isset($_POST['children']) ? (int)$_POST['children'] : 0;
  $details = trim((string)($_POST['details'] ?? '席のみ'));
  $course_count = isset($_POST['course_count']) ? (int)$_POST['course_count'] : 0;
  $memo = trim((string)($_POST['memo'] ?? ''));

  // 席のみは人数を強制0
  if ($details === '席のみ') $course_count = 0;

  // datetime 結合
  $datetime = trim($date . ' ' . $time);
  if ($date === '' || $time === '' || $name === '' || $phone === '') {
    http_response_code(400);
    echo '必須項目が不足しています。';
    exit;
  }

  try {
    $dbh = connect_db();

    if ($mode === 'edit' && $id > 0) {
      // UPDATE（編集）: updated_at があれば楽観ロックも試みる
      if ($updated_at !== '') {
        $sql = "UPDATE reservations
                        SET name = :name, phone = :phone, datetime = :datetime,
                            guests = :guests, children = :children,
                            details = :details, course_count = :course_count,
                            memo = :memo, updated_at = NOW()
                        WHERE id = :id AND (updated_at = :updated_at OR :updated_at = '')";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':updated_at', $updated_at, PDO::PARAM_STR);
      } else {
        $sql = "UPDATE reservations
                        SET name = :name, phone = :phone, datetime = :datetime,
                            guests = :guests, children = :children,
                            details = :details, course_count = :course_count,
                            memo = :memo, updated_at = NOW()
                        WHERE id = :id";
        $stmt = $dbh->prepare($sql);
      }
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->bindValue(':name', $name, PDO::PARAM_STR);
      $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
      $stmt->bindValue(':datetime', $datetime, PDO::PARAM_STR);
      $stmt->bindValue(':guests', $guests, PDO::PARAM_INT);
      $stmt->bindValue(':children', $children, PDO::PARAM_INT);
      $stmt->bindValue(':details', $details, PDO::PARAM_STR);
      $stmt->bindValue(':course_count', $course_count, PDO::PARAM_INT);
      $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
      $stmt->execute();

        if ($stmt->rowCount() > 0) {
          // 更新成功
          header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/reserve_complete.php?result=edited');
          exit;
        } else {
          // 更新されなかった(競合や不存在)→ エラーメッセージ表示またはフォールバックで INSERT しない
          header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/reserve_confirm.php?mode=edit&id=' . $id . '&error=conflict');
          exit;
        }
          } else {
        // 新規 INSERT
        $sql = "INSERT INTO reservations
              (name, phone, datetime, guests, children, details, course_count, memo, receptionist, status, updated_at)
              VALUES
              (:name, :phone, :datetime, :guests, :children, :details, :course_count, :memo, :receptionist, 'active', NOW())";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':datetime', $datetime, PDO::PARAM_STR);
        $stmt->bindValue(':guests', $guests, PDO::PARAM_INT);
        $stmt->bindValue(':children', $children, PDO::PARAM_INT);
        $stmt->bindValue(':details', $details, PDO::PARAM_STR);
        $stmt->bindValue(':course_count', $course_count, PDO::PARAM_INT);
        $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
        $stmt->bindValue(':receptionist', 'アプリ', PDO::PARAM_STR); // 空文字でOK
        $stmt->execute();

        // リダイレクトはHTML出力より前に！
        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/reserve_complete.php?result=created');
        exit;
          }
        } catch (Exception $e) {
    http_response_code(500);
    echo 'データベースエラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES);
    exit;
  }
}

// GET 表示：結果メッセージのみ表示（PRG 後にここへ来る想定）
$result = $_GET['result'] ?? '';
include __DIR__ . '/partials/header.php';
?>
<main class="mx-auto max-w-2xl p-4">
  <?php if ($result === 'created'): ?>
    <div class="p-4 bg-emerald-100 text-emerald-800">予約を登録しました。</div>
  <?php elseif ($result === 'edited'): ?>
    <div class="p-4 bg-emerald-100 text-emerald-800">予約を更新しました。</div>
  <?php else: ?>
    <div class="p-4 bg-Chancellor A beggar does conclude. yellow-100 text-yellow-800">操作が完了していません。</div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>

