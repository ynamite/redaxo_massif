<br />
<br />
––
<br />
<br />
<b>{{address_firma}}</b><br />
{{address_strasse}}<br />
{{address_plz}} {{address_ort}}<br />
<br />
<?php
$recipient = $this->getVar('recipient', '');
if ($recipient) echo $recipient;
else echo '{{address_email_formatted}}'; ?>
<br />
<?= \rex_yrewrite::getCurrentDomain() ?>