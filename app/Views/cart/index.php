<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php
$store      = config(\Config\Store::class);
$freeOver   = $store->freeShippingOver * 100;
$toFreeShip = $store->shippingFee > 0 && $freeOver > 0 ? $freeOver - ($totals['subtotal'] - $totals['discount']) : 0;
?>
<div class="wrap page">
  <h1>Your cart</h1>

  <?php foreach ($notices as $notice): ?>
    <p class="flash flash-warn mt-4" role="status"><?= esc($notice) ?></p>
  <?php endforeach ?>

  <?php if ($lines === []): ?>
    <div class="empty mt-6">
      <h2>Your cart is empty</h2>
      <p>Find something you like and it will wait for you here.</p>
      <a class="btn" href="<?= esc(url_to('shop'), 'attr') ?>">Start shopping</a>
    </div>
  <?php else: ?>
    <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_22rem] lg:gap-12">
      <ul class="divide-y divide-stone-200 border-y border-stone-200">
        <?php foreach ($lines as $line): ?>
          <?php
          $product = $line['product'];
          $options = line_options($line['colour'], $line['size']);
          $label   = trim("{$product->name} {$options}");
          ?>
          <li class="flex gap-4 py-5">
            <a class="aspect-[4/5] w-20 shrink-0 overflow-hidden rounded-lg bg-stone-100 sm:w-24" href="<?= esc(url_to('products.show', $product->slug), 'attr') ?>" tabindex="-1" aria-hidden="true">
              <?= partial('product_image', ['product' => $product]) ?>
            </a>

            <div class="flex min-w-0 flex-1 flex-col">
              <div class="flex items-start justify-between gap-4">
                <a class="font-medium text-stone-900 hover:text-brand-800" href="<?= esc(url_to('products.show', $product->slug), 'attr') ?>"><?= esc($product->name) ?></a>
                <p class="shrink-0 font-medium tabular-nums"><?= esc(money($line['total'])) ?></p>
              </div>
              <?php if ($options !== ''): ?>
                <p class="mt-0.5 text-sm text-stone-500"><?= esc($options) ?></p>
              <?php endif ?>
              <p class="mt-0.5 text-sm text-stone-500"><?= esc(money($product->price)) ?> each</p>

              <div class="mt-auto flex items-center gap-4 pt-3">
                <?= form_open(url_to('cart.update', $product->id), ['class' => 'inline-flex items-center rounded-lg ring-1 ring-stone-300 ring-inset']) ?>
                  <input type="hidden" name="colour" value="<?= esc($line['colour'], 'attr') ?>">
                  <input type="hidden" name="size" value="<?= esc($line['size'], 'attr') ?>">
                  <button class="grid size-9 place-items-center rounded-l-lg transition-colors hover:bg-stone-100 disabled:cursor-not-allowed disabled:opacity-40" type="submit" name="quantity" value="<?= $line['quantity'] - 1 ?>" aria-label="One fewer <?= esc($label, 'attr') ?>" <?= $line['quantity'] <= 1 ? 'disabled' : '' ?>>−</button>
                  <span class="w-8 text-center text-sm tabular-nums"><span class="sr-only">Quantity </span><?= $line['quantity'] ?></span>
                  <button class="grid size-9 place-items-center rounded-r-lg transition-colors hover:bg-stone-100 disabled:cursor-not-allowed disabled:opacity-40" type="submit" name="quantity" value="<?= $line['quantity'] + 1 ?>" aria-label="One more <?= esc($label, 'attr') ?>" <?= $line['more'] ? '' : 'disabled' ?>>+</button>
                <?= form_close() ?>

                <?= form_open(url_to('cart.remove', $product->id)) ?>
                  <input type="hidden" name="colour" value="<?= esc($line['colour'], 'attr') ?>">
                  <input type="hidden" name="size" value="<?= esc($line['size'], 'attr') ?>">
                  <button type="submit" class="link-button" aria-label="Remove <?= esc($label, 'attr') ?> from cart">Remove</button>
                <?= form_close() ?>
              </div>
            </div>
          </li>
        <?php endforeach ?>
      </ul>

      <aside class="panel self-start p-6 lg:sticky lg:top-24" aria-label="Order summary">
        <h2 class="mb-4 text-xl">Summary</h2>
        <?= partial('order_totals', $totals) ?>
        <?php if ($toFreeShip > 0): ?>
          <p class="mt-4 rounded-lg bg-gold-50 px-3 py-2 text-sm text-gold-800">Add <?= esc(money($toFreeShip)) ?> more for free delivery.</p>
        <?php endif ?>

        <div class="mt-5 border-t border-stone-200 pt-4">
          <?php if ($voucher !== null): ?>
            <?= form_open(url_to('cart.voucher.remove'), ['class' => 'flex items-center justify-between gap-3 text-sm']) ?>
              <span>Voucher <strong class="font-medium"><?= esc($voucher) ?></strong></span>
              <button type="submit" class="link-button">Remove</button>
            <?= form_close() ?>
            <?php if ($voucherNote !== null): ?>
              <p class="mt-2 text-sm text-stone-500"><?= esc($voucherNote) ?></p>
            <?php endif ?>
          <?php else: ?>
            <?= form_open(url_to('cart.voucher')) ?>
              <label class="label" for="voucher-code">Have a voucher?</label>
              <div class="flex gap-2">
                <input class="input uppercase" id="voucher-code" type="text" name="code" maxlength="24" autocomplete="off" autocapitalize="characters">
                <button type="submit" class="btn btn-outline shrink-0">Apply</button>
              </div>
            <?= form_close() ?>
          <?php endif ?>
        </div>

        <a class="btn btn-large btn-block mt-5" href="<?= esc(url_to('checkout'), 'attr') ?>">Go to checkout</a>
        <p class="mt-3 text-center"><a class="text-link text-sm" href="<?= esc(url_to('shop'), 'attr') ?>">Keep shopping</a></p>
      </aside>
    </div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
