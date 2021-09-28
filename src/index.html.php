<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title><?= $page_title ?></title>
	<meta name="description" content="<?= $page_description ?>">
	<base href="<?= $page_url ?>" target="_blank">
	<meta name="color-scheme" content="dark light">

    <style>
        <?= $styles ?>
    </style>

	<link rel="alternate" type="application/rss+xml" href="<?= $feed ?>" title="<?= $page_title ?>">
</head>
<body>
	<header>
		<h1><?= $page_title ?></h1>

		<hr>
	</header>

    <?php if (empty($articles)): ?>
        <article>
            <section>
                No articles available yet.
            </section>
        </article>
    <?php endif; ?>

	<?php foreach($articles as $article): ?>
		<article id="<?= $article->id ?>">
			<header>
				<a href="#<?= $article->id ?>">
					<h2><?= $article->title ?></h2>
				</a>

				<small>from <time datetime="<?= $article->time ?>"><?= $article->human_time ?></time></small>
			</header>

			<section>
				<?= $article->content ?>
			</section>
		</article>
	<?php endforeach; ?>
</body>
</html>
