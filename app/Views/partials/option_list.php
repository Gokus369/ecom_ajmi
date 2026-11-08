<?php
/**
 * One master list in the admin, with a form to add to it. Call through partial().
 *
 * Needs $list (colours or sizes), $noun, $heading and $items.
 */
$hasSwatch = $list === 'colours';
?>
<section class="panel min-w-0 overflow-hidden" aria-labelledby="<?= $list ?>-heading">
  <h2 id="<?= $list ?>-heading" class="border-b border-stone-200 px-4 py-3 font-sans text-base font-semibold tracking-normal"><?= esc($heading) ?></h2>

  <?php if ($items === []): ?>
    <p class="px-4 py-6 text-sm text-stone-500">Nothing here yet. Add the first one below.</p>
  <?php else: ?>
    <ul class="divide-y divide-stone-200">
      <?php foreach ($items as $item): ?>
        <?php
        $pieces = (int) $item->pieces;
        $label  = esc($item->name, 'attr');
        ?>
        <li class="flex flex-wrap items-center gap-2 px-4 py-3">
          <?= form_open(url_to('admin.options.update', $list, $item->id), ['class' => 'flex min-w-0 flex-1 flex-wrap items-center gap-2', 'data-submit-once' => true]) ?>
            <?php if ($hasSwatch): ?>
              <input class="size-9 shrink-0 cursor-pointer rounded-lg border border-stone-300 bg-white p-0.5" type="color" name="colours_hex" value="<?= esc($item->hex, 'attr') ?>" aria-label="Swatch for <?= $label ?>">
            <?php endif ?>
            <input class="input min-w-28 flex-1 py-1.5" type="text" name="<?= $list ?>_name" value="<?= $label ?>" maxlength="40" aria-label="Name of <?= $label ?>">
            <input class="input w-16 px-2 py-1.5 text-center" type="text" inputmode="numeric" name="<?= $list ?>_sort_order" value="<?= (int) $item->sort_order ?>" aria-label="Display order of <?= $label ?>">
            <button type="submit" class="btn btn-outline min-h-9 px-3 py-1 text-sm">Save</button>
          <?= form_close() ?>

          <?php if ($pieces > 0): ?>
            <span class="w-20 text-right text-xs text-stone-500"><?= $pieces ?> <?= $pieces === 1 ? 'piece' : 'pieces' ?></span>
          <?php else: ?>
            <?= form_open(url_to('admin.options.delete', $list, $item->id), ['class' => 'w-20 text-right', 'data-confirm' => "Delete {$item->name}?"]) ?>
              <button type="submit" class="link-button text-sm" aria-label="Delete <?= $label ?>">Delete</button>
            <?= form_close() ?>
          <?php endif ?>
        </li>
      <?php endforeach ?>
    </ul>
  <?php endif ?>

  <?= form_open(url_to('admin.options.create', $list), ['class' => 'grid gap-4 border-t border-stone-200 bg-stone-50 p-4', 'novalidate' => true, 'data-submit-once' => true]) ?>
    <h3 class="font-sans text-sm font-semibold tracking-normal">Add a <?= esc($noun) ?></h3>
    <div class="grid items-start gap-4 sm:grid-cols-[1fr_auto_auto]">
      <?= partial('field', ['name' => "{$list}_name", 'label' => 'Name', 'required' => true, 'attributes' => 'maxlength="40"']) ?>
      <?php if ($hasSwatch): ?>
        <?= partial('field', ['name' => 'colours_hex', 'label' => 'Swatch', 'type' => 'color', 'value' => '#c9a24b', 'required' => true, 'attributes' => 'class="h-11 w-16 cursor-pointer p-1"']) ?>
      <?php endif ?>
      <?= partial('field', ['name' => "{$list}_sort_order", 'label' => 'Order', 'attributes' => 'class="w-20" inputmode="numeric"']) ?>
    </div>
    <button type="submit" class="btn justify-self-start">Add <?= esc($noun) ?></button>
  <?= form_close() ?>
</section>
