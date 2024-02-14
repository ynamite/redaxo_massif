<br />
<br />
––
<br />
<br />
<b>{{address_firma}}</b><br />
{{address_strasse}}<br />
{{address_plz}} {{address_ort}}<br />
<br />
<?php if ($this->params['recipient']) echo $this->params['recipient'];
else echo '{{address_e-mail_html}}'; ?>
<br />
<?= \rex_yrewrite::getCurrentDomain() ?>