<section class="page">
    <div class="page__header">
        <?php if (isset($creation_date)): ?><div class="header__meta"><a href="/">&laquo; back</a> &mdash; from <?= $creation_date->format('d/m/Y') ?><?php if (!empty($tags)): ?>, tags:
                <?php foreach($tags as $index => $tag): ?>
                    <a href="<?= $tag->url ?>"><?= $tag->label ?></a><?php echo ($index < count($tags) - 1) ? ', ' : ''; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div><?php endif; ?>
        <h1 class="header__title"><?= $title ?></h1>
    </div>

    <div class="page__content">
        <?= $content ?>
    </div>
</section>
