<?php
use Nm\Icons;
use Nm\View;
$from = $from ?? date('Y-m-01');
$to = $to ?? date('Y-m-d');
$courseId = $courseId ?? 0;
$page = $page ?? 1;
$total = $total ?? 0;
$perPage = $perPage ?? 10;
$courses = $courses ?? [];
$monthly = $monthly ?? [];
$maxAmt = $maxAmt ?? 1;
$breakdown = $breakdown ?? [];
$transactions = $transactions ?? [];
$kpis = $kpis ?? [];
?>
<div class="ins-page-header" data-accent="green">
  <div>
    <nav class="ins-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li>Earnings</li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Finance · Monthly chart · Live breakdown</div>
    <h1>Earnings overview</h1>
    <p class="ins-lead">KPI total/month/pending/available/withdrawn, CSS chart-bars green/amber, breakdown course cards with share bar, transactions table collapse data-label. Preserve from/to/courseId/page.</p>
  </div>
  <div class="ins-header-actions">
    <a class="btn btn-ghost btn-sm" href="/instructor/withdrawalrequests/create"><?= Icons::svg('wallet','i') ?> Withdraw</a>
    <a class="btn btn-primary btn-sm" href="/instructor/coupons/create"><?= Icons::svg('tag','i') ?> New coupon</a>
  </div>
</div>

<div class="ins-kpi-strip">
  <?php foreach ($kpis as $k): ?>
    <div class="ins-kpi" data-accent="<?= View::e($k['accent']) ?>" tabindex="0">
      <div class="ins-kpi-head">
        <span class="ins-kpi-icon"><?= Icons::svg($k['icon'],'i') ?></span>
        <span class="ins-chip ins-chip-<?= $k['accent'] ?>" style="font-size:.68rem"><?= View::e($k['trend']) ?></span>
      </div>
      <div class="ins-kpi-v"><?= View::e((string)$k['v']) ?></div>
      <div class="ins-kpi-l"><?= View::e($k['l']) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<form class="ins-filterbar" method="get" role="search">
  <input type="date" name="from" value="<?= View::e($from) ?>" aria-label="From date" style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:var(--r-pill); padding:0 14px; font:inherit; background:var(--surface)">
  <input type="date" name="to" value="<?= View::e($to) ?>" aria-label="To date" style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:var(--r-pill); padding:0 14px; font:inherit; background:var(--surface)">
  <select name="courseId" aria-label="Filter by course">
    <option value="0">All courses</option>
    <?php foreach ($courses as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id']===(int)$courseId?'selected':'' ?>><?= View::e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-ghost btn-sm" type="submit">Apply</button>
  <span class="ins-count"><?= (int)$total ?> transactions</span>
</form>

<div class="ins-grid-2" style="grid-template-columns: 1.6fr 1fr">
  <div class="ins-card" data-accent="green">
    <div class="ins-card-head">
      <span class="ins-card-icon"><?= Icons::svg('chart','i') ?></span>
      <div>
        <h3 class="ins-card-title">Monthly earnings · CSS chart-bars</h3>
        <div class="ins-card-meta">Green = income, amber = dip, pine = stable — dynamic coloring, hover lift, max <?= (int)$maxAmt ?></div>
      </div>
      <span class="ins-chip ins-chip-green" style="margin-inline-start:auto; font-size:.68rem"><span class="dot"></span>2026 YTD</span>
    </div>
    <div class="ins-chart">
      <div class="ins-chart-bars" role="img" aria-label="Monthly earnings chart">
        <?php foreach ($monthly as $m): 
          $h = $maxAmt > 0 ? round($m['amount'] / $maxAmt * 100) : 0;
        ?>
          <div class="ins-chart-bar" data-accent="<?= View::e($m['accent']) ?>">
            <div class="ins-chart-bar-track">
              <div class="ins-chart-bar-fill" style="height:<?= $h ?>%">
                <span class="ins-chart-bar-v">$<?= (int)$m['amount'] ?></span>
              </div>
            </div>
            <span class="ins-chart-bar-l"><?= View::e($m['month']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="ins-chart-legend">
        <span class="ins-chip ins-chip-green"><span class="dot"></span>Income</span>
        <span class="ins-chip ins-chip-amber"><span class="dot"></span>Dip</span>
        <span class="ins-chip ins-chip-pine"><span class="dot"></span>Stable</span>
      </div>
    </div>
  </div>

  <div class="ins-card" data-accent="pine">
    <div class="ins-card-head">
      <span class="ins-card-icon"><?= Icons::svg('wallet','i') ?></span>
      <div>
        <h3 class="ins-card-title">Balance snapshot</h3>
        <div class="ins-card-meta">Available, pending, withdrawn — gradient accent</div>
      </div>
    </div>
    <div class="ins-breakdown" style="grid-template-columns:1fr">
      <div class="ins-card ins-break-card" data-accent="green" style="padding:var(--sp-4)">
        <div style="display:flex; justify-content:space-between; align-items:center">
          <small style="font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); font-size:.68rem">Available</small>
          <span class="ins-chip ins-chip-green" style="font-size:.68rem">Withdraw</span>
        </div>
        <div style="font-size:1.6rem; font-weight:900; margin:6px 0">$1,240.50</div>
        <div class="ins-break-share"><div class="ins-break-share-bar" style="width:78%"></div></div>
      </div>
      <div class="ins-card ins-break-card" data-accent="amber" style="padding:var(--sp-4)">
        <div style="display:flex; justify-content:space-between; align-items:center">
          <small style="font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); font-size:.68rem">Pending</small>
          <span class="ins-chip ins-chip-amber" style="font-size:.68rem">3 tx</span>
        </div>
        <div style="font-size:1.2rem; font-weight:800; margin:6px 0">$320.00</div>
        <div class="ins-break-share"><div class="ins-break-share-bar" style="width:22%"></div></div>
      </div>
      <div class="ins-card ins-break-card" data-accent="pine" style="padding:var(--sp-4)">
        <div style="display:flex; justify-content:space-between; align-items:center">
          <small style="font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); font-size:.68rem">Withdrawn</small>
          <span class="ins-chip ins-chip-pine" style="font-size:.68rem">Ok</span>
        </div>
        <div style="font-size:1.2rem; font-weight:800; margin:6px 0">$875.00</div>
        <div class="ins-break-share"><div class="ins-break-share-bar" style="width:60%"></div></div>
      </div>
    </div>
  </div>
</div>

<div class="ins-card" data-accent="green">
  <div class="ins-card-head">
    <span class="ins-card-icon"><?= Icons::svg('tag','i') ?></span>
    <div>
      <h3 class="ins-card-title">Earnings by course · Breakdown cards</h3>
      <div class="ins-card-meta">Course category colors: Fresh Fruits green, Citrus amber, Cold-Chain pine, Packaging leaf — share bar dynamic</div>
    </div>
  </div>
  <div class="ins-breakdown">
    <?php foreach ($breakdown as $b): ?>
      <div class="ins-card ins-break-card" data-accent="<?= View::e($b['accent']) ?>">
        <div style="display:flex; justify-content:space-between; align-items:start; gap:8px">
          <div style="flex:1; min-width:0">
            <h4 style="margin:0; font-size:.95rem; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis"><?= View::e($b['course']) ?></h4>
            <small style="color:var(--text-muted); font-size:.75rem"><?= (int)$b['enrollments'] ?> enrollments · <?= (int)$b['share'] ?>% share</small>
          </div>
          <span class="ins-chip ins-chip-<?= View::e($b['accent']) ?>" style="font-size:.68rem">$<?= number_format($b['earnings'],2) ?></span>
        </div>
        <div class="ins-break-share"><div class="ins-break-share-bar" style="width:<?= (int)$b['share'] ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="ins-card" data-accent="amber" style="padding:0; overflow:hidden">
  <div class="ins-card-head" style="padding:var(--sp-5) var(--sp-5) 0">
    <span class="ins-card-icon"><?= Icons::svg('doc','i') ?></span>
    <div>
      <h3 class="ins-card-title">Transactions · Responsive table → cards</h3>
      <div class="ins-card-meta">Paid green, pending amber, refunded leaf, withdrawn pine — data-label collapse on mobile</div>
    </div>
    <span class="ins-count" style="margin-inline-start:auto"><?= count($transactions) ?> rows</span>
  </div>
  <div style="overflow:auto">
    <table class="adm-table ins-table" style="width:100%">
      <thead>
        <tr><th>ID</th><th>Date</th><th>Course</th><th>Student</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $t): ?>
          <tr>
            <td data-label="ID"><span class="ins-chip ins-chip-mono ins-chip-pine" style="font-size:.72rem"><?= View::e($t['id']) ?></span></td>
            <td data-label="Date"><?= View::e($t['date']) ?></td>
            <td data-label="Course"><span class="ins-chip ins-chip-pine" style="font-size:.72rem"><?= View::e($t['course']) ?></span></td>
            <td data-label="Student"><?= View::e($t['student']) ?></td>
            <td data-label="Amount" style="font-weight:800">$<?= number_format($t['amount'],2) ?></td>
            <td data-label="Fee" style="color:var(--text-muted)">-$<?= number_format($t['fee'],2) ?></td>
            <td data-label="Net" style="font-weight:800; color:var(--green-700)">$<?= number_format($t['net'],2) ?></td>
            <td data-label="Status"><span class="ins-chip ins-chip-<?= View::e($t['accent']) ?>"><span class="dot"></span><?= View::e($t['status']) ?></span></td>
            <td data-label="Actions" class="row-actions"><a class="btn btn-ghost btn-sm" href="/instructor/earnings?courseId=<?= (int)$courseId ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($total > $perPage): 
    $pages = (int)ceil($total / $perPage);
  ?>
    <div class="ins-pagination" role="navigation" aria-label="Pagination">
      <?php for ($p=1; $p<=$pages; $p++): ?>
        <?php if ($p===$page): ?><span class="on"><?= $p ?></span>
        <?php else: ?><a href="/instructor/earnings?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&courseId=<?= (int)$courseId ?>&page=<?= $p ?>"><?= $p ?></a><?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<div class="ins-card" data-accent="pine" style="margin-top:8px">
  <div class="ins-card-head">
    <span class="ins-card-icon"><?= Icons::svg('spark','i') ?></span>
    <div>
      <h3 class="ins-card-title">Design notes — Earnings modernization</h3>
      <p class="ins-card-body" style="margin:4px 0 0">KPI 5-col strip, chart-bars pure CSS with height % calc, track+fill gradient per accent, value bubble absolute top, legend chips, breakdown 4-col cards with share bar width = %, transactions table uses ins-table responsive collapse (thead hide, td flex + data-label). Query from/to/courseId/page preserved, no aggregation logic changed.</p>
    </div>
  </div>
</div>
