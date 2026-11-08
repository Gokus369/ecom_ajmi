<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', [
    'eyebrow' => 'Sales',
    'title'   => 'Festival offers',
    'lede'    => 'Discounts that apply by themselves between two dates.',
    'action'  => [url_to('admin.offers.new'), 'New offer'],
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="panel overflow-hidden">
  <?php if ($offers === []): ?>
    <div class="empty border-0 py-12">
      <span class="mb-3 inline-grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500"><?= partial('icon', ['name' => 'percent', 'size' => 24]) ?></span>
      <h2 class="font-sans text-base font-semibold tracking-normal">No offers yet</h2>
      <p>Set up an offer for your next festival, and it will start and stop by itself.</p>
      <a class="btn" href="<?= esc(url_to('admin.offers.new'), 'attr') ?>">New offer</a>
    </div>
  <?php else: ?>
    <div class="table-wrap sm:max-h-[75vh]">
      <table class="table border-separate border-spacing-0">
        <thead class="sticky top-0 z-1 [&_th]:shadow-[0_1px_0_var(--color-stone-200)]">
          <tr>
            <th scope="col">Offer</th>
            <th scope="col">Discount</th>
            <th scope="col">Minimum order</th>
            <th scope="col">Dates</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($offers as $offer): ?>
            <?php [$label, $tone] = $offer->state($today); ?>
            <tr class="relative transition-colors hover:bg-stone-50">
              <td><a class="font-medium text-brand-800 after:absolute after:inset-0" href="<?= esc(url_to('admin.offers.edit', $offer->id), 'attr') ?>"><?= esc($offer->name) ?></a></td>
              <td><?= esc($offer->discount_text) ?></td>
              <td><?= $offer->min_order > 0 ? esc(money($offer->min_order)) : 'None' ?></td>
              <td><?= esc(date('j M Y', strtotime($offer->starts_on))) ?> to <?= esc(date('j M Y', strtotime($offer->ends_on))) ?></td>
              <td><span class="status <?= $tone ?>"><?= $label ?></span></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>

    <div class="has-[nav]:border-t has-[nav]:border-stone-200 has-[nav]:p-2 [&_ul]:mt-0"><?= $pager->links('default', 'store') ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
