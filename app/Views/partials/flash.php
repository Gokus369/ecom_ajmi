<?php
$messages = [
    'message' => ['ok', 'status'],
    'warning' => ['warn', 'status'],
    'error'   => ['danger', 'alert'],
];
?>
<?php foreach ($messages as $key => [$tone, $role]): ?>
  <?php if (is_string(session($key))): ?>
    <div class="wrap pt-4">
      <p class="flash flash-<?= $tone ?>" role="<?= $role ?>">
        <?= esc(session($key)) ?>
        <?php if ($key === 'message' && ! str_starts_with(uri_string(), 'admin') && service('cart')->count() > 0): ?>
          <a href="<?= esc(url_to('cart'), 'attr') ?>">View cart</a>
        <?php endif ?>
      </p>
    </div>
  <?php endif ?>
<?php endforeach ?>
