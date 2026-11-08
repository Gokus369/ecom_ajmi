<?php
$store = config(\Config\Store::class);
$title = match (true) {
    uri_string() === 'register'              => 'Create your account',
    str_starts_with(uri_string(), 'auth/a/') => 'Verify your email',
    default                                  => 'Sign in',
};
?>
<!DOCTYPE html>
<html lang="en" data-motion="<?= esc($store->motion, 'attr') ?>">
<head>
  <?= partial('head', ['title' => $title, 'noindex' => true]) ?>
</head>
<body class="auth-page">
  <div class="grid min-h-screen lg:grid-cols-[1.1fr_1fr]">
    <aside class="relative flex min-h-56 items-end overflow-hidden bg-brand-900 p-6 text-white lg:sticky lg:top-0 lg:h-screen lg:p-12">
      <img class="absolute inset-0 size-full object-cover object-[center_20%] lg:object-[center_30%]" src="<?= esc(asset_url('auth-gown.webp'), 'attr') ?>" alt="" width="1280" height="1600" fetchpriority="high">
      <div class="absolute inset-0 bg-linear-to-t from-brand-950/95 via-brand-950/40 to-transparent"></div>
      <div class="relative">
        <a class="block w-fit text-white" href="<?= esc(url_to('home'), 'attr') ?>" aria-label="<?= esc($store->name, 'attr') ?>, home"><?= partial('brand', ['dark' => true]) ?></a>
        <p class="mt-3 font-display text-lg italic lg:text-2xl"><?= esc($store->tagline) ?></p>
      </div>
    </aside>

    <main class="grid place-items-center px-5 py-10 lg:px-10">
      <div class="w-full max-w-sm animate-rise">
        <?= $this->renderSection('content') ?>
        <p class="mt-4 text-center"><a class="text-link text-sm" href="<?= esc(url_to('home'), 'attr') ?>">Back to the store</a></p>
      </div>
    </main>
  </div>
</body>
</html>
