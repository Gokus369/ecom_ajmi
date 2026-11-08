<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['back' => [url_to('admin.offers'), 'All offers'], 'title' => $title]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Entities\Offer;

$isNew  = ! $offer->id;
$action = $isNew ? url_to('admin.offers.create') : url_to('admin.offers.update', $offer->id);
$active = session()->has('_ci_old_input') ? (bool) old('is_active') : (bool) $offer->is_active;
?>
<?= form_open($action, ['class' => 'panel max-w-3xl p-4 sm:p-5', 'novalidate' => true, 'data-submit-once' => true]) ?>
  <div class="form-grid">
    <div class="form-wide">
      <?= partial('field', ['name' => 'name', 'label' => 'Offer name', 'value' => $offer->name, 'required' => true, 'hint' => 'Shoppers see this, so make it the festival, like Onam Offer.', 'attributes' => 'maxlength="100"']) ?>
    </div>
    <?= partial('field', ['name' => 'discount_type', 'label' => 'Discount type', 'type' => 'select', 'options' => Offer::DISCOUNT_TYPES, 'value' => $offer->discount_type, 'required' => true]) ?>
    <?= partial('field', ['name' => 'discount_value', 'label' => 'Discount', 'value' => $isNew ? '' : $offer->discountInput(), 'required' => true, 'hint' => 'A percent from 1 to 90, or an amount in rupees.', 'attributes' => 'inputmode="decimal"']) ?>
    <?= partial('field', ['name' => 'starts_on', 'label' => 'First day', 'type' => 'date', 'value' => $offer->starts_on, 'required' => true]) ?>
    <?= partial('field', ['name' => 'ends_on', 'label' => 'Last day', 'type' => 'date', 'value' => $offer->ends_on, 'required' => true]) ?>
    <div class="form-wide">
      <?= partial('field', ['name' => 'min_order', 'label' => 'Minimum order (₹)', 'value' => $offer->min_order > 0 ? money_input($offer->min_order) : '', 'hint' => 'Leave empty to apply to every order.', 'attributes' => 'inputmode="decimal"']) ?>
    </div>
    <div class="form-wide">
      <label class="check">
        <input type="checkbox" name="is_active" value="1" <?= $active ? 'checked' : '' ?>>
        Offer is on
      </label>
      <p class="field-hint">Untick to stop it early without deleting it.</p>
    </div>
  </div>

  <div class="mt-5 flex items-center gap-4">
    <button type="submit" class="btn"><?= $isNew ? 'Add offer' : 'Save changes' ?></button>
    <a class="text-link text-sm" href="<?= esc(url_to('admin.offers'), 'attr') ?>">Cancel</a>
  </div>
<?= form_close() ?>

<?php if (! $isNew): ?>
  <?= form_open(url_to('admin.offers.delete', $offer->id), ['class' => 'panel mt-5 flex max-w-3xl flex-wrap items-center justify-between gap-x-6 gap-y-3 border-red-200 p-4 sm:p-5', 'data-confirm' => "Delete {$offer->name}?"]) ?>
    <p class="text-sm text-stone-500">Past orders keep the discount they received.</p>
    <button type="submit" class="btn btn-danger-outline">Delete offer</button>
  <?= form_close() ?>
<?php endif ?>
<?= $this->endSection() ?>
