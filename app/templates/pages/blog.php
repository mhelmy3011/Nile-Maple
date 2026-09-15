<?php use Nm\I18n; use Nm\View; ?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= I18n::t('blog.eyebrow') ?></p>
    <h1><?= I18n::t('blog.title') ?></h1>
    <p class="lead"><?= I18n::t('blog.lead') ?></p>
  </div>
</div>
<section class="section">
  <div class="container">
    <div class="post-list">
      <?php foreach ($posts as $post): ?><?= View::render('ui/card-post', ['post' => $post, 'lang' => $lang]) ?><?php endforeach; ?>
    </div>
    <?php if ($pageNo < $pages): ?><div class="loadmore-wrap"><a class="btn btn-ghost" href="page-<?= (int) $pageNo + 1 ?>/"><?= I18n::t('listing.more') ?></a></div><?php endif; ?>
  </div>
</section>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
