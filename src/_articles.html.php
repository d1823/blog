<nav id="articles">
    <?php foreach($articles as $index => $article): ?>
    <div class="article-link">
        <span class="article-link__date"><?= $article->creation_date->format('d/m/Y') ?></span>

        <a href="<?= $article->url ?>">
            <?= $article->title ?>
        </a>
    </div>
    <?php endforeach; ?>
</nav>
