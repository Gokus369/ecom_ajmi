<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['eyebrow' => 'Sales', 'title' => 'Vouchers', 'lede' => 'Generate codes for customers to enter at the cart.']) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Offer;

$total  = $pager->getTotal();
$export = url_to('admin.vouchers.export') . ($batch !== null ? '?batch=' . urlencode($batch) : '');
?>
<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
  <section class="panel min-w-0 overflow-hidden" aria-labelledby="list-heading">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 px-4 py-3">
      <h2 id="list-heading" class="font-sans text-base font-semibold tracking-normal"><?= $batch !== null ? 'This batch' : 'All vouchers' ?> <span class="font-normal text-stone-500">(<?= number_format($total) ?>)</span></h2>
      <div class="flex items-center gap-4">
        <?php if ($batch !== null): ?>
          <a class="text-link text-sm" href="<?= esc(url_to('admin.vouchers'), 'attr') ?>">Show all</a>
        <?php endif ?>
        <?php if ($vouchers !== []): ?>
          <a class="btn btn-outline btn-small" href="<?= esc($export, 'attr') ?>">Download CSV</a>
        <?php endif ?>
      </div>
    </div>

    <?php if ($vouchers === []): ?>
      <div class="empty border-0 py-12">
        <span class="mb-3 inline-grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500"><?= partial('icon', ['name' => 'ticket', 'size' => 24]) ?></span>
        <h3 class="font-sans text-base font-semibold tracking-normal">No vouchers yet</h3>
        <p class="mb-0">Use the form on this page to make your first batch.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap sm:max-h-[75vh]">
        <table class="table border-separate border-spacing-0">
          <thead class="sticky top-0 z-1 [&_th]:shadow-[0_1px_0_var(--color-stone-200)]">
            <tr>
              <th scope="col">Code</th>
              <th scope="col">Discount</th>
              <th scope="col">Expires</th>
              <th scope="col" class="num">Used</th>
              <th scope="col">Status</th>
              <th scope="col"><span class="sr-only">Action</span></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($vouchers as $voucher): ?>
              <?php [$label, $tone] = $voucher->state($today); ?>
              <tr>
                <td>
                  <span class="font-mono font-medium select-all"><?= esc($voucher->code) ?></span>
                  <span class="block text-xs text-stone-500"><?= esc($voucher->name) ?></span>
                </td>
                <td>
                  <?= esc($voucher->discount_text) ?>
                  <?php if ($voucher->min_order > 0): ?>
                    <span class="block text-xs text-stone-500">Over <?= esc(money($voucher->min_order)) ?></span>
                  <?php endif ?>
                </td>
                <td><?= $voucher->expires_on ? esc(date('j M Y', strtotime($voucher->expires_on))) : 'Never' ?></td>
                <td class="num"><?= $voucher->used_count ?> of <?= $voucher->max_uses ?></td>
                <td><span class="status <?= $tone ?>"><?= $label ?></span></td>
                <td class="text-right">
                  <?= form_open(url_to('admin.vouchers.toggle', $voucher->id), ['data-submit-once' => true]) ?>
                    <button type="submit" class="link-button"><?= $voucher->is_active ? 'Turn off' : 'Turn on' ?></button>
                  <?= form_close() ?>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>

      <div class="has-[nav]:border-t has-[nav]:border-stone-200 has-[nav]:p-2 [&_ul]:mt-0"><?= $pager->links('default', 'store') ?></div>
    <?php endif ?>
  </section>

  <section class="panel min-w-0 p-4 sm:p-5" aria-labelledby="generate-heading">
    <h2 id="generate-heading" class="mb-4 font-sans text-base font-semibold tracking-normal">Generate vouchers</h2>
    <?= form_open(url_to('admin.vouchers.generate'), ['class' => 'grid gap-4', 'novalidate' => true, 'data-submit-once' => true]) ?>
      <?= partial('field', ['name' => 'name', 'label' => 'Name', 'required' => true, 'hint' => 'For your records, like Onam gift vouchers.', 'attributes' => 'maxlength="100"']) ?>
      <div class="grid grid-cols-2 gap-4">
        <?= partial('field', ['name' => 'quantity', 'label' => 'How many', 'type' => 'number', 'value' => 10, 'required' => true, 'attributes' => 'min="1" max="500"']) ?>
        <?= partial('field', ['name' => 'prefix', 'label' => 'Code starts with', 'attributes' => 'maxlength="10" placeholder="ONAM"']) ?>
      </div>
      <?= partial('field', ['name' => 'discount_type', 'label' => 'Discount type', 'type' => 'select', 'options' => Offer::DISCOUNT_TYPES, 'value' => Offer::PERCENT, 'required' => true]) ?>
      <?= partial('field', ['name' => 'discount_value', 'label' => 'Discount', 'required' => true, 'hint' => 'A percent from 1 to 90, or an amount in rupees.', 'attributes' => 'inputmode="decimal"']) ?>
      <?= partial('field', ['name' => 'min_order', 'label' => 'Minimum order (₹)', 'attributes' => 'inputmode="decimal"']) ?>
      <div class="grid grid-cols-2 gap-4">
        <?= partial('field', ['name' => 'expires_on', 'label' => 'Last day', 'type' => 'date', 'attributes' => 'min="' . esc($today, 'attr') . '"']) ?>
        <?= partial('field', ['name' => 'max_uses', 'label' => 'Uses per code', 'type' => 'number', 'value' => 1, 'required' => true, 'attributes' => 'min="1"']) ?>
      </div>
      <button type="submit" class="btn btn-block">Generate</button>
    <?= form_close() ?>
  </section>
</div>
<?= $this->endSection() ?>
