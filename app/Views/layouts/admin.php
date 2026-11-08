<!DOCTYPE html>
<html lang="en">
<head>
  <?= partial('head', ['noindex' => true]) ?>
</head>
<body class="bg-stone-50">
  <a class="skip-link" href="#main">Skip to content</a>
  <div class="min-h-dvh lg:grid lg:grid-cols-[15rem_minmax(0,1fr)]">
    <aside class="admin-nav grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-2 border-b border-stone-200 bg-white px-4 pt-3 sm:px-6 lg:sticky lg:top-0 lg:flex lg:h-dvh lg:flex-col lg:items-stretch lg:gap-6 lg:overflow-y-auto lg:border-r lg:border-b-0 lg:p-4">
      <a class="w-fit lg:px-3 lg:pt-2" href="<?= esc(url_to('admin.home'), 'attr') ?>"><?= partial('brand') ?></a>
      <?= view_cell(\App\Cells\AdminNavCell::class) ?>
      <div class="col-start-2 row-start-1 flex gap-1 lg:mt-auto lg:flex-col lg:gap-0.5 lg:border-t lg:border-stone-200 lg:pt-3">
        <a class="flex min-h-9 items-center gap-2 rounded-lg px-2 text-sm text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-950 max-lg:[&_svg]:hidden lg:px-3" href="<?= esc(url_to('home'), 'attr') ?>"><?= partial('icon', ['name' => 'store', 'size' => 18]) ?>View store</a>
        <a class="flex min-h-9 items-center gap-2 rounded-lg px-2 text-sm text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-950 max-lg:[&_svg]:hidden lg:px-3" href="<?= esc(url_to('logout'), 'attr') ?>"><?= partial('icon', ['name' => 'sign-out', 'size' => 18]) ?>Sign out</a>
      </div>
    </aside>

    <main id="main" class="min-w-0">
      <div class="wrap py-5 lg:py-8">
        <?= $this->renderSection('head') ?>
        <?php // The flash partial brings its own .wrap, which would double the page gutter here. ?>
        <div class="[&_.wrap]:max-w-none [&_.wrap]:p-0 [&_.wrap]:pb-4">
          <?= $this->include('partials/flash') ?>
        </div>
        <?= $this->renderSection('content') ?>
      </div>
    </main>
  </div>
</body>
</html>
