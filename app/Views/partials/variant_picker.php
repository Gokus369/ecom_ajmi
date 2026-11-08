<?php
/**
 * Colour and size tick boxes, with a stock box for each combination. Call through partial().
 *
 * Needs $product, $colours and $sizes. app.js shows only the ticked rows and columns.
 * Without it every box shows, and only the ticked ones are saved. Row and column 0
 * are for a piece with sizes only or colours only.
 */
$hasOld = session()->has('_ci_old_input');
$stocks = [];

foreach ($product->variants as $variant) {
    $stocks[(int) $variant->colour_id][(int) $variant->size_id] = $variant->stock;
}

$tickedColours = array_keys($stocks);
$tickedSizes   = array_merge(...array_values(array_map('array_keys', $stocks ?: [[]])));

if ($hasOld) {
    $tickedColours = array_map('intval', (array) old('colours', [], false));
    $tickedSizes   = array_map('intval', (array) old('sizes', [], false));
    $stocks        = (array) old('variant_stock', [], false);
}

$rows    = [...$colours, (object) ['id' => 0, 'name' => 'Stock']];
$columns = [...$sizes, (object) ['id' => 0, 'name' => 'Stock']];
$error   = (session('errors') ?? [])['variant_stock'] ?? null;
?>
<div class="grid gap-5" data-variants>
  <fieldset>
    <legend class="label">Colours</legend>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($colours as $colour): ?>
        <label class="chip">
          <input type="checkbox" name="colours[]" value="<?= (int) $colour->id ?>" <?= in_array((int) $colour->id, $tickedColours, true) ? 'checked' : '' ?>>
          <span class="chip-face"><span class="chip-swatch" style="background-color:<?= esc($colour->hex, 'attr') ?>"></span><?= esc($colour->name) ?></span>
        </label>
      <?php endforeach ?>
    </div>
  </fieldset>

  <fieldset>
    <legend class="label">Sizes</legend>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($sizes as $size): ?>
        <label class="chip">
          <input type="checkbox" name="sizes[]" value="<?= (int) $size->id ?>" <?= in_array((int) $size->id, $tickedSizes, true) ? 'checked' : '' ?>>
          <span class="chip-face"><?= esc($size->name) ?></span>
        </label>
      <?php endforeach ?>
    </div>
  </fieldset>

  <div data-variant-grid>
    <p class="label">Stock for each combination</p>
    <div class="overflow-x-auto rounded-lg border border-stone-200">
      <table class="w-full text-sm">
        <thead class="bg-stone-50 text-stone-600">
          <tr>
            <td class="p-2"></td>
            <?php foreach ($columns as $column): ?>
              <th class="p-2 font-medium" scope="col" data-size="<?= (int) $column->id ?>"><?= esc($column->name) ?></th>
            <?php endforeach ?>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-200">
          <?php foreach ($rows as $row): ?>
            <tr data-colour="<?= (int) $row->id ?>">
              <th class="p-2 text-left font-medium whitespace-nowrap" scope="row"><?= esc($row->name) ?></th>
              <?php foreach ($columns as $column): ?>
                <td class="p-1.5 text-center" data-size="<?= (int) $column->id ?>">
                  <input class="input mx-auto w-16 px-2 py-1.5 text-center" type="text" inputmode="numeric" name="variant_stock[<?= (int) $row->id ?>][<?= (int) $column->id ?>]" value="<?= esc((string) ($stocks[$row->id][$column->id] ?? ''), 'attr') ?>" aria-label="<?= esc("Stock of {$row->name}, {$column->name}", 'attr') ?>">
                </td>
              <?php endforeach ?>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php if ($error): ?>
      <p class="field-error"><?= esc($error) ?></p>
    <?php else: ?>
      <p class="field-hint">An empty box means none, and that combination shows as sold out.</p>
    <?php endif ?>
  </div>

  <p class="field-hint mt-0">Shoppers must pick from what you tick. <a class="text-link" href="<?= esc(url_to('admin.options'), 'attr') ?>">Change these lists</a></p>
</div>
