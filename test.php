<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->IncludeComponent(
    'test:test',
    '',
    array(
        'SHOW_ONLY_ACTIVE' => false,
    ),
    null,
    array()
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
