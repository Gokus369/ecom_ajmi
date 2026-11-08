<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['eyebrow' => 'Catalogue', 'title' => 'Categories', 'lede' => 'Group your pieces so shoppers can find them.']) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid items-start gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
  <section class="panel min-w-0 overflow-hidden" aria-labelledby="list-heading">
    <h2 id="list-heading" class="border-b border-stone-200 px-4 py-3 font-sans text-base font-semibold tracking-normal">All categories</h2>
    <?php if ($categories === []): ?>
      <div class="empty border-0 py-10">
        <span class="mb-3 inline-grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500"><?= partial('icon', ['name' => 'categories', 'size' => 24]) ?></span>
        <h3 class="font-sans text-base font-semibold tracking-normal">No categories yet</h3>
        <p class="mb-0">Add your first one with the form on this page.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap sm:max-h-[75vh]">
        <table class="table border-separate border-spacing-0">
          <thead class="sticky top-0 z-1 [&_th]:shadow-[0_1px_0_var(--color-stone-200)]">
            <tr>
              <th scope="col">Name</th>
              <th scope="col" class="num">Products</th>
              <th scope="col" class="num">Order</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $category): ?>
              <tr class="relative transition-colors hover:bg-stone-50">
                <td><a class="font-medium text-brand-800 after:absolute after:inset-0" href="<?= esc(url_to('admin.categories.edit', $category->id), 'attr') ?>"><?= esc($category->name) ?></a></td>
                <td class="num"><?= (int) $category->product_count ?></td>
                <td class="num"><?= $category->sort_order ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php endif ?>
  </section>

  <section class="panel min-w-0 p-4 sm:p-5" aria-labelledby="add-heading">
    <h2 id="add-heading" class="mb-4 font-sans text-base font-semibold tracking-normal">Add a category</h2>
    <?= form_open(url_to('admin.categories.create'), ['class' => 'grid gap-4', 'novalidate' => true, 'data-submit-once' => true]) ?>
      <?= partial('field', ['name' => 'name', 'label' => 'Name', 'required' => true, 'attributes' => 'maxlength="80"']) ?>
      <?= partial('field', ['name' => 'sort_order', 'label' => 'Display order', 'hint' => 'Lower numbers appear first.', 'attributes' => 'inputmode="numeric"']) ?>
      <button type="submit" class="btn btn-block">Add category</button>
    <?= form_close() ?>
  </section>
</div>
<?= $this->endSection() ?>
