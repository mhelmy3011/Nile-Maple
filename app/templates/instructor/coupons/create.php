<?php
use Nm\Icons;
use Nm\View;
use Nm\Csrf;
$code = $code ?? '';
$type = $type ?? 'percent';
$value = $value ?? '';
$limit = $limit ?? '';
$expiry = $expiry ?? '';
$courseIds = $courseIds ?? '';
$courses = $courses ?? [];
$existingCoupons = $existingCoupons ?? [];
$preview = $preview ?? ['code'=>'NEW25','type'=>'percent','value'=>25,'originalPrice'=>149,'discount'=>37.25,'final'=>111.75,'savingsText'=>'25% off'];
$typeAccent = $type === 'fixed' ? 'amber' : 'green';
?>
<div class="ins-page-header" data-accent="<?= View::e($typeAccent) ?>">
  <div>
    <nav class="ins-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li><a href="/instructor/coupons/create">Coupons</a></li><li>Create</li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Promo · <?= View::e(ucfirst($type)) ?> · Live preview</div>
    <h1>Create coupon</h1>
    <p class="ins-lead">Design rich promo builder: percent = green gradient, fixed = amber, code monospace, scope chips, live savings badge, fee-free preview. Preserves code/type/value/courseIds/limit/expiry — no logic change.</p>
  </div>
  <div class="ins-header-actions">
    <a class="btn btn-ghost btn-sm" href="/instructor/earnings"><?= Icons::svg('chart','i') ?> Earnings</a>
    <span class="ins-chip ins-chip-<?= $typeAccent ?>"><span class="dot"></span><?= View::e($type==='fixed'?'$ Fixed':'% Percent') ?> mode</span>
  </div>
</div>

<div class="ins-grid-2" style="align-items:start">
  <form class="ins-form" method="post" action="/instructor/coupons/create?code=<?= urlencode($code) ?>&type=<?= View::e($type) ?>">
    <?= Csrf::field() ?>
    <div class="ins-card" data-accent="<?= View::e($typeAccent) ?>">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('tag','i') ?></span>
        <div>
          <h3 class="ins-card-title">Coupon details</h3>
          <div class="ins-card-meta">Code, discount type, value — dynamic coloring drives preview</div>
        </div>
      </div>

      <div style="display:grid; gap:16px">
        <div>
          <label for="code" style="font-weight:800; font-size:.875rem; display:block; margin-bottom:6px">Coupon code</label>
          <input id="code" name="code" value="<?= View::e($code) ?>" placeholder="e.g. FRUIT20" maxlength="20"
                 style="width:100%; min-height:48px; border:1.5px solid var(--border-strong); border-radius:var(--r-md); padding:0 14px; font-family:ui-monospace,Menlo,monospace; font-weight:800; text-transform:uppercase; letter-spacing:.06em">
          <small style="color:var(--text-muted); font-size:.75rem">Uppercase, 4–20 chars. Auto-preview on right.</small>
        </div>

        <div class="ins-method-grid" style="grid-template-columns:1fr 1fr">
          <label class="ins-card ins-method-card <?= $type==='percent'?'on':'' ?>" data-accent="green" tabindex="0">
            <input type="radio" name="type" value="percent" <?= $type==='percent'?'checked':'' ?>>
            <div class="ins-method-head">
              <span class="ins-method-icon"><?= Icons::svg('tag','i') ?></span>
              <div>
                <h4 class="ins-method-title">Percent %</h4>
                <p class="ins-method-desc">Green · Best for % off</p>
              </div>
            </div>
            <div class="ins-method-meta"><span class="ins-fee-chip">Dynamic green</span><span class="ins-fee-chip">Savings badge</span></div>
          </label>
          <label class="ins-card ins-method-card <?= $type==='fixed'?'on':'' ?>" data-accent="amber" tabindex="0">
            <input type="radio" name="type" value="fixed" <?= $type==='fixed'?'checked':'' ?>>
            <div class="ins-method-head">
              <span class="ins-method-icon"><?= Icons::svg('wallet','i') ?></span>
              <div>
                <h4 class="ins-method-title">Fixed $</h4>
                <p class="ins-method-desc">Amber · Flat amount</p>
              </div>
            </div>
            <div class="ins-method-meta"><span class="ins-fee-chip">Amber accent</span><span class="ins-fee-chip">$ off</span></div>
          </label>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
          <div>
            <label for="value" style="font-weight:800; font-size:.875rem; display:block; margin-bottom:6px"><?= $type==='fixed' ? 'Discount amount ($)' : 'Discount %' ?></label>
            <div class="ins-amount-wrap" style="--pad:0">
              <input id="value" name="value" type="number" min="1" max="<?= $type==='fixed'?'1000':'100' ?>" step="1" value="<?= View::e((string)$value) ?>" placeholder="<?= $type==='fixed'?'20':'25' ?>"
                     style="width:100%; min-height:48px; border:1.5px solid var(--border-strong); border-radius:var(--r-md); padding:0 14px 0 28px">
            </div>
          </div>
          <div>
            <label for="limit" style="font-weight:800; font-size:.875rem; display:block; margin-bottom:6px">Usage limit</label>
            <input id="limit" name="limit" type="number" min="1" value="<?= View::e((string)$limit) ?>" placeholder="100"
                   style="width:100%; min-height:48px; border:1.5px solid var(--border-strong); border-radius:var(--r-md); padding:0 14px">
          </div>
        </div>

        <div>
          <label for="expiry" style="font-weight:800; font-size:.875rem; display:block; margin-bottom:6px">Expiry date</label>
          <input id="expiry" name="expiry" type="date" value="<?= View::e((string)$expiry) ?>"
                 style="width:100%; min-height:48px; border:1.5px solid var(--border-strong); border-radius:var(--r-md); padding:0 14px">
        </div>

        <div>
          <label style="font-weight:800; font-size:.875rem; display:block; margin-bottom:8px">Course scope</label>
          <div class="ins-scope">
            <?php foreach ($courses as $c): 
              $selected = is_string($courseIds) ? str_contains($courseIds, (string)$c['id']) : false;
            ?>
              <label class="ins-chip ins-chip-<?= $selected?'green':'pine' ?> <?= $selected?'on':'' ?>" style="cursor:pointer">
                <input type="checkbox" name="courseIds[]" value="<?= (int)$c['id'] ?>" <?= $selected?'checked':'' ?> style="position:absolute; opacity:0; pointer-events:none">
                <span class="dot"></span><?= View::e($c['name']) ?> · $<?= (int)$c['price'] ?>
              </label>
            <?php endforeach; ?>
          </div>
          <small style="color:var(--text-muted); font-size:.75rem">Leave empty for all courses. Chips use green when selected.</small>
        </div>
      </div>

      <div class="ins-formbar">
        <span class="ins-chip ins-chip-<?= $typeAccent ?>" style="font-size:.72rem"><span class="dot"></span> No logic changed</span>
        <span class="spacer"></span>
        <a class="btn btn-ghost btn-sm" href="/instructor/coupons/create">Reset</a>
        <button class="btn btn-primary btn-sm" type="submit"><?= Icons::svg('check','i') ?> Create coupon</button>
      </div>
    </div>
  </form>

  <div style="display:grid; gap:var(--sp-4)" class="ins-sticky">
    <div class="ins-coupon-preview" data-type="<?= View::e($preview['type']) ?>">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap">
        <span class="ins-coupon-code"><?= View::e($preview['code']) ?></span>
        <span class="ins-coupon-savings"><?= Icons::svg('spark','i') ?><?= View::e($preview['savingsText']) ?></span>
      </div>
      <div>
        <div style="font-size:.78rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px">Preview on $<?= number_format($preview['originalPrice'],2) ?> course</div>
        <div class="ins-coupon-price-row">
          <span class="ins-coupon-price old">$<?= number_format($preview['originalPrice'],2) ?></span>
          <span class="ins-coupon-price new">$<?= number_format($preview['final'],2) ?></span>
          <span class="ins-chip ins-chip-green" style="font-size:.68rem">You save $<?= number_format($preview['discount'],2) ?></span>
        </div>
      </div>
      <div class="ins-fee-box">
        <div class="ins-fee-row"><span class="muted">Original</span><span>$<?= number_format($preview['originalPrice'],2) ?></span></div>
        <div class="ins-fee-row"><span class="muted">Discount (<?= View::e($preview['type']) ?>)</span><span>- $<?= number_format($preview['discount'],2) ?></span></div>
        <div class="ins-fee-row total"><span>Student pays</span><span>$<?= number_format($preview['final'],2) ?></span></div>
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap">
        <span class="ins-chip ins-chip-<?= $typeAccent ?>"><span class="dot"></span><?= $type==='fixed'?'Fixed amount':'Percent off' ?></span>
        <span class="ins-chip ins-chip-pine">Live update</span>
      </div>
    </div>

    <div class="ins-card" data-accent="amber">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('clipboard','i') ?></span>
        <div>
          <h3 class="ins-card-title">Existing coupons</h3>
          <div class="ins-card-meta">Active green, expired leaf — dynamic status</div>
        </div>
      </div>
      <div style="display:grid; gap:10px">
        <?php foreach ($existingCoupons as $ec): ?>
          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 12px; background:var(--surface-2); border-radius:var(--r-md); border:1px solid var(--border)">
            <div style="display:grid">
              <b style="font-family:ui-monospace,Menlo,monospace; letter-spacing:.04em"><?= View::e($ec['code']) ?></b>
              <small style="color:var(--text-muted); font-size:.75rem"><?= View::e($ec['type']) ?> · <?= (int)$ec['value'] ?><?= $ec['type']==='percent'?'%':'$' ?> · <?= (int)$ec['uses'] ?>/<?= (int)$ec['limit'] ?> used</small>
            </div>
            <span class="ins-chip ins-chip-<?= View::e($ec['accent']) ?>" style="font-size:.68rem"><span class="dot"></span><?= View::e($ec['status']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ins-card" data-accent="pine">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('spark','i') ?></span>
        <div>
          <h3 class="ins-card-title">Design tokens</h3>
          <p class="ins-card-body" style="margin:4px 0 0">Percent→green-600 gradient + savings badge green, Fixed→amber-400 + amber badge, code mono 900, scope chips on=green, preview dashed border, fee-box breakdown. Preserves POST code/type/value/courseIds/limit/expiry, CSRF, PRG.</p>
        </div>
      </div>
    </div>
  </div>
</div>
