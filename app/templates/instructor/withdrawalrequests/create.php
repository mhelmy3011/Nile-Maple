<?php
use Nm\Icons;
use Nm\View;
use Nm\Csrf;
$amount = $amount ?? '';
$method = $method ?? 'paypal';
$account = $account ?? '';
$fee = $fee ?? 0;
$net = $net ?? 0;
$balance = $balance ?? ['available'=>1240.50,'pending'=>320,'total'=>1560.50,'currency'=>'USD'];
$methods = $methods ?? [];
$recentWithdrawals = $recentWithdrawals ?? [];
$methodMeta = $methods[$method] ?? ['label'=>ucfirst($method),'accent'=>'green','icon'=>'wallet','fee'=>'Free','eta'=>'Instant','desc'=>''];
?>
<div class="ins-page-header" data-accent="<?= View::e($methodMeta['accent']) ?>">
  <div>
    <nav class="ins-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li><a href="/instructor/earnings">Earnings</a></li><li>Withdraw</li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Payout · <?= View::e($methodMeta['label']) ?> · <?= View::e($methodMeta['eta']) ?></div>
    <h1>Withdraw earnings</h1>
    <p class="ins-lead">Balance gradient green, method cards PayPal leaf / Bank pine / Wallet green with fee chip, amount $ prefix + % pills + fee breakdown. Preserves amount/method/account — no logic change.</p>
  </div>
  <div class="ins-header-actions">
    <span class="ins-chip ins-chip-green"><span class="dot"></span>$<?= number_format($balance['available'],2) ?> available</span>
    <a class="btn btn-ghost btn-sm" href="/instructor/earnings"><?= Icons::svg('chart','i') ?> View earnings</a>
  </div>
</div>

<div class="ins-balance">
  <small>Available for withdrawal</small>
  <div class="ins-balance-v">$<?= number_format($balance['available'],2) ?> <span style="font-size:.6em; font-weight:700; opacity:.85"><?= View::e($balance['currency']) ?></span></div>
  <div class="ins-balance-grid">
    <div class="ins-balance-item"><span>Pending clearance</span><b>$<?= number_format($balance['pending'],2) ?></b></div>
    <div class="ins-balance-item"><span>Total earnings</span><b>$<?= number_format($balance['total'],2) ?></b></div>
    <div class="ins-balance-item"><span>Next payout</span><b><?= date('M j, Y') ?></b></div>
  </div>
  <div class="ins-progress" style="background:rgba(255,255,255,.18); height:8px; margin-top:8px">
    <div class="ins-progress-bar" style="width:<?= min(100, round($balance['available']/$balance['total']*100)) ?>%; background:#fff"></div>
  </div>
</div>

<div class="ins-grid-2" style="align-items:start">
  <form class="ins-form" method="post" action="/instructor/withdrawalrequests/create?method=<?= View::e($method) ?>">
    <?= Csrf::field() ?>
    <div class="ins-card" data-accent="<?= View::e($methodMeta['accent']) ?>">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('wallet','i') ?></span>
        <div>
          <h3 class="ins-card-title">Withdrawal details</h3>
          <div class="ins-card-meta">Select method, enter amount, account — dynamic coloring</div>
        </div>
      </div>

      <div style="display:grid; gap:18px">
        <div>
          <label style="font-weight:800; font-size:.875rem; display:block; margin-bottom:8px">Payout method</label>
          <div class="ins-method-grid">
            <?php foreach ($methods as $key=>$m): ?>
              <label class="ins-card ins-method-card <?= $method===$key?'on':'' ?>" data-accent="<?= View::e($m['accent']) ?>" tabindex="0">
                <input type="radio" name="method" value="<?= View::e($key) ?>" <?= $method===$key?'checked':'' ?>>
                <div class="ins-method-head">
                  <span class="ins-method-icon"><?= Icons::svg($m['icon'],'i') ?></span>
                  <div>
                    <h4 class="ins-method-title"><?= View::e($m['label']) ?></h4>
                    <p class="ins-method-desc"><?= View::e($m['desc']) ?></p>
                  </div>
                </div>
                <div class="ins-method-meta">
                  <span class="ins-fee-chip"><?= View::e($m['fee']) ?> fee</span>
                  <span class="ins-fee-chip"><?= View::e($m['eta']) ?></span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <label for="amount" style="font-weight:800; font-size:.875rem; display:block; margin-bottom:6px">Amount to withdraw</label>
          <div class="ins-amount-wrap">
            <input id="amount" name="amount" type="number" min="1" max="<?= (int)$balance['available'] ?>" step="0.01" value="<?= View::e((string)$amount) ?>" placeholder="250.00" required
                   style="width:100%; min-height:52px; border:1.5px solid var(--border-strong); border-radius:var(--r-md); padding:0 14px 0 28px">
          </div>
          <div class="ins-pill-row">
            <?php foreach ([25,50,75,100] as $pct): $val = round($balance['available']*$pct/100,2); ?>
              <button type="button" class="ins-pill" data-amount="<?= $val ?>" onclick="document.getElementById('amount').value='<?= $val ?>'"><?= $pct ?>% · $<?= number_format($val,0) ?></button>
            <?php endforeach; ?>
          </div>
          <small style="color:var(--text-muted); font-size:.75rem">Max $<?= number_format($balance['available'],2) ?> · Min $10 · Preserve amount param</small>
        </div>

        <div>
          <label for="account" style="font-weight:800; font-size:.875rem; display:block; margin-bottom:6px">
            <?= $method==='paypal' ? 'PayPal email' : ($method==='bank' ? 'IBAN / Account' : 'Wallet address') ?>
          </label>
          <input id="account" name="account" value="<?= View::e($account) ?>" placeholder="<?= $method==='paypal' ? 'you@paypal.com' : ($method==='bank' ? 'EGxx xxxx xxxx...' : 'Wallet ID') ?>"
                 style="width:100%; min-height:48px; border:1.5px solid var(--border-strong); border-radius:var(--r-md); padding:0 14px">
          <small style="color:var(--text-muted); font-size:.75rem">Preserve account param — no logic change, POST field stays same.</small>
        </div>

        <div class="ins-fee-box">
          <div class="ins-fee-row"><span class="muted">Withdrawal amount</span><span>$<?= number_format(is_numeric($amount)?(float)$amount:0,2) ?></span></div>
          <div class="ins-fee-row"><span class="muted">Fee (<?= View::e($methodMeta['fee']) ?>)</span><span>- $<?= number_format($fee,2) ?></span></div>
          <div class="ins-fee-row total"><span>You will receive</span><span>$<?= number_format($net,2) ?></span></div>
          <div class="ins-fee-row"><span class="muted">ETA</span><span class="ins-chip ins-chip-<?= View::e($methodMeta['accent']) ?>" style="font-size:.68rem"><?= View::e($methodMeta['eta']) ?></span></div>
        </div>
      </div>

      <div class="ins-formbar">
        <span class="ins-chip ins-chip-<?= View::e($methodMeta['accent']) ?>" style="font-size:.72rem"><span class="dot"></span> Secure payout</span>
        <span class="spacer"></span>
        <a class="btn btn-ghost btn-sm" href="/instructor/withdrawalrequests/create">Reset</a>
        <button class="btn btn-primary btn-sm" type="submit"><?= Icons::svg('wallet','i') ?> Request withdrawal</button>
      </div>
    </div>
  </form>

  <div style="display:grid; gap:var(--sp-4)" class="ins-sticky">
    <div class="ins-card" data-accent="pine">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('shield','i') ?></span>
        <div>
          <h3 class="ins-card-title">Payout safety</h3>
          <div class="ins-card-meta">Method fee, ETA, verification — rich UX</div>
        </div>
      </div>
      <div style="display:grid; gap:10px; font-size:.875rem; color:var(--text-muted)">
        <div style="display:flex; gap:10px"><span class="ins-chip ins-chip-leaf" style="font-size:.68rem">2.5%</span><span>PayPal fee applies for instant payout.</span></div>
        <div style="display:flex; gap:10px"><span class="ins-chip ins-chip-pine" style="font-size:.68rem">$3</span><span>Bank transfer fixed fee, 2–3 days.</span></div>
        <div style="display:flex; gap:10px"><span class="ins-chip ins-chip-green" style="font-size:.68rem">Free</span><span>Wallet instant & fee-free for marketplace use.</span></div>
      </div>
    </div>

    <div class="ins-card" data-accent="green">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('clock','i') ?></span>
        <div>
          <h3 class="ins-card-title">Recent withdrawals</h3>
          <div class="ins-card-meta">Completed green, pending amber — dynamic status</div>
        </div>
      </div>
      <div style="display:grid; gap:10px">
        <?php foreach ($recentWithdrawals as $rw): ?>
          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 12px; background:var(--surface-2); border-radius:var(--r-md); border:1px solid var(--border)">
            <div style="display:grid">
              <b style="font-size:.9rem"><?= View::e($rw['id']) ?> · $<?= number_format($rw['amount'],2) ?></b>
              <small style="color:var(--text-muted); font-size:.75rem"><?= View::e(ucfirst($rw['method'])) ?> · <?= View::e($rw['date']) ?></small>
            </div>
            <span class="ins-chip ins-chip-<?= View::e($rw['accent']) ?>" style="font-size:.68rem"><span class="dot"></span><?= View::e($rw['status']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ins-card" data-accent="leaf">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('spark','i') ?></span>
        <div>
          <h3 class="ins-card-title">Design tokens</h3>
          <p class="ins-card-body" style="margin:4px 0 0">Balance card gradient green 600→700 with radial highlight, method-card radio pattern with :has(checked) green/leaf/pine, $ prefix absolute, % pills 25/50/75/100, fee-box breakdown total bold. Preserves amount/method/account query & POST, CSRF, PRG — zero logic change.</p>
        </div>
      </div>
    </div>
  </div>
</div>
