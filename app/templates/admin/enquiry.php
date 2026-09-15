<?php use Nm\Csrf; use Nm\View; if (!$r) { echo '<p>Not found</p>'; return; } ?>
<div class="adm-card">
  <h2><?= View::e($r['subject']) ?> <span class="hint">#<?= (int) $r['id'] ?> · <?= View::e($r['created_at']) ?> · <?= strtoupper(View::e($r['lang'])) ?></span></h2>
  <dl class="adm-dl">
    <dt>Name</dt><dd><?= View::e($r['full_name']) ?></dd>
    <dt>Email</dt><dd><a href="mailto:<?= View::e($r['email']) ?>?subject=<?= rawurlencode('Re: ' . $r['subject']) ?>"><?= View::e($r['email']) ?></a></dd>
    <dt>Phone</dt><dd><?= View::e((string) $r['phone']) ?></dd>
    <dt>Company / Country</dt><dd><?= View::e((string) $r['company']) ?> / <?= View::e((string) $r['country']) ?></dd>
    <dt>Product</dt><dd><?= View::e((string) $r['product_interest']) ?></dd>
    <dt>Message</dt><dd><?= nl2br(View::e($r['message'])) ?></dd>
    <dt>Consent</dt><dd><?= $r['consent'] ? 'yes' : 'no' ?> · mailed: <?= $r['mailed'] ? 'yes' : 'no' ?></dd>
  </dl>
  <form method="post">
    <?= Csrf::field() ?>
    <select name="status"><?php foreach (['new', 'read', 'replied', 'closed'] as $s): ?><option<?= $r['status'] === $s ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary btn-sm">Update</button>
  </form>
</div>
