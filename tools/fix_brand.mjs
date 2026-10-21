import fs from 'node:fs';
let code = fs.readFileSync('tools/build_brand.php', 'utf-8');
code = code.replace(
  /if \(\$gap >= \$minGap && \$best\[1\] === 0\) \{ \$best = \[\$run, \$y\]; \$run = null; break; \}/,
  'if ($gap > ($best[1] - $best[0])) { $best = [$run, $y]; } $run = null;'
);
fs.writeFileSync('tools/build_brand.php', code);
