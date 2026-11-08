<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<?php $booked = session('booked'); ?>
<div class="grid lg:grid-cols-[1fr_1.15fr]">
  <div class="h-52 overflow-hidden sm:h-72 lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)]">
    <img class="size-full object-cover object-[center_22%]" src="<?= esc(asset_url('auth-gown.webp'), 'attr') ?>" alt="" width="1280" height="1600" fetchpriority="high">
  </div>

  <div class="mx-auto w-full max-w-xl px-4 py-10 sm:px-6 lg:py-14">
    <p class="eyebrow">For your wedding day</p>
    <h1>Saree draping</h1>

    <?php if ($booked): ?>
      <div class="mt-6 rounded-2xl bg-cream p-6" role="status">
        <h2 class="text-xl">Thank you</h2>
        <p class="mt-2 mb-5 text-stone-600">We have your request for <?= esc($booked['date']) ?>. We will contact you on your phone number to confirm it.</p>
        <?php if ($booked['whatsapp']): ?>
          <a class="btn btn-block" href="<?= esc($booked['whatsapp'], 'attr') ?>" target="_blank" rel="noopener">Also send it on WhatsApp</a>
        <?php endif ?>
      </div>
    <?php else: ?>
      <p class="lede mt-3">Book saree draping for your wedding. Share your date and place, and we will contact you to confirm.</p>

      <?= form_open(url_to('draping.book'), ['class' => 'mt-8', 'novalidate' => true, 'data-submit-once' => true]) ?>
        <div class="form-grid">
          <?= partial('field', ['name' => 'customer_name', 'label' => 'Full name', 'value' => auth()->user()?->name, 'required' => true, 'attributes' => 'autocomplete="name" maxlength="100"']) ?>
          <?= partial('field', ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true, 'attributes' => 'autocomplete="tel" maxlength="20"']) ?>
          <?= partial('field', ['name' => 'wedding_date', 'label' => 'Wedding date', 'type' => 'date', 'required' => true, 'attributes' => 'min="' . esc($today, 'attr') . '"']) ?>
          <?= partial('field', ['name' => 'ready_by', 'label' => 'Ready by', 'type' => 'time', 'hint' => 'When you need to be ready.']) ?>
          <div class="form-wide">
            <?= partial('field', ['name' => 'location', 'label' => 'Venue or place', 'required' => true, 'hint' => 'Where the draping would happen. We will confirm this with you.', 'attributes' => 'maxlength="255"']) ?>
          </div>
          <?= partial('field', ['name' => 'people', 'label' => 'Number of people', 'type' => 'number', 'value' => 1, 'required' => true, 'attributes' => 'min="1" max="20"']) ?>
          <div class="form-wide">
            <?= partial('field', ['name' => 'notes', 'label' => 'Anything else', 'type' => 'textarea', 'rows' => 3, 'attributes' => 'maxlength="500"']) ?>
          </div>
        </div>

        <button type="submit" class="btn btn-large btn-block mt-6">Request booking</button>
        <p class="mt-3 text-center text-sm text-stone-500">Your booking is confirmed once we contact you.</p>
      <?= form_close() ?>
    <?php endif ?>
  </div>
</div>
<?= $this->endSection() ?>
