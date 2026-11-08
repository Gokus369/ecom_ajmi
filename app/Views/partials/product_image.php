<?php
/**
 * A product's photo, or a clean lettered tile until one is added. Call through partial().
 *
 * Needs $product. $size is thumb (default) or large. $alt defaults to empty because
 * cards name the product right beside the image. $eager marks an above-the-fold image.
 * $class styles the image, for pages that crop or zoom it.
 */
$size            = $size ?? 'thumb';
[$width, $height] = \App\Libraries\ProductImages::SIZES[$size];
$url             = $size === 'large' ? $product->image_url : $product->thumb_url;
$class           = $class ?? 'size-full object-cover';
?>
<?php if ($url): ?>
  <img class="<?= esc($class, 'attr') ?>" src="<?= esc($url, 'attr') ?>" alt="<?= esc($alt ?? '', 'attr') ?>" width="<?= $width ?>" height="<?= $height ?>"
       <?= empty($eager) ? 'loading="lazy"' : 'fetchpriority="high"' ?> decoding="async">
<?php else: ?>
  <span class="image-fallback grid size-full place-items-center bg-stone-100 font-display text-4xl text-stone-400" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr($product->name, 0, 1))) ?></span>
<?php endif ?>
