<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php
$store     = config(\Config\Store::class);
$instagram = instagram_url();
$phone     = phone_url();
$maps      = maps_url();
$errors    = session('errors');
$open      = is_array($errors) && str_starts_with((string) array_key_first($errors), 'bridal_') ? 'bridal' : 'custom';
$choices   = [
    'custom' => ['Custom styling', 'A piece made around your occasion'],
    'bridal' => ['Bridal consultation', 'Your wedding look, talked through with you'],
];
?>
<div class="wrap page">
  <header class="mx-auto mb-10 max-w-2xl text-center">
    <p class="eyebrow">Bespoke and bridal</p>
    <h1 class="text-4xl sm:text-5xl"><?= esc($store->tagline) ?></h1>
    <p class="lede mt-4">Fashion designer Ajmi Anzari creates custom pieces tailored to you, from festive wear to bridal couture.</p>
  </header>

  <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_1.2fr] lg:gap-14" data-reveal>
    <div class="mx-auto aspect-[4/5] w-56 overflow-hidden rounded-2xl bg-stone-100 lg:sticky lg:top-24 lg:w-full lg:self-start">
      <img class="size-full object-cover object-[center_15%]" src="<?= esc(asset_url('auth-gown.webp'), 'attr') ?>" alt="" width="1280" height="1600" loading="lazy">
    </div>

    <?php // The radios choose which form shows, using CSS alone. Without :has() both simply stay open. ?>
    <div class="min-w-0 has-[#choose-bridal:checked]:[&_#custom-form]:hidden has-[#choose-custom:checked]:[&_#bridal-form]:hidden">
      <fieldset class="mb-6">
        <legend class="mb-4 font-display text-2xl font-medium sm:text-3xl">What can we make for you?</legend>
        <div class="grid gap-3 sm:grid-cols-2">
          <?php foreach ($choices as $value => [$label, $hint]): ?>
            <label class="relative cursor-pointer">
              <input class="peer absolute inset-0 size-full cursor-pointer opacity-0" type="radio" name="bespoke_form" id="choose-<?= $value ?>" autocomplete="off"<?= $value === $open ? ' checked' : '' ?>>
              <span class="block h-full rounded-xl p-4 ring-1 ring-stone-300 transition ring-inset peer-hover:ring-stone-500 peer-checked:bg-brand-50 peer-checked:ring-2 peer-checked:ring-brand-800 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-700">
                <span class="block font-medium text-stone-900"><?= esc($label) ?></span>
                <span class="mt-0.5 block text-sm text-stone-600"><?= esc($hint) ?></span>
              </span>
            </label>
          <?php endforeach ?>
        </div>
      </fieldset>

      <section id="custom-form" class="animate-rise" aria-labelledby="custom-heading">
        <h2 id="custom-heading" class="sr-only">Custom styling</h2>
        <p class="mb-5 text-stone-600">Tell us the occasion, your date and any colours or references you love. We will take it from there.</p>
        <?= partial('enquiry_form', ['form' => 'custom', 'dateLabel' => 'Date you need it by', 'detailsLabel' => 'Occasion, colours and references', 'today' => $today]) ?>
      </section>

      <section id="bridal-form" class="animate-rise" aria-labelledby="bridal-heading">
        <h2 id="bridal-heading" class="sr-only">Bridal consultation</h2>
        <p class="mb-5 text-stone-600">Tell us your wedding date and the look you have in mind, and we will talk it through with you.</p>
        <?= partial('enquiry_form', ['form' => 'bridal', 'dateLabel' => 'Wedding date', 'detailsLabel' => 'The look you have in mind', 'today' => $today]) ?>
      </section>
    </div>
  </div>

  <?php if ($instagram): ?>
    <p class="mt-10 text-center text-sm text-stone-600" data-reveal>Prefer Instagram? <a class="text-link" href="<?= esc($instagram, 'attr') ?>" target="_blank" rel="noopener">DM @<?= esc($store->instagram) ?></a></p>
  <?php endif ?>

  <?php if ($maps): ?>
    <section class="mx-auto mt-12 max-w-3xl rounded-2xl bg-cream p-8 text-center" data-reveal aria-labelledby="visit-heading">
      <h2 id="visit-heading" class="text-2xl">Visit the boutique</h2>
      <address class="mt-2 mb-5 text-stone-600"><?= esc($store->address) ?></address>
      <div class="button-row justify-center">
        <a class="btn" href="<?= esc($maps, 'attr') ?>" target="_blank" rel="noopener">Get directions</a>
        <?php if ($phone): ?>
          <a class="btn btn-outline" href="<?= esc($phone, 'attr') ?>">Call <?= esc($store->phone) ?></a>
        <?php endif ?>
      </div>
    </section>
  <?php endif ?>
</div>
<?= $this->endSection() ?>
