<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['eyebrow' => 'Services', 'title' => 'Enquiries', 'lede' => 'Custom styling and bridal consultation requests, newest first.']) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Enquiry;

$baseUrl = url_to('admin.enquiries');
?>
<nav class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 py-0.5 [scrollbar-width:none] sm:-mx-6 sm:px-6 lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0" aria-label="Filter enquiries by status">
  <a class="flex min-h-10 flex-none items-center gap-2 rounded-lg border border-stone-200 bg-white px-2.5 shadow-xs transition-colors hover:bg-stone-50 aria-[current=page]:border-brand-800 aria-[current=page]:ring-1 aria-[current=page]:ring-brand-800" href="<?= esc($baseUrl, 'attr') ?>" <?= $status === null ? 'aria-current="page"' : '' ?>>
    <span class="status">All</span>
    <span class="text-sm font-semibold text-stone-950 tabular-nums"><?= (int) array_sum($counts) ?></span>
  </a>
  <?php foreach (Enquiry::STATUS_LABELS as $value => $label): ?>
    <a class="flex min-h-10 flex-none items-center gap-2 rounded-lg border border-stone-200 bg-white px-2.5 shadow-xs transition-colors hover:bg-stone-50 aria-[current=page]:border-brand-800 aria-[current=page]:ring-1 aria-[current=page]:ring-brand-800" href="<?= esc($baseUrl . '?status=' . $value, 'attr') ?>" <?= $status === $value ? 'aria-current="page"' : '' ?>>
      <span class="status status-<?= esc($value, 'attr') ?>"><?= esc($label) ?></span>
      <span class="text-sm font-semibold text-stone-950 tabular-nums"><?= (int) ($counts[$value] ?? 0) ?></span>
    </a>
  <?php endforeach ?>
</nav>

<div class="panel overflow-hidden">
  <?php if ($enquiries === []): ?>
    <div class="empty border-0 py-12">
      <span class="mb-3 inline-grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500"><?= partial('icon', ['name' => 'enquiries', 'size' => 24]) ?></span>
      <?php if ($status !== null): ?>
        <h2 class="mb-5 font-sans text-base font-semibold tracking-normal">No enquiries match that filter</h2>
        <a class="btn btn-outline" href="<?= esc($baseUrl, 'attr') ?>">Show all enquiries</a>
      <?php else: ?>
        <h2 class="font-sans text-base font-semibold tracking-normal">No enquiries yet</h2>
        <p>They will appear here as soon as someone sends one from the Bespoke page.</p>
        <a class="btn btn-outline" href="<?= esc(url_to('bespoke'), 'attr') ?>">View Bespoke page</a>
      <?php endif ?>
    </div>
  <?php else: ?>
    <div class="table-wrap sm:max-h-[75vh]">
      <table class="table border-separate border-spacing-0">
        <thead class="sticky top-0 z-1 [&_th]:shadow-[0_1px_0_var(--color-stone-200)]">
          <tr>
            <th scope="col">Received</th>
            <th scope="col">Customer</th>
            <th scope="col">Looking for</th>
            <th scope="col">Date</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enquiries as $enquiry): ?>
            <tr class="relative transition-colors hover:bg-stone-50">
              <td><a class="font-medium text-brand-800 after:absolute after:inset-0" href="<?= esc(url_to('admin.enquiries.show', $enquiry->id), 'attr') ?>"><?= esc(local_time($enquiry->created_at)) ?></a></td>
              <td>
                <?= esc($enquiry->customer_name) ?>
                <span class="block text-xs text-stone-500"><?= esc($enquiry->phone) ?></span>
                <?php if ($enquiry->email): ?>
                  <span class="block text-xs text-stone-500"><?= esc($enquiry->email) ?></span>
                <?php endif ?>
              </td>
              <td><?= esc($enquiry->type_label) ?></td>
              <td><?= esc($enquiry->date_label ?? '') ?></td>
              <td><span class="status status-<?= esc($enquiry->status, 'attr') ?>"><?= esc($enquiry->status_label) ?></span></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <div class="has-[nav]:border-t has-[nav]:border-stone-200 has-[nav]:p-2 [&_ul]:mt-0"><?= $pager->links('default', 'store') ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
