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
