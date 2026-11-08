<!DOCTYPE html>
<html lang="en" data-motion="<?= esc(config(\Config\Store::class)->motion, 'attr') ?>">
<head>
  <?= $this->include('partials/head') ?>
</head>
<body class="flex min-h-screen flex-col">
  <a class="skip-link" href="#main">Skip to content</a>
  <?= view_cell(\App\Cells\OfferBannerCell::class) ?>
  <?= $this->include('partials/header') ?>
  <main id="main" class="flex-1">
    <?= $this->include('partials/flash') ?>
    <?= $this->renderSection('content') ?>
  </main>
  <?= $this->include('partials/footer') ?>
  <?= $this->include('partials/chat') ?>
</body>
</html>
