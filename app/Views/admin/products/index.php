<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', [
    'eyebrow' => 'Catalogue',
    'title'   => 'Products',
    'lede'    => 'Every piece in the store, live or hidden.',
    'action'  => [url_to('admin.products.new'), 'Add product'],
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $total = $pager->getTotal(); ?>
<div class="panel overflow-hidden">
  <form class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-stone-200 p-3 sm:px-4" action="<?= esc(url_to('admin.products'), 'attr') ?>" method="get" role="search">
    <label class="sr-only" for="q">Search products</label>
    <span class="relative min-w-0 flex-[1_1_14rem] sm:max-w-md">
      <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-stone-400"><?= partial('icon', ['name' => 'search', 'size' => 18]) ?></span>
      <input class="input h-10 py-0 pl-10" id="q" type="search" name="q" value="<?= esc($term, 'attr') ?>" placeholder="Search by name" maxlength="80">
    </span>
    <button type="submit" class="btn btn-outline h-10 py-0">Search</button>
    <?php if ($term !== ''): ?>
      <a class="text-link text-sm" href="<?= esc(url_to('admin.products'), 'attr') ?>">Clear</a>
    <?php endif ?>
    <?php if ($products !== []): ?>
      <p class="ml-auto text-sm text-stone-500"><?= number_format($total) ?> <?= $total === 1 ? 'product' : 'products' ?></p>
    <?php endif ?>
  </form>

  <?php if ($products === []): ?>
    <div class="empty border-0 py-12">
      <span class="mb-3 inline-grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500"><?= partial('icon', ['name' => 'hanger', 'size' => 24]) ?></span>
      <?php if ($term !== ''): ?>
        <h2 class="font-sans text-base font-semibold tracking-normal">No products match “<?= esc($term) ?>”</h2>
      <?php else: ?>
        <h2 class="font-sans text-base font-semibold tracking-normal">No products yet</h2>
        <p>Add your first product and it will appear in the store straight away.</p>
        <a class="btn" href="<?= esc(url_to('admin.products.new'), 'attr') ?>">Add product</a>
      <?php endif ?>
    </div>
  <?php else: ?>
    <div class="table-wrap sm:max-h-[75vh]">
      <table class="table border-separate border-spacing-0">
        <thead class="sticky top-0 z-1 [&_th]:shadow-[0_1px_0_var(--color-stone-200)]">
          <tr>
            <th scope="col">Product</th>
            <th scope="col">Category</th>
            <th scope="col" class="num">Price</th>
            <th scope="col" class="num">Stock</th>
            <th scope="col">Store</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $product): ?>
            <tr class="relative transition-colors hover:bg-stone-50">
              <td>
                <span class="flex min-w-52 items-center gap-3">
                  <span class="h-12.5 w-10 flex-none overflow-hidden rounded-md bg-stone-100 [&_.image-fallback]:text-base"><?= partial('product_image', ['product' => $product]) ?></span>
                  <a class="font-medium whitespace-normal text-brand-800 after:absolute after:inset-0" href="<?= esc(url_to('admin.products.edit', $product->id), 'attr') ?>"><?= esc($product->name) ?></a>
                </span>
              </td>
              <td><?= esc($product->category_name ?? 'None') ?></td>
              <td class="num"><?= $product->is_enquiry_only ? 'On request' : esc(money($product->price)) ?></td>
              <td class="num"><?= $product->is_enquiry_only ? 'To order' : number_format($product->stock) ?></td>
              <td>
                <span class="status <?= $product->is_published ? 'status-delivered' : '' ?>"><?= $product->is_published ? 'Live' : 'Hidden' ?></span>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>

    <div class="has-[nav]:border-t has-[nav]:border-stone-200 has-[nav]:p-2 [&_ul]:mt-0"><?= $pager->links('default', 'store') ?></div>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
