<?php
$store     = config(\Config\Store::class);
$whatsapp  = whatsapp_url();
$instagram = instagram_url();
$phone     = phone_url();
$maps      = maps_url();
$link      = 'text-stone-300 transition-colors hover:text-white';
?>
<footer class="mt-auto bg-stone-950 text-sm text-stone-300">
  <div class="wrap grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_2fr] lg:py-16" data-reveal>
    <div>
      <div class="text-white"><?= partial('brand', ['dark' => true]) ?></div>
      <p class="mt-4 max-w-xs text-stone-400"><?= esc($store->tagline) ?></p>
    </div>

    <nav aria-label="Footer">
      <h2 class="mb-4 font-sans text-sm font-semibold tracking-normal text-white">Shop</h2>
      <ul class="grid gap-2.5">
        <li><a class="<?= $link ?>" href="<?= esc(url_to('shop'), 'attr') ?>">Shop</a></li>
        <li><a class="<?= $link ?>" href="<?= esc(url_to('bespoke'), 'attr') ?>">Bespoke and bridal</a></li>
        <li><a class="<?= $link ?>" href="<?= esc(url_to('draping'), 'attr') ?>">Saree draping</a></li>
        <li><a class="<?= $link ?>" href="<?= esc(url_to('cart'), 'attr') ?>">Cart</a></li>
      </ul>
    </nav>

    <?php if ($whatsapp || $instagram || $phone || $maps || $store->contactEmail !== ''): ?>
      <div>
        <h2 class="mb-4 font-sans text-sm font-semibold tracking-normal text-white">Visit or contact us</h2>
        <address class="grid gap-2.5">
          <?php if ($maps): ?>
            <span class="text-stone-400"><?= esc($store->address) ?></span>
            <a class="<?= $link ?>" href="<?= esc($maps, 'attr') ?>" target="_blank" rel="noopener">Get directions</a>
          <?php endif ?>
          <?php if ($phone): ?>
            <a class="<?= $link ?>" href="<?= esc($phone, 'attr') ?>">Call <?= esc($store->phone) ?></a>
          <?php endif ?>
          <?php if ($whatsapp): ?>
            <a href="<?= esc($whatsapp, 'attr') ?>" class="<?= $link ?>" target="_blank" rel="noopener">WhatsApp <?= esc($store->whatsapp) ?></a>
          <?php endif ?>
          <?php if ($instagram): ?>
            <a class="<?= $link ?>" href="<?= esc($instagram, 'attr') ?>" target="_blank" rel="noopener">Instagram @<?= esc($store->instagram) ?></a>
          <?php endif ?>
          <?php if ($store->contactEmail !== ''): ?>
            <a class="<?= $link ?>" href="mailto:<?= esc($store->contactEmail, 'attr') ?>"><?= esc($store->contactEmail) ?></a>
          <?php endif ?>
        </address>
      </div>
    <?php endif ?>
  </div>

  <div class="border-t border-white/10">
    <p class="wrap py-5 text-xs text-stone-500">© <?= date('Y') ?> <?= esc($store->name) ?>. Kochi, Kerala.</p>
  </div>
</footer>
