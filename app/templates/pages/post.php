<?php
use Nm\I18n; use Nm\Markdown; use Nm\Media; use Nm\View;
$jsBundles = ['base', 'post'];
?>
<div class="progress-bar" aria-hidden="true"></div>
<div class="page-head page-head-compact">
  <div class="container container-narrow">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <h1><?= View::e($post['title']) ?></h1>
    <p class="post-meta"><time datetime="<?= View::e(substr((string) $post['published_at'], 0, 10)) ?>"><?= I18n::date((string) $post['published_at']) ?></time> · <?= (int) $post['reading_minutes'] ?> <?= I18n::t('blog.min') ?><?php if ($post['author']): ?> · <?= View::e($post['author']) ?><?php endif; ?></p>
  </div>
</div>
<section class="section section-first">
  <div class="container container-narrow">
    <?php if ($post['cover_media_id']): ?><div class="post-cover"><?= Media::img((int) $post['cover_media_id'], $post['title'], 'hero', '100vw') ?></div><?php endif; ?>
    <div class="prose post-body"><?= Markdown::render($post['body']) ?></div>
    <div class="share-row">
      <span><?= I18n::t('blog.share') ?>:</span>
      <button class="btn btn-ghost btn-sm" type="button" data-share><?= I18n::t('blog.share.native') ?></button>
      <a class="btn btn-ghost btn-sm" href="<?= \Nm\Util::waLink($post['title'] . ' ' . \Nm\Seo::urlFor($lang, 'blog/' . $post['slug'])) ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a class="btn btn-ghost btn-sm" href="mailto:?subject=<?= rawurlencode($post['title']) ?>&body=<?= rawurlencode(\Nm\Seo::urlFor($lang, 'blog/' . $post['slug'])) ?>">Email</a>
    </div>
  </div>
</section>
<?php if ($related): ?>
<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => I18n::t('blog.related')]) ?>
    <div class="post-list"><?php foreach ($related as $r): ?><?= View::render('ui/card-post', ['post' => $r, 'lang' => $lang]) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
