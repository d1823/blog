<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?= $page_title ?></title>
    <meta name="description" content="<?= $page_description ?>">
    <base href="<?= $page_url ?>">
    <meta name="color-scheme" content="dark light">

    <style>
        <?= $styles ?>
    </style>

    <link rel="icon" type="image/png" href="data:image/png;base64,iVBORw0KGgo=">
    <link rel="alternate" type="application/rss+xml" href="<?= $feed ?>" title="<?= $page_title ?>">
</head>
<body>
    <header>
        <h1><?= $page_title ?></h1>

        <nav>
            <a href="/">articles</a> | <a href="/contact.html">about</a>
        </nav>
    </header>

    <hr>

    <?= $content ?>

    <footer>
        <hr>
        <p>Copyright &copy; 2020-<?php echo date_format(new DateTime(), 'Y'); ?> <a href="/contact.html">d1823</a>. This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License</a>.</p>
    </footer>
</body>
</html>
