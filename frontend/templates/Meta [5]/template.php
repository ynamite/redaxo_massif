<?php

use Ynamite\MassifSettings\Seo;

?>

<!--
      __    __  ______  ______  ______  __  ______
     /\ "-./  \/\  __ \/\  ___\/\  ___\/\ \/\  ___\
     \ \ \-./\ \ \  __ \ \___  \ \___  \ \ \ \  __\
      \ \_\ \ \_\ \_\ \_\/\_____\/\_____\ \_\ \_\/
       \/_/  \/_/\/_/\/_/\/_____/\/_____/\/_/\/_/

                Website by www.massif.ch

-->

<!doctype html>
<html class="<?= $isMobileOrTablet ?>" lang="<?= $lang->getCode() ?>">

<head>

    <meta charset="utf-8" />

    <?= Seo::getTags() ?>

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#7a7256">
    <meta name="msapplication-TileColor" content="#ebeae6">
    <meta name="theme-color" content="#ebeae6">
    <link rel="icon" type="image/ico" href="/favicon.ico">
    <meta name="msapplication-config" content="browserconfig.xml" />

    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="generator" content="REDAXO CMS" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <style>
        @view-transition {
            navigation: auto;
        }
    </style>

    REX_VITE

</head>