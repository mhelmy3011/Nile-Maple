<?php
use Nm\I18n; use Nm\Media; use Nm\View;
?>
<a class="card-post" href="/<?= $lang ?>/blog/<?= View::e($post['slug']) ?>/">
  <span class="cpo-media"><?= $post['cover_media_id'] ? Media::img((int) $post['cover_media_id'], $post['title']) : '' ?></span>
  <span class="cpo-body">
    <span class="cpo-meta"><time datetime="<?= View::e(substr((string) $post['published_at'], 0, 10)) ?>"><?= I18n::date((string) $post['published_at']) ?></time> · <?= (int) $post['reading_minutes'] ?> <?= I18n::t('blog.min') ?></span>
    <h3><?= View::e($post['title']) ?></h3>
    <p><?= View::e($post['excerpt']) ?></p>
    <span class="cs-more"><?= I18n::t('blog.read') ?>
      <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
  </span>
</a>
