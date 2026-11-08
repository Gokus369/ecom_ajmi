<?= $this->extend('layouts/admin') ?>

<?= $this->section('head') ?>
<?= partial('admin_head', ['eyebrow' => 'Catalogue', 'title' => 'Colours and sizes', 'lede' => 'Keep each list here, then tick what applies on each product.']) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid items-start gap-5 xl:grid-cols-2">
  <?= partial('option_list', ['list' => 'colours', 'noun' => 'colour', 'heading' => 'Colours', 'items' => $colours]) ?>
  <?= partial('option_list', ['list' => 'sizes', 'noun' => 'size', 'heading' => 'Sizes', 'items' => $sizes]) ?>
</div>
<?= $this->endSection() ?>
