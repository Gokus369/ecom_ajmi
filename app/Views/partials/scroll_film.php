<?php
/** The current RUA brand film. Needs $frames from FrameSequence. */
$store = config(\Config\Store::class);
$sequence = $frames['desktop'] ?? $frames['mobile'];
$phone    = $frames['mobile'] ?? $sequence;
?>
<div class="film" data-film data-frames="<?= esc(json_encode($frames, JSON_UNESCAPED_SLASHES), 'attr') ?>">
  <section class="film-scroll" aria-label="RUA couture film">
    <div class="film-stage">
      <picture>
        <source media="(max-width: 768px), (max-height: 500px)" srcset="<?= esc("{$phone['url']}/frame_0001.webp?v={$phone['version']}", 'attr') ?>">
        <img src="<?= esc("{$sequence['url']}/frame_0001.webp?v={$sequence['version']}", 'attr') ?>" alt="" width="<?= $sequence['width'] ?>" height="<?= $sequence['height'] ?>" fetchpriority="high" decoding="async">
      </picture>
      <canvas role="img" aria-label="Wine and gold silk unfolds to reveal the RUA bridal boutique"></canvas>
      <div class="film-bar" aria-hidden="true"><i></i></div>
      <div class="film-hint" aria-hidden="true"><span>Scroll to discover</span><span class="film-hint-line"></span></div>
      <div class="film-loading" aria-hidden="true">Loading film</div>
    </div>
  </section>

  <section class="bg-brand-950 px-4 py-20 text-center text-white sm:py-28">
    <p class="eyebrow text-gold-400">Couture from Kerala</p>
    <h1 class="mx-auto mt-3 max-w-3xl text-4xl text-white sm:text-6xl"><?= esc($store->tagline) ?>.</h1>
    <div class="button-row mt-8 justify-center">
      <a class="btn btn-large btn-light" href="<?= esc(url_to('shop'), 'attr') ?>">Explore the collection</a>
      <a class="btn btn-large btn-outline-light" href="<?= esc(url_to('bespoke'), 'attr') ?>">Bespoke and bridal</a>
    </div>
  </section>
</div>
