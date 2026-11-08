<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<div class="wrap page">
  <div class="mb-8 flex flex-wrap items-center justify-between gap-4 border-b border-stone-200 pb-6">
    <div>
      <h1>Hello, <?= esc($user->name ?? 'there') ?></h1>
      <p class="muted mt-1 text-sm"><?= esc($user->email) ?></p>
    </div>
    <a class="btn btn-outline" href="<?= esc(url_to('logout'), 'attr') ?>">Sign out</a>
  </div>

  <h2 class="mb-4 text-xl">Your orders</h2>

  <?php if ($orders === []): ?>
    <div class="empty">
      <h3>No orders yet</h3>
      <p>Orders you place while signed in will appear here.</p>
      <a class="btn" href="<?= esc(url_to('shop'), 'attr') ?>">Start shopping</a>
    </div>
  <?php else: ?>
    <ul class="panel divide-y divide-stone-200 overflow-hidden">
      <?php foreach ($orders as $order): ?>
        <li>
          <a class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1 px-5 py-4 text-sm transition-colors hover:bg-stone-50 sm:grid-cols-[2fr_2fr_1fr_1fr]" href="<?= esc(url_to('orders.show', $order->number), 'attr') ?>">
            <span class="font-medium"><?= esc($order->number) ?></span>
            <span class="muted max-sm:text-right"><?= esc(local_time($order->created_at, 'j M Y')) ?></span>
            <span class="status status-<?= esc($order->status, 'attr') ?>"><?= esc($order->status_label) ?></span>
            <span class="text-right font-medium tabular-nums"><?= esc(money($order->total)) ?></span>
          </a>
        </li>
      <?php endforeach ?>
    </ul>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
