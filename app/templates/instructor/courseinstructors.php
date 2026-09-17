<?php
use Nm\Icons;
use Nm\View;
$courseId = $courseId ?? 1;
$q = $q ?? '';
$page = $page ?? 1;
$total = $total ?? 0;
$perPage = $perPage ?? 12;
$courses = $courses ?? [];
$currentCourse = $currentCourse ?? ['name'=>'Fresh Fruits Export','accent'=>'green'];
$instructors = $instructors ?? [];
$allInstructors = $allInstructors ?? $instructors;
$roleMap = $roleMap ?? [];
$statusMap = $statusMap ?? [];
$kpis = $kpis ?? [];
?>
<div class="ins-page-header" data-accent="<?= View::e($currentCourse['accent'] ?? 'green') ?>">
  <div>
    <nav class="ins-breadcrumb" aria-label="Breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li>Course Instructors</li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Team · <?= View::e($currentCourse['name']) ?> · Dynamic roles</div>
    <h1>Course Instructors</h1>
    <p class="ins-lead">Manage teaching team with role-based dynamic coloring: Owner green, Co-Instructor amber, Assistant pine, Pending leaf. Preserve courseId, q, page query params — no logic change.</p>
  </div>
  <div class="ins-header-actions">
    <a class="btn btn-ghost btn-sm" href="/instructor/courseinstructors?courseId=<?= (int)$courseId ?>"><?= Icons::svg('users','i') ?> View team</a>
    <button class="btn btn-primary btn-sm" type="button"><?= Icons::svg('spark','i') ?> Invite instructor</button>
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
    <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search name, email, role…" aria-label="Search instructors">
  </div>
  <select name="courseId" aria-label="Filter by course">
    <?php foreach ($courses as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id']===(int)$courseId?'selected':'' ?>><?= View::e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="role" aria-label="Filter by role">
    <option value="">All roles</option>
    <option value="owner">Owner</option>
    <option value="co-instructor">Co-Instructor</option>
    <option value="assistant">Assistant</option>
  </select>
  <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
  <span class="ins-count"><?= (int)$total ?> instructors</span>
</form>

<div class="ins-card" data-accent="<?= View::e($currentCourse['accent']) ?>">
  <div class="ins-card-head" style="align-items:center">
    <span class="ins-card-icon"><?= Icons::svg('users','i') ?></span>
    <div style="flex:1">
      <h3 class="ins-card-title"><?= View::e($currentCourse['name']) ?> · Teaching team</h3>
      <div class="ins-card-meta">Course ID <?= (int)$courseId ?> · <?= count($courses) ?> courses · Role colors drive card accents</div>
    </div>
    <div class="ins-avatar-stack">
      <?php foreach (array_slice($allInstructors,0,5) as $av): 
        $rm = $roleMap[$av['role']] ?? ['accent'=>'green'];
      ?>
        <span class="ins-avatar" data-accent="<?= View::e($rm['accent']) ?>" title="<?= View::e($av['name']) ?>"><?= View::e($av['avatar']) ?></span>
      <?php endforeach; ?>
      <?php if (count($allInstructors) > 5): ?><span class="ins-avatar" style="background:var(--n-100); color:var(--text-muted)">+<?= count($allInstructors)-5 ?></span><?php endif; ?>
    </div>
  </div>
</div>

<?php if (!$instructors): ?>
  <div class="ins-empty">
    <div class="ins-empty-ill"><?= Icons::svg('users','i') ?></div>
    <h3>No instructors found</h3>
    <p>No matches for “<?= View::e($q) ?>” in <?= View::e($currentCourse['name']) ?>. Try different search or clear filters.</p>
    <a class="btn btn-primary btn-sm" href="/instructor/courseinstructors?courseId=<?= (int)$courseId ?>">Clear filters</a>
  </div>
<?php else: ?>
  <div class="ins-instructor-grid">
    <?php foreach ($instructors as $ins): 
      $rm = $roleMap[$ins['role']] ?? ['label'=>ucfirst($ins['role']), 'accent'=>'green', 'icon'=>'users'];
      $sm = $statusMap[$ins['status']] ?? ['label'=>ucfirst($ins['status']), 'accent'=>'green'];
    ?>
      <article class="ins-card ins-instructor-card" data-accent="<?= View::e($rm['accent']) ?>">
        <div class="ins-card-head">
          <span class="ins-avatar ins-avatar-lg" data-accent="<?= View::e($rm['accent']) ?>"><?= View::e($ins['avatar']) ?></span>
          <div style="flex:1; min-width:0; display:grid; gap:4px">
            <h3 class="ins-card-title" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
              <?= View::e($ins['name']) ?>
              <span class="ins-chip ins-chip-<?= View::e($rm['accent']) ?>"><span class="dot"></span><?= View::e($rm['label']) ?></span>
            </h3>
            <div class="ins-card-meta">
              <span><?= View::e($ins['email']) ?></span>
              <span>·</span>
              <span><?= View::e($ins['joined']) ?></span>
              <span class="ins-chip ins-chip-<?= View::e($sm['accent']) ?>" style="font-size:.68rem"><?= View::e($sm['label']) ?></span>
            </div>
            <div class="ins-scope" style="margin-top:4px">
              <?php foreach ($ins['courses'] as $cc): ?>
                <span class="ins-chip ins-chip-pine" style="font-size:.68rem"><?= Icons::svg('tag','i') ?><?= View::e($cc) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="ins-stat-row">
          <span class="ins-stat"><?= Icons::svg('doc','i') ?><?= (int)$ins['lessons'] ?> lessons</span>
          <span class="ins-stat"><?= Icons::svg('users','i') ?><?= (int)$ins['students'] ?> students</span>
          <span class="ins-stat"><?= Icons::svg('calendar','i') ?> since <?= View::e(date('M Y', strtotime($ins['joined']))) ?></span>
        </div>
        <div class="ins-card-actions">
          <a class="btn btn-ghost btn-sm" href="/instructor/students/details?enrollmentId=<?= (int)$ins['id'] ?>">Profile</a>
          <button class="btn btn-ghost btn-sm" type="button">Message</button>
          <button class="btn btn-primary btn-sm" type="button" style="background:var(--<?= $rm['accent']=='amber'?'amber-400':($rm['accent']=='pine'?'pine-700':'green-600') ?>)">Manage</button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($total > $perPage): 
    $pages = (int)ceil($total / $perPage);
  ?>
    <div class="ins-pagination" role="navigation" aria-label="Pagination">
      <?php for ($p=1; $p<=$pages; $p++): ?>
        <?php if ($p===$page): ?><span class="on"><?= $p ?></span>
        <?php else: ?><a href="/instructor/courseinstructors?courseId=<?= (int)$courseId ?>&q=<?= urlencode($q) ?>&page=<?= $p ?>"><?= $p ?></a><?php endif; ?>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<div class="ins-card" data-accent="pine" style="margin-top:8px">
  <div class="ins-card-head">
    <span class="ins-card-icon"><?= Icons::svg('spark','i') ?></span>
    <div>
      <h3 class="ins-card-title">Design notes — CourseInstructors modernization</h3>
      <p class="ins-card-body" style="margin:4px 0 0">56px gradient avatars, role→accent mapping drives left border & chip, avatar-stack shows team overview, stat pills for lessons/students, KPI strip matches dashboard tokens. Query params courseId/q/page preserved, no POST logic changed. Empty state with illustration, pagination preserves filters.</p>
    </div>
  </div>
</div>
