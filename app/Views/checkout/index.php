<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php $user = auth()->user(); ?>
<div class="wrap page">
  <h1>Checkout</h1>

  <?php foreach ($notices as $notice): ?>
    <p class="flash flash-warn mt-4" role="status"><?= esc($notice) ?></p>
  <?php endforeach ?>

  <?= form_open(url_to('checkout.place'), ['class' => 'mt-6 grid gap-8 lg:grid-cols-[1fr_24rem] lg:gap-12', 'novalidate' => true, 'data-submit-once' => true]) ?>
    <input type="hidden" name="expected_total" value="<?= $totals['total'] ?>">

    <div class="grid gap-8">
      <fieldset>
        <legend class="mb-4 font-display text-xl font-medium">Delivery details</legend>
        <div class="form-grid">
          <?= partial('field', ['name' => 'customer_name', 'label' => 'Full name', 'value' => $user?->name, 'required' => true, 'attributes' => 'autocomplete="name" maxlength="100"']) ?>
          <?= partial('field', ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true, 'hint' => 'The courier will call this number.', 'attributes' => 'autocomplete="tel" maxlength="20"']) ?>
          <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $user?->email, 'attributes' => 'autocomplete="email" maxlength="120"']) ?>
          <div class="form-wide">
            <?= partial('field', ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 3, 'required' => true, 'hint' => 'House or flat number, street and any landmark.', 'attributes' => 'autocomplete="street-address" maxlength="255"']) ?>
          </div>
          <?= partial('field', ['name' => 'city', 'label' => 'City or town', 'required' => true, 'attributes' => 'autocomplete="address-level2" maxlength="80"']) ?>
          <?= partial('field', ['name' => 'state', 'label' => 'State', 'required' => true, 'attributes' => 'autocomplete="address-level1" maxlength="80"']) ?>
          <?= partial('field', ['name' => 'postcode', 'label' => 'PIN code', 'required' => true, 'attributes' => 'autocomplete="postal-code" inputmode="numeric" maxlength="12"']) ?>
          <div class="form-wide">
            <?= partial('field', ['name' => 'notes', 'label' => 'Delivery notes', 'type' => 'textarea', 'rows' => 2, 'attributes' => 'maxlength="500"']) ?>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend class="mb-4 font-display text-xl font-medium">Payment</legend>
        <p class="rounded-lg bg-brand-50 p-4 text-sm ring-2 ring-brand-800"><strong class="font-semibold">Cash on delivery.</strong> Pay the courier when your order arrives.</p>
      </fieldset>
    </div>

    <aside class="panel self-start p-6 lg:sticky lg:top-24" aria-label="Order summary">
      <h2 class="mb-4 text-xl">Your order</h2>
      <ul class="mb-4 grid gap-3 border-b border-stone-200 pb-4 text-sm">
        <?php foreach ($lines as $line): ?>
          <?php $options = line_options($line['colour'], $line['size']); ?>
          <li class="flex justify-between gap-4">
            <span>
              <?= esc($line['product']->name) ?> <span class="muted">× <?= $line['quantity'] ?></span>
              <?php if ($options !== ''): ?>
                <span class="block text-stone-500"><?= esc($options) ?></span>
              <?php endif ?>
            </span>
            <span class="shrink-0 tabular-nums"><?= esc(money($line['total'])) ?></span>
          </li>
        <?php endforeach ?>
      </ul>
      <?= partial('order_totals', $totals) ?>
      <button type="submit" class="btn btn-large btn-block mt-5">Place order</button>
      <p class="mt-3 text-center"><a class="text-link text-sm" href="<?= esc(url_to('cart'), 'attr') ?>">Back to cart</a></p>
    </aside>
  <?= form_close() ?>
</div>
<?= $this->endSection() ?>
