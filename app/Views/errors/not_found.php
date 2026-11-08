<?= $this->extend('layouts/store') ?>

<?= $this->section('content') ?>
<div class="wrap page">
  <div class="empty">
    <h1>We could not find that page</h1>
    <p>It may have moved, or the product may no longer be available.</p>
    <a class="btn" href="<?= esc(url_to('shop'), 'attr') ?>">Browse the shop</a>
  </div>
</div>
<?= $this->endSection() ?>
