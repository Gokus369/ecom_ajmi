<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['eyebrow' => local_time(\CodeIgniter\I18n\Time::now(), 'l, j F'), 'title' => 'Dashboard', 'lede' => $attention]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php // A gold top edge and a wine number mean something is waiting here. Each card's link covers the card. ?>
<dl class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-3 2xl:grid-cols-6">
  <div class="panel relative flex flex-col gap-1 p-4 transition-shadow has-[a]:hover:shadow-md <?= $newOrders > 0 ? 'border-t-2 border-t-gold-500 [&_dd]:text-brand-800' : '' ?>">
    <dt class="flex items-start justify-between gap-2 text-sm/5 text-stone-500">New orders <span class="shrink-0 text-stone-400"><?= partial('icon', ['name' => 'orders', 'size' => 18]) ?></span></dt>
    <dd class="text-xl font-semibold text-stone-950 tabular-nums wrap-anywhere sm:text-2xl"><?= number_format($newOrders) ?></dd>
    <a class="mt-auto inline-flex w-fit items-center gap-1 text-sm font-medium text-brand-800 after:absolute after:inset-0 [&_svg]:transition-transform hover:[&_svg]:translate-x-0.5" href="<?= esc(url_to('admin.orders') . '?status=pending', 'attr') ?>">Review <?= partial('icon', ['name' => 'forward', 'size' => 14]) ?></a>
  </div>
  <div class="panel relative flex flex-col gap-1 p-4 transition-shadow has-[a]:hover:shadow-md <?= $newBookings > 0 ? 'border-t-2 border-t-gold-500 [&_dd]:text-brand-800' : '' ?>">
    <dt class="flex items-start justify-between gap-2 text-sm/5 text-stone-500">Bookings to confirm <span class="shrink-0 text-stone-400"><?= partial('icon', ['name' => 'calendar', 'size' => 18]) ?></span></dt>
    <dd class="text-xl font-semibold text-stone-950 tabular-nums wrap-anywhere sm:text-2xl"><?= number_format($newBookings) ?></dd>
    <a class="mt-auto inline-flex w-fit items-center gap-1 text-sm font-medium text-brand-800 after:absolute after:inset-0 [&_svg]:transition-transform hover:[&_svg]:translate-x-0.5" href="<?= esc(url_to('admin.bookings') . '?status=pending', 'attr') ?>">Review <?= partial('icon', ['name' => 'forward', 'size' => 14]) ?></a>
  </div>
  <div class="panel relative flex flex-col gap-1 p-4 transition-shadow has-[a]:hover:shadow-md <?= $newEnquiries > 0 ? 'border-t-2 border-t-gold-500 [&_dd]:text-brand-800' : '' ?>">
    <dt class="flex items-start justify-between gap-2 text-sm/5 text-stone-500">Enquiries to answer <span class="shrink-0 text-stone-400"><?= partial('icon', ['name' => 'enquiries', 'size' => 18]) ?></span></dt>
    <dd class="text-xl font-semibold text-stone-950 tabular-nums wrap-anywhere sm:text-2xl"><?= number_format($newEnquiries) ?></dd>
    <a class="mt-auto inline-flex w-fit items-center gap-1 text-sm font-medium text-brand-800 after:absolute after:inset-0 [&_svg]:transition-transform hover:[&_svg]:translate-x-0.5" href="<?= esc(url_to('admin.enquiries') . '?status=pending', 'attr') ?>">Review <?= partial('icon', ['name' => 'forward', 'size' => 14]) ?></a>
  </div>
  <div class="panel relative flex flex-col gap-1 p-4 transition-shadow has-[a]:hover:shadow-md">
    <dt class="flex items-start justify-between gap-2 text-sm/5 text-stone-500">Ready to ship <span class="shrink-0 text-stone-400"><?= partial('icon', ['name' => 'ship', 'size' => 18]) ?></span></dt>
    <dd class="text-xl font-semibold text-stone-950 tabular-nums wrap-anywhere sm:text-2xl"><?= number_format($toShip) ?></dd>
    <a class="mt-auto inline-flex w-fit items-center gap-1 text-sm font-medium text-brand-800 after:absolute after:inset-0 [&_svg]:transition-transform hover:[&_svg]:translate-x-0.5" href="<?= esc(url_to('admin.orders') . '?status=confirmed', 'attr') ?>">View <?= partial('icon', ['name' => 'forward', 'size' => 14]) ?></a>
  </div>
  <div class="panel relative flex flex-col gap-1 p-4">
    <dt class="flex items-start justify-between gap-2 text-sm/5 text-stone-500">Sales, last 30 days <span class="shrink-0 text-stone-400"><?= partial('icon', ['name' => 'rupee', 'size' => 18]) ?></span></dt>
    <dd class="text-xl font-semibold text-stone-950 tabular-nums wrap-anywhere sm:text-2xl"><?= esc(money($revenue)) ?></dd>
  </div>
  <div class="panel relative flex flex-col gap-1 p-4 transition-shadow has-[a]:hover:shadow-md">
    <dt class="flex items-start justify-between gap-2 text-sm/5 text-stone-500">Products live <span class="shrink-0 text-stone-400"><?= partial('icon', ['name' => 'products', 'size' => 18]) ?></span></dt>
    <dd class="text-xl font-semibold text-stone-950 tabular-nums wrap-anywhere sm:text-2xl"><?= number_format($liveCount) ?></dd>
    <a class="mt-auto inline-flex w-fit items-center gap-1 text-sm font-medium text-brand-800 after:absolute after:inset-0 [&_svg]:transition-transform hover:[&_svg]:translate-x-0.5" href="<?= esc(url_to('admin.products'), 'attr') ?>">Manage <?= partial('icon', ['name' => 'forward', 'size' => 14]) ?></a>
  </div>
</dl>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
  <section class="panel min-w-0 overflow-hidden" aria-labelledby="recent-heading">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-stone-200 px-4 py-3">
      <h2 id="recent-heading" class="font-sans text-base font-semibold tracking-normal">Recent orders</h2>
      <?php if ($recent !== []): ?>
        <a class="text-link text-sm" href="<?= esc(url_to('admin.orders'), 'attr') ?>">View all</a>
      <?php endif ?>
    </div>
    <?php if ($recent === []): ?>
      <div class="empty border-0 py-10">
        <span class="mb-3 inline-grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500"><?= partial('icon', ['name' => 'orders', 'size' => 24]) ?></span>
        <h3 class="font-sans text-base font-semibold tracking-normal">No orders yet</h3>
        <p class="mb-0">They will appear here as soon as someone checks out.</p>
      </div>
    <?php else: ?>
      <?= partial('order_rows', ['orders' => $recent]) ?>
    <?php endif ?>
  </section>

  <div class="grid min-w-0 gap-5">
    <section class="panel min-w-0 overflow-hidden" aria-labelledby="upcoming-heading">
      <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-stone-200 px-4 py-3">
        <h2 id="upcoming-heading" class="font-sans text-base font-semibold tracking-normal">Upcoming weddings</h2>
        <?php if ($upcoming !== []): ?>
          <a class="text-link text-sm" href="<?= esc(url_to('admin.bookings'), 'attr') ?>">View all</a>
        <?php endif ?>
      </div>
      <?php if ($upcoming === []): ?>
        <p class="muted p-4 text-sm">Saree draping requests will appear here.</p>
      <?php else: ?>
        <ul class="divide-y divide-stone-100">
          <?php foreach ($upcoming as $booking): ?>
            <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-2.5 text-sm">
              <span class="min-w-0">
                <a class="font-medium text-brand-800" href="<?= esc(url_to('admin.bookings.show', $booking->id), 'attr') ?>"><?= esc($booking->customer_name) ?></a>
                <span class="block text-xs text-stone-500"><?= esc($booking->date_label) ?></span>
              </span>
              <span class="status status-<?= esc($booking->status, 'attr') ?>"><?= esc($booking->status_label) ?></span>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </section>

    <section class="panel min-w-0 overflow-hidden" aria-labelledby="stock-heading">
      <h2 id="stock-heading" class="border-b border-stone-200 px-4 py-3 font-sans text-base font-semibold tracking-normal">Low stock</h2>
      <?php if ($lowStock === []): ?>
        <p class="muted p-4 text-sm">Everything is well stocked.</p>
      <?php else: ?>
        <ul class="divide-y divide-stone-100">
          <?php foreach ($lowStock as $product): ?>
            <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-2.5 text-sm">
              <a class="min-w-0 font-medium text-brand-800" href="<?= esc(url_to('admin.products.edit', $product->id), 'attr') ?>"><?= esc($product->name) ?></a>
              <span class="<?= $product->stock === 0 ? 'status status-cancelled' : 'status status-pending' ?>">
                <?= $product->stock === 0 ? 'Sold out' : $product->stock . ' left' ?>
              </span>
            </li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </section>
  </div>
</div>
<?= $this->endSection() ?>
