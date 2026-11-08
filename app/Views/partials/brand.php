<?php
/** The wordmark. It takes its colour from around it. Pass $dark on dark backgrounds. */
$store = config(\Config\Store::class);
?>
<span class="brand-mark"><?= esc($store->shortName) ?></span>
<span class="brand-by <?= empty($dark) ? 'text-gold-700' : 'text-gold-400' ?>"><?= esc($store->byline) ?></span>
