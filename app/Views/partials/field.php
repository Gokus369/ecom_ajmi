<?php
/**
 * A labelled form control with its error message underneath. Call through partial().
 *
 * Needs $name and $label. Optional: $type (text, email, tel, file, textarea, select),
 * $value, $options (for select), $required, $hint, $rows, $multiple (for file),
 * $attributes (trusted, static HTML).
 */
$type     = $type ?? 'text';
$required = $required ?? false;
$hint     = $hint ?? null;
$multiple = $multiple ?? false;
$error    = (session('errors') ?? [])[$name] ?? null;
// A failed form keeps what was typed, but a password must never go back into the page.
$current  = $type === 'password' ? '' : old($name, $value ?? '', false);
$describedBy = $error ? "{$name}-error" : ($hint ? "{$name}-hint" : null);

$common = 'id="' . esc($name, 'attr') . '" name="' . esc($name . ($multiple ? '[]' : ''), 'attr') . '"'
    . ($multiple ? ' multiple' : '')
    . ($required ? ' required' : '')
    . ($error ? ' aria-invalid="true"' : '')
    . ($describedBy ? ' aria-describedby="' . esc($describedBy, 'attr') . '"' : '')
    . (isset($attributes) ? ' ' . $attributes : '');
?>
<div class="field<?= $error ? ' field-invalid' : '' ?>">
  <label for="<?= esc($name, 'attr') ?>">
    <?= esc($label) ?><?php if (! $required): ?> <span class="optional">(optional)</span><?php endif ?>
  </label>

  <?php if ($type === 'textarea'): ?>
    <textarea <?= $common ?> rows="<?= (int) ($rows ?? 4) ?>"><?= esc($current) ?></textarea>
  <?php elseif ($type === 'select'): ?>
    <select <?= $common ?>>
      <?php foreach ($options as $optionValue => $optionLabel): ?>
        <option value="<?= esc((string) $optionValue, 'attr') ?>" <?= (string) $optionValue === (string) $current ? 'selected' : '' ?>><?= esc($optionLabel) ?></option>
      <?php endforeach ?>
    </select>
  <?php elseif ($type === 'file'): ?>
    <input type="file" <?= $common ?>>
  <?php else: ?>
    <input type="<?= esc($type, 'attr') ?>" <?= $common ?> value="<?= esc((string) $current, 'attr') ?>">
  <?php endif ?>

  <?php if ($error): ?>
    <p class="field-error" id="<?= esc($name, 'attr') ?>-error"><?= esc($error) ?></p>
  <?php elseif ($hint): ?>
    <p class="field-hint" id="<?= esc($name, 'attr') ?>-hint"><?= esc($hint) ?></p>
  <?php endif ?>
</div>
