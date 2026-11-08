<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Order;

$steps     = [Order::PENDING, Order::CONFIRMED, Order::SHIPPED, Order::DELIVERED];
$reached   = array_search($order->status, $steps, true);
$cancelled = $order->status === Order::CANCELLED;
$store     = config(\Config\Store::class);
$whatsapp  = whatsapp_url("Hi {$store->shortName}, I'm messaging about order {$order->number}.");
?>
<div class="wrap page">
  <?php if (session('placed')): ?>
    <div>
      <h1>Thank you, <?= esc($order->customer_name) ?>.</h1>
      <p class="lede mt-2">Your order is in. We will contact you on <?= esc($order->phone) ?> to confirm it.</p>
    </div>
  <?php else: ?>
    <h1>Your order</h1>
  <?php endif ?>

  <div class="panel mt-6 grid gap-4 p-5 text-sm sm:grid-cols-3">
    <div><span class="muted block">Order number</span><strong class="font-semibold break-all"><?= esc($order->number) ?></strong></div>
    <div><span class="muted block">Placed</span><strong class="font-semibold"><?= esc(local_time($order->created_at, 'j M Y')) ?></strong></div>
    <div><span class="muted block">Payment</span><strong class="font-semibold">Cash on delivery</strong></div>
  </div>

  <?php if ($cancelled): ?>
    <p class="flash flash-danger mt-6">This order was cancelled.</p>
  <?php else: ?>
    <ol class="mt-6 grid grid-cols-4 gap-2" aria-label="Order progress">
      <?php foreach ($steps as $index => $step): ?>
        <li class="border-t-4 pt-2 text-xs sm:text-sm <?= $index <= $reached ? 'border-brand-800 font-medium text-stone-900' : 'border-stone-200 text-stone-500' ?>" <?= $index === $reached ? 'aria-current="step"' : '' ?>>
          <?= esc($step === Order::PENDING ? 'Received' : Order::STATUS_LABELS[$step]) ?>
        </li>
      <?php endforeach ?>
    </ol>
  <?php endif ?>

  <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem] lg:gap-12">
    <section aria-labelledby="items-heading">
      <h2 id="items-heading" class="mb-4 text-xl">Items</h2>
      <ul class="mb-5 grid gap-3 border-b border-stone-200 pb-5 text-sm">
        <?php foreach ($items as $item): ?>
          <?php $options = line_options($item->colour, $item->size); ?>
          <li class="flex justify-between gap-4">
            <span>
              <?= esc($item->name) ?> <span class="muted">× <?= $item->quantity ?></span>
              <?php if ($options !== ''): ?>
                <span class="block text-stone-500"><?= esc($options) ?></span>
              <?php endif ?>
            </span>
            <span class="shrink-0 tabular-nums"><?= esc(money($item->line_total)) ?></span>
          </li>
        <?php endforeach ?>
      </ul>
      <?= partial('order_totals', ['subtotal' => $order->subtotal, 'discount' => $order->discount, 'discount_label' => $order->discount_label, 'shipping' => $order->shipping, 'total' => $order->total]) ?>
    </section>

    <aside class="panel self-start p-6" aria-label="Delivery address">
      <h2 class="mb-3 text-xl">Delivering to</h2>
      <address class="text-sm/relaxed text-stone-700">
        <?= esc($order->customer_name) ?><br>
        <?= nl2br(esc($order->address)) ?><br>
        <?= esc($order->city) ?>, <?= esc($order->state) ?> <?= esc($order->postcode) ?><br>
        <?= esc($order->phone) ?>
      </address>
      <?php if ($whatsapp): ?>
        <a class="btn btn-block mt-5" href="<?= esc($whatsapp, 'attr') ?>" target="_blank" rel="noopener">Message us about this order</a>
      <?php endif ?>
      <p class="field-hint">Bookmark this page to check on your order. Anyone with the link can see it, so please keep it private.</p>
    </aside>
  </div>

  <p class="mt-8"><a class="text-link text-sm" href="<?= esc(url_to('shop'), 'attr') ?>">Continue shopping</a></p>
</div>
<?= $this->endSection() ?>
