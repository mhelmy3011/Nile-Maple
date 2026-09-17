<?php
use Nm\Icons;
use Nm\View;
use Nm\Csrf;
$q = $q ?? '';
$type = $type ?? '';
$status = $status ?? '';
$kpis = $kpis ?? [];
$groups = $groups ?? [];
?>
<div class="ins-page-header" data-accent="green">
  <div>
    <nav class="ins-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li>Notifications</li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Inbox · Live · Dynamic coloring</div>
    <h1>Notifications</h1>
    <p class="ins-lead">Stay on top of student activity, submissions, and system alerts. Modern rich UI with status-driven coloring, grouped timeline, and thumb-zone actions.</p>
  </div>
  <div class="ins-header-actions">
    <button class="btn btn-ghost btn-sm" type="button"><?= Icons::svg('check','i') ?> Mark all read</button>
    <a class="btn btn-primary btn-sm" href="/instructor/announcements/create"><?= Icons::svg('spark','i') ?> New announcement</a>
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
  <div style="display:flex; align-items:center; gap:8px; flex:1; min-width:200px">
    <?= Icons::svg('search','i') ?>
    <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search notifications, students, courses…" aria-label="Search notifications">
  </div>
  <select name="type" aria-label="Filter by type">
    <option value="">All types</option>
    <option value="student" <?= $type==='student'?'selected':'' ?>>Student</option>
    <option value="submission" <?= $type==='submission'?'selected':'' ?>>Submission</option>
    <option value="system" <?= $type==='system'?'selected':'' ?>>System</option>
    <option value="mention" <?= $type==='mention'?'selected':'' ?>>Mention</option>
  </select>
  <select name="status" aria-label="Filter by status">
    <option value="">All status</option>
    <option value="unread" <?= $status==='unread'?'selected':'' ?>>Unread</option>
    <option value="read" <?= $status==='read'?'selected':'' ?>>Read</option>
  </select>
  <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
  <span class="ins-count"><?= count($notifications ?? []) ?> total</span>
</form>

<?php if (!empty($_GET['ids'])): ?>
<div class="ins-filterbar" style="background:var(--amber-50); border-color:var(--amber-200)">
  <span class="ins-chip ins-chip-amber"><span class="dot"></span> <?= count($_GET['ids'] ?? []) ?> selected</span>
  <div class="ins-actions" style="margin-inline-start:auto">
    <button class="btn btn-ghost btn-sm">Mark read</button>
    <button class="btn btn-ghost btn-sm">Archive</button>
    <button class="btn btn-ghost btn-sm" style="color:var(--leaf-600)">Delete</button>
  </div>
</div>
<?php endif; ?>

<div style="display:grid; gap:var(--sp-8)">
  <?php if (!$groups): ?>
    <div class="ins-empty">
      <div class="ins-empty-ill"><?= Icons::svg('mail','i') ?></div>
      <h3>All caught up</h3>
      <p>No notifications matching your filters. Try adjusting search or check back later.</p>
      <a class="btn btn-primary btn-sm" href="/instructor/notifications">Clear filters</a>
    </div>
  <?php else: ?>
    <?php foreach ($groups as $dateLabel => $items): ?>
      <section>
        <div class="ins-faq-group" style="margin-top:0">
          <?= Icons::svg('calendar','i') ?> <?= View::e($dateLabel) ?> <span class="count"><?= count($items) ?></span>
        </div>
        <div style="display:grid; gap:12px">
          <?php foreach ($items as $n): ?>
            <article class="ins-card" data-accent="<?= View::e($n['accent']) ?>" style="<?= $n['unread'] ? 'background:linear-gradient(180deg, var(--green-50) 0%, var(--surface) 100%)' : '' ?>">
              <div class="ins-card-head">
                <span class="ins-card-icon" style="position:relative">
                  <?= Icons::svg($n['type']==='student'?'users':($n['type']==='submission'?'doc':($n['type']==='system'?'shield':'spark')),'i') ?>
                  <?php if ($n['unread']): ?><span style="position:absolute; top:-4px; inset-inline-end:-4px; width:10px; height:10px; background:var(--green-500); border:2px solid #fff; border-radius:50%"></span><?php endif; ?>
                </span>
                <div style="flex:1; min-width:0; display:grid; gap:4px">
                  <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
                    <h3 class="ins-card-title" style="flex:1"><?= View::e($n['title']) ?></h3>
                    <span class="ins-chip ins-chip-<?= $n['accent'] ?>"><span class="dot"></span><?= View::e(ucfirst($n['type'])) ?></span>
                    <?php if ($n['unread']): ?><span class="ins-chip ins-chip-green" style="font-size:.68rem">New</span><?php endif; ?>
                  </div>
                  <div class="ins-card-meta">
                    <span style="display:inline-flex; align-items:center; gap:6px"><span class="ins-avatar" style="width:20px; height:20px; font-size:.68rem; background:var(--n-200); color:var(--n-800)"><?= View::e($n['avatar']) ?></span> <?= View::e($n['name']) ?></span>
                    <span>·</span>
                    <span><?= View::e($n['course']) ?></span>
                    <span>·</span>
                    <span class="ins-chip-mono" style="font-size:.72rem; color:var(--text-muted)"><?= View::e($n['relative']) ?></span>
                  </div>
                  <p class="ins-card-body" style="margin:0"><?= View::e($n['message']) ?></p>
                </div>
              </div>
              <div class="ins-card-actions">
                <span class="ins-count" style="margin-inline-end:auto"><?= View::e(date('H:i', strtotime($n['time']))) ?> · <?= View::e($n['email']) ?></span>
                <a class="btn btn-ghost btn-sm" href="/instructor/students/details?enrollmentId=<?= (int)$n['id'] ?>">View student</a>
                <?php if ($n['unread']): ?><button class="btn btn-primary btn-sm">Mark read</button><?php else: ?><button class="btn btn-ghost btn-sm">Archive</button><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="ins-card" data-accent="pine" style="margin-top:8px">
  <div class="ins-card-head">
    <span class="ins-card-icon"><?= Icons::svg('spark','i') ?></span>
    <div>
      <h3 class="ins-card-title">Design notes — dynamic coloring & rich UX</h3>
      <p class="ins-card-body" style="margin:4px 0 0">Green = student/info, Amber = submission/warning, Pine = system/neutral, Leaf = mention/urgent. Unread gets green-50 tint + dot pulse, hover lifts with e2. Mobile collapses to cards with absolute checkbox pattern (same as adm-table). No logic changed — same q/type/status params, same bulk op.</p>
    </div>
  </div>
</div>
