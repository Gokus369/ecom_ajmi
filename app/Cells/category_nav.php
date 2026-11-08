<?php
$item = 'block rounded-lg px-3 py-2 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-100 hover:text-stone-950 aria-[current=page]:bg-brand-50 aria-[current=page]:text-brand-800';
?>
<nav aria-label="Shop">
  <details class="nav-menu group relative">
    <summary class="flex min-h-11 w-full cursor-pointer list-none items-center justify-center gap-2 rounded-full bg-brand-800 px-7 text-base font-semibold tracking-wide text-white shadow-md ring-2 ring-gold-500 ring-offset-2 ring-offset-white transition-colors hover:bg-brand-700 group-open:bg-brand-900 lg:w-fit [&::-webkit-details-marker]:hidden">
      <?= partial('icon', ['name' => 'hanger', 'size' => 20]) ?>
      Shop
      <span class="transition-transform group-open:rotate-180"><?= partial('icon', ['name' => 'down', 'size' => 18]) ?></span>
    </summary>

    <div class="mt-3 grid gap-5 rounded-2xl border-t-2 border-gold-500 bg-cream p-4 sm:grid-cols-2 lg:absolute lg:top-full lg:left-0 lg:mt-3 lg:w-[40rem] lg:grid-cols-[1fr_1fr_10rem] lg:bg-white lg:shadow-xl lg:ring-1 lg:ring-stone-200">
      <div>
        <p class="eyebrow mb-2 px-3">Collections</p>
        <a class="<?= $item ?>" href="<?= esc(url_to('shop'), 'attr') ?>" <?= $current === 'shop' ? 'aria-current="page"' : '' ?>>All pieces</a>
        <?php foreach ($categories as $category): ?>
          <a class="<?= $item ?>" href="<?= esc(url_to('shop.category', $category->slug), 'attr') ?>" <?= $current === 'shop/' . $category->slug ? 'aria-current="page"' : '' ?>><?= esc($category->name) ?></a>
        <?php endforeach ?>
      </div>

      <div>
        <p class="eyebrow mb-2 px-3">Services</p>
        <a class="<?= $item ?>" href="<?= esc(url_to('bespoke'), 'attr') ?>" <?= $current === 'bespoke' ? 'aria-current="page"' : '' ?>>Bespoke</a>
        <a class="<?= $item ?>" href="<?= esc(url_to('draping'), 'attr') ?>" <?= $current === 'saree-draping' ? 'aria-current="page"' : '' ?>>Saree draping</a>
      </div>

      <img class="hidden size-full max-h-56 rounded-xl object-cover object-[center_22%] lg:block" src="<?= esc(asset_url('auth-gown.webp'), 'attr') ?>" alt="" width="1280" height="1600" loading="lazy" decoding="async">
    </div>
  </details>
</nav>
