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

    <?php echo $content; ?>

    <footer>
        <hr>
        <p>Copyright &copy; 2020-<?php echo date_format(new DateTime(), 'Y'); ?> <a href="/contact.html">d1823</a>. All rights reserved.</p>
    </footer>
</body>
</html>
