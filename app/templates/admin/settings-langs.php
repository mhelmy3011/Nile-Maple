<?php use Nm\Csrf; ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/settings/languages">
  <?= Csrf::field() ?>
  <h2>Trade glossary (EN → AR → FR)</h2>
  <p class="hint">Used by translators & the workbench to keep terminology consistent.</p>
  <div class="field-row"><div class="field"><label>EN</label><input name="glossary[0][en]" placeholder="reefer container"></div>
  <div class="field"><label>AR</label><input name="glossary[0][ar]" placeholder="حاوية مبردة" dir="rtl"></div>
  <div class="field"><label>FR</label><input name="glossary[0][fr]" placeholder="conteneur reefer"></div></div>
  <button class="btn btn-primary btn-sm">Save glossary</button>
</form>
