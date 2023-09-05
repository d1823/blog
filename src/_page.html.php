<section class="page">
    <div class="page__header">
        <?php if (isset($creation_date)): ?><div class="header__meta"><a href="/">&laquo; back</a> &mdash; from <?= e($creation_date->format('d/m/Y')) ?><?php if ($update_date ?? false): ?>, updated <?= e($update_date->format('d/m/Y')) ?><?php endif; ?><?php if (!empty($tags)): ?>, tags:
                <?php foreach($tags as $index => $tag): ?>
                    <a href="<?= e($tag->url) ?>"><?= e($tag->label) ?></a><?php echo ($index < count($tags) - 1) ? ', ' : ''; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div><?php endif; ?>
        <h1 class="header__title"><?= e($title) ?></h1>
    </div>

    <div class="page__content">
        <?= $content ?>
    </div>
</section>
