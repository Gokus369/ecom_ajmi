<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['back' => [url_to('admin.products'), 'All products'], 'title' => $title]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$isNew      = ! $product->id;
$formAction = $isNew ? url_to('admin.products.create') : url_to('admin.products.update', $product->id);
$hasOld     = session()->has('_ci_old_input');
$published  = $hasOld ? (bool) old('is_published') : (bool) $product->is_published;
$enquiry    = $hasOld ? (bool) old('is_enquiry_only') : (bool) $product->is_enquiry_only;
$mainPhoto  = $hasOld ? old('main_photo', null, false) : ($photos[0] ?? null);
$removing   = $hasOld ? (array) old('remove_photos', [], false) : [];
// A post over this limit is dropped whole, so app.js stops it before it is sent.
$maxBytes   = max(0, ini_parse_quantity(ini_get('post_max_size')) - 1048576);

$categoryOptions = ['' => 'No category'];

foreach ($categories as $category) {
    $categoryOptions[$category->id] = $category->name;
}
?>
<?= form_open_multipart($formAction, ['novalidate' => true, 'data-submit-once' => true]) ?>
  <?php // What the piece is on the left, how it sells on the right. ?>
  <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="grid min-w-0 gap-5">
      <section class="panel grid gap-4 p-4 sm:p-5" aria-labelledby="details-heading">
        <h2 id="details-heading" class="font-sans text-base font-semibold tracking-normal">Details</h2>
        <?= partial('field', ['name' => 'name', 'label' => 'Name', 'value' => $product->name, 'required' => true, 'attributes' => 'maxlength="140"']) ?>
        <?= partial('field', ['name' => 'summary', 'label' => 'Summary', 'value' => $product->summary, 'hint' => 'One line, shown near the price and to search engines.', 'attributes' => 'maxlength="200"']) ?>
        <?= partial('field', ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 6, 'value' => $product->description, 'attributes' => 'maxlength="5000"']) ?>
      </section>

      <section class="panel grid gap-4 p-4 sm:p-5" aria-labelledby="photos-heading">
        <h2 id="photos-heading" class="font-sans text-base font-semibold tracking-normal">Photos</h2>
        <?php if ($photos !== []): ?>
          <fieldset class="min-w-0">
            <legend class="label">Current photos</legend>
            <ul class="flex flex-wrap gap-x-5 gap-y-4">
              <?php foreach ($photos as $index => $photo): ?>
                <li>
                  <img class="mb-1 block h-30 w-24 rounded-lg object-cover" src="<?= esc($product->photoUrl($photo, 'thumb'), 'attr') ?>" alt="Photo <?= $index + 1 ?> of <?= esc($product->name, 'attr') ?>" width="96" height="120">
                  <label class="check sm:min-h-8">
                    <input type="radio" name="main_photo" value="<?= esc($photo, 'attr') ?>" <?= $photo === $mainPhoto ? 'checked' : '' ?>>
                    Main photo
                  </label>
                  <label class="check sm:min-h-8">
                    <input type="checkbox" name="remove_photos[]" value="<?= esc($photo, 'attr') ?>" <?= in_array($photo, $removing, true) ? 'checked' : '' ?>>
                    Remove
                  </label>
                </li>
              <?php endforeach ?>
            </ul>
          </fieldset>
        <?php endif ?>
        <?= partial('field', ['name' => 'photos', 'label' => $photos === [] ? 'Photos' : 'Add more photos', 'type' => 'file', 'multiple' => true, 'hint' => 'Choose several at once, up to ' . \App\Libraries\ProductImages::MAX_PER_PIECE . ' for each piece. JPG, PNG or WebP up to 5 MB each. They are cropped to a 4:5 portrait, so keep the piece centred.', 'attributes' => 'accept="image/jpeg,image/png,image/webp" data-max-bytes="' . $maxBytes . '" data-previews="photo-previews"']) ?>
        <div id="photo-previews" hidden>
          <p class="label">New photos, not saved yet</p>
          <ul class="flex flex-wrap gap-3"></ul>
        </div>
      </section>

      <section class="panel grid gap-4 p-4 sm:p-5" aria-labelledby="options-heading">
        <h2 id="options-heading" class="font-sans text-base font-semibold tracking-normal">Colours and sizes</h2>
        <?= partial('variant_picker', ['product' => $product, 'colours' => $colours, 'sizes' => $sizes]) ?>
      </section>
    </div>

    <div class="grid min-w-0 gap-5">
      <section class="panel grid gap-3 p-4 sm:p-5" aria-labelledby="store-heading">
        <h2 id="store-heading" class="font-sans text-base font-semibold tracking-normal">In the store</h2>
        <label class="check">
          <input type="checkbox" name="is_published" value="1" <?= $published ? 'checked' : '' ?>>
          Show this piece in the store
        </label>
        <?= partial('field', ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => $categoryOptions, 'value' => $product->category_id]) ?>
      </section>

      <section class="group panel grid gap-3 p-4 sm:p-5" aria-labelledby="pricing-heading">
        <h2 id="pricing-heading" class="font-sans text-base font-semibold tracking-normal">Price and stock</h2>
        <div>
          <label class="check">
            <input type="checkbox" name="is_enquiry_only" value="1" <?= $enquiry ? 'checked' : '' ?>>
            Enquiry only (price on request)
          </label>
          <p class="field-hint mt-0">Hides the price and cart, and shows a WhatsApp button. Price and stock are ignored, so 0 is fine for both.</p>
        </div>
        <?php // Enquiry-only pieces ignore these, so they step back rather than disappear. That box is the only one in this card. ?>
        <div class="form-grid transition-opacity group-has-checked:opacity-50">
          <?= partial('field', ['name' => 'price', 'label' => 'Price (' . config(\Config\Store::class)->currency . ')', 'value' => $isNew ? '' : money_input($product->price), 'required' => true, 'attributes' => 'inputmode="decimal"']) ?>
          <div data-plain-stock>
            <?= partial('field', ['name' => 'stock', 'label' => 'Stock', 'value' => $isNew ? '' : $product->stock, 'required' => true, 'hint' => 'Use 0 to show the piece as sold out.', 'attributes' => 'inputmode="numeric"']) ?>
          </div>
        </div>
      </section>
    </div>
  </div>

  <?php // A child of the form itself, so it stays in view for the form's whole height. ?>
  <div class="sticky bottom-0 z-2 -mx-4 mt-5 flex items-center gap-4 border-t border-stone-200 bg-stone-50 px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <button type="submit" class="btn"><?= $isNew ? 'Add product' : 'Save changes' ?></button>
    <a class="text-link text-sm" href="<?= esc(url_to('admin.products'), 'attr') ?>">Cancel</a>
  </div>
<?= form_close() ?>

<?php if (! $isNew): ?>
  <?= form_open(url_to('admin.products.delete', $product->id), ['class' => 'panel mt-5 flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-red-200 p-4 sm:p-5', 'data-confirm' => "Delete {$product->name}? It will disappear from the store. Past orders are not affected."]) ?>
    <p class="text-sm text-stone-500">Deleting removes this product from the store. Past orders are not affected.</p>
    <button type="submit" class="btn btn-danger-outline">Delete product</button>
  <?= form_close() ?>
<?php endif ?>
<?= $this->endSection() ?>
