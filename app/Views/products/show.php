<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php
$store    = config(\Config\Store::class);
$pageUrl  = url_to('products.show', $product->slug);
$whatsapp = whatsapp_url("Hi {$store->shortName}, I'm interested in {$product->name}: {$pageUrl}");
$choices  = [
    'colour' => ['Colour', $product->colour_list],
    'size'   => ['Size', $product->size_list],
];
?>
<div class="wrap page">
  <nav class="mb-5 flex items-center gap-2 text-sm text-stone-500" aria-label="Breadcrumb">
    <a class="transition-colors hover:text-stone-900" href="<?= esc(url_to('shop'), 'attr') ?>">Shop</a>
    <?php if ($category): ?>
      <span aria-hidden="true">/</span>
      <a class="transition-colors hover:text-stone-900" href="<?= esc(url_to('shop.category', $category->slug), 'attr') ?>"><?= esc($category->name) ?></a>
    <?php endif ?>
  </nav>

  <div class="grid gap-8 lg:grid-cols-[1.1fr_1fr] lg:gap-14">
    <?= partial('product_gallery', ['product' => $product, 'photos' => $photos]) ?>

    <div class="lg:sticky lg:top-24 lg:self-start">
      <h1><?= esc($product->name) ?></h1>
      <p class="mt-2 text-xl text-stone-900"><?= $product->is_enquiry_only ? 'Price on request' : esc(money($product->price)) ?></p>

      <?php if ($product->summary): ?>
        <p class="mt-4 text-stone-600"><?= esc($product->summary) ?></p>
      <?php endif ?>

      <div class="mt-6 grid gap-5">
        <?php if ($product->is_enquiry_only): ?>
          <p class="status status-delivered">Made to order</p>
          <p class="text-stone-600">This piece is made for you. Message us and we will talk through your style, your date and the price.</p>
          <?php if ($whatsapp): ?>
            <a class="btn btn-large btn-block" href="<?= esc($whatsapp, 'attr') ?>" target="_blank" rel="noopener">Enquire on WhatsApp</a>
          <?php endif ?>
        <?php elseif ($product->isSoldOut()): ?>
          <p class="status status-cancelled">Sold out</p>
          <?php if ($whatsapp): ?>
            <a class="btn btn-large btn-outline btn-block" href="<?= esc($whatsapp, 'attr') ?>" target="_blank" rel="noopener">Ask about this piece</a>
          <?php endif ?>
        <?php else: ?>
          <?php if ($product->variants === [] && $product->stock <= $store->lowStockAt): ?>
            <p class="status status-pending">Only <?= $product->stock ?> left</p>
          <?php else: ?>
            <p class="status status-delivered" data-variant-status>In stock</p>
          <?php endif ?>

          <?php // app.js reads the stock map to show what each colour has left. The cart checks again. ?>
          <?= form_open(url_to('cart.add'), array_filter([
              'class'              => 'grid gap-5',
              'data-variant-stock' => $product->variants === [] ? null : json_encode($product->stock_map),
              'data-low-stock'     => $store->lowStockAt,
          ])) ?>
            <input type="hidden" name="product_id" value="<?= $product->id ?>">
            <?php foreach ($choices as $name => [$label, $options]): ?>
              <?php if ($options !== []): ?>
                <fieldset>
                  <legend class="label"><?= esc($label) ?></legend>
                  <div class="flex flex-wrap gap-2">
                    <?php foreach ($options as $option): ?>
                      <?php $soldOut = ($name === 'colour' ? $product->unitsLeft(colour: $option) : $product->unitsLeft(size: $option)) < 1; ?>
                      <label class="chip">
                        <input type="radio" name="<?= $name ?>" value="<?= esc($option, 'attr') ?>" required <?= count($options) === 1 && ! $soldOut ? 'checked' : '' ?> <?= $soldOut ? 'disabled' : '' ?>>
                        <span class="chip-face">
                          <?php if ($name === 'colour'): ?>
                            <span class="chip-swatch" style="background-color:<?= esc($product->swatchOf($option), 'attr') ?>"></span>
                          <?php endif ?>
                          <?= esc($option) ?>
                        </span>
                      </label>
                    <?php endforeach ?>
                  </div>
                </fieldset>
              <?php endif ?>
            <?php endforeach ?>

            <div class="flex items-end gap-3">
              <div class="w-20 shrink-0">
                <label class="label" for="quantity">Quantity</label>
                <input class="input h-12" id="quantity" type="number" name="quantity" value="1" min="1" max="<?= min($product->stock, 99) ?>" inputmode="numeric">
              </div>
              <button type="submit" class="btn btn-large h-12 flex-1">Add to cart</button>
            </div>
          <?= form_close() ?>

          <?php if ($whatsapp): ?>
            <a class="btn btn-outline btn-block" href="<?= esc($whatsapp, 'attr') ?>" target="_blank" rel="noopener">Ask about this piece or customise it</a>
          <?php endif ?>
        <?php endif ?>

        <?php if (! $product->canBeBought()): ?>
          <?php foreach ($choices as [$label, $options]): ?>
            <?php if ($options !== []): ?>
              <p class="text-sm"><span class="muted"><?= esc($label) ?></span> <?= esc(implode(', ', $options)) ?></p>
            <?php endif ?>
          <?php endforeach ?>
        <?php endif ?>
      </div>

      <?php if ($product->description): ?>
        <div class="mt-8 border-t border-stone-200 pt-6" data-reveal>
          <h2 class="mb-2 font-sans text-base font-semibold tracking-normal">Details</h2>
          <?php // nl2br runs after esc(), so only the line breaks it adds are unescaped. ?>
          <p class="text-stone-600"><?= nl2br(esc($product->description)) ?></p>
        </div>
      <?php endif ?>
    </div>
  </div>

  <?php if ($related !== []): ?>
    <section class="mt-16 border-t border-stone-200 pt-12" aria-labelledby="related-heading">
      <h2 id="related-heading" class="mb-6" data-reveal>You might also like</h2>
      <div class="grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4">
        <?php foreach ($related as $item): ?>
          <?= partial('product_card', ['product' => $item]) ?>
        <?php endforeach ?>
      </div>
    </section>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
