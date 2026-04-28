<?php

use Ynamite\Massif\Media;

?>
REX_TEMPLATE[key="config"]

REX_TEMPLATE[key="html-head"]

<body class="<?= $pageClass ?> bg-body text-primary font-sans">

	REX_TEMPLATE[key="header"]

	<main role="main" class="flex flex-col flex-1 pt-[var(--shoulder)] page-margin section-p" id="content">
		<?php

		$articleContent = $this->getArticle(1);
		echo $articleContent;
		?>
	</main>

	REX_TEMPLATE[key="footer"]

	REX_TEMPLATE[key="html-scripts"]

</body>
<?php if (!rex::getProperty('req-with')) echo '</html>'; ?>