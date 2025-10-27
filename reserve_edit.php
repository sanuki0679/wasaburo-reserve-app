<?php
// reserve_edit.php

include __DIR__ . '/partials/header.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// 編集モード判定とID取得
$mode = 'edit';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : null;

// 初期値
$values = [
    'name' => '',
    'phone' => '',
    'guests' => 2,
    'children' => 0,
    'date' => '',
    'time' => '',
    'details' => '席のみ',
    'course_count' => '',
    'memo' => '',
    'updated_at' => ''
];

// DBから既存予約データ取得
if ($id) {
    try {
        $dbh = connect_db();
        $sql = <<<EOM
SELECT id, name, phone, guests, children, datetime, details, course_count, memo, updated_at
FROM reservations
WHERE id = :id
EOM;
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo '予約が見つかりません';
            include __DIR__ . '/partials/footer.php';
            exit;
        }
        // datetimeをdateとtimeに分割
        if (isset($row['datetime'])) {
            $dt = explode(' ', $row['datetime']);
            $row['date'] = $dt[0] ?? '';
            $row['time'] = isset($dt[1]) ? substr($dt[1], 0, 5) : '';
            unset($row['datetime']);
        }
        $values = array_merge($values, $row);
    } catch (Exception $e) {
        http_response_code(500);
        echo 'データベースエラー: ' . htmlspecialchars($e->getMessage());
        include __DIR__ . '/partials/footer.php';
        exit;
    }
} else {
    http_response_code(400);
    echo '予約IDが指定されていません';
    include __DIR__ . '/partials/footer.php';
    exit;
}
?>
<!-- ダークモードを強制的に無効化 -->
<style>
    html, body {
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

<form action="/public/public/reserve_confirm.php" method="post" class="mx-auto grid max-w-2xl gap-6">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES) ?>">
    <input type="hidden" name="mode" value="edit">
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <input type="hidden" name="updated_at" value="<?= htmlspecialchars($values['updated_at'], ENT_QUOTES) ?>">
    <div class="text-xs text-emerald-600">※ 予約ID・更新日時はシステム管理用</div>

    <div>
        <h1 class="text-xl font-bold">予約内容の編集</h1>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="grid gap-1">
            <label class="text-sm font-medium">お名前</label>
            <input name="name" required class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="山田 太郎" value="<?= htmlspecialchars($values['name'], ENT_QUOTES) ?>">
        </div>
        <div class="grid gap-1">
            <label class="text-sm font-medium">電話番号</label>
            <input name="phone" required inputmode="tel" pattern="[0-9\-+ ]{9,}" class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="080-1234-5678" value="<?= htmlspecialchars($values['phone'], ENT_QUOTES) ?>">
        </div>
    </div>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="grid gap-1">
            <label class="text-sm font-medium">日付</label>
            <?php
            $minDate = date('Y-m-d', strtotime('+2 days'));
            $maxDate = date('Y-m-d', strtotime('+2 months'));
            ?>
            <input
                type="date"
                name="date"
                required
                min="<?= $minDate ?>"
                max="<?= $maxDate ?>"
                class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-emerald-600"
                value="<?= htmlspecialchars($values['date'], ENT_QUOTES) ?>">
        </div>
        <div class="grid gap-1">
            <label class="text-sm font-medium">時間</label>
            <select name="time" required class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-emerald-600">
                <?php
                $start = strtotime('11:30');
                $end = strtotime('19:00');
                for ($t = $start; $t <= $end; $t += 1800): // 1800 seconds = 30 minutes
                    $timeStr = date('H:i', $t);
                ?>
                    <option value="<?= $timeStr ?>" <?= ($values['time'] == $timeStr ? 'selected' : '') ?>><?= $timeStr ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="grid gap-1">
            <label class="text-sm font-medium">人数</label>
            <select name="guests" required class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= $values['guests'] == $i ? 'selected' : '' ?>><?= $i ?>名</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="grid gap-1">
            <label class="text-sm font-medium">お子様の人数（小学生以下）</label>
            <select name="children" required class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <?php for ($i = 0; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= $values['children'] == $i ? 'selected' : '' ?>><?= $i ?>名</option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2 items-end">
        <div class="grid gap-1">
            <label class="text-sm font-medium">ご利用内容</label>
            <select name="details" required class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-emerald-600" onchange="toggleCourseCount()">
                <?php
                $options = [
                    "席のみ",
                    "和コース",
                    "和コース（飲み放題付き）",
                    "和さぶろ弁当",
                    "天ざる定食"
                ];
                foreach ($options as $label): ?>
                    <option value="<?= htmlspecialchars($label, ENT_QUOTES) ?>" <?= (isset($values['details']) && $values['details'] == $label) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid gap-1" id="courseCountDiv" style="display:none;">
            <label class="text-sm font-medium">人前</label>
            <select name="course_count" class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="0" <?= (isset($values['course_count']) && $values['course_count'] == 0) ? 'selected' : '' ?>>0人前</option>
                <?php for ($i = 2; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= (isset($values['course_count']) && $values['course_count'] == $i) ? 'selected' : '' ?>><?= $i ?>人前</option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    <script>
        function toggleCourseCount() {
            const usageSelect = document.querySelector('select[name="details"]');
            const courseCountDiv = document.getElementById('courseCountDiv');
            if (usageSelect.value !== '席のみ') {
                courseCountDiv.style.display = '';
            } else {
                courseCountDiv.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', toggleCourseCount);
        document.querySelector('select[name="details"]').addEventListener('change', toggleCourseCount);
    </script>

    <div class="grid gap-1">
        <label class="text-sm font-medium">備考（任意）</label>
        <textarea name="memo" rows="3" class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="アレルギー、席のご希望など"><?= htmlspecialchars($values['memo'], ENT_QUOTES) ?></textarea>
    </div>

    <div class="flex items-center justify-between">
        <a href="/public/public/index.php" class="text-sm text-slate-600 hover:text-slate-800">← トップに戻る</a>
        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-3 font-medium text-white hover:bg-emerald-700">
            確認へ（変更内容）
        </button>
    </div>
</form>

<?php include __DIR__ . '/partials/footer.php'; ?>
