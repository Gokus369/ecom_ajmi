<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', [
    'back'     => [url_to('admin.orders'), 'All orders'],
    'title'    => "Order {$order->number}",
    'lede'     => 'Placed ' . local_time($order->created_at),
    'statusOf' => $order,
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Order;

$actionLabels = [
    Order::CONFIRMED => 'Confirm order',
    Order::SHIPPED   => 'Mark as shipped',
    Order::DELIVERED => 'Mark as delivered',
];
$steps   = [Order::PENDING, Order::CONFIRMED, Order::SHIPPED, Order::DELIVERED];
$reached = array_search($order->status, $steps, true);
?>
<?php if ($order->status !== Order::CANCELLED): ?>
  <div class="panel mb-5 flex flex-wrap items-center justify-between gap-x-10 gap-y-5 p-4 sm:p-5">
    <ol class="grid max-w-xl min-w-0 flex-[1_1_22rem] grid-cols-4 text-xs text-stone-500 sm:text-sm" aria-label="Order progress">
      <?php foreach ($steps as $index => $step): ?>
        <li class="group relative flex flex-col items-center gap-1.5 not-first:before:absolute not-first:before:top-3.25 not-first:before:right-1/2 not-first:before:h-0.5 not-first:before:w-full <?= $index <= $reached ? 'font-medium text-stone-950 not-first:before:bg-brand-800' : 'not-first:before:bg-stone-200' ?>" <?= $index === $reached ? 'aria-current="step"' : '' ?>>
          <span class="relative z-1 grid size-7 place-items-center rounded-full border-2 group-aria-[current=step]:ring-4 group-aria-[current=step]:ring-gold-500/30 [&_svg]:stroke-3 <?= $index <= $reached ? 'border-brand-800 bg-brand-800 text-white' : 'border-stone-200 bg-white' ?>"><?= $index <= $reached ? partial('icon', ['name' => 'check', 'size' => 14]) : '' ?></span>
          <?= esc(Order::STATUS_LABELS[$step]) ?>
        </li>
      <?php endforeach ?>
    </ol>

    <?php if ($order->nextStatuses() !== []): ?>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($order->nextStatuses() as $next): ?>
          <?php $isCancel = $next === Order::CANCELLED; ?>
          <?= form_open(url_to('admin.orders.status', $order->id), array_filter([
              'data-submit-once' => true,
              'data-confirm'     => $isCancel ? 'Cancel this order? Its items go back into stock.' : null,
          ])) ?>
            <input type="hidden" name="status" value="<?= esc($next, 'attr') ?>">
            <button type="submit" class="btn <?= $isCancel ? 'btn-danger-outline' : '' ?>"><?= esc($isCancel ? 'Cancel order' : $actionLabels[$next]) ?></button>
          <?= form_close() ?>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>
<?php endif ?>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
  <section class="panel min-w-0 overflow-hidden" aria-labelledby="items-heading">
    <h2 id="items-heading" class="border-b border-stone-200 px-4 py-3 font-sans text-base font-semibold tracking-normal">Items</h2>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th scope="col">Product</th>
            <th scope="col" class="num">Price</th>
            <th scope="col" class="num">Qty</th>
            <th scope="col" class="num">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <?php $options = line_options($item->colour, $item->size); ?>
            <tr>
              <td class="min-w-32 whitespace-normal">
                <?= esc($item->name) ?>
                <?php if ($options !== ''): ?>
                  <span class="block text-xs text-stone-500"><?= esc($options) ?></span>
                <?php endif ?>
              </td>
              <td class="num"><?= esc(money($item->unit_price)) ?></td>
              <td class="num"><?= $item->quantity ?></td>
              <td class="num"><?= esc(money($item->line_total)) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php // The totals partial is shared with the store, so its parts are styled from out here. ?>
    <div class="border-t border-stone-200 p-4 [&_dl]:ml-auto [&_dl]:max-w-xs">
      <?= partial('order_totals', ['subtotal' => $order->subtotal, 'discount' => $order->discount, 'discount_label' => $order->discount_label, 'shipping' => $order->shipping, 'total' => $order->total]) ?>
    </div>
  </section>

  <section class="panel min-w-0 p-4 sm:p-5" aria-labelledby="customer-heading">
    <h2 id="customer-heading" class="mb-3 font-sans text-base font-semibold tracking-normal">Customer</h2>
    <address class="text-sm/6 wrap-anywhere">
      <strong class="font-medium text-stone-950"><?= esc($order->customer_name) ?></strong><br>
      <a class="text-link" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $order->phone), 'attr') ?>"><?= esc($order->phone) ?></a><br>
      <?php if ($order->email): ?>
        <a class="text-link" href="mailto:<?= esc($order->email, 'attr') ?>"><?= esc($order->email) ?></a><br>
      <?php endif ?>
      <br>
      <?= nl2br(esc($order->address)) ?><br>
      <?= esc($order->city) ?>, <?= esc($order->state) ?> <?= esc($order->postcode) ?>
    </address>

    <?php if ($order->notes): ?>
      <h3 class="mt-4 mb-1 font-sans text-xs font-medium tracking-normal text-stone-500">Delivery notes</h3>
      <p class="text-sm/6 wrap-anywhere"><?= nl2br(esc($order->notes)) ?></p>
    <?php endif ?>

    <h3 class="mt-4 mb-1 font-sans text-xs font-medium tracking-normal text-stone-500">Payment</h3>
    <p class="text-sm/6">Cash on delivery</p>
      <?= form_open(url_to('admin.orders.source', $order->id), ['class' => 'mt-5 flex items-end gap-2 border-t border-stone-200 pt-4']) ?>
      <div class="min-w-0 flex-1">
        <?= partial('field', ['name' => 'heard_from', 'label' => 'Came from', 'type' => 'select', 'options' => ['' => 'Not known'] + \App\Entities\Enquiry::SOURCE_LABELS, 'value' => $order->heard_from ?? '', 'required' => true]) ?>
      </div>
      <button type="submit" class="btn btn-outline">Save</button>
    <?= form_close() ?>
  </section>
</div>
<?= $this->endSection() ?>
