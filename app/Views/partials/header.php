<?php
$store     = config(\Config\Store::class);
$cartCount = service('cart')->count();
$signedIn  = auth()->loggedIn();
?>
<header class="site-header top-0 z-30 border-b border-stone-200 bg-white/95 backdrop-blur lg:sticky">
  <div class="wrap grid grid-cols-[1fr_auto] items-center gap-x-6 gap-y-3 py-3 lg:grid-cols-[auto_1fr_auto] lg:py-0">
    <a class="block w-fit text-stone-950 lg:py-3" href="<?= esc(url_to('home'), 'attr') ?>" aria-label="<?= esc($store->name, 'attr') ?>, home">
      <?= partial('brand') ?>
    </a>

    <div class="col-span-2 row-start-3 min-w-0 lg:col-span-1 lg:col-start-2 lg:row-start-1">
      <?= view_cell(\App\Cells\CategoryNavCell::class) ?>
    </div>

    <div class="flex items-center gap-1 whitespace-nowrap sm:gap-2 lg:col-start-3">
      <form class="relative hidden lg:block" action="<?= esc(url_to('shop'), 'attr') ?>" method="get" role="search">
        <label class="sr-only" for="site-search">Search the collection</label>
        <input id="site-search" class="w-52 rounded-full border-0 bg-stone-100 py-2 pr-4 pl-10 text-sm text-stone-900 placeholder:text-stone-500 focus:bg-white focus:ring-2 focus:ring-brand-700 focus:outline-none" type="search" name="q" placeholder="Search" maxlength="80" value="<?= esc($term ?? '', 'attr') ?>">
        <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-stone-500"><?= partial('icon', ['name' => 'search', 'size' => 18]) ?></span>
      </form>

      <a class="inline-flex h-10 items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-100 hover:text-stone-950" href="<?= esc($signedIn ? url_to('account') : url_to('login'), 'attr') ?>">
        <?= partial('icon', ['name' => 'user', 'size' => 20]) ?>
        <span class="max-[22.5rem]:sr-only"><?= $signedIn ? 'My orders' : 'Sign in' ?></span>
      </a>

      <a class="relative inline-flex h-10 items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-100 hover:text-stone-950" href="<?= esc(url_to('cart'), 'attr') ?>" aria-label="Cart, <?= $cartCount ?> item<?= $cartCount === 1 ? '' : 's' ?>">
        <?= partial('icon', ['name' => 'orders', 'size' => 20]) ?>
        <span class="max-sm:sr-only">Cart</span>
        <?php if ($cartCount > 0): ?>
          <span class="grid h-5 min-w-5 place-items-center rounded-full bg-brand-800 px-1 text-xs font-semibold text-white tabular-nums"><?= $cartCount ?></span>
        <?php endif ?>
      </a>
    </div>

    <form class="relative col-span-2 row-start-2 lg:hidden" action="<?= esc(url_to('shop'), 'attr') ?>" method="get" role="search">
      <label class="sr-only" for="site-search-small">Search the collection</label>
      <input id="site-search-small" class="w-full rounded-full border-0 bg-stone-100 py-2 pr-4 pl-10 text-base text-stone-900 placeholder:text-stone-500 focus:bg-white focus:ring-2 focus:ring-brand-700 focus:outline-none" type="search" name="q" placeholder="Search the collection" maxlength="80" value="<?= esc($term ?? '', 'attr') ?>">
      <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-stone-500"><?= partial('icon', ['name' => 'search', 'size' => 18]) ?></span>
    </form>
  </div>
</header>
