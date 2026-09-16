<?php
namespace Nm;

/** Dashboard controller: routing, RBAC, generic CRUD over entity configs (doc 06). */
final class Admin
{
    public static function handle(): void
    {
        Session::start();
        $uri = trim((string) ($_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)), '/');
        /* the trailing slash is gone after trim('/') above, so the bare "/manage/" request
           (dashboard home) arrives here as exactly "manage" — `^manage/` (slash required)
           would silently fail to strip it, leaving $page = 'manage' and 404ing a route that
           exists. Optional slash so this fallback (normally short-circuited by $_GET['path']
           from .htaccess/dev_server.php) is correct on its own too. */
        $uri = preg_replace('#^manage/?#', '', $uri);
        $segs = $uri === '' ? [] : explode('/', trim($uri, '/'));
        $allow = cfg('admin.allow_ips');
        if ($allow && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allow, true)) { http_response_code(403); exit('Forbidden'); }

        $page = $segs[0] ?? 'dashboard';
        /* the public routes bypass auth; note the method names are pgXxx, not $page */
        if (in_array($page, ['login', 'logout', '2fa'], true)) { self::{'pg' . ucfirst($page)}($segs); return; }
        $user = Auth::requireLogin();
        View::share('user', $user);
        View::share('section', $page);
        /* D-11: an account that signed in with the seed password sees ONLY the rotation screen
           until the credential is changed — no dashboard, no entity edits, no API surface. */
        if (Auth::needsRotation() && $page !== 'password') Util::redirect(cfg('admin.path') . '/password');
        if (in_array($page, ['users', 'settings', 'rebuild'], true) && !Auth::isOwner()) { http_response_code(403); exit('Owner only'); }
        if (!method_exists(self::class, 'pg' . str_replace('-', '', ucfirst($page)))) { http_response_code(404); exit('Not found'); }
        self::{'pg' . str_replace('-', '', ucfirst($page))}($segs);
    }

    /* ---------------- entity configs (doc 06 §3) ---------------- */
    public static function entities(): array
    {
        $cats = static fn() => array_map(fn($c) => [$c['id'], $c['code']], Db::all('SELECT id, code FROM categories ORDER BY sort_order'));
        return [
            'categories' => [
                'table' => 'categories', 'i18n' => 'category_i18n', 'fk' => 'category_id', 'label' => 'Categories',
                'fields' => [
                    ['name' => 'code', 'type' => 'text', 'req' => 1, 'lock' => 1],
                    ['name' => 'icon_key', 'type' => 'icon'],
                    ['name' => 'accent', 'type' => 'select', 'options' => [['green', 'green'], ['amber', 'amber'], ['pine', 'pine'], ['leaf', 'leaf']]],
                    ['name' => 'cover_media_id', 'type' => 'media'],
                    ['name' => 'sort_order', 'type' => 'int'],
                    ['name' => 'is_published', 'type' => 'bool'],
                ],
                'i18n_fields' => [
                    ['name' => 'name', 'type' => 'text', 'req' => 1],
                    ['name' => 'slug', 'type' => 'slug', 'req' => 1, 'from' => 'name'],
                    ['name' => 'headline', 'type' => 'text', 'req' => 1],
                    ['name' => 'summary', 'type' => 'textarea', 'req' => 1],
                    ['name' => 'meta_title', 'type' => 'text', 'max' => 70],
                    ['name' => 'meta_description', 'type' => 'textarea', 'max' => 165],
                ],
                'list' => ['code', 'name', 'cnt'],
            ],
            'products' => [
                'table' => 'products', 'i18n' => 'product_i18n', 'fk' => 'product_id', 'label' => 'Products',
                'fields' => [
                    ['name' => 'category_id', 'type' => 'select', 'options' => $cats, 'req' => 1],
                    ['name' => 'sku', 'type' => 'text'],
                    ['name' => 'source_index', 'type' => 'int'],
                    ['name' => 'card_media_id', 'type' => 'media'],
                    ['name' => 'temp_min', 'type' => 'num'], ['name' => 'temp_max', 'type' => 'num'],
                    ['name' => 'temp_unit', 'type' => 'select', 'options' => [['C', '°C'], ['F', '°F']]],
                    ['name' => 'temp_note', 'type' => 'text'],
                    ['name' => 'sort_order', 'type' => 'int'],
                    ['name' => 'is_featured', 'type' => 'bool'],
                    ['name' => 'is_published', 'type' => 'bool'],
                ],
                'i18n_fields' => [
                    ['name' => 'name', 'type' => 'text', 'req' => 1],
                    ['name' => 'slug', 'type' => 'slug', 'req' => 1, 'from' => 'name'],
                    ['name' => 'description', 'type' => 'textarea', 'req' => 1],
                    ['name' => 'label_varieties', 'type' => 'text', 'req' => 1],
                    ['name' => 'varieties', 'type' => 'textarea', 'req' => 1],
                    ['name' => 'label_handling', 'type' => 'text', 'req' => 1],
                    ['name' => 'handling', 'type' => 'textarea', 'req' => 1],
                    ['name' => 'label_packing', 'type' => 'text', 'req' => 1],
                    ['name' => 'packing', 'type' => 'textarea', 'req' => 1],
                    ['name' => 'label_chain', 'type' => 'text', 'req' => 1],
                    ['name' => 'chain', 'type' => 'textarea', 'req' => 1],
                    ['name' => 'meta_title', 'type' => 'text', 'max' => 70],
                    ['name' => 'meta_description', 'type' => 'textarea', 'max' => 165],
                ],
                'list' => ['sku', 'name', 'cat', 'comp'],
            ],
            'services' => [
                'table' => 'services', 'i18n' => 'service_i18n', 'fk' => 'service_id', 'label' => 'Services',
                'fields' => [
                    ['name' => 'code', 'type' => 'text', 'req' => 1, 'lock' => 1],
                    ['name' => 'icon_key', 'type' => 'icon'],
                    ['name' => 'media_id', 'type' => 'media'],
                    ['name' => 'sort_order', 'type' => 'int'],
                    ['name' => 'is_published', 'type' => 'bool'],
                ],
                'i18n_fields' => [
                    ['name' => 'name', 'type' => 'text', 'req' => 1],
                    ['name' => 'slug', 'type' => 'slug', 'req' => 1, 'from' => 'name'],
                    ['name' => 'teaser', 'type' => 'textarea', 'req' => 1, 'max' => 220],
                    ['name' => 'body', 'type' => 'markdown', 'req' => 1],
                    ['name' => 'bullets', 'type' => 'rows'],
                    ['name' => 'meta_title', 'type' => 'text', 'max' => 70],
                    ['name' => 'meta_description', 'type' => 'textarea', 'max' => 165],
                ],
                'list' => ['code', 'name'],
            ],
            'posts' => [
                'table' => 'posts', 'i18n' => 'post_i18n', 'fk' => 'post_id', 'label' => 'Blog posts',
                'fields' => [
                    ['name' => 'author_user_id', 'type' => 'users'],
                    ['name' => 'cover_media_id', 'type' => 'media'],
                    ['name' => 'status', 'type' => 'select', 'options' => [['draft', 'draft'], ['published', 'published'], ['archived', 'archived']]],
                    ['name' => 'published_at', 'type' => 'datetime'],
                    ['name' => 'reading_minutes', 'type' => 'int'],
                ],
                'i18n_fields' => [
                    ['name' => 'title', 'type' => 'text', 'req' => 1],
                    ['name' => 'slug', 'type' => 'slug', 'req' => 1, 'from' => 'title'],
                    ['name' => 'excerpt', 'type' => 'textarea', 'req' => 1, 'max' => 300],
                    ['name' => 'body', 'type' => 'markdown', 'req' => 1],
                    ['name' => 'meta_title', 'type' => 'text', 'max' => 70],
                    ['name' => 'meta_description', 'type' => 'textarea', 'max' => 165],
                ],
                'list' => ['status', 'title', 'published_at'],
            ],
            'faqs' => [
                'table' => 'faqs', 'i18n' => 'faq_i18n', 'fk' => 'faq_id', 'label' => 'FAQs',
                'fields' => [
                    ['name' => 'group_code', 'type' => 'select', 'options' => [['general', 'general'], ['products', 'products'], ['packaging', 'packaging'], ['payment', 'payment'], ['logistics', 'logistics'], ['documents', 'documents']]],
                    ['name' => 'sort_order', 'type' => 'int'],
                    ['name' => 'is_published', 'type' => 'bool'],
                ],
                'i18n_fields' => [
                    ['name' => 'question', 'type' => 'text', 'req' => 1],
                    ['name' => 'answer', 'type' => 'textarea', 'req' => 1],
                ],
                'list' => ['group_code', 'question'],
            ],
        ];
    }

    /* ---------------- pages ---------------- */
    private static function pgLogin(array $s): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_csrf'] ?? null)) Util::redirect(cfg('admin.path') . '/login?e=csrf');
            if (!RateLimit::hit('login-' . Util::ipHash(), 8, 900)) Util::redirect(cfg('admin.path') . '/login?e=rate');
            $r = Auth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
            if (!empty($r['ok'])) {
                if (!empty(cfg('admin.totp_secret')) && $r['user']['role'] === 'owner') Util::redirect(cfg('admin.path') . '/2fa');
                Util::redirect(cfg('admin.path') . '/');
            }
            Util::redirect(cfg('admin.path') . '/login?e=1');
        }
        echo View::render('admin/login', ['error' => $_GET['e'] ?? null, 'layout' => false]);
        exit;
    }
    private static function pg2fa(array $s): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Csrf::check($_POST['_csrf'] ?? null) && Auth::totpVerify((string) cfg('admin.totp_secret'), (string) ($_POST['code'] ?? ''))) {
                Util::redirect(cfg('admin.path') . '/');
            }
            Util::redirect(cfg('admin.path') . '/2fa?e=1');
        }
        echo View::render('admin/login', ['error' => $_GET['e'] ?? null, 'totp' => true, 'layout' => false]);
        exit;
    }
    private static function pgLogout(array $s): void { Auth::logout(); Util::redirect(cfg('admin.path') . '/login'); }

    /* D-11: forced credential rotation. The only screen reachable while $_SESSION['force_pw'] is set. */
    private static function pgPassword(array $s): void
    {
        $user = Auth::user();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_csrf'] ?? null)) Util::redirect(cfg('admin.path') . '/password?e=csrf');
            $cur = (string) ($_POST['current'] ?? '');
            $new = (string) ($_POST['password'] ?? '');
            $row = Db::one('SELECT password_hash FROM users WHERE id=?', [(int) $user['id']]);
            if (!$row || !password_verify($cur, (string) $row['password_hash'])) Util::redirect(cfg('admin.path') . '/password?e=current');
            if (hash_equals(Auth::SEED_PASSWORD, $new)) Util::redirect(cfg('admin.path') . '/password?e=seed');
            if (strlen($new) < 10) Util::redirect(cfg('admin.path') . '/password?e=short');
            if ($new === $cur) Util::redirect(cfg('admin.path') . '/password?e=same');
            Db::run('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);
            unset($_SESSION['force_pw']);
            Audit::log('auth.password-change', 'user', (int) $user['id']);
            Util::redirect(cfg('admin.path') . '/?pw=1');
        }
        View::share('section', 'password');
        echo View::page('admin/password', [], 'layouts/admin');
        exit;
    }

    private static function pgDashboard(array $s): void
    {
        $d = [
            'enq_new' => (int) Db::val("SELECT COUNT(*) FROM enquiries WHERE status='new'"),
            'enq_30' => (int) Db::val('SELECT COUNT(*) FROM enquiries WHERE created_at >= ?', [date('Y-m-d H:i:s', time() - 2592000)]),
            'enq_spam' => (int) Db::val("SELECT COUNT(*) FROM enquiries WHERE status='spam'"), /* D-10 volume alert */
            'products' => (int) Db::val('SELECT COUNT(*) FROM products'),
            'published' => (int) Db::val('SELECT COUNT(*) FROM products WHERE is_published=1'),
            'jobs' => Db::all('SELECT * FROM build_jobs ORDER BY id DESC LIMIT 5'),
            'events' => Db::all("SELECT name, COUNT(*) c FROM events GROUP BY name ORDER BY c DESC LIMIT 8"),
            'comp' => self::completenessBoard(),
        ];
        echo View::page('admin/dashboard', $d, 'layouts/admin');
    }

    public static function completenessBoard(): array
    {
        $out = [];
        foreach (self::entities() as $key => $e) {
            $type = rtrim($key, 's') === 'categorie' ? 'category' : rtrim($key, 's');
            $rows = Db::all('SELECT id FROM ' . $e['table'] . ' ORDER BY id');
            $miss = 0;
            foreach ($rows as $r) {
                foreach (Content::completeness($type, (int) $r['id']) as $pct) if ($pct < 100) { $miss++; break; }
            }
            $out[$key] = ['total' => count($rows), 'incomplete' => $miss];
        }
        return $out;
    }

    private static function entityKey(string $k): ?array { return self::entities()[$k] ?? null; }

    private static function pgCategories(array $s): void { self::entityPage('categories', $s); }
    private static function pgProducts(array $s): void { self::entityPage('products', $s); }
    private static function pgServices(array $s): void { self::entityPage('services', $s); }
    private static function pgPosts(array $s): void { self::entityPage('posts', $s); }
    private static function pgFaqs(array $s): void { self::entityPage('faqs', $s); }

    private static function entityPage(string $key, array $segs): void
    {
        $e = self::entityKey($key);
        $action = $segs[1] ?? 'list';
        $id = (int) ($segs[2] ?? 0);
        /* the form posts to /manage/<entity>/<id> — a bare numeric segment is the id, not an action */
        if ($id === 0 && is_numeric($action)) { $id = (int) $action; $action = 'edit'; }

        if ($action === 'order' && $_SERVER['REQUEST_METHOD'] === 'POST'
            && in_array('sort_order', Db::columns($e['table']), true)) {
            $ids = json_decode((string) file_get_contents('php://input'), true) ?: [];
            foreach ($ids as $i => $rid) Db::run('UPDATE ' . $e['table'] . ' SET sort_order=? WHERE id=?', [$i, (int) $rid]);
            self::afterWrite($key);
            Util::json(['ok' => true]);
        }
        if ($action === 'bulk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_csrf'] ?? null)) Util::json(['error' => 'csrf'], 419);
            $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
            $op = (string) ($_POST['op'] ?? '');
            foreach ($ids as $rid) {
                if ($op === 'publish') Db::run('UPDATE ' . $e['table'] . " SET is_published=1 WHERE id=?", [$rid]);
                if ($op === 'unpublish') Db::run('UPDATE ' . $e['table'] . " SET is_published=0 WHERE id=?", [$rid]);
                if ($op === 'delete') Db::run('DELETE FROM ' . $e['table'] . ' WHERE id=?', [$rid]);
                Audit::log("bulk.$op", $key, $rid);
            }
            self::afterWrite($key);
            Util::redirect(cfg('admin.path') . "/$key");
        }
        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Csrf::check($_POST['_csrf'] ?? null)) Util::json(['error' => 'csrf'], 419);
            if ($key === 'categories' && (int) Db::val('SELECT COUNT(*) FROM products WHERE category_id=?', [$id]) > 0) {
                Util::redirect(cfg('admin.path') . "/$key?e=hasproducts");
            }
            Db::run('DELETE FROM ' . $e['table'] . ' WHERE id=?', [$id]);
            Audit::log('delete', $key, $id);
            self::afterWrite($key);
            Util::redirect(cfg('admin.path') . "/$key");
        }
        if (($action === 'edit' || $action === 'new') && $_SERVER['REQUEST_METHOD'] === 'POST') {
            self::saveEntity($key, $e, $id);
            return;
        }
        if ($action === 'edit' || $action === 'new') {
            $row = $id ? Db::one('SELECT * FROM ' . $e['table'] . ' WHERE id=?', [$id]) : null;
            if ($id && !$row) { http_response_code(404); exit('nf'); }
            $i18n = [];
            foreach (cfg('langs') as $l) {
                $i18n[$l] = $id ? (Db::one('SELECT * FROM ' . $e['i18n'] . ' WHERE ' . $e['fk'] . '=? AND lang=?', [$id, $l]) ?: []) : [];
            }
            /* redisplay exactly what was typed after a failed save, instead of the pre-edit DB
               row (see flashFail()) — one-shot, so a manual reload of this URL goes back to the
               saved state rather than reoffering stale draft data forever. */
            Session::start();
            $flashKey = $key . ':' . ($id ?: 'new');
            if (!empty($_SESSION['form_flash'][$flashKey])) {
                $posted = $_SESSION['form_flash'][$flashKey];
                unset($_SESSION['form_flash'][$flashKey]);
                $row = array_merge($row ?? [], array_intersect_key($posted, array_flip(array_map(fn($f) => $f['name'], $e['fields']))));
                foreach (cfg('langs') as $l) {
                    if (!empty($posted['i18n'][$l])) $i18n[$l] = array_merge($i18n[$l], $posted['i18n'][$l]);
                }
            }
            echo View::page('admin/form', ['e' => $e, 'key' => $key, 'row' => $row, 'i18n' => $i18n, 'id' => $id, 'err' => $_GET['e'] ?? null], 'layouts/admin');
            return;
        }
        /* list */
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per = 25;
        $lang = cfg('default_lang');
        $join = ' LEFT JOIN ' . $e['i18n'] . " i ON i.{$e['fk']}=t.id AND i.lang='" . $lang . "'";
        $extra = '';
        $args = [];
        if ($key === 'products') { $join .= ' LEFT JOIN categories c ON c.id=t.category_id'; $extra = ', c.code AS cat'; }
        if ($key === 'categories') { $extra = ', (SELECT COUNT(*) FROM products p WHERE p.category_id=t.id) AS cnt'; }
        /* which label columns this entity's i18n child actually has (doc 01 §2.3 shapes differ) */
        $cols = Db::columns($e['i18n']);
        $label = array_values(array_intersect(['name', 'title', 'question', 'slug'], $cols));
        if (!$label) $label = ['name'];
        $search = array_values(array_intersect(['name', 'title', 'question', 'teaser', 'answer'], $cols));
        $tcols = Db::columns($e['table']);
        $ord = in_array('sort_order', $tcols, true) ? 't.sort_order, t.id'
            : (in_array('published_at', $tcols, true) ? 't.published_at DESC, t.id DESC' : 't.id DESC');
        $where = '';
        if ($q !== '' && $search) {
            $where = ' WHERE ' . implode(' OR ', array_map(static fn(string $c): string => "i.$c LIKE ?", $search));
            $args = array_fill(0, count($search), "%$q%");
        }
        $total = (int) Db::val('SELECT COUNT(*) FROM ' . $e['table'] . ' t' . $join . $where, $args);
        $rows = Db::all('SELECT t.*, ' . implode(', ', array_map(static fn(string $c): string => "i.$c", $label)) . $extra .
            ' FROM ' . $e['table'] . ' t' . $join . $where .
            ' ORDER BY ' . $ord . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $args);
        foreach ($rows as &$r) {
            $type = $key === 'categories' ? 'category' : rtrim($key, 's');
            $r['comp'] = Content::completeness($type, (int) $r['id']);
        }
        echo View::page('admin/list', ['e' => $e, 'key' => $key, 'rows' => $rows, 'total' => $total, 'page' => $page, 'per' => $per, 'q' => $q], 'layouts/admin');
    }

    /** One-shot flash of a failed POST so the edit form can redisplay exactly what the admin
        typed instead of losing it to the PRG redirect (docs/06 §2: "never loses posted data on
        error"). Session-backed rather than query-string: i18n payloads are multi-field arrays
        that would not survive a URL, and a flash naturally clears itself after one read. */
    private static function flashFail(string $key, int $id, string $errParam): void
    {
        Session::start();
        $_SESSION['form_flash'][$key . ':' . ($id ?: 'new')] = $_POST;
        Util::redirect(cfg('admin.path') . "/$key/" . ($id ?: 'new') . '?e=' . $errParam);
    }

    private static function saveEntity(string $key, array $e, int $id): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) Util::json(['error' => 'csrf'], 419);
        /*
         * Every editable column gets a rule — not only the required ones. Validator::make()
         * returns one key per rule, so a field left out of $rules would be written as NULL and
         * silently blank that column on save (and trip NOT NULL on sort_order).
         */
        $rules = [];
        foreach ($e['fields'] as $f) {
            $r = [];
            if (!empty($f['req'])) $r[] = 'required';
            if (($f['type'] ?? '') === 'int') $r[] = 'int';
            if (($f['type'] ?? '') === 'num') $r[] = 'num';
            if (($f['type'] ?? '') === 'select' && !empty($f['options'])) {
                /* options are either a literal [value, label] list or a callback that builds it (categories) */
                $opts = is_callable($f['options']) ? (array) ($f['options'])() : (array) $f['options'];
                if ($opts) $r[] = 'in:' . implode(',', array_map(static fn($o) => is_array($o) ? (string) $o[0] : (string) $o, $opts));
            }
            $rules[$f['name']] = $r;
        }
        [$errs, $clean] = Validator::make($_POST, $rules);
        if ($errs) self::flashFail($key, $id, implode(',', array_keys($errs)));
        foreach ($e['fields'] as $f) {
            if (!isset($clean[$f['name']])) continue;
            if ($f['type'] === 'int') $clean[$f['name']] = (int) ($clean[$f['name']] ?? 0);
            if ($f['type'] === 'num') $clean[$f['name']] = $clean[$f['name']] === null || $clean[$f['name']] === '' ? null : (float) $clean[$f['name']];
            if ($f['type'] === 'bool') $clean[$f['name']] = $clean[$f['name']] ? 1 : 0;
        }
        foreach ($e['fields'] as $f) if ($f['type'] === 'bool' && !isset($clean[$f['name']])) $clean[$f['name']] = 0;
        $i18nPost = (array) ($_POST['i18n'] ?? []);
        /* completeness gate: block publish when default lang incomplete (doc 06 §2) */
        $publishing = (int) ($clean['is_published'] ?? 0) === 1;
        $dl = cfg('default_lang');
        $missing = [];
        if ($publishing) {
            foreach ($e['i18n_fields'] as $f) {
                if (empty($f['req'])) continue;
                $v = trim((string) ($i18nPost[$dl][$f['name']] ?? ''));
                if ($v === '') $missing[] = $f['name'];
            }
            if ($missing) {
                self::flashFail($key, $id, 'incomplete:' . implode(',', $missing));
            }
        }
        $cols = array_map(fn($f) => $f['name'], $e['fields']);
        if ($id) {
            $sets = implode(',', array_map(fn($c) => "$c=?", $cols));
            Db::run('UPDATE ' . $e['table'] . " SET $sets WHERE id=$id", array_map(fn($c) => $clean[$c] ?? null, $cols));
        } else {
            $vals = array_map(fn($c) => $clean[$c] ?? null, $cols);
            Db::run('INSERT INTO ' . $e['table'] . '(' . implode(',', $cols) . ') VALUES(' . implode(',', array_fill(0, count($cols), '?')) . ')', $vals);
            $id = Db::lastId();
        }
        foreach (cfg('langs') as $l) {
            $row = (array) ($i18nPost[$l] ?? []);
            if (!array_filter($row, fn($v) => trim((string) $v) !== '')) continue;
            foreach ($e['i18n_fields'] as $f) {
                $v = $row[$f['name']] ?? '';
                if ($f['type'] === 'slug' && trim((string) $v) === '') {
                    $v = Slug::make((string) ($row[$f['from'] ?? ''] ?? 'item'), $l);
                }
                if ($f['type'] === 'slug') {
                    $v = Slug::make((string) $v, $l);
                    $v = Slug::unique($v, $l, fn($s, $ll) => (bool) Db::one('SELECT 1 FROM ' . $e['i18n'] . ' WHERE lang=? AND slug=? AND ' . $e['fk'] . '<>?', [$ll, $s, $id]));
                }
                if ($f['type'] === 'rows') $v = json_encode(array_values(array_filter(array_map('trim', (array) $v)), fn($x) => $x !== ''), JSON_UNESCAPED_UNICODE);
                $row[$f['name']] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
            }
            $icols = array_map(fn($f) => $f['name'], $e['i18n_fields']);
            $exists = Db::one('SELECT 1 FROM ' . $e['i18n'] . ' WHERE ' . $e['fk'] . '=? AND lang=?', [$id, $l]);
            if ($exists) {
                $sets = implode(',', array_map(fn($c) => "$c=?", $icols));
                Db::run('UPDATE ' . $e['i18n'] . " SET $sets WHERE {$e['fk']}=$id AND lang='$l'", array_map(fn($c) => $row[$c] ?? '', $icols));
            } else {
                Db::run('INSERT INTO ' . $e['i18n'] . '(' . $e['fk'] . ',lang,' . implode(',', $icols) . ') VALUES(' .
                    implode(',', array_fill(0, count($icols) + 2, '?')) . ')',
                    array_merge([$id, $l], array_map(fn($c) => $row[$c] ?? '', $icols)));
            }
        }
        Audit::log($id ? 'update' : 'create', $key, $id);
        self::afterWrite($key, $id);
        Util::redirect(cfg('admin.path') . "/$key/$id?saved=1");
    }

    /** invalidate caches + scoped static rebuild (doc 06 §7) */
    public static function afterWrite(string $key, int $id = 0): void
    {
        Cache::forgetAll();
        Settings::flush();
        Manifest::flush();
        $t0 = microtime(true);
        $scopes = [];
        if ($key === 'products') {
            $p = Db::one('SELECT * FROM products WHERE id=?', [$id]);
            if ($p) {
                $scopes[] = ['entity', 'product', $id];
                $scopes[] = ['listing', (int) $p['category_id']];
            } else {
                foreach (cfg('langs') as $l) {
                    $i = Db::one('SELECT slug FROM product_i18n WHERE product_id=? AND lang=?', [$id, $l]);
                    if ($i) $scopes[] = ['page', 'products/' . $i['slug']];
                }
            }
            $scopes[] = ['page', ''];
        } elseif ($key === 'categories') {
            $scopes[] = ['entity', 'category', $id];
            $scopes[] = ['page', 'categories']; $scopes[] = ['page', ''];
        } elseif ($key === 'posts') {
            $scopes[] = ['entity', 'post', $id];
            $scopes[] = ['page', 'blog']; $scopes[] = ['page', ''];
        } elseif ($key === 'services') {
            $scopes[] = ['entity', 'service', $id];
            $scopes[] = ['page', 'services'];
        } elseif ($key === 'faqs') {
            $scopes[] = ['page', 'faq']; $scopes[] = ['page', ''];
        } else {
            $scopes[] = ['full', null];
        }
        $scopes[] = ['sitemaps', null];
        try {
            $n = StaticBuilder::buildScope($scopes);
            Db::run('INSERT INTO build_jobs(scope,status,pages,ms,started_at,finished_at) VALUES(?,?,?,?,?,?)',
                [implode(',', array_map(fn($s) => $s[0], $scopes)), 'done', $n, (int) ((microtime(true) - $t0) * 1000), date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        } catch (\Throwable $ex) {
            Db::run('INSERT INTO build_jobs(scope,status,error,started_at,finished_at) VALUES(?,?,?,?,?)',
                [implode(',', array_map(fn($s) => $s[0], $scopes)), 'failed', $ex->getMessage(), date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        }
    }

    /* ---------------- blocks editor ---------------- */
    private static function pgBlocks(array $s): void
    {
        $zones = Db::all('SELECT DISTINCT zone FROM blocks ORDER BY zone');
        $zone = (string) ($_GET['zone'] ?? ($_POST['zone'] ?? ($zones[0]['zone'] ?? '')));
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::check($_POST['_csrf'] ?? null)) {
            $bid = (int) ($_POST['block_id'] ?? 0);
            $payload = (string) ($_POST['payload'] ?? '{}');
            json_decode($payload); if (json_last_error() !== JSON_ERROR_NONE) Util::redirect(cfg('admin.path') . '/blocks?zone=' . urlencode($zone) . '&e=json');
            foreach (cfg('langs') as $l) {
                $pl = (string) ($_POST['payload_' . $l] ?? $payload);
                json_decode($pl); if (json_last_error() !== JSON_ERROR_NONE) Util::redirect(cfg('admin.path') . '/blocks?zone=' . urlencode($zone) . '&e=json');
                $title = (string) ($_POST['title_' . $l] ?? '');
                $eyebrow = (string) ($_POST['eyebrow_' . $l] ?? '');
                if ($bid) {
                    Db::run('UPDATE block_i18n SET title=?, eyebrow=?, payload=? WHERE block_id=? AND lang=?', [$title, $eyebrow, $pl, $bid, $l]);
                }
            }
            if ($bid) Db::run('UPDATE blocks SET payload=? WHERE id=?', [$payload, $bid]);
            Audit::log('update', 'block', $bid);
            self::afterWrite('blocks');
            Util::redirect(cfg('admin.path') . '/blocks?zone=' . urlencode($zone) . '&saved=1');
        }
        $rows = Db::all('SELECT b.*, i.title, i.eyebrow, i.payload AS i18n_payload, i.lang FROM blocks b LEFT JOIN block_i18n i ON i.block_id=b.id WHERE b.zone=? ORDER BY b.sort_order, b.id, i.lang', [$zone]);
        $byId = [];
        foreach ($rows as $r) {
            $byId[$r['id']]['base'] = $byId[$r['id']]['base'] ?? $r;
            if (!empty($r['lang'])) $byId[$r['id']]['langs'][$r['lang']] = $r;
        }
        echo View::page('admin/blocks', ['zones' => $zones, 'zone' => $zone, 'blocks' => array_values($byId)], 'layouts/admin');
    }

    /* ---------------- media ---------------- */
    private static function pgMedia(array $s): void
    {
        $action = $s[1] ?? 'list';
        if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_csrf'] ?? null)) Util::json(['error' => 'csrf'], 419);
            $ids = [];
            foreach ((array) ($_FILES['files'] ?? []) ? self::normFiles($_FILES['files']) : [] as $f) {
                $ids[] = self::ingestUpload($f);
            }
            Audit::log('upload', 'media', $ids ? (int) $ids[0] : null);
            self::afterWrite('media');
            Util::json(['ok' => true, 'ids' => $ids]);
        }
        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_csrf'] ?? null)) Util::redirect(cfg('admin.path') . '/media?e=csrf');
            $mid = (int) ($_POST['media_id'] ?? 0);
            foreach (cfg('langs') as $l) {
                $alt = (string) ($_POST['alt_' . $l] ?? '');
                Db::upsert('media_i18n', ['media_id' => $mid, 'lang' => $l, 'alt' => $alt], ['media_id', 'lang']);
            }
            if (isset($_POST['focal_x'])) Db::run('UPDATE media SET focal_x=?, focal_y=? WHERE id=?', [(float) $_POST['focal_x'], (float) $_POST['focal_y'], $mid]);
            Audit::log('update', 'media', $mid);
            self::afterWrite('media');
            Util::redirect(cfg('admin.path') . '/media?saved=1');
        }
        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_csrf'] ?? null)) Util::json(['error' => 'csrf'], 419);
            $mid = (int) ($_POST['media_id'] ?? 0);
            if (Media::usage($mid)) Util::json(['error' => 'in_use'], 409);
            Db::run('DELETE FROM media WHERE id=?', [$mid]);
            Audit::log('delete', 'media', $mid);
            self::afterWrite('media');
            Util::json(['ok' => true]);
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $rows = Db::all('SELECT m.*, (SELECT alt FROM media_i18n i WHERE i.media_id=m.id AND i.lang=\'en\') alt FROM media m ' .
            ($q ? 'WHERE m.filename LIKE ? OR m.source_ref LIKE ?' : '') . ' ORDER BY m.id DESC LIMIT 200', $q ? ["%$q%", "%$q%"] : []);
        echo View::page('admin/media', ['rows' => $rows, 'q' => $q, 'edit' => (int) ($_GET['edit'] ?? 0)], 'layouts/admin');
    }

    private static function normFiles(array $f): array
    {
        $out = [];
        foreach ($f['name'] as $i => $n) {
            $out[] = ['name' => $n, 'tmp_name' => $f['tmp_name'][$i], 'size' => $f['size'][$i], 'error' => $f['error'][$i]];
        }
        return $out;
    }

    public static function ingestUpload(array $f): int
    {
        if ($f['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException('upload error ' . $f['error']);
        $info = @getimagesize($f['tmp_name']);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']);
        if (!$info || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new \RuntimeException('bad type');
        if ($f['size'] > 8 * 1024 * 1024) throw new \RuntimeException('too big');
        [$w, $h] = $info;
        $name = bin2hex(random_bytes(8)) . '.' . ($mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : 'webp'));
        $dir = nm_path('storage/tmp');
        move_uploaded_file($f['tmp_name'], $dir . '/' . $name) || rename($f['tmp_name'], $dir . '/' . $name);
        Db::run('INSERT INTO media(filename,mime,width,height,bytes,source_ref) VALUES(?,?,?,?,?,?)',
            [$name, $mime, $w, $h, (int) $f['size'], 'upload']);
        $id = Db::lastId();
        self::makeVariants($id, $dir . '/' . $name, 'misc');
        foreach (cfg('langs') as $l) Db::run('INSERT INTO media_i18n(media_id,lang,alt) VALUES(?,?,?)', [$id, $l, '']);
        return $id;
    }

    public static function makeVariants(int $mediaId, string $srcPath, string $group, ?string $slug = null): void
    {
        $src = Img::load($srcPath);
        if (!$src) return;
        $m = Media::row($mediaId);
        $slug ??= 'm' . $mediaId;
        $dirRel = "assets/media/$group";
        $dirAbs = cfg('paths.public') . "/$dirRel";
        if (!is_dir($dirAbs)) mkdir($dirAbs, 0775, true);
        $fmts = ['jpg' => 82, 'webp' => 78];
        if (Img::can('avif')) $fmts = ['avif' => 55] + $fmts;
        foreach (Img::WIDTHS as $w) {
            if ($w > $m['width']) continue;
            $h = (int) round($w * $m['height'] / $m['width']);
            $r = Img::resize($src, $w, $h);
            foreach ($fmts as $fmt => $q) {
                $path = "$group/$slug-$w.$fmt";
                $bytes = Img::save($r, $dirAbs . "/$slug-$w.$fmt", $fmt, $q);
                Db::upsert('media_variant', ['media_id' => $mediaId, 'fmt' => $fmt, 'w' => $w, 'path' => $path, 'bytes' => $bytes], ['media_id', 'fmt', 'w']);
            }
        }
        Cache::forgetGroup('data');
    }

    /* ---------------- enquiries ---------------- */
    private static function pgEnquiries(array $s): void
    {
        $id = (int) ($s[1] ?? 0);
        if ($id && $_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::check($_POST['_csrf'] ?? null)) {
            Db::run('UPDATE enquiries SET status=? WHERE id=?', [(string) $_POST['status'], $id]);
            Audit::log('status', 'enquiry', $id);
            Util::redirect(cfg('admin.path') . '/enquiries/' . $id);
        }
        if ($id) {
            $r = Db::one('SELECT * FROM enquiries WHERE id=?', [$id]);
            if ($r && $r['status'] === 'new') Db::run("UPDATE enquiries SET status='read' WHERE id=?", [$id]);
            echo View::page('admin/enquiry', ['r' => $r], 'layouts/admin');
            return;
        }
        if (($s[1] ?? '') === 'export') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="enquiries.csv"');
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'created', 'lang', 'status', 'name', 'email', 'phone', 'company', 'country', 'subject', 'product', 'message']);
            foreach (Db::all('SELECT * FROM enquiries ORDER BY id DESC') as $r) {
                fputcsv($out, [$r['id'], $r['created_at'], $r['lang'], $r['status'], $r['full_name'], $r['email'], $r['phone'], $r['company'], $r['country'], $r['subject'], $r['product_interest'], $r['message']]);
            }
            fclose($out);
            exit;
        }
        $status = (string) ($_GET['status'] ?? '');
        /* D-10: the inbox shows real leads by default; flagged rows live behind the Spam tab */
        $where = $status !== '' ? 'WHERE status=?' : "WHERE status<>'spam'";
        $rows = Db::all("SELECT * FROM enquiries $where ORDER BY created_at DESC LIMIT 300", $status !== '' ? [$status] : []);
        $spamCount = (int) Db::val("SELECT COUNT(*) FROM enquiries WHERE status='spam'");
        echo View::page('admin/enquiries', ['rows' => $rows, 'status' => $status, 'spamCount' => $spamCount], 'layouts/admin');
    }

    /* ---------------- seo ---------------- */
    private static function pgSeo(array $s): void
    {
        $sub = $s[1] ?? 'index';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::check($_POST['_csrf'] ?? null)) {
            if ($sub === 'globals') {
                foreach (cfg('langs') as $l) {
                    foreach (['seo.title_tpl', 'seo.desc_tpl', 'seo.robots'] as $k) {
                        Settings::set($k, (string) ($_POST[$k . '_' . $l] ?? ''), $l);
                    }
                }
                Audit::log('update', 'seo_globals');
                self::afterWrite('seo');
                Util::redirect(cfg('admin.path') . '/seo/globals?saved=1');
            }
            if ($sub === 'redirects') {
                $from = (string) ($_POST['from_path'] ?? ''); $to = (string) ($_POST['to_path'] ?? '');
                if ($from && $to) {
                    if ($from === $to || self::redirectLoop($from, $to)) Util::redirect(cfg('admin.path') . '/seo/redirects?e=loop');
                    Db::upsert('redirects', ['from_path' => $from, 'to_path' => $to, 'code' => (int) ($_POST['code'] ?? 301)], ['from_path']);
                    Audit::log('create', 'redirect');
                }
                if (!empty($_POST['delete_id'])) Db::run('DELETE FROM redirects WHERE id=?', [(int) $_POST['delete_id']]);
                Util::redirect(cfg('admin.path') . '/seo/redirects?saved=1');
            }
            if ($sub === 'sitemap') { StaticBuilder::sitemaps(); StaticBuilder::robots(); Util::redirect(cfg('admin.path') . '/seo?saved=1'); }
            if ($sub === 'save') {
                $type = (string) $_POST['entity_type']; $eid = (int) $_POST['entity_id']; $lang = (string) $_POST['lang'];
                Db::upsert('seo_meta', ['entity_type' => $type, 'entity_id' => $eid, 'lang' => $lang,
                    'title' => (string) ($_POST['title'] ?? ''), 'description' => (string) ($_POST['description'] ?? ''),
                    'canonical_override' => (string) ($_POST['canonical_override'] ?? '') ?: null,
                    'robots' => (string) ($_POST['robots'] ?? '') ?: null], ['entity_type', 'entity_id', 'lang']);
                Audit::log('update', 'seo', $eid);
                self::afterWrite('seo');
                Util::redirect(cfg('admin.path') . '/seo?saved=1');
            }
        }
        if ($sub === 'globals') { echo View::page('admin/seo-globals', [], 'layouts/admin'); return; }
        if ($sub === 'redirects') { echo View::page('admin/seo-redirects', ['rows' => Db::all('SELECT * FROM redirects ORDER BY id DESC'), 'e' => $_GET['e'] ?? null], 'layouts/admin'); return; }
        echo View::page('admin/seo', ['board' => self::completenessBoard(), 'missing' => self::seoMissing()], 'layouts/admin');
    }
    private static function redirectLoop(string $from, string $to): bool
    {
        $seen = [$from]; $cur = $to;
        for ($i = 0; $i < 10; $i++) {
            $r = Db::one('SELECT to_path FROM redirects WHERE from_path=?', [$cur]);
            if (!$r) return false;
            if (in_array($r['to_path'], $seen, true) || $r['to_path'] === $from) return true;
            $seen[] = $cur; $cur = $r['to_path'];
        }
        return true;
    }
    public static function seoMissing(): array
    {
        $out = [];
        $ents = self::entities();
        foreach (['category' => 'categories', 'product' => 'products', 'service' => 'services', 'post' => 'posts'] as $type => $key) {
            $e = $ents[$key] ?? null;
            if (!$e) continue;
            foreach (Db::all('SELECT id FROM ' . $e['table']) as $r) {
                foreach (cfg('langs') as $l) {
                    $i = Db::one('SELECT meta_title, meta_description FROM ' . $e['i18n'] .
                        ' WHERE ' . $e['fk'] . '=? AND lang=?', [$r['id'], $l]);
                    $o = Db::one('SELECT title, description FROM seo_meta WHERE entity_type=? AND entity_id=? AND lang=?', [$type, $r['id'], $l]);
                    $t = ($o['title'] ?? '') ?: ($i['meta_title'] ?? '');
                    $d = ($o['description'] ?? '') ?: ($i['meta_description'] ?? '');
                    if (!$t || !$d) $out[] = [$type, (int) $r['id'], $l];
                }
            }
        }
        return $out;
    }

    /* ---------------- settings / users / audit / system ---------------- */
    private static function pgSettings(array $s): void
    {
        $sub = $s[1] ?? 'contact';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::check($_POST['_csrf'] ?? null)) {
            if ($sub === 'contact') {
                foreach (['contact.email' => 'email', 'contact.phone' => '', 'contact.whatsapp' => '', 'social.instagram' => 'url', 'social.facebook' => 'url', 'hours' => '', 'address' => '', 'quote_promise' => ''] as $k => $rule) {
                    $v = (string) ($_POST[$k] ?? '');
                    if ($rule === 'email' && $v && !filter_var($v, FILTER_VALIDATE_EMAIL)) Util::redirect(cfg('admin.path') . '/settings/contact?e=email');
                    if ($rule === 'url' && $v && !filter_var($v, FILTER_VALIDATE_URL)) Util::redirect(cfg('admin.path') . '/settings/contact?e=url');
                    Settings::set($k, $v);
                }
                foreach (cfg('langs') as $l) Settings::set('wa.prefill', (string) ($_POST['wa_prefill_' . $l] ?? ''), $l);
                Audit::log('update', 'settings');
                self::afterWrite('settings');
                Util::redirect(cfg('admin.path') . '/settings/contact?saved=1');
            }
            if ($sub === 'languages') {
                foreach ((array) ($_POST['glossary'] ?? []) as $row) {
                    if (!trim((string) ($row['en'] ?? ''))) continue;
                    Settings::set('glossary.' . Slug::make((string) $row['en']), json_encode($row, JSON_UNESCAPED_UNICODE));
                }
                Audit::log('update', 'glossary');
                Util::redirect(cfg('admin.path') . '/settings/languages?saved=1');
            }
            if ($sub === 'system') {
                if (!empty($_POST['maintenance'])) Settings::set('sys.maintenance', (string) $_POST['maintenance']);
                if (!empty($_POST['purge'])) { Cache::forgetAll(); }
                if (!empty($_POST['backup'])) self::backup();
                if (!empty($_POST['rebuild'])) { $t0 = microtime(true); $n = StaticBuilder::buildAll();
                    Db::run('INSERT INTO build_jobs(scope,status,pages,ms,started_at,finished_at) VALUES(?,?,?,?,?,?)',
                        ['full', 'done', $n, (int) ((microtime(true) - $t0) * 1000), date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]); }
                Audit::log('system', 'settings');
                Util::redirect(cfg('admin.path') . '/settings/system?saved=1');
            }
        }
        if ($sub === 'languages') { echo View::page('admin/settings-langs', [], 'layouts/admin'); return; }
        if ($sub === 'system') {
            echo View::page('admin/settings-system', ['jobs' => Db::all('SELECT * FROM build_jobs ORDER BY id DESC LIMIT 15')], 'layouts/admin');
            return;
        }
        echo View::page('admin/settings-contact', [], 'layouts/admin');
    }

    public static function backup(): string
    {
        $f = nm_path('storage/backups/db-' . date('Ymd-His') . '.json');
        $data = [];
        foreach (array_keys(Schema::tables()) as $t) $data[$t] = Db::all("SELECT * FROM $t");
        file_put_contents($f, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $f;
    }

    private static function pgUsers(array $s): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::check($_POST['_csrf'] ?? null)) {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            if (!empty($_POST['delete_id'])) { Db::run('DELETE FROM users WHERE id=? AND role<>\'owner\'', [(int) $_POST['delete_id']]); Audit::log('delete', 'user', (int) $_POST['delete_id']); }
            elseif ($email && !empty($_POST['password'])) {
                /* D-11: the seed password is never a valid new credential */
                if (hash_equals(Auth::SEED_PASSWORD, (string) $_POST['password'])) {
                    Util::redirect(cfg('admin.path') . '/users?e=seedpw');
                }
                Db::upsert('users', ['email' => $email, 'password_hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
                    'full_name' => (string) ($_POST['full_name'] ?? ''), 'role' => (string) ($_POST['role'] ?? 'editor')], ['email']);
                Audit::log('save', 'user');
            }
            Util::redirect(cfg('admin.path') . '/users?saved=1');
        }
        echo View::page('admin/users', ['rows' => Db::all('SELECT id,email,full_name,role,status,last_login_at FROM users ORDER BY id')], 'layouts/admin');
    }

    private static function pgAudit(array $s): void
    {
        echo View::page('admin/audit', ['rows' => Db::all('SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 300')], 'layouts/admin');
    }
}
