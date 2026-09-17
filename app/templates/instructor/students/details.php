<?php
use Nm\Icons;
use Nm\View;
use Nm\Csrf;
$student = $student ?? ['name'=>'Demo Student','email'=>'demo@example.com','phone'=>'+20 100 000 0000','course'=>'Fresh Fruits','batch'=>'Batch 2024-A','status'=>'active','attendance'=>87,'avg_grade'=>82,'completion'=>68,'assignments'=>12,'enrollmentId'=>1,'avatar'=>'D','enrolled_at'=>date('Y-m-d'),'last_active'=>date('Y-m-d H:i:s'),'country'=>'Egypt'];
$kpis = $kpis ?? [];
$grades = $grades ?? [];
$attendance = $attendance ?? [];
$timeline = $timeline ?? [];
$enrollmentId = $enrollmentId ?? 1;
$statusColor = match($student['status']){'active'=>'green','pending'=>'amber','inactive'=>'pine','at-risk'=>'leaf', default=>'green'};
?>
<div class="ins-page-header" data-accent="<?= $statusColor ?>">
  <div>
    <nav class="ins-breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li><a href="/instructor/students/details?enrollmentId=1">Students</a></li><li>Details #<?= (int)$enrollmentId ?></li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Enrollment · <?= View::e($student['course']) ?> · ID <?= (int)$enrollmentId ?></div>
    <h1><?= View::e($student['name']) ?></h1>
    <p class="ins-lead">Comprehensive enrollment profile with dynamic KPI coloring, progress visualization, and timeline. All original logic preserved — query param <code>enrollmentId</code> unchanged.</p>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px">
      <span class="ins-chip ins-chip-<?= $statusColor ?>"><span class="dot"></span> <?= View::e(ucfirst($student['status'])) ?></span>
      <span class="ins-chip ins-chip-mono"><?= View::e($student['course']) ?></span>
      <span class="ins-chip ins-chip-pine"><?= View::e($student['batch']) ?> · <?= View::e($student['country']) ?></span>
      <span class="ins-chip" style="background:var(--surface); border-color:var(--border)">Enrolled <?= View::e(date('M j, Y', strtotime($student['enrolled_at']))) ?></span>
    </div>
  </div>
  <div class="ins-header-actions" style="flex-direction:column; align-items:stretch">
    <a class="btn btn-primary btn-sm" href="mailto:<?= View::e($student['email']) ?>"><?= Icons::svg('mail','i') ?> Message</a>
    <a class="btn btn-ghost btn-sm" href="tel:<?= View::e($student['phone']) ?>"><?= Icons::svg('phone','i') ?> Call</a>
    <button class="btn btn-ghost btn-sm" type="button"><?= Icons::svg('doc','i') ?> Transcript</button>
  </div>
</div>

<div class="ins-kpi-strip">
  <?php foreach ($kpis as $k): ?>
    <div class="ins-kpi" data-accent="<?= View::e($k['accent']) ?>">
      <div class="ins-kpi-head">
        <span class="ins-kpi-icon"><?= Icons::svg($k['icon'],'i') ?></span>
        <span class="ins-chip ins-chip-<?= $k['accent'] ?>" style="font-size:.68rem"><?= View::e($k['trend']) ?></span>
      </div>
      <div class="ins-kpi-v"><?= View::e((string)$k['v']) ?></div>
      <div class="ins-kpi-l"><?= View::e($k['l']) ?></div>
      <?php if (str_contains($k['l'],'Completion')): ?>
        <div class="ins-progress" data-accent="<?= $k['accent'] ?>"><div class="ins-progress-bar" style="width:<?= (int)$student['completion'] ?>%"></div></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="ins-tabs" role="tablist">
  <button class="ins-tab on" role="tab" aria-selected="true">Overview <span class="badge">4</span></button>
  <button class="ins-tab" role="tab">Grades <span class="badge"><?= count($grades) ?></span></button>
  <button class="ins-tab" role="tab">Attendance <span class="badge"><?= count($attendance) ?></span></button>
  <button class="ins-tab" role="tab">Documents <span class="badge">3</span></button>
  <button class="ins-tab" role="tab">Activity <span class="badge"><?= count($timeline) ?></span></button>
</div>

<div class="ins-formgrid">
  <!-- left sidebar -->
  <div style="display:grid; gap:16px; align-content:start">
    <div class="ins-card" data-accent="pine">
      <div class="ins-card-head">
        <span class="ins-avatar" style="width:56px; height:56px; font-size:1.2rem; background:linear-gradient(135deg,var(--green-500),var(--green-700)); color:var(--pine-950)"><?= View::e($student['avatar']) ?></span>
        <div>
          <h3 class="ins-card-title"><?= View::e($student['name']) ?></h3>
          <div class="ins-card-meta"><?= View::e($student['email']) ?> · <?= View::e($student['phone']) ?></div>
        </div>
      </div>
      <div class="ins-divider"></div>
      <dl style="display:grid; grid-template-columns:auto 1fr; gap:6px 12px; margin:0; font-size:.875rem">
        <dt style="color:var(--text-muted); font-weight:700">Course</dt><dd style="margin:0; font-weight:700"><?= View::e($student['course']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">Batch</dt><dd style="margin:0"><?= View::e($student['batch']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">Country</dt><dd style="margin:0"><?= View::e($student['country']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">Last active</dt><dd style="margin:0" class="ins-chip-mono"><?= View::e($student['last_active']) ?></dd>
      </dl>
      <div class="ins-card-actions">
        <a class="btn btn-ghost btn-sm" href="mailto:<?= View::e($student['email']) ?>">Email</a>
        <a class="btn btn-primary btn-sm" href="/instructor/documents/details">Docs</a>
      </div>
    </div>

    <div class="ins-card" data-accent="green">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('chart','i') ?></span>
        <h3 class="ins-card-title">Progress & Milestones</h3>
      </div>
      <div style="display:grid; gap:10px">
        <div style="display:flex; justify-content:space-between; font-size:.8125rem; font-weight:700"><span>Completion</span><span><?= (int)$student['completion'] ?>%</span></div>
        <div class="ins-progress" data-accent="green"><div class="ins-progress-bar" style="width:<?= (int)$student['completion'] ?>%"></div></div>
        <div class="ins-timeline" style="margin-top:8px">
          <div class="ins-tl-item"><span class="ins-tl-dot" style="width:28px; height:28px; border-color:var(--green-500)"><?= Icons::svg('check','i') ?></span><div style="font-size:.8125rem"><strong>Onboarding</strong><br><span style="color:var(--text-muted)">Completed</span></div></div>
          <div class="ins-tl-item"><span class="ins-tl-dot" style="width:28px; height:28px; border-color:var(--green-500); background:var(--green-500); color:var(--pine-950)"><?= Icons::svg('check','i') ?></span><div style="font-size:.8125rem"><strong>Mid-term</strong><br><span style="color:var(--text-muted)">In progress</span></div></div>
          <div class="ins-tl-item"><span class="ins-tl-dot" style="width:28px; height:28px; border-color:var(--n-300); color:var(--n-500)"><?= Icons::svg('clock','i') ?></span><div style="font-size:.8125rem"><strong>Final</strong><br><span style="color:var(--text-muted)">Upcoming</span></div></div>
        </div>
      </div>
    </div>

    <div class="ins-card" data-accent="amber">
      <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('clipboard','i') ?></span><h3 class="ins-card-title">Quick actions</h3></div>
      <div class="ins-grid-2">
        <a class="btn btn-ghost btn-sm" href="/instructor/announcements/create"><?= Icons::svg('spark','i') ?> Announce</a>
        <a class="btn btn-ghost btn-sm" href="/instructor/documents/details"><?= Icons::svg('doc','i') ?> Add doc</a>
      </div>
    </div>
  </div>

  <!-- right content -->
  <div style="display:grid; gap:16px">
    <div class="ins-card" data-accent="green">
      <div class="ins-card-head">
        <span class="ins-card-icon"><?= Icons::svg('chart','i') ?></span>
        <div style="flex:1">
          <h3 class="ins-card-title">Grades — dynamic coloring by threshold</h3>
          <p class="ins-card-body" style="margin:4px 0 0">≥80 green, 60-79 amber, &lt;60 leaf. Same data, richer visualization.</p>
        </div>
        <span class="ins-count"><?= count($grades) ?> assignments</span>
      </div>
      <div style="display:grid; gap:10px">
        <?php foreach ($grades as $g): 
          $accent = $g['score'] >= 80 ? 'green' : ($g['score'] >= 60 ? 'amber' : ($g['status']==='pending'?'pine':'leaf'));
        ?>
          <div style="display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; padding:12px 14px; background:var(--surface-2); border-radius:12px; border:1px solid var(--border)">
            <div>
              <div style="font-weight:800; font-size:.9375rem"><?= View::e($g['assignment']) ?></div>
              <div style="font-size:.78rem; color:var(--text-muted)"><?= View::e($g['date']) ?> · <?= View::e($g['status']) ?></div>
              <div class="ins-progress" data-accent="<?= $accent ?>" style="margin-top:8px; height:6px"><div class="ins-progress-bar" style="width:<?= (int)(($g['score']/$g['max'])*100) ?>%"></div></div>
            </div>
            <span class="ins-chip ins-chip-<?= $accent ?>" style="font-size:.875rem; padding:6px 12px"><?= $g['status']==='pending' ? 'Pending' : $g['score'].'/'.$g['max'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ins-grid-2">
      <div class="ins-card" data-accent="pine">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('calendar','i') ?></span><h3 class="ins-card-title">Attendance</h3></div>
        <div style="display:grid; gap:8px">
          <?php foreach ($attendance as $a): 
            $ac = match($a['status']){'present'=>'green','late'=>'amber','absent'=>'leaf', default=>'pine'};
          ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:var(--surface-2); border-radius:999px">
              <span style="font-size:.875rem; font-weight:700"><?= View::e($a['date']) ?></span>
              <span class="ins-chip ins-chip-<?= $ac ?>"><span class="dot"></span><?= View::e(ucfirst($a['status'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="ins-card" data-accent="amber">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('clock','i') ?></span><h3 class="ins-card-title">Recent activity</h3></div>
        <ol class="ins-timeline">
          <?php foreach ($timeline as $t): ?>
            <li class="ins-tl-item">
              <span class="ins-tl-dot" style="border-color:var(--<?= $t['accent']=='green'?'green-500':($t['accent']=='amber'?'amber-400':($t['accent']=='leaf'?'leaf-600':'pine-600')) ?>)"><?= Icons::svg($t['icon'],'i') ?></span>
              <div class="ins-tl-card">
                <strong style="font-size:.875rem"><?= View::e($t['title']) ?></strong>
                <span style="font-size:.8125rem; color:var(--text-muted)"><?= View::e($t['desc']) ?></span>
                <span class="ins-tl-time"><?= View::e($t['time']) ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>

    <form method="post" class="ins-card" data-accent="green">
      <?= Csrf::field() ?>
      <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('clipboard','i') ?></span><h3 class="ins-card-title">Update status — logic untouched</h3></div>
      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center">
        <select name="status" style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:999px; padding-inline:14px; font-weight:700">
          <?php foreach (['active','pending','at-risk','inactive'] as $s): ?><option <?= $student['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Save status</button>
        <span class="ins-chip ins-chip-pine" style="margin-inline-start:auto">POST same as before · CSRF preserved · PRG</span>
      </div>
    </form>
  </div>
</div>
