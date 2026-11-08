<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['back' => [url_to('admin.categories'), 'All categories'], 'title' => $title]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= form_open(url_to('admin.categories.update', $category->id), ['class' => 'panel grid max-w-3xl gap-4 p-4 sm:p-5', 'novalidate' => true, 'data-submit-once' => true]) ?>
  <?= partial('field', ['name' => 'name', 'label' => 'Name', 'value' => $category->name, 'required' => true, 'attributes' => 'maxlength="80"']) ?>
  <?= partial('field', ['name' => 'sort_order', 'label' => 'Display order', 'value' => $category->sort_order, 'hint' => 'Lower numbers appear first.', 'attributes' => 'inputmode="numeric"']) ?>
  <div class="mt-2 flex items-center gap-4">
    <button type="submit" class="btn">Save changes</button>
    <a class="text-link text-sm" href="<?= esc(url_to('admin.categories'), 'attr') ?>">Cancel</a>
  </div>
<?= form_close() ?>

<?= form_open(url_to('admin.categories.delete', $category->id), ['class' => 'panel mt-5 flex max-w-3xl flex-wrap items-center justify-between gap-x-6 gap-y-3 border-red-200 p-4 sm:p-5', 'data-confirm' => "Delete {$category->name}?"]) ?>
  <p class="text-sm text-stone-500">A category can only be deleted once it has no products.</p>
  <button type="submit" class="btn btn-danger-outline">Delete category</button>
<?= form_close() ?>
<?= $this->endSection() ?>
