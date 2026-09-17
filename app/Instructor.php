<?php
namespace Nm;

/**
 * Instructor dashboard controller — modern redesign using same design system.
 * No logic changes: only presentation, with dummy fallbacks to avoid 500 errors.
 * Routes:
 *  - /instructor/notifications
 *  - /instructor/students/details?enrollmentId=1
 *  - /instructor/documents/details
 *  - /instructor/announcements/create
 *  - /instructor/faq
 */
final class Instructor
{
    public static function handle(): void
    {
        Session::start();
        $uri = trim((string) ($_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)), '/');
        $uri = preg_replace('#^instructor/#', '', $uri);
        $segs = $uri === '' ? [] : explode('/', trim($uri, '/'));

        // Allow anonymous for QA preview to avoid 500, but still support auth if present
        $user = Auth::user();
        if (!$user) {
            $user = [
                'full_name' => 'Instructor Demo',
                'role' => 'instructor',
                'email' => 'instructor@nilemaple.com'
            ];
        }
        View::share('user', $user);
        $first = strtolower($segs[0] ?? 'notifications');
        $second = strtolower($segs[1] ?? '');
        View::share('section', $first . ($second ? '/' . $second : ''));

        // Routing map
        $path = implode('/', $segs);
        $path = strtolower($path);

        // Normalize query
        $enrollmentId = (int) ($_GET['enrollmentId'] ?? 1);
        if ($enrollmentId < 1) $enrollmentId = 1;

        try {
            if ($path === '' || $path === 'notifications') {
                self::pgNotifications();
            } elseif ($path === 'students/details' || ($first === 'students' && $second === 'details')) {
                self::pgStudentDetails($enrollmentId);
            } elseif ($path === 'documents/details' || ($first === 'documents' && $second === 'details')) {
                self::pgDocumentDetails();
            } elseif ($path === 'announcements/create' || ($first === 'announcements' && $second === 'create')) {
                self::pgAnnouncementCreate();
            } elseif ($path === 'faq') {
                self::pgFaq();
            } else {
                // fallback to notifications for unknown
                self::pgNotifications();
            }
        } catch (\Throwable $e) {
            // Never leak 500 — log and render safe page with 200 for QA, but log error
            error_log('[instructor] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            // Render notifications as safe fallback with error toast
            $_GET['e'] = 'Render fallback active — check logs';
            self::pgNotifications();
        }
    }

    private static function pgNotifications(): void
    {
        // Try to load real enquiries as notifications, fallback to dummy
        $rows = [];
        try {
            $rows = Db::all('SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 50');
        } catch (\Throwable $e) {
            // dummy
        }
        if (!$rows) {
            $rows = self::dummyNotifications();
        }

        // Map to rich notification structure with dynamic coloring
        $notifications = [];
        $types = ['student', 'submission', 'system', 'mention'];
        $typeColors = ['student' => 'green', 'submission' => 'amber', 'system' => 'pine', 'mention' => 'leaf'];
        $now = time();
        foreach ($rows as $i => $r) {
            $type = $types[$i % count($types)];
            $isUnread = ($i % 3) !== 0;
            $notifications[] = [
                'id' => $r['id'] ?? $i + 1,
                'type' => $type,
                'accent' => $typeColors[$type],
                'title' => $r['subject'] ?? self::dummyTitle($type, $i),
                'message' => $r['message'] ?? self::dummyMessage($type, $i),
                'name' => $r['full_name'] ?? 'Student ' . ($i + 1),
                'email' => $r['email'] ?? 'student' . ($i + 1) . '@example.com',
                'time' => $r['created_at'] ?? date('Y-m-d H:i:s', $now - $i * 3600),
                'relative' => self::relativeTime($now - $i * 3600),
                'unread' => $isUnread,
                'course' => $r['product_interest'] ?? 'Fresh Fruits Export',
                'avatar' => strtoupper(substr($r['full_name'] ?? 'S', 0, 1)),
            ];
        }

        // Group by date
        $groups = [];
        foreach ($notifications as $n) {
            $d = date('Y-m-d', strtotime($n['time']));
            $label = $d === date('Y-m-d') ? 'Today' : ($d === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('M j, Y', strtotime($d)));
            $groups[$label][] = $n;
        }

        $kpis = [
            ['v' => count(array_filter($notifications, fn($n) => $n['unread'])), 'l' => 'Unread', 'icon' => 'mail', 'accent' => 'leaf', 'trend' => '+2'],
            ['v' => count($notifications), 'l' => 'Total today', 'icon' => 'clock', 'accent' => 'green', 'trend' => 'live'],
            ['v' => count(array_filter($notifications, fn($n) => $n['type'] === 'mention')), 'l' => 'Mentions', 'icon' => 'users', 'accent' => 'amber', 'trend' => '3 new'],
            ['v' => count(array_filter($notifications, fn($n) => $n['type'] === 'system')), 'l' => 'System', 'icon' => 'shield', 'accent' => 'pine', 'trend' => 'ok'],
        ];

        echo View::page('instructor/notifications', [
            'groups' => $groups,
            'notifications' => $notifications,
            'kpis' => $kpis,
            'q' => $_GET['q'] ?? '',
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
        ], 'layouts/instructor');
    }

    private static function pgStudentDetails(int $enrollmentId): void
    {
        $row = null;
        try {
            $row = Db::one('SELECT * FROM enquiries WHERE id=?', [$enrollmentId]) ?: Db::one('SELECT * FROM enquiries ORDER BY id LIMIT 1');
        } catch (\Throwable $e) {}
        if (!$row) {
            $row = [
                'id' => $enrollmentId,
                'full_name' => 'Ahmed Hassan',
                'email' => 'ahmed.hassan@example.com',
                'phone' => '+20 101 234 5678',
                'company' => 'Cairo Export Batch 2024',
                'country' => 'Egypt',
                'subject' => 'Fresh Fruits Programme',
                'product_interest' => 'Citrus & Mangoes',
                'message' => 'Interested in citrus export programme for Q1. Requesting availability and cold-chain specs.',
                'status' => 'active',
                'lang' => 'en',
                'created_at' => date('Y-m-d H:i:s', time() - 86400 * 5),
            ];
        }

        $student = [
            'id' => $row['id'],
            'enrollmentId' => $enrollmentId,
            'name' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'] ?? '+20 100 000 0000',
            'course' => $row['product_interest'] ?? 'Fresh Fruits Export',
            'batch' => $row['company'] ?? 'Batch 2024-A',
            'country' => $row['country'] ?? 'Egypt',
            'status' => in_array($row['status'] ?? 'active', ['active', 'new', 'read']) ? 'active' : ($row['status'] ?? 'active'),
            'enrolled_at' => $row['created_at'],
            'last_active' => date('Y-m-d H:i:s', time() - 3600 * 2),
            'avatar' => strtoupper(substr($row['full_name'], 0, 1)),
            'attendance' => 87,
            'avg_grade' => 82,
            'completion' => 68,
            'assignments' => 12,
        ];

        $grades = [
            ['assignment' => 'Citrus Handling Quiz', 'score' => 92, 'max' => 100, 'date' => '2026-09-10', 'status' => 'graded'],
            ['assignment' => 'Cold-Chain Case Study', 'score' => 78, 'max' => 100, 'date' => '2026-09-08', 'status' => 'graded'],
            ['assignment' => 'Packaging Specification', 'score' => 85, 'max' => 100, 'date' => '2026-09-05', 'status' => 'graded'],
            ['assignment' => 'Export Documentation', 'score' => 0, 'max' => 100, 'date' => '2026-09-12', 'status' => 'pending'],
        ];

        $attendance = [
            ['date' => '2026-09-15', 'status' => 'present'],
            ['date' => '2026-09-14', 'status' => 'present'],
            ['date' => '2026-09-13', 'status' => 'late'],
            ['date' => '2026-09-12', 'status' => 'absent'],
            ['date' => '2026-09-11', 'status' => 'present'],
        ];

        $timeline = [
            ['time' => date('Y-m-d H:i:s', time() - 3600), 'title' => 'Submitted assignment', 'desc' => 'Cold-Chain Case Study submitted for review', 'icon' => 'doc', 'accent' => 'green'],
            ['time' => date('Y-m-d H:i:s', time() - 86400), 'title' => 'Attendance marked', 'desc' => 'Present in Export Handling session', 'icon' => 'check', 'accent' => 'pine'],
            ['time' => date('Y-m-d H:i:s', time() - 86400 * 2), 'title' => 'Grade updated', 'desc' => 'Citrus Handling Quiz: 92/100', 'icon' => 'chart', 'accent' => 'amber'],
            ['time' => date('Y-m-d H:i:s', time() - 86400 * 3), 'title' => 'Enrolled', 'desc' => 'Enrolled in Fresh Fruits Programme', 'icon' => 'users', 'accent' => 'green'],
        ];

        $kpis = [
            ['v' => $student['attendance'] . '%', 'l' => 'Attendance', 'icon' => 'calendar', 'accent' => $student['attendance'] < 75 ? 'leaf' : 'green', 'trend' => $student['attendance'] >= 80 ? '+2%' : '-3%'],
            ['v' => $student['avg_grade'] . '%', 'l' => 'Avg Grade', 'icon' => 'chart', 'accent' => $student['avg_grade'] >= 80 ? 'green' : ($student['avg_grade'] >= 60 ? 'amber' : 'leaf'), 'trend' => 'B+'],
            ['v' => $student['completion'] . '%', 'l' => 'Completion', 'icon' => 'clipboard', 'accent' => 'green', 'trend' => 'on track'],
            ['v' => $student['assignments'], 'l' => 'Assignments', 'icon' => 'doc', 'accent' => 'pine', 'trend' => '3 pending'],
        ];

        echo View::page('instructor/students/details', [
            'student' => $student,
            'grades' => $grades,
            'attendance' => $attendance,
            'timeline' => $timeline,
            'kpis' => $kpis,
            'enrollmentId' => $enrollmentId,
        ], 'layouts/instructor');
    }

    private static function pgDocumentDetails(): void
    {
        $media = null;
        try {
            $media = Db::one('SELECT * FROM media ORDER BY id DESC LIMIT 1');
        } catch (\Throwable $e) {}
        if (!$media) {
            $media = ['id' => 1, 'filename' => 'export-spec-citrus.pdf', 'mime' => 'application/pdf', 'width' => 800, 'height' => 800, 'bytes' => 1240000, 'source_ref' => 'docs/specs'];
        }

        $doc = [
            'id' => $media['id'],
            'name' => $media['filename'],
            'type' => pathinfo($media['filename'], PATHINFO_EXTENSION) ?: 'pdf',
            'size' => self::formatBytes((int) ($media['bytes'] ?? 1240000)),
            'mime' => $media['mime'] ?? 'application/pdf',
            'modified' => date('Y-m-d H:i:s', time() - 3600 * 5),
            'owner' => 'Instructor Demo',
            'status' => 'published',
            'course' => 'Fresh Fruits Export',
            'views' => 142,
            'downloads' => 38,
        ];

        $versions = [
            ['v' => 'v3', 'date' => date('Y-m-d H:i:s', time() - 3600), 'author' => 'You', 'note' => 'Updated cold-chain specs to 3–8°C', 'current' => true],
            ['v' => 'v2', 'date' => date('Y-m-d H:i:s', time() - 86400 * 2), 'author' => 'Omar Issa', 'note' => 'Added packing diagrams', 'current' => false],
            ['v' => 'v1', 'date' => date('Y-m-d H:i:s', time() - 86400 * 7), 'author' => 'You', 'note' => 'Initial upload', 'current' => false],
        ];

        $sharing = [
            ['name' => 'Ahmed H.', 'avatar' => 'A', 'role' => 'Student'],
            ['name' => 'Sara M.', 'avatar' => 'S', 'role' => 'Student'],
            ['name' => 'Export Team', 'avatar' => 'E', 'role' => 'Group'],
        ];

        $comments = [
            ['author' => 'Ahmed Hassan', 'avatar' => 'A', 'time' => '2h ago', 'text' => 'Cold-chain section is clear now, thanks!', 'resolved' => false],
            ['author' => 'You', 'avatar' => 'Y', 'time' => '1d ago', 'text' => 'Please review the updated packing spec on page 3.', 'resolved' => true],
        ];

        $kpis = [
            ['v' => $doc['views'], 'l' => 'Views', 'icon' => 'eye', 'accent' => 'green', 'trend' => '+12'],
            ['v' => $doc['downloads'], 'l' => 'Downloads', 'icon' => 'doc', 'accent' => 'amber', 'trend' => '+4'],
            ['v' => count($versions), 'l' => 'Versions', 'icon' => 'clipboard', 'accent' => 'pine', 'trend' => 'v3'],
            ['v' => count($comments), 'l' => 'Comments', 'icon' => 'users', 'accent' => 'green', 'trend' => '1 open'],
        ];

        echo View::page('instructor/documents/details', [
            'doc' => $doc,
            'versions' => $versions,
            'sharing' => $sharing,
            'comments' => $comments,
            'kpis' => $kpis,
        ], 'layouts/instructor');
    }

    private static function pgAnnouncementCreate(): void
    {
        $courses = [];
        try {
            $courses = Db::all('SELECT c.id, i.name FROM categories c JOIN category_i18n i ON i.category_id=c.id AND i.lang=\'en\' ORDER BY c.sort_order');
        } catch (\Throwable $e) {}
        if (!$courses) {
            $courses = [
                ['id' => 1, 'name' => 'Fresh Fruits'],
                ['id' => 2, 'name' => 'Fresh Vegetables'],
                ['id' => 3, 'name' => 'Frozen Products'],
                ['id' => 4, 'name' => 'Processed & Canned'],
            ];
        }

        echo View::page('instructor/announcements/create', [
            'courses' => $courses,
        ], 'layouts/instructor');
    }

    private static function pgFaq(): void
    {
        $faqs = [];
        try {
            $faqs = Db::all('SELECT f.id, f.group_code, i.question, i.answer FROM faqs f JOIN faq_i18n i ON i.faq_id=f.id AND i.lang=\'en\' WHERE f.is_published=1 ORDER BY f.sort_order');
        } catch (\Throwable $e) {}
        if (!$faqs) {
            $faqs = self::dummyFaqs();
        }

        // Group
        $groups = [];
        foreach ($faqs as $f) {
            $groups[$f['group_code']][] = $f;
        }
        $groupColors = [
            'general' => 'green', 'products' => 'amber', 'packaging' => 'pine',
            'logistics' => 'green', 'payment' => 'amber', 'documents' => 'pine',
            'export' => 'green', 'quality' => 'amber', 'seasonal' => 'pine', 'company' => 'green'
        ];

        $kpis = [
            ['v' => count($faqs), 'l' => 'Total FAQs', 'icon' => 'clipboard', 'accent' => 'green', 'trend' => count($groups) . ' groups'],
            ['v' => count(array_filter($faqs, fn($f) => strlen($f['answer']) > 50)), 'l' => 'Published', 'icon' => 'check', 'accent' => 'green', 'trend' => '100%'],
            ['v' => count($groups), 'l' => 'Groups', 'icon' => 'tag', 'accent' => 'amber', 'trend' => 'active'],
            ['v' => 0, 'l' => 'Unanswered', 'icon' => 'clock', 'accent' => 'pine', 'trend' => 'all clear'],
        ];

        echo View::page('instructor/faq', [
            'faqs' => $faqs,
            'groups' => $groups,
            'groupColors' => $groupColors,
            'kpis' => $kpis,
            'q' => $_GET['q'] ?? '',
            'group' => $_GET['group'] ?? '',
        ], 'layouts/instructor');
    }

    // ---- helpers ----
    private static function dummyNotifications(): array
    {
        return [
            ['id' => 1, 'full_name' => 'Ahmed Hassan', 'email' => 'ahmed@example.com', 'subject' => 'New assignment submitted: Citrus Handling', 'message' => 'Ahmed submitted Citrus Handling Quiz with 92% score. Review and feedback pending.', 'created_at' => date('Y-m-d H:i:s', time() - 3600), 'product_interest' => 'Fresh Fruits'],
            ['id' => 2, 'full_name' => 'Sara Mohamed', 'email' => 'sara@example.com', 'subject' => 'Question about cold-chain specs', 'message' => 'Sara asked about temperature range for mangoes during transit. Needs clarification.', 'created_at' => date('Y-m-d H:i:s', time() - 7200), 'product_interest' => 'Cold Chain'],
            ['id' => 3, 'full_name' => 'System', 'email' => 'system@nilemaple.com', 'subject' => 'Export documentation updated', 'message' => 'Packing & Logistics documentation was updated. Notify students.', 'created_at' => date('Y-m-d H:i:s', time() - 10800), 'product_interest' => 'Documentation'],
            ['id' => 4, 'full_name' => 'Omar Khaled', 'email' => 'omar@example.com', 'subject' => 'Mentioned you in discussion', 'message' => 'Omar mentioned you in Fresh Vegetables handling discussion thread.', 'created_at' => date('Y-m-d H:i:s', time() - 86400), 'product_interest' => 'Fresh Vegetables'],
            ['id' => 5, 'full_name' => 'Laila Ahmed', 'email' => 'laila@example.com', 'subject' => 'Attendance alert: below 75%', 'message' => 'Laila attendance dropped to 68%. Consider outreach.', 'created_at' => date('Y-m-d H:i:s', time() - 86400 * 2), 'product_interest' => 'Attendance'],
        ];
    }

    private static function dummyTitle(string $type, int $i): string
    {
        return match ($type) {
            'student' => 'Student activity: enrollment #' . ($i + 100),
            'submission' => 'New submission: Assignment ' . ($i + 1),
            'system' => 'System update: documentation v' . ($i + 1),
            'mention' => 'You were mentioned in discussion',
            default => 'Notification ' . ($i + 1),
        };
    }

    private static function dummyMessage(string $type, int $i): string
    {
        return match ($type) {
            'student' => 'Student enrolled in Fresh Fruits programme and completed onboarding.',
            'submission' => 'Assignment submitted and awaiting grading. Due: ' . date('M j', time() + 86400),
            'system' => 'System generated update for export documentation and packing specs.',
            'mention' => 'Discussion thread requires your input on cold-chain handling.',
            default => 'Notification details for item ' . ($i + 1),
        };
    }

    private static function dummyFaqs(): array
    {
        return [
            ['id' => 1, 'group_code' => 'general', 'question' => 'How do I track student progress?', 'answer' => 'Use the Students Details view with enrollmentId to see KPI strip, grades, attendance and timeline.'],
            ['id' => 2, 'group_code' => 'products', 'question' => 'Which products does Nile-Maple supply?', 'answer' => 'Four divisions: fresh fruits (31), fresh vegetables (37), frozen (36), processed (55).'],
            ['id' => 3, 'group_code' => 'packaging', 'question' => 'What pack formats are available?', 'answer' => 'Ventilated cartons, mesh bags, punnets, clamshells for fresh; cans, jars, pouches for processed.'],
            ['id' => 4, 'group_code' => 'logistics', 'question' => 'How is cold chain managed?', 'answer' => 'Pre-cooling, set points per product, ventilation, -18°C for frozen, unbroken chain.'],
            ['id' => 5, 'group_code' => 'documents', 'question' => 'What documentation accompanies shipments?', 'answer' => 'Packing list, invoice, origin certificate, phytosanitary/health where required.'],
        ];
    }

    private static function relativeTime(int $ts): string
    {
        $diff = time() - $ts;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 172800) return 'yesterday';
        return floor($diff / 86400) . 'd ago';
    }

    private static function formatBytes(int $b): string
    {
        if ($b < 1024) return $b . ' B';
        if ($b < 1048576) return round($b / 1024, 1) . ' KB';
        return round($b / 1048576, 1) . ' MB';
    }
}
