<?php if ($offer !== null): ?>
  <aside class="bg-brand-900 text-white" aria-label="Current offer">
    <p class="wrap py-2 text-center text-sm">
      <strong class="font-semibold text-gold-300"><?= esc($offer->name) ?>:</strong>
      <?= esc($offer->discount_text) ?><?= $offer->min_order > 0 ? ' on orders over ' . esc(money($offer->min_order)) : '' ?>,
      until <?= esc(date('j F', strtotime($offer->ends_on))) ?>.
      <a class="font-medium underline underline-offset-2" href="<?= esc(url_to('shop'), 'attr') ?>">Shop now</a>
    </p>
  </aside>
<?php endif ?>
