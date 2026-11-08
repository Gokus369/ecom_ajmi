<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php
$store  = config(\Config\Store::class);
$custom = whatsapp_url("Hi {$store->shortName}, I'd like a custom piece.");
?>

<?php if ($store->address !== ''): ?>
  <script type="application/ld+json"><?= json_encode(array_filter([
      '@context'  => 'https://schema.org',
      '@type'     => 'ClothingStore',
      'name'      => $store->name,
      'url'       => base_url(),
      'telephone' => $store->phone,
      'address'   => $store->address,
      'sameAs'    => instagram_url(),
  ]), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif ?>

<?php if ($frames !== []): ?>
  <script>
    // Decided here, before the first paint, so the page never jumps into place.
    if ((document.documentElement.dataset.motion === 'always' || !matchMedia('(prefers-reduced-motion: reduce)').matches) && !navigator.connection?.saveData) {
      document.documentElement.classList.add('has-film');
    }
  </script>
  <?= partial('scroll_film', ['frames' => $frames]) ?>
  <script src="<?= esc(asset_url('js/scroll-film.js'), 'attr') ?>" defer></script>
<?php endif ?>

<section class="hero">
  <div class="wrap grid items-center gap-10 py-14 lg:grid-cols-2 lg:gap-16 lg:py-20">
    <div class="animate-rise">
      <p class="eyebrow">Couture from Kerala</p>
      <h1 class="text-4xl sm:text-6xl"><?= esc($store->tagline) ?>.</h1>
      <p class="lede mt-5 max-w-lg">Custom ethnic wear, bespoke bridal outfits and designer couture by fashion designer Ajmi Anzari.</p>
      <div class="button-row mt-8">
        <a class="btn btn-large" href="<?= esc(url_to('shop'), 'attr') ?>">Explore the collection</a>
        <a class="btn btn-large btn-outline" href="<?= esc(url_to('bespoke'), 'attr') ?>">Bespoke and bridal</a>
      </div>
    </div>

    <?php if ($covers !== []): ?>
      <div class="hero-mosaic grid grid-cols-3 gap-3" aria-hidden="true">
        <?php foreach (array_slice(array_values($covers), 0, 3) as $cover): ?>
          <div class="aspect-[4/5] overflow-hidden rounded-xl bg-stone-100">
            <?= partial('product_image', ['product' => $cover, 'eager' => true]) ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>
</section>

<?php if ($categories !== []): ?>
  <section class="wrap section" aria-labelledby="collections-heading">
    <div class="mb-8 flex items-end justify-between gap-4" data-reveal>
      <div>
        <p class="eyebrow">Signature pieces</p>
        <h2 id="collections-heading">The collections</h2>
      </div>
      <a class="text-link shrink-0 text-sm" href="<?= esc(url_to('shop'), 'attr') ?>">View all</a>
    </div>

    <?php // The row always fills, so a few collections leave no gap at the end. ?>
    <div class="collection-grid grid grid-cols-2 gap-4 <?= [2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][max(2, min(4, count($categories)))] ?>">
      <?php foreach ($categories as $category): ?>
        <?php $cover = collection_image_url($category->slug); ?>
        <a class="group relative block aspect-[4/3] overflow-hidden rounded-2xl bg-stone-200 lg:aspect-[16/10]" data-reveal="unroll" href="<?= esc(url_to('shop.category', $category->slug), 'attr') ?>">
          <span class="absolute inset-0 block">
            <?php if ($cover): ?>
              <img class="size-full object-cover transition-transform duration-700 ease-soft group-hover:scale-105" src="<?= esc($cover, 'attr') ?>" alt="" width="720" height="960" loading="lazy" decoding="async">
            <?php elseif (isset($covers[$category->id])): ?>
              <?= partial('product_image', ['product' => $covers[$category->id], 'class' => 'size-full object-cover transition-transform duration-700 ease-soft group-hover:scale-105']) ?>
            <?php endif ?>
          </span>
          <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 bg-linear-to-t from-black/70 to-transparent p-4 pt-16 text-white">
            <span class="font-display text-lg leading-tight sm:text-2xl"><?= esc($category->name) ?></span>
            <span class="shrink-0" aria-hidden="true"><?= partial('icon', ['name' => 'forward', 'size' => 20]) ?></span>
          </span>
        </a>
      <?php endforeach ?>
    </div>
  </section>
<?php endif ?>

<section class="band" aria-labelledby="bespoke-heading">
  <div class="wrap grid items-center gap-8 py-14 lg:grid-cols-2 lg:gap-16 lg:py-0" data-reveal>
    <div class="max-lg:order-2 lg:py-20">
      <p class="eyebrow text-gold-400">Bespoke</p>
      <h2 id="bespoke-heading" class="text-3xl text-white sm:text-5xl">Made for you, and only you</h2>
      <p class="mt-4 max-w-lg text-lg/relaxed text-brand-100">Every bespoke piece is designed around your style and your occasion, from festive wear to your wedding day.</p>
      <div class="button-row mt-8">
        <a class="btn btn-large btn-light" href="<?= esc(url_to('bespoke'), 'attr') ?>">How it works</a>
        <?php if ($custom): ?>
          <a class="btn btn-large btn-outline-light" href="<?= esc($custom, 'attr') ?>" target="_blank" rel="noopener">Message us on WhatsApp</a>
        <?php endif ?>
      </div>
    </div>
    <?php // Absolute on large screens, or the tall photo would set the height of the whole band. ?>
    <div class="relative h-72 overflow-hidden rounded-2xl sm:h-96 lg:h-auto lg:self-stretch lg:rounded-none">
      <img class="absolute inset-0 size-full object-cover object-[center_25%]" src="<?= esc(asset_url('auth-gown.webp'), 'attr') ?>" alt="" width="1280" height="1600" loading="lazy" decoding="async">
    </div>
  </div>
</section>

<section class="wrap section" aria-labelledby="new-heading">
  <div class="mb-8 flex items-end justify-between gap-4" data-reveal>
    <div>
      <p class="eyebrow">Just in</p>
      <h2 id="new-heading">New arrivals</h2>
    </div>
    <?php if ($products !== []): ?>
      <a class="text-link shrink-0 text-sm" href="<?= esc(url_to('shop'), 'attr') ?>">See everything</a>
    <?php endif ?>
  </div>

  <?php if ($products === []): ?>
    <div class="empty">
      <h3>The new collection is on its way</h3>
      <p>Pieces will appear here as soon as they are added. Please check back soon.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4">
      <?php foreach ($products as $product): ?>
        <?= partial('product_card', ['product' => $product]) ?>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</section>

<section class="wrap section pt-0" aria-labelledby="draping-heading" data-reveal>
  <div class="flex flex-col items-start gap-5 rounded-2xl bg-cream p-8 sm:flex-row sm:items-center sm:justify-between sm:p-10">
    <div>
      <p class="eyebrow">For your wedding day</p>
      <h2 id="draping-heading">Saree draping</h2>
      <p class="mt-2 max-w-xl text-stone-600">Book saree draping for your wedding, and we will contact you to confirm.</p>
    </div>
    <a class="btn btn-large shrink-0" href="<?= esc(url_to('draping'), 'attr') ?>">Book saree draping</a>
  </div>
</section>

<section class="band" aria-labelledby="consultation-heading">
  <div class="wrap section text-center" data-reveal>
    <h2 id="consultation-heading" class="text-3xl text-white sm:text-5xl"><?= esc($store->tagline) ?></h2>
    <a class="btn btn-large btn-light mt-8" href="<?= esc(url_to('bespoke'), 'attr') ?>">Book a consultation</a>
  </div>
</section>

<?= $this->endSection() ?>
