<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', [
    'back'     => [url_to('admin.bookings'), 'All bookings'],
    'title'    => "Saree draping, {$booking->date_label}",
    'lede'     => 'Requested ' . local_time($booking->created_at),
    'statusOf' => $booking,
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Booking;

$actionLabels = [
    Booking::CONFIRMED => 'Confirm booking',
    Booking::COMPLETED => 'Mark as completed',
];
?>
<?php if ($booking->nextStatuses() !== []): ?>
  <div class="panel mb-5 flex flex-wrap gap-2 p-4">
    <?php foreach ($booking->nextStatuses() as $next): ?>
      <?php $isCancel = $next === Booking::CANCELLED; ?>
      <?= form_open(url_to('admin.bookings.status', $booking->id), array_filter([
          'data-submit-once' => true,
          'data-confirm'     => $isCancel ? 'Cancel this booking?' : null,
      ])) ?>
        <input type="hidden" name="status" value="<?= esc($next, 'attr') ?>">
        <button type="submit" class="btn <?= $isCancel ? 'btn-danger-outline' : '' ?>"><?= esc($isCancel ? 'Cancel booking' : $actionLabels[$next]) ?></button>
      <?= form_close() ?>
    <?php endforeach ?>
  </div>
<?php endif ?>

<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
  <section class="panel grid min-w-0 gap-4 p-4 text-sm/6 wrap-anywhere sm:p-5" aria-labelledby="details-heading">
    <h2 id="details-heading" class="font-sans text-base font-semibold tracking-normal">Details</h2>
    <p><strong class="block text-xs font-medium text-stone-500">Wedding date</strong><?= esc($booking->date_label) ?></p>
    <?php if ($booking->ready_by_label): ?>
      <p><strong class="block text-xs font-medium text-stone-500">Ready by</strong><?= esc($booking->ready_by_label) ?></p>
    <?php endif ?>
    <p><strong class="block text-xs font-medium text-stone-500">Venue or place</strong><?= esc($booking->location) ?></p>
    <p><strong class="block text-xs font-medium text-stone-500">Number of people</strong><?= (int) $booking->people ?></p>
    <?php if ($booking->notes): ?>
      <p><strong class="block text-xs font-medium text-stone-500">Anything else</strong><?= nl2br(esc($booking->notes)) ?></p>
    <?php endif ?>
  </section>

  <section class="panel min-w-0 p-4 sm:p-5" aria-labelledby="customer-heading">
    <h2 id="customer-heading" class="mb-3 font-sans text-base font-semibold tracking-normal">Customer</h2>
    <address class="text-sm/6 wrap-anywhere">
      <strong class="font-medium text-stone-950"><?= esc($booking->customer_name) ?></strong><br>
      <a class="text-link" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $booking->phone), 'attr') ?>"><?= esc($booking->phone) ?></a>
    </address>
  </section>
</div>
<?= $this->endSection() ?>
