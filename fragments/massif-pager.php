<?php

/** @var rex_pager $pager */
$pager = $this->getVar('pager', null);
/** @var rex_url_provider_interface $urlProvider */
$urlProvider = $this->getVar('urlprovider', rex_article::getCurrent());
$url_key = $this->getVar('url_key', null);
$url_key_val = $this->getVar('url_key_val', null);

$firstPage = $pager->getFirstPage();
$currentPage = $pager->getCurrentPage();
$lastPage = $pager->getLastPage();

$showMax = 1;
$anchor = '';

$params = [];
if ($_GET) {
    foreach ($_GET as $key => $val) {
        $params[$key] = $val;
    }
    unset($params[$pager->getCursorName()]);
}
if ($url_key && $url_key_val) {
    $params[$url_key] = $url_key_val;
}

$from = max($firstPage + 1, $currentPage - $showMax);
$to = min($lastPage - 1, $from + ($showMax * 2));
$to = $to > $currentPage + $showMax ? $currentPage + $showMax : $to;

$isPageActive = fn($page) => $pager->isActivePage($page) ? ' text-white bg-accent rounded-xs' : '';
?>
<?php if ($pager->getRowCount() > $pager->getRowsPerPage()) : ?>
    <div role="navigation" class="flex justify-between mt-4 pt-4 border-fg/10 border-t text-sm uppercase tracking-wider pager" aria-label="Pagination Navigation">

        <div class="pager-info">
            <?= sprintf('{{page}} %s/%s', $pager->getCurrentPage(), $pager->getLastPage()) ?>
        </div>

        <div class="[&_.pager-a]:block flex items-center gap-2 *:h-full *:aspect-square text-center pager-pages">

            <?php if (!$pager->isActivePage($firstPage)) { ?>
                <div class="pager-prev">
                    <a class="pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $pager->getPrevPage()], $params)) . $anchor ?>" title="<?= $this->i18n('list_previous') ?>">
                        <i class="fa-chevron-left far icon"></i>
                    </a>
                </div>
            <?php } ?>

            <div class="pager-page<?= $isPageActive($firstPage) ?>">
                <a class="pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $firstPage], $params)) . $anchor ?>">
                    <?= $firstPage ?>
                </a>
            </div>

            <?php if ($from > $firstPage + 1) : ?>
                <div class="pager-page empty">
                    <span>…</span>
                </div>
            <?php endif; ?>

            <?php for ($page = $from; $page <= $to; ++$page) : ?>
                <div class="pager-page<?= $isPageActive($page) ?>">
                    <a class="pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $page], $params)) . $anchor ?>">
                        <?= $page ?>
                    </a>
                </div>
            <?php endfor; ?>

            <?php if ($to < $lastPage - 1) : ?>
                <div class="pager-page">
                    <span>…</span>
                </div>
            <?php endif; ?>

            <div class="pager-page<?= $isPageActive($lastPage) ?>">
                <a class="pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $lastPage], $params)) . $anchor ?>">
                    <?= $lastPage ?>
                </a>
            </div>

            <?php if (!$pager->isActivePage($lastPage)) { ?>
                <div class="pager-next">
                    <a class="pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $pager->getNextPage()], $params)) . $anchor ?>" title="<?= $this->i18n('list_next') ?>">
                        <i class="fa-chevron-right far icon"></i>
                    </a>
                </div>
            <?php } ?>
        </div>

        <div class="pager-total">
            <?= sprintf('%s {{articles}}', $pager->getRowCount()) ?>
        </div>

        </nav>
    <?php endif;
