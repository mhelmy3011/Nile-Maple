<?php
namespace Nm;

/** Dynamic endpoints: enquiry, events, load-more fragment (doc 04 §2). */
final class ApiController
{
    public static function handle(string $path): void
    {
        $path = trim($path, '/');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === 'enquiry') self::enquiry();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === 'event') self::event();
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $path === 'more') self::more();
        /* live CSRF token for cached HTML (see Nm\Csrf): sets the visit cookie + returns a token */
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $path === 'csrf') {
            header('Cache-Control: no-store');
            Util::json(['token' => Csrf::issue()]);
        }
        Util::json(['error' => 'not_found'], 404);
    }

    private static function enquiry(): void
    {
        $lang = in_array($_POST['lang'] ?? 'en', cfg('langs'), true) ? $_POST['lang'] : 'en';
        I18n::boot($lang);
        /* spam gates (doc 04 §7): honeypot + time-trap + rate limit.
           D-10: a tripped honeypot/time-trap no longer DESTROYS the submission — it is persisted
           as status='spam' with the trip reason (a fast autofill user can genuinely hit the 3 s
           time-trap, and a B2B lead is worth more than the review costs). The response is still
           the fake success so bots learn nothing. No mail is sent for spam rows. */
        $spamReason = null;
        if (!empty($_POST['website'])) $spamReason = 'honeypot';
        elseif ((int) ($_POST['_t'] ?? 0) > time() - 3) $spamReason = 'time-trap';
        if ($spamReason !== null) { self::persistSpam($lang, $spamReason); Util::json(['ok' => true, 'message' => I18n::t('form.success')]); }
        if (!RateLimit::hit('enq-' . Util::ipHash(), 5, 600)) Util::json(['error' => I18n::t('form.rate')], 429);
        if (!Csrf::check($_POST['_csrf'] ?? null)) Util::json(['error' => I18n::t('form.csrf')], 419);

        [$errs, $c] = Validator::make($_POST, [
            'full_name' => ['required', 'max:120'],
            'email'     => ['required', 'email', 'max:190'],
            'phone'     => ['max:40'],
            'company'   => ['max:160'],
            'country'   => ['max:90'],
            'subject'   => ['required', 'max:190'],
            'product_interest' => ['max:190'],
            'message'   => ['required', 'min:10', 'max:4000'],
            'consent'   => ['required', 'checked'],
        ]);
        /* the error list, not a key count: a field that failed a rule is simply absent from $c,
           and counting would wave it through into a NOT NULL violation. */
        if ($errs) Util::json(['error' => I18n::t('form.invalid'), 'fields' => $errs], 422);
        $id = Db::run('INSERT INTO enquiries(full_name,email,phone,company,country,subject,product_interest,message,consent,lang,ip_hash,ua)
                       VALUES(?,?,?,?,?,?,?,?,?,?,?,?)', [
            $c['full_name'], $c['email'], $c['phone'], $c['company'], $c['country'], $c['subject'],
            $c['product_interest'], $c['message'], 1, $lang, Util::ipHash(), Util::ua(),
        ])->rowCount() ? Db::lastId() : 0;

        $mailed = self::deliverEnquiry($c, $lang);
        Db::run('UPDATE enquiries SET mailed=? WHERE id=?', [$mailed ? 1 : 0, $id]);
        Audit::log('enquiry.submit', 'enquiry', $id);
        Util::json(['ok' => true, 'message' => I18n::t('form.success')]);
    }

    /** D-10: keep a gate-flagged lead on file — flagged, unmailed, reviewable in the Spam tab. */
    private static function persistSpam(string $lang, string $reason): void
    {
        try {
            $v = static fn(string $k, int $max) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
            Db::run('INSERT INTO enquiries(full_name,email,phone,company,country,subject,product_interest,message,consent,lang,ip_hash,ua,status,spam_reason)
                     VALUES(?,?,?,?,?,?,?,?,0,?,?,?,?,?)', [
                $v('full_name', 120) ?: '(none)', $v('email', 190) ?: '(none)', $v('phone', 40),
                $v('company', 160), $v('country', 90), $v('subject', 190) ?: '(spam)', $v('product_interest', 190),
                $v('message', 4000) ?: '(empty)', $lang, Util::ipHash(), Util::ua(), 'spam', $reason,
            ]);
            Audit::log('enquiry.spam', 'enquiry', Db::lastId());
        } catch (\Throwable $e) {
            error_log('[nm] persistSpam failed: ' . $e->getMessage());
        }
    }

    public static function deliverEnquiry(array $c, string $lang): bool
    {
        I18n::boot($lang);
        $rows = '';
        foreach (['full_name' => I18n::t('form.name'), 'email' => I18n::t('form.email'), 'phone' => I18n::t('form.phone'),
                  'company' => I18n::t('form.company'), 'country' => I18n::t('form.country'), 'subject' => I18n::t('form.subject'),
                  'product_interest' => I18n::t('form.product')] as $k => $label) {
            if (!empty($c[$k])) $rows .= '<tr><td style="padding:6px 12px;color:#556457">' . htmlspecialchars($label) . '</td><td style="padding:6px 12px"><strong>' . htmlspecialchars((string) $c[$k]) . '</strong></td></tr>';
        }
        $html = '<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto;border:1px solid #dde4dd;border-radius:14px;overflow:hidden">'
            . '<div style="background:#16382b;color:#fff;padding:16px 20px"><strong>Nile-Maple</strong> — ' . htmlspecialchars(I18n::t('mail.enquiry')) . ' (' . strtoupper($lang) . ')</div>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px">' . $rows
            . '<tr><td style="padding:6px 12px;color:#556457;vertical-align:top">' . htmlspecialchars(I18n::t('form.message')) . '</td><td style="padding:6px 12px">' . nl2br(htmlspecialchars((string) $c['message'])) . '</td></tr></table>'
            . '<div style="padding:12px 20px;background:#eafbf1;font-size:12px;color:#0b542e">' . htmlspecialchars(I18n::t('mail.footer')) . '</div></div>';
        $text = I18n::t('mail.enquiry') . "\n" . implode("\n", array_filter([$c['full_name'], $c['email'], $c['phone'], $c['company'], $c['country'], $c['subject'], $c['product_interest']])) . "\n\n" . $c['message'];
        return Mailer::send(cfg('mail.to'), '[' . strtoupper($lang) . '] ' . I18n::t('mail.enquiry') . ': ' . $c['subject'], $html, $text, $c['email']);
    }

    private static function event(): void
    {
        $name = preg_replace('/[^a-z0-9_.-]/', '', strtolower((string) ($_POST['name'] ?? '')));
        if (!$name || !RateLimit::hit('ev-' . Util::ipHash(), 120, 3600)) Util::json(['ok' => true]);
        Db::run('INSERT INTO events(name,lang,path,ip_hash) VALUES(?,?,?,?)',
            [$name, $_POST['lang'] ?? 'en', mb_substr((string) ($_POST['path'] ?? ''), 0, 250), Util::ipHash()]);
        Util::json(['ok' => true]);
    }

    private static function more(): void
    {
        $lang = in_array($_GET['lang'] ?? 'en', cfg('langs'), true) ? $_GET['lang'] : 'en';
        I18n::boot($lang);
        $slug = (string) ($_GET['cat'] ?? '');
        $page = max(2, (int) ($_GET['page'] ?? 2));
        $q = trim((string) ($_GET['q'] ?? ''));
        $c = Content::categoryBySlug($lang, $slug);
        if (!$c) Util::json(['error' => 'nf'], 404);
        $key = "more|$lang|$slug|$page|$q";
        $html = Cache::remember('frag', $key, static function () use ($lang, $c, $page, $q) {
            $rows = Content::products($lang, (int) $c['id'], $page, $q);
            return View::render('ui/product-grid', ['products' => $rows, 'lang' => $lang]);
        }, 300);
        $total = Content::productCount($lang, (int) $c['id'], $q);
        $pages = (int) ceil($total / Content::PER_PAGE);
        header('Content-Type: text/html; charset=utf-8');
        header('X-NM-Pages: ' . $pages);
        echo $html;
        exit;
    }
}
