<section class="page">
    <div class="page__header">
        <?php if (isset($creation_date)): ?><div class="header__meta">from <?= $creation_date->format('d/m/Y') ?><?php /*, tags: <a href="#">symfony</a>, <a href="#">doctrine</a>, <a href="#">entity manager</a> */ ?></div><?php endif; ?>
        <h1 class="header__title"><?= $title ?></h1>
    </div>

    <div class="page__content">
        <?= $content ?>
    </div>
</section>
