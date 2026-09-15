<?php use Nm\Csrf; use Nm\Settings; use Nm\View; ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/seo/globals">
  <?= Csrf::field() ?>
  <?php foreach (cfg('langs') as $l): ?>
    <h2><?= strtoupper($l) ?></h2>
    <div class="field"><label>title template</label><input name="seo.title_tpl_<?= $l ?>" value="<?= View::e(Settings::get('seo.title_tpl', $l)) ?>" placeholder="{title} | Nile-Maple"></div>
    <div class="field"><label>description template</label><input name="seo.desc_tpl_<?= $l ?>" value="<?= View::e(Settings::get('seo.desc_tpl', $l)) ?>"></div>
    <div class="field"><label>robots default</label><input name="seo.robots_<?= $l ?>" value="<?= View::e(Settings::get('seo.robots', $l) ?: 'index, follow') ?>"></div>
  <?php endforeach; ?>
  <button class="btn btn-primary btn-sm">Save</button>
</form>
