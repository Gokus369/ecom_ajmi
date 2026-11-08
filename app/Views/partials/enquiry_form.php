<?php
/**
 * A bespoke enquiry form. Call through partial().
 *
 * Needs $form (custom or bridal), $dateLabel, $detailsLabel and $today. Both forms
 * share one page, so every field name starts with its form, which keeps ids, typed
 * values and error messages from appearing on the wrong one. It is not called
 * $type because nested partials inherit it, and field.php would use it as the input type.
 */
?>
<?= form_open(url_to('bespoke.enquire', $form), ['class' => 'grid gap-5 sm:grid-cols-2', 'novalidate' => true, 'data-submit-once' => true]) ?>
  <?= partial('field', ['name' => "{$form}_customer_name", 'label' => 'Your name', 'value' => auth()->user()?->name, 'required' => true, 'attributes' => 'autocomplete="name" maxlength="100"']) ?>
  <?= partial('field', ['name' => "{$form}_phone", 'label' => 'Phone', 'type' => 'tel', 'required' => true, 'attributes' => 'autocomplete="tel" maxlength="20"']) ?>
  <?= partial('field', ['name' => "{$form}_email", 'label' => 'Email', 'type' => 'email', 'value' => auth()->user()?->email, 'required' => true, 'attributes' => 'autocomplete="email" maxlength="120"']) ?>
  <?= partial('field', ['name' => "{$form}_event_date", 'label' => $dateLabel, 'type' => 'date', 'attributes' => 'min="' . esc($today, 'attr') . '"']) ?>
  <div class="sm:col-span-2">
    <?= partial('field', ['name' => "{$form}_details", 'label' => $detailsLabel, 'type' => 'textarea', 'rows' => 3, 'attributes' => 'maxlength="1000"']) ?>
  </div>
  <div class="sm:col-span-2">
    <button type="submit" class="btn btn-large btn-block">Book</button>
  </div>
<?= form_close() ?>
