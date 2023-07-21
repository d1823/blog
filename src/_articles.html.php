<nav id="articles">
    <?php if ($tag_label ?? false): ?>
        <h1 class="articles-header">Articles tagged "<?= $tag_label ?>":</h1>
    <?php endif; ?>

    <?php foreach($articles as $index => $article): ?>
    <h2 class="article-link">
        <span class="article-link__date"><?= $article->creation_date->format('d/m/Y') ?></span>

        <a href="<?= $article->url ?>">
            <?= $article->title ?>
        </a>
    </h2>
    <?php endforeach; ?>
</nav>
