<?php
/**
 * A piece's photos, the main one first. Needs $product and $photos. Call through partial().
 *
 * The photos swipe and the thumbnails link without JavaScript. app.js only smooths
 * the move and marks the photo on show.
 */
$count = count($photos);
?>
<?php if ($count < 2): ?>
  <div class="aspect-[4/5] overflow-hidden rounded-2xl bg-stone-100">
    <?= partial('product_image', ['product' => $product, 'size' => 'large', 'alt' => $product->name, 'eager' => true]) ?>
  </div>
<?php else: ?>
  <?php
  [$width, $height]           = \App\Libraries\ProductImages::SIZES['large'];
  [$thumbWidth, $thumbHeight] = \App\Libraries\ProductImages::SIZES['thumb'];
  ?>
  <div data-gallery>
    <div class="gallery-track aspect-[4/5] rounded-2xl bg-stone-100" role="group" aria-label="<?= esc("Photos of {$product->name}", 'attr') ?>" tabindex="0">
      <?php foreach ($photos as $index => $photo): ?>
        <img class="size-full object-cover" id="photo-<?= $index + 1 ?>" src="<?= esc($product->photoUrl($photo), 'attr') ?>"
             alt="<?= esc("{$product->name}, photo " . ($index + 1) . " of {$count}", 'attr') ?>" width="<?= $width ?>" height="<?= $height ?>"
             <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
      <?php endforeach ?>
    </div>
    <div class="mt-3 flex flex-wrap gap-2">
      <?php foreach ($photos as $index => $photo): ?>
        <a class="gallery-thumb aspect-[4/5] w-16 overflow-hidden rounded-lg bg-stone-100 opacity-60 ring-brand-800 ring-offset-2 transition-opacity hover:opacity-100 aria-[current]:opacity-100 aria-[current]:ring-2" href="#photo-<?= $index + 1 ?>" aria-label="Show photo <?= $index + 1 ?> of <?= $count ?>"<?= $index === 0 ? ' aria-current="true"' : '' ?>>
          <img class="size-full object-cover" src="<?= esc($product->photoUrl($photo, 'thumb'), 'attr') ?>" alt="" width="<?= $thumbWidth ?>" height="<?= $thumbHeight ?>" loading="lazy" decoding="async">
        </a>
      <?php endforeach ?>
    </div>
  </div>
<?php endif ?>
