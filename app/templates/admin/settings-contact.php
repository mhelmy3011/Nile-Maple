<?php use Nm\Csrf; use Nm\Settings; use Nm\View; ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/settings/contact">
  <?= Csrf::field() ?>
  <?php foreach (['contact.email' => 'email (header+footer, every page)', 'contact.phone' => 'phone (tel:)', 'contact.whatsapp' => 'whatsapp (wa.me)', 'social.instagram' => 'instagram URL', 'social.facebook' => 'facebook URL', 'hours' => 'working hours', 'address' => 'address (optional)', 'quote_promise' => 'response promise'] as $k => $label): ?>
    <div class="field"><label><?= $label ?></label><input name="<?= $k ?>" value="<?= View::e(Settings::get($k)) ?>"></div>
  <?php endforeach; ?>
  <?php foreach (cfg('langs') as $l): ?>
    <div class="field"><label>whatsapp prefill (<?= $l ?>)</label><input name="wa_prefill_<?= $l ?>" value="<?= View::e(Settings::get('wa.prefill', $l)) ?>"></div>
  <?php endforeach; ?>
  <button class="btn btn-primary btn-sm">Save & rebuild all pages</button>
</form>
