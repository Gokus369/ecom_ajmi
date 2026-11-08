<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['eyebrow' => 'Sales', 'title' => 'Orders', 'lede' => 'Confirm, ship and track every order.']) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Order;

$baseUrl   = url_to('admin.orders');
$statusUrl = $baseUrl . ($status !== null ? '?status=' . $status : '');
$total     = $pager->getTotal();
?>
<nav class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 py-0.5 [scrollbar-width:none] sm:-mx-6 sm:px-6 lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0" aria-label="Filter orders by status">
  <a class="flex min-h-10 flex-none items-center gap-2 rounded-lg border border-stone-200 bg-white px-2.5 shadow-xs transition-colors hover:bg-stone-50 aria-[current=page]:border-brand-800 aria-[current=page]:ring-1 aria-[current=page]:ring-brand-800" href="<?= esc($baseUrl, 'attr') ?>" <?= $status === null ? 'aria-current="page"' : '' ?>>
    <span class="status">All</span>
    <span class="text-sm font-semibold text-stone-950 tabular-nums"><?= (int) array_sum($counts) ?></span>
  </a>
  <?php foreach (Order::STATUS_LABELS as $value => $label): ?>
    <a class="flex min-h-10 flex-none items-center gap-2 rounded-lg border border-stone-200 bg-white px-2.5 shadow-xs transition-colors hover:bg-stone-50 aria-[current=page]:border-brand-800 aria-[current=page]:ring-1 aria-[current=page]:ring-brand-800" href="<?= esc($baseUrl . '?status=' . $value, 'attr') ?>" <?= $status === $value ? 'aria-current="page"' : '' ?>>
      <span class="status status-<?= esc($value, 'attr') ?>"><?= esc($label) ?></span>
      <span class="text-sm font-semibold text-stone-950 tabular-nums"><?= (int) ($counts[$value] ?? 0) ?></span>
    </a>
  <?php endforeach ?>
</nav>

<div class="panel overflow-hidden">
  <form class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-stone-200 p-3 sm:px-4" action="<?= esc($baseUrl, 'attr') ?>" method="get" role="search">
    <?php if ($status !== null): ?>
      <input type="hidden" name="status" value="<?= esc($status, 'attr') ?>">
    <?php endif ?>
    <label class="sr-only" for="q">Search orders</label>
    <span class="relative min-w-0 flex-[1_1_14rem] sm:max-w-md">
      <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-stone-400"><?= partial('icon', ['name' => 'search', 'size' => 18]) ?></span>
      <input class="input h-10 py-0 pl-10" id="q" type="search" name="q" value="<?= esc($term, 'attr') ?>" placeholder="Order number, name or phone" maxlength="80">
    </span>
    <button type="submit" class="btn btn-outline h-10 py-0">Search</button>
    <?php if ($term !== ''): ?>
      <a class="text-link text-sm" href="<?= esc($statusUrl, 'attr') ?>">Clear</a>
    <?php endif ?>
    <?php if ($orders !== []): ?>
      <p class="ml-auto text-sm text-stone-500"><?= number_format($total) ?> <?= $total === 1 ? 'order' : 'orders' ?></p>
    <?php endif ?>
  </form>

  <?php if ($orders === []): ?>
    <div class="empty border-0 py-12">
      <span class="mb-3 inline-grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500"><?= partial('icon', ['name' => 'orders', 'size' => 24]) ?></span>
      <?php if ($term !== '' || $status !== null): ?>
        <h2 class="font-sans text-base font-semibold tracking-normal">No orders match that filter</h2>
        <p>Try a different name, phone or order number.</p>
        <a class="btn btn-outline" href="<?= esc($baseUrl, 'attr') ?>">Show all orders</a>
      <?php else: ?>
        <h2 class="font-sans text-base font-semibold tracking-normal">No orders yet</h2>
        <p>They will appear here as soon as someone checks out.</p>
        <a class="btn btn-outline" href="<?= esc(url_to('home'), 'attr') ?>">View store</a>
      <?php endif ?>
    </div>
  <?php else: ?>
    <?= partial('order_rows', ['orders' => $orders]) ?>
    <div class="has-[nav]:border-t has-[nav]:border-stone-200 has-[nav]:p-2 [&_ul]:mt-0"><?= $pager->links('default', 'store') ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
