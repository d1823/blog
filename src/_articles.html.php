<nav id="articles">
    <?php if ($tag_label ?? false): ?>
        <h1 class="articles-header">Articles tagged "<?= e($tag_label) ?>":</h1>
    <?php endif; ?>

    <?php foreach($articles as $index => $article): ?>
    <h2 class="article-link">
        <span class="article-link__date"><?= e($article->creation_date->format('d/m/Y')) ?></span>

        <a href="<?= e($article->url) ?>">
            <?= e($article->title) ?>
        </a>
    </h2>
    <?php endforeach; ?>
</nav>
