<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', [
    'back'     => [url_to('admin.enquiries'), 'All enquiries'],
    'title'    => "{$enquiry->type_label}, {$enquiry->customer_name}",
    'lede'     => 'Received ' . local_time($enquiry->created_at),
    'statusOf' => $enquiry,
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Enquiry;

$isBridal = $enquiry->type === Enquiry::BRIDAL;
?>
<?php if ($enquiry->nextStatuses() !== []): ?>
  <div class="panel mb-5 flex flex-wrap gap-2 p-4">
    <?php foreach ($enquiry->nextStatuses() as $next): ?>
      <?php $isClose = $next === Enquiry::CLOSED; ?>
      <?= form_open(url_to('admin.enquiries.status', $enquiry->id), array_filter([
          'data-submit-once' => true,
          'data-confirm'     => $isClose ? 'Close this enquiry? It cannot be reopened.' : null,
      ])) ?>
        <input type="hidden" name="status" value="<?= esc($next, 'attr') ?>">
        <button type="submit" class="btn <?= $isClose ? 'btn-outline' : '' ?>"><?= $isClose ? 'Close enquiry' : 'Mark as contacted' ?></button>
      <?= form_close() ?>
    <?php endforeach ?>
  </div>
<?php endif ?>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
  <section class="panel grid min-w-0 gap-4 p-4 text-sm/6 wrap-anywhere sm:p-5" aria-labelledby="details-heading">
    <h2 id="details-heading" class="font-sans text-base font-semibold tracking-normal">Details</h2>
    <p><strong class="block text-xs font-medium text-stone-500">Looking for</strong><?= esc($enquiry->type_label) ?></p>
    <?php if ($enquiry->date_label): ?>
      <p><strong class="block text-xs font-medium text-stone-500"><?= $isBridal ? 'Wedding date' : 'Date needed' ?></strong><?= esc($enquiry->date_label) ?></p>
    <?php endif ?>
    <?php if ($enquiry->details): ?>
      <p><strong class="block text-xs font-medium text-stone-500"><?= $isBridal ? 'The look in mind' : 'Occasion, colours and references' ?></strong><?= nl2br(esc($enquiry->details)) ?></p>
    <?php endif ?>
  </section>

  <section class="panel min-w-0 p-4 sm:p-5" aria-labelledby="customer-heading">
    <h2 id="customer-heading" class="mb-3 font-sans text-base font-semibold tracking-normal">Customer</h2>
    <address class="text-sm/6 wrap-anywhere">
      <strong class="font-medium text-stone-950"><?= esc($enquiry->customer_name) ?></strong><br>
      <?php if ($enquiry->phone !== ''): ?>
        <a class="text-link" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $enquiry->phone), 'attr') ?>"><?= esc($enquiry->phone) ?></a>
      <?php endif ?>
      <?php if ($enquiry->email): ?>
        <br><a class="text-link" href="mailto:<?= esc($enquiry->email, 'attr') ?>"><?= esc($enquiry->email) ?></a>
      <?php endif ?>
    </address>

    <?= form_open(url_to('admin.enquiries.sale', $enquiry->id), ['class' => 'mt-5 grid gap-4 border-t border-stone-200 pt-4', 'novalidate' => true, 'data-submit-once' => true]) ?>
      <?= partial('field', ['name' => 'source', 'label' => 'Came from', 'type' => 'select', 'options' => Enquiry::SOURCE_LABELS, 'value' => $enquiry->source ?? Enquiry::WEBSITE, 'required' => true]) ?>
      <?= partial('field', ['name' => 'order_value', 'label' => 'Sold for (₹)', 'value' => $enquiry->order_value === null ? '' : money_input($enquiry->order_value), 'hint' => 'Fill this in once it becomes an order.', 'attributes' => 'inputmode="decimal"']) ?>
      <button type="submit" class="btn btn-outline btn-block">Save</button>
    <?= form_close() ?>
  </section>
</div>
<?= $this->endSection() ?>
