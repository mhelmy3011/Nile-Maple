<?php
use Nm\View;
/** @var array $products @var string $lang @var array|null $cat */
?>
<div class="product-grid">
<?php foreach ($products as $p): ?>
  <?= View::render('ui/card-product', ['p' => $p, 'lang' => $lang, 'cat' => $cat ?? null]) ?>
<?php endforeach; ?>
</div>
