<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php
$total   = $pager->getTotal();
$listUrl = $category ? url_to('shop.category', $category->slug) : url_to('shop');
$query   = $term !== '' ? ['q' => $term] : [];
$sorts   = \App\Models\ProductModel::SORTS;
?>
<div class="wrap page">
  <div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-stone-200 pb-5">
    <div>
      <h1><?= esc($category->name ?? ($term !== '' ? "Results for “{$term}”" : 'All products')) ?></h1>
      <p class="muted mt-1 text-sm"><?= number_format($total) ?> product<?= $total === 1 ? '' : 's' ?></p>
    </div>

    <?php if ($total > 1): ?>
      <details class="sort group relative">
        <summary class="flex h-10 list-none items-center gap-2 rounded-lg px-3 text-sm ring-1 ring-stone-300 transition-colors ring-inset hover:bg-stone-50 [&::-webkit-details-marker]:hidden">
          <span class="text-stone-500">Sort by</span>
          <span class="font-medium text-stone-900"><?= esc($sorts[$sort]['label']) ?></span>
          <span class="transition-transform group-open:rotate-180"><?= partial('icon', ['name' => 'down', 'size' => 16]) ?></span>
        </summary>
        <ul class="absolute top-full right-0 z-20 mt-2 w-56 animate-rise rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-stone-200">
          <?php foreach ($sorts as $value => $option): ?>
            <li>
              <a class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm whitespace-nowrap text-stone-700 transition-colors hover:bg-stone-100 aria-[current]:font-medium aria-[current]:text-brand-800" href="<?= esc($listUrl . '?' . http_build_query([...$query, 'sort' => $value]), 'attr') ?>" rel="nofollow" <?= $value === $sort ? 'aria-current="true"' : '' ?>>
                <?= esc($option['label']) ?>
                <?php if ($value === $sort): ?>
                  <?= partial('icon', ['name' => 'check', 'size' => 16]) ?>
                <?php endif ?>
              </a>
            </li>
          <?php endforeach ?>
        </ul>
      </details>
    <?php endif ?>
  </div>

  <?php if ($products === []): ?>
    <div class="empty">
      <?php if ($term !== ''): ?>
        <h2>Nothing matched “<?= esc($term) ?>”</h2>
        <p>Check the spelling, or try a broader word.</p>
      <?php else: ?>
        <h2>Nothing here yet</h2>
        <p>Products in this category will appear here once they are added.</p>
      <?php endif ?>
      <a class="btn" href="<?= esc(url_to('shop'), 'attr') ?>">See all products</a>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4 lg:gap-x-6">
      <?php foreach ($products as $product): ?>
        <?= partial('product_card', ['product' => $product, 'headingTag' => 'h2']) ?>
      <?php endforeach ?>
    </div>

    <?= $pager->links('default', 'store') ?>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
