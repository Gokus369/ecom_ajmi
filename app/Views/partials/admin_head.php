<?php
/**
 * The band at the top of every admin page. Call through partial().
 *
 * Needs $title. Optional: $eyebrow, $lede, $back as [url, label] for a link above
 * the title, $action as [url, label] for its button, and $statusOf, an Order to show the status of.
 */
?>
<header class="mb-5 flex flex-wrap items-end justify-between gap-x-8 gap-y-3">
  <div class="min-w-0">
    <?php if (isset($back)): ?>
      <a class="mb-1 inline-flex min-h-8 items-center gap-1.5 text-sm text-stone-500 transition-colors hover:text-stone-950" href="<?= esc($back[0], 'attr') ?>"><?= partial('icon', ['name' => 'back', 'size' => 16]) ?><?= esc($back[1]) ?></a>
    <?php elseif (isset($eyebrow)): ?>
      <p class="eyebrow mb-1"><?= esc($eyebrow) ?></p>
    <?php endif ?>
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
      <h1 class="font-sans text-2xl font-semibold tracking-tight"><?= esc($title) ?></h1>
      <?php if (isset($statusOf)): ?>
        <span class="status status-<?= esc($statusOf->status, 'attr') ?>"><?= esc($statusOf->status_label) ?></span>
      <?php endif ?>
    </div>
    <?php if (isset($lede)): ?>
      <p class="mt-1 text-sm text-stone-500"><?= esc($lede) ?></p>
    <?php endif ?>
  </div>
  <?php if (isset($action)): ?>
    <a class="btn" href="<?= esc($action[0], 'attr') ?>"><?= esc($action[1]) ?></a>
  <?php endif ?>
</header>
