<?= $this->extend('layouts/admin') ?>

<?php
use App\Entities\Enquiry;

$current = $report['current'];
$changes = $report['changes'];
$range   = date('j M', strtotime($current['from'])) . ' to ' . date('j M Y', strtotime($current['to']));
$errors  = session('errors') ?? [];
$rupees  = static fn (?int $amount): ?string => $amount === null ? null : money($amount);
$number  = static fn (float $value): string => rtrim(rtrim(number_format($value, 1), '0'), '.');
$percent = static fn (?float $share): ?string => $share === null ? null : $number($share) . '%';
$cards   = [
    ['Enquiries per week', 'enquiries', $number($current['enquiries_per_week']), 'enquiries_per_week', "{$current['enquiries']} in these two weeks"],
    ['Became orders', 'percent', $percent($current['conversion']), 'conversion', 'Share of enquiries'],
    ['Sales', 'rupee', money($current['sales']), 'sales', number_format($current['orders']) . ' ' . ($current['orders'] === 1 ? 'order' : 'orders')],
    ['Average order', 'orders', $rupees($current['average_order']), 'average_order', 'Site orders and logged sales'],
    ['Repeat or referred', 'user', $percent($current['repeat_share']), 'repeat_share', "Of {$current['customers']} " . ($current['customers'] === 1 ? 'customer' : 'customers')],
];
$heading = 'font-sans text-base font-semibold tracking-normal';
$summary = 'flex min-h-14 cursor-pointer list-none items-center gap-3 px-4 text-sm font-semibold text-stone-950 sm:px-5 [&::-webkit-details-marker]:hidden';
$tile    = 'grid size-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-800';
?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['eyebrow' => 'Two-week review', 'title' => 'Analytics', 'lede' => "{$range}, compared with the two weeks before."]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid gap-6">
  <section aria-labelledby="glance-heading">
    <h2 id="glance-heading" class="sr-only">At a glance</h2>
    <dl class="grid grid-cols-2 gap-3 md:grid-cols-6 xl:grid-cols-5">
      <?php foreach ($cards as $index => [$label, $icon, $value, $key, $note]): ?>
        <?php $change = $changes[$key]; ?>
        <div class="panel flex flex-col p-4 xl:col-span-1 <?= $index < 3 ? 'md:col-span-2' : 'md:col-span-3' ?> <?= $index === 4 ? 'max-md:col-span-2' : '' ?>">
          <dt class="flex items-start justify-between gap-2 text-sm text-stone-500">
            <?= esc($label) ?>
            <span class="text-stone-400"><?= partial('icon', ['name' => $icon, 'size' => 18]) ?></span>
          </dt>
          <dd class="mt-2 text-2xl font-semibold text-stone-950 tabular-nums"><?= $value === null ? '<span class="text-base font-normal text-stone-500">Not enough data</span>' : esc($value) ?></dd>
          <dd class="mb-3 text-xs text-stone-500"><?= esc($note) ?></dd>
          <dd class="mt-auto border-t border-stone-100 pt-2 text-xs font-medium <?= $change === null ? 'text-stone-500' : ($change >= 0 ? 'text-emerald-700' : 'text-red-700') ?>">
            <?= $change === null ? 'Nothing to compare yet' : ($change >= 0 ? '+' : '−') . esc($number(abs($change))) . '% on last time' ?>
          </dd>
        </div>
      <?php endforeach ?>
    </dl>
  </section>

  <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <section class="panel border-t-2 border-t-gold-500 p-4 sm:p-5" aria-labelledby="actions-heading">
      <h2 id="actions-heading" class="<?= $heading ?>">What to do next</h2>
      <p class="mb-4 text-sm text-stone-500">Up to three steps from these figures. Start with the first.</p>
      <ol class="divide-y divide-stone-100">
        <?php foreach ($report['actions'] as $index => $action): ?>
          <li class="flex gap-3 py-3 first:pt-0 last:pb-0">
            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-800 text-xs font-semibold text-white"><?= $index + 1 ?></span>
            <div class="min-w-0">
              <p class="flex flex-wrap items-center gap-x-2 gap-y-1 font-medium text-stone-950"><?= esc($action['title']) ?> <span class="status"><?= esc($action['step']) ?></span></p>
              <p class="mt-0.5 text-sm text-stone-600"><?= esc($action['why']) ?></p>
            </div>
          </li>
        <?php endforeach ?>
      </ol>
    </section>

    <section class="panel divide-y divide-stone-200" aria-labelledby="records-heading">
      <h2 id="records-heading" class="<?= $heading ?> px-4 py-3 sm:px-5">Add to your records</h2>

      <details class="group" <?= isset($errors['customer_name']) || isset($errors['source']) || isset($errors['type']) || isset($errors['order_value']) || isset($errors['phone']) ? 'open' : '' ?>>
        <summary class="<?= $summary ?>">
          <span class="<?= $tile ?>"><?= partial('icon', ['name' => 'enquiries', 'size' => 18]) ?></span>
          <span class="flex-1">Log a DM or walk-in enquiry</span>
          <span class="transition-transform group-open:rotate-180"><?= partial('icon', ['name' => 'down', 'size' => 18]) ?></span>
        </summary>
        <?= form_open(url_to('admin.analytics.enquiries'), ['class' => 'grid gap-4 px-4 pb-5 sm:px-5', 'novalidate' => true, 'data-submit-once' => true]) ?>
          <p class="text-sm text-stone-500">Website enquiries are counted by themselves.</p>
          <?= partial('field', ['name' => 'customer_name', 'label' => 'Name or Instagram handle', 'required' => true, 'attributes' => 'maxlength="100"']) ?>
          <?= partial('field', ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'hint' => 'Lets repeat customers be recognised.', 'attributes' => 'maxlength="20"']) ?>
          <div class="grid grid-cols-2 gap-4">
            <?= partial('field', ['name' => 'source', 'label' => 'Came from', 'type' => 'select', 'options' => Enquiry::SOURCE_LABELS, 'value' => Enquiry::INSTAGRAM, 'required' => true]) ?>
            <?= partial('field', ['name' => 'type', 'label' => 'Looking for', 'type' => 'select', 'options' => Enquiry::TYPE_LABELS, 'required' => true]) ?>
          </div>
          <?= partial('field', ['name' => 'order_value', 'label' => 'Sold for (₹)', 'hint' => 'Leave empty if it has not sold yet.', 'attributes' => 'inputmode="decimal"']) ?>
          <button type="submit" class="btn btn-block">Log enquiry</button>
        <?= form_close() ?>
      </details>

      <details class="group" <?= isset($errors['title']) || isset($errors['posted_on']) || isset($errors['saves']) || isset($errors['shares']) ? 'open' : '' ?>>
        <summary class="<?= $summary ?>">
          <span class="<?= $tile ?>"><?= partial('icon', ['name' => 'chart', 'size' => 18]) ?></span>
          <span class="flex-1">Log a post's saves and shares</span>
          <span class="transition-transform group-open:rotate-180"><?= partial('icon', ['name' => 'down', 'size' => 18]) ?></span>
        </summary>
        <?= form_open(url_to('admin.analytics.posts'), ['class' => 'grid gap-4 px-4 pb-5 sm:px-5', 'novalidate' => true, 'data-submit-once' => true]) ?>
          <p class="text-sm text-stone-500">Copy the numbers from Instagram's insights.</p>
          <?= partial('field', ['name' => 'title', 'label' => 'Which post', 'required' => true, 'attributes' => 'maxlength="140"']) ?>
          <?= partial('field', ['name' => 'posted_on', 'label' => 'Posted on', 'type' => 'date', 'value' => $today, 'required' => true]) ?>
          <div class="grid grid-cols-2 gap-4">
            <?= partial('field', ['name' => 'saves', 'label' => 'Saves', 'type' => 'number', 'value' => 0, 'required' => true, 'attributes' => 'min="0"']) ?>
            <?= partial('field', ['name' => 'shares', 'label' => 'Shares', 'type' => 'number', 'value' => 0, 'required' => true, 'attributes' => 'min="0"']) ?>
          </div>
          <button type="submit" class="btn btn-block">Log post</button>
        <?= form_close() ?>
      </details>
    </section>
  </div>

  <section class="panel min-w-0 overflow-hidden" aria-labelledby="progress-heading">
    <div class="border-b border-stone-200 px-4 py-3 sm:px-5">
      <h2 id="progress-heading" class="<?= $heading ?>">Progress</h2>
      <p class="text-sm text-stone-500">The last 12 weeks, newest first.</p>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th scope="col">Two weeks ending</th>
            <th scope="col" class="num">Enquiries</th>
            <th scope="col" class="num">Became orders</th>
            <th scope="col" class="num">Orders</th>
            <th scope="col" class="num">Sales</th>
            <th scope="col" class="num">Average order</th>
            <th scope="col" class="num">Repeat or referred</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($report['history']) as $index => $period): ?>
            <tr class="<?= $index === 0 ? 'bg-brand-50/50 font-medium' : '' ?>">
              <td><?= esc(date('j M Y', strtotime($period['to']))) ?></td>
              <td class="num"><?= $period['enquiries'] ?></td>
              <td class="num"><?= esc($percent($period['conversion']) ?? '–') ?></td>
              <td class="num"><?= $period['orders'] ?></td>
              <td class="num"><?= esc(money($period['sales'])) ?></td>
              <td class="num"><?= esc($rupees($period['average_order']) ?? '–') ?></td>
              <td class="num"><?= esc($percent($period['repeat_share']) ?? '–') ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <section class="panel min-w-0 overflow-hidden" aria-labelledby="posts-heading">
      <div class="border-b border-stone-200 px-4 py-3 sm:px-5">
        <h2 id="posts-heading" class="<?= $heading ?>">Top posts</h2>
        <p class="text-sm text-stone-500">By saves and shares in these two weeks.</p>
      </div>
      <?php if ($report['top_posts'] === []): ?>
        <p class="p-4 text-sm text-stone-500 sm:px-5">No posts logged for these two weeks. Add them under Add to your records.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th scope="col">Post</th>
                <th scope="col">Posted</th>
                <th scope="col" class="num">Saves</th>
                <th scope="col" class="num">Shares</th>
                <th scope="col"><span class="sr-only">Action</span></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($report['top_posts'] as $post): ?>
                <tr>
                  <td class="font-medium whitespace-normal"><?= esc($post->title) ?></td>
                  <td><?= esc(date('j M', strtotime($post->posted_on))) ?></td>
                  <td class="num"><?= $post->saves ?></td>
                  <td class="num"><?= $post->shares ?></td>
                  <td class="text-right">
                    <?= form_open(url_to('admin.analytics.posts.delete', $post->id), ['data-confirm' => 'Remove this post?']) ?>
                      <button type="submit" class="link-button">Remove</button>
                    <?= form_close() ?>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </section>

    <section class="rounded-xl bg-gold-50 p-4 text-sm text-gold-800 sm:p-5" aria-labelledby="profit-heading">
      <h2 id="profit-heading" class="<?= $heading ?> mb-1 text-gold-800">Profit</h2>
      <p>These figures show sales, not profit, because fabric, labour and other costs are not recorded here. Sales rising does not always mean profit rising, especially during a discount.</p>
    </section>
  </div>
</div>
<?= $this->endSection() ?>
