// Provisions one dedicated admin account with a real (non-seed) password before the suite
// runs, so admin-auth/admin-crud tests exercise a normal, already-rotated session rather than
// mutating the real seeded owner account through the UI on every run. Shells out to the app's
// own PHP (not a Node sqlite driver) so the hash is produced by the exact same password_hash()
// call the app itself uses — guarantees compatibility regardless of PHP's PASSWORD_DEFAULT.
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(here, '../../..');

export const TEST_ADMIN = { email: 'qa-admin@nilemaple.test', password: 'PlaywrightQA!2026x' };

export default function globalSetup() {
  const php = `
    require '${repoRoot}/app/bootstrap.php';
    use Nm\\Db;
    \$hash = password_hash('${TEST_ADMIN.password}', PASSWORD_DEFAULT);
    \$exists = Db::val('SELECT id FROM users WHERE email=?', ['${TEST_ADMIN.email}']);
    if (\$exists) {
      Db::run('UPDATE users SET password_hash=?, status=?, failed_logins=0, locked_until=NULL WHERE id=?', [\$hash, 'active', (int) \$exists]);
    } else {
      Db::run('INSERT INTO users(email,password_hash,full_name,role,status) VALUES(?,?,?,?,?)',
        ['${TEST_ADMIN.email}', \$hash, 'Playwright QA', 'owner', 'active']);
    }
    echo "admin fixture ready\\n";
  `;
  execFileSync('php', ['-r', php], { cwd: repoRoot, stdio: 'inherit' });
}
