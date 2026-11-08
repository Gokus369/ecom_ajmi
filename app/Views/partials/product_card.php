<?php
/** Needs $product. $headingTag keeps the page outline in order. Call through partial(). */
$headingTag = $headingTag ?? 'h3';
?>
<article class="group relative flex flex-col" data-reveal="unroll">
  <div class="relative aspect-[4/5] overflow-hidden rounded-xl bg-stone-100">
    <?= partial('product_image', ['product' => $product, 'class' => 'size-full object-cover transition-transform duration-500 ease-soft group-hover:scale-105']) ?>
    <?php if ($product->isSoldOut()): ?>
      <span class="absolute top-3 left-3 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-stone-900 shadow-xs">Sold out</span>
    <?php elseif ($product->is_enquiry_only): ?>
      <span class="absolute top-3 left-3 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-stone-900 shadow-xs">Made to order</span>
    <?php endif ?>
  </div>

  <div class="flex flex-1 flex-col pt-3">
    <?php // The link's ::after covers the whole card, so a click on the photo opens the piece. ?>
    <<?= $headingTag ?> class="font-sans text-sm font-medium tracking-normal text-stone-900 sm:text-base"><a class="after:absolute after:inset-0 after:z-3 hover:text-brand-800" href="<?= esc(url_to('products.show', $product->slug), 'attr') ?>"><?= esc($product->name) ?></a></<?= $headingTag ?>>
    <p class="mt-0.5 text-sm text-stone-600"><?= $product->is_enquiry_only ? 'Price on request' : esc(money($product->price)) ?></p>

    <?php if ($product->canBeBought()): ?>
      <div class="relative z-4 mt-auto pt-3">
        <?php if ($product->colour_list === [] && $product->size_list === []): ?>
          <?= form_open(url_to('cart.add')) ?>
            <input type="hidden" name="product_id" value="<?= $product->id ?>">
            <button type="submit" class="btn btn-block" aria-label="Add <?= esc($product->name, 'attr') ?> to cart">Add to cart</button>
          <?= form_close() ?>
        <?php else: ?>
          <a class="btn btn-outline btn-block" href="<?= esc(url_to('products.show', $product->slug), 'attr') ?>" aria-label="Choose options for <?= esc($product->name, 'attr') ?>">Choose options</a>
        <?php endif ?>
      </div>
    <?php endif ?>
  </div>
</article>
