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

            <small>
                from <time datetime="<?= $article->creation_time ?>"><?= $article->creation_human_time ?></time>
            </small><?php if ($article->update_time): ?><small>
                , modified <time datetime="<?= $article->update_time ?>"><?= $article->update_human_time ?></time>
                </small>
            <?php endif; ?>
        </header>

        <section>
            <?= $article->content ?>
        </section>
    </article>
<?php endforeach; ?>
