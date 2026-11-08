<?php /** @var \CodeIgniter\Pager\PagerRenderer $pager */ $pager->setSurroundCount(2); ?>
<?php if ($pager->getPageCount() > 1): ?>
  <nav class="pager" aria-label="Pagination">
    <ul>
      <?php if ($pager->hasPreviousPage()): ?>
        <li><a href="<?= esc($pager->getPreviousPage(), 'attr') ?>" rel="prev">Previous</a></li>
      <?php endif ?>
      <?php foreach ($pager->links() as $link): ?>
        <li><a href="<?= esc($link['uri'], 'attr') ?>" <?= $link['active'] ? 'aria-current="page"' : '' ?>><?= (int) $link['title'] ?></a></li>
      <?php endforeach ?>
      <?php if ($pager->hasNextPage()): ?>
        <li><a href="<?= esc($pager->getNextPage(), 'attr') ?>" rel="next">Next</a></li>
      <?php endif ?>
    </ul>
  </nav>
<?php endif ?>
