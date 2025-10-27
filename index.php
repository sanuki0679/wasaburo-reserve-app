<?php include __DIR__ . '/partials/header.php'; ?>
<section class="grid gap-8 md:grid-cols-2">
    <div class="flex flex-col justify-center">
        <h1 class="text-2xl md:text-3xl font-bold leading-tight">
            ご予約はかんたん4ステップ。
        </h1>
        <p class="mt-3 text-slate-600">
            日付・時間・人数・コースを選ぶだけ。確認後に確定できます。
        </p>
        <div class="mt-6 flex gap-3">
            <a href="reserve_form.php"
                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-5 py-3 font-medium text-white hover:bg-emerald-700">
                予約する
            </a>
            <a href="my_reservations.php"
                class="inline-flex items-center justify-center rounded-lg border px-5 py-3 font-medium hover:bg-slate-100">
                マイ予約を確認
            </a>
        </div>
    </div>

    <div class="rounded-xl border bg-white p-5">
        <h2 class="font-semibold">コースのご案内</h2>
        <ul class="mt-4 space-y-3 text-sm">
            <li class="rounded-lg border p-4">
                <div class="font-medium">和コース</div>
                <div class="text-slate-600">先付け・お造り3種盛・一品5種・皿そば・デザート 3,300円（税込3,630円）
                </div>
            </li>
            <li class="rounded-lg border p-4">
                <div class="font-medium">彩々コース</div>
                <div class="text-slate-600">先付け・お造り5種盛・一品6種・皿そば・デザート 3,800円（税込4,180円）</div>
            </li>
            <li class="rounded-lg border p-4">
                <div class="font-medium">煌コース</div>
                <div class="text-slate-600">先付け・お造り7種盛・一品7種・皿そば・デザート 4,300円（税込4,730円）</div>
            </li>
            <br>
                    ※先付けは3種、お造りは2貫ずつ。一品はサラダ・天ぷらを含みます。
                    皿そばはデザート前に提供されます。
        </ul>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
