<?php $store = config(\Config\Store::class); ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc(isset($title) ? "{$title} · {$store->name}" : "{$store->name} · {$store->tagline}") ?></title>
<meta name="description" content="<?= esc($metaDescription ?? $store->description, 'attr') ?>">
<?php if (! empty($noindex)): ?>
<meta name="robots" content="noindex">
<?php endif ?>
<link rel="canonical" href="<?= esc(current_url(), 'attr') ?>">
<link rel="icon" href="<?= esc(asset_url('favicon.svg'), 'attr') ?>" type="image/svg+xml">
<link rel="preload" href="<?= esc(asset_url('fonts/jost-latin.woff2'), 'attr') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= esc(asset_url('css/app.css'), 'attr') ?>">
<script src="<?= esc(asset_url('js/app.js'), 'attr') ?>" defer></script>
