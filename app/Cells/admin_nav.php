<nav class="col-span-2 row-start-2 -mx-4 flex gap-1 overflow-x-auto px-4 pb-2 [scrollbar-width:none] sm:-mx-6 sm:px-6 lg:mx-0 lg:min-h-0 lg:flex-col lg:gap-0.5 lg:overflow-y-auto lg:p-0 lg:[scrollbar-width:thin]" aria-label="Admin">
  <?php $group = null; ?>
  <?php foreach ($links as $link): ?>
    <?php if ($link['group'] !== $group): ?>
      <?php $group = $link['group']; ?>
      <p class="hidden px-3 pt-4 pb-1 text-xs font-medium text-stone-400 first:pt-0 lg:block"><?= esc($group) ?></p>
    <?php endif ?>
    <?php // relative keeps the sr-only text inside the scrolling menu. Without it the text escapes the scroller and widens the page on phones. ?>
    <a class="relative flex min-h-10 flex-none items-center gap-2 rounded-lg px-3 text-sm font-medium text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-950 aria-[current=page]:bg-stone-100 aria-[current=page]:text-stone-950 lg:min-h-9 aria-[current=page]:[&_svg]:text-brand-800" href="<?= esc(url_to($link['route']), 'attr') ?>" <?= $link['key'] === $section ? 'aria-current="page"' : '' ?>>
      <?= partial('icon', ['name' => $link['icon'], 'size' => 18]) ?>
      <?= esc($link['label']) ?>
      <?php if ($link['new'] > 0): ?>
        <span class="nav-count ml-auto min-w-5 rounded-full bg-gold-500 px-1.5 text-center text-xs/5 font-semibold text-stone-950 tabular-nums"><?= number_format($link['new']) ?><span class="sr-only"> new</span></span>
      <?php endif ?>
    </a>
  <?php endforeach ?>
</nav>
