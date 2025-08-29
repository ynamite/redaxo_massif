<?php

/** @var rex_pager $pager */
$pager = $this->getVar('pager', null);
/** @var rex_url_provider_interface $urlProvider */
$urlProvider = $this->getVar('urlprovider', null);
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
?>
<?php if ($pager->getRowCount() > $pager->getRowsPerPage()) : ?>
    <nav class="page-pager">
        <ul class="page-pager-list">

            <?php if (!$pager->isActivePage($firstPage)) { ?>
                <li class="page-pager-prev">
                    <a class="massif-pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $pager->getPrevPage()], $params)) . $anchor ?>" title="<?= $this->i18n('list_previous') ?>">
                        <i class="fa-chevron-left far icon"></i>
                    </a>
                </li>
            <?php } ?>

            <li class="page-pager-page<?= $pager->isActivePage($firstPage) ? ' active' : '' ?>">
                <a class="massif-pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $firstPage], $params)) . $anchor ?>">
                    <?= $firstPage ?>
                </a>
            </li>

            <?php if ($from > $firstPage + 1) : ?>
                <li>
                    <span>…</span>
                </li>
            <?php endif; ?>

            <?php for ($page = $from; $page <= $to; ++$page) : ?>
                <li class="page-pager-page<?= $pager->isActivePage($page) ? ' active' : '' ?>">
                    <a class="massif-pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $page], $params)) . $anchor ?>">
                        <?= $page ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($to < $lastPage - 1) : ?>
                <li class="page-pager-page">
                    <span>…</span>
                </li>
            <?php endif; ?>

            <li class="page-pager-page<?= $pager->isActivePage($lastPage) ? ' active' : '' ?>">
                <a class="massif-pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $lastPage], $params)) . $anchor ?>">
                    <?= $lastPage ?>
                </a>
            </li>

            <?php if (!$pager->isActivePage($lastPage)) { ?>
                <li class="page-pager-next">
                    <a class="massif-pager-a" href="<?= $urlProvider->getUrl(array_merge([$pager->getCursorName() => $pager->getNextPage()], $params)) . $anchor ?>" title="<?= $this->i18n('list_next') ?>">
                        <i class="fa-chevron-right far icon"></i>
                    </a>
                </li>
            <?php } ?>

        </ul>
    </nav>
<?php endif;
