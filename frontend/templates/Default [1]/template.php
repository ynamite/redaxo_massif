<?php

use Ynamite\Massif\Media;

?>
REX_TEMPLATE[key="config"]

REX_TEMPLATE[key="meta"]

<body class="<?= $pageClass ?> bg-body text-primary font-sans">

	REX_TEMPLATE[key="header"]

	REX_TEMPLATE[key="main"]

	REX_TEMPLATE[key="footer"]

</body>
<?php if (!rex::getProperty('req-with')) echo '</html>'; ?>
