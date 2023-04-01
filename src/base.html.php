<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= $site_description ?>">

    <base href="<?= $site_url ?>">

    <title><?= $site_title ?></title>

    <style>
        <?= $styles ?>
    </style>

    <link
        href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAABhGlDQ1BJQ0MgcHJvZmlsZQAAKJF9kT1Iw0AcxV9TRZGKQzuoCGaoThZERRy1CkWoEGqFVh1MLv2CJg1Jiouj4Fpw8GOx6uDirKuDqyAIfoC4ujgpukiJ/0sKLWI8OO7Hu3uPu3eAUC8zzeoYBzTdNlOJuJjJropdrwhiAGEMIyQzy5iTpCR8x9c9Any9i/Es/3N/jl41ZzEgIBLPMsO0iTeIpzdtg/M+cYQVZZX4nHjMpAsSP3Jd8fiNc8FlgWdGzHRqnjhCLBbaWGljVjQ14iniqKrplC9kPFY5b3HWylXWvCd/YSinryxzneYQEljEEiSIUFBFCWXYiNGqk2IhRftxH/+g65fIpZCrBEaOBVSgQXb94H/wu1srPznhJYXiQOeL43yMAF27QKPmON/HjtM4AYLPwJXe8lfqwMwn6bWWFj0C+raBi+uWpuwBlztA/5Mhm7IrBWkK+TzwfkbflAXCt0DPmtdbcx+nD0CaukreAAeHwGiBstd93t3d3tu/Z5r9/QBS3nKamEF+kgAAAAZiS0dEAP0AcAA7rB2u5wAAAAlwSFlzAAAuIwAALiMBeKU/dgAAAAd0SU1FB+cDGRUMGX8XZQgAAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAAC/0lEQVR42u3by2sTQRwH8O/MbJPYpm9bU6qJEm1BPbSCgiDqTWxFvGrFQ/8AsQcv/gFa8KQHqYgHwYuIePAgilI8ScG2qPVRsI0varQNTdMkbZKdGQ8VtbabrM86ye93HTbZ+ex35wXL5vrLNEq4OEq8CIAACIAACIAACIAACIAACKA0y/rvbmjLIMTqrcu2ZQd7oJMXKAEEQAAEQAAEQADFCqBliQPITJGtBEU7ROA4eN02sIomMKscYAw6l4LOxKFiQ1DRK9DZ218CMF88AGLNeYhwF1iZf0kb81aDeavBq0JAsBP2+HXIiW6wXLw4AKz1NyBCB1xKeWBt6gJWNUPNvoQwHUAE+tx3/vubWbsXMlpu9iDIPJ2wwod/A2+H2a8AD/UAlq/AVKeh0x+hs7OA8IBXBADhLYLzAL4ZVuP2/LPcpyHIsdPQ2VuLrwv1QgT3mQ3A67vzPn0ZHYA9untpg3oOO3IQWtyF1bzH3DGA1bQ5pz6Xhv3qZP50RE4BMmsuAPcHnTs3OQzIgQKrwEeQsWcGJ8BX55yA6UF3W4FkxFAAVr/siu9rx1LD7gDSY4YC8I0AY84dy7hLAHLTZgIwXpXnsSpAjbpLgG0ogLZH8ukA8Bf5gYj+kH8Ks9rcJcmqMXcW0Pac8x+V73T3I76QwQAZ5/eXVbe7S0Blq7kAasZ5CuMNLnZ5rAmiod3gBCScB0JeuQ4i0FfgEOUimNfgMUDFbwLKdu5gyzGI4DWA/xBzaxdE+I75u0HYDyGnnkI0OsSYCVgbDsEKdkClooCcB8oqFs4DmMBK1B8/EZJvLhU+2xce8KogeG0LuL95xTr/VwB0+jLku3u/dm0uBTn5xGwAALAjRyAnH/9kdHKwR85ARs4B0GYDAEnYL/ZDvr+/sA8oNHgm3iI7fAIqcRZ67ipUfNzQQXBRnmOwxzogJ46CN3WB17aC+erBhAdaZoDsLFTiNdTUA6hYL4DkN5BoP3hN+J8AMPpkpsSLAAiAAAiAAAiAAAiAAAigROszfw/sXyvnr2MAAAAASUVORK5CYII="
        rel="icon" type="image/x-icon">
    <link rel="alternate" type="application/rss+xml" href="<?= $feed ?>" title="<?= $site_title ?>">
</head>
<body>

<header>
    <a href="/">
        <img id="logo"
             src="data:image/webp;base64,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"
             alt="d1823.pl logo">
    </a>

    <nav>
        <a href="/" <?= $page_url == '/' || str_starts_with($page_url, '/articles') ? 'class="link--current"' : '' ?>>Articles</a>
        <a href="/projects" <?= $page_url == '/projects' ? 'class="link--current"' : '' ?>>Projects</a>
        <a href="/about" <?= $page_url == '/about' ? 'class="link--current"' : '' ?>>About</a>
    </nav>
</header>

<?= $content ?>

<footer>
    Copyright &copy; 2020-<?php
    echo date_format(new DateTime(), 'Y'); ?> 1823.pl&nbsp;&mdash;&nbsp;<a
        href="https://creativecommons.org/licenses/by-nc-sa/4.0/">CC BY-NC-SA 4.0</a>
</footer>

</body>
</html>
