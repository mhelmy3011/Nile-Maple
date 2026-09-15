<?php
namespace Nm;

/**
 * Single source of truth for the schema (doc 01 §3). Neutral column types are mapped
 * per driver so migrate.php produces identical semantics on MySQL (prod) and SQLite (tests).
 */
final class Schema
{
    public static function tables(): array
    {
        $T = [];
        $T['users'] = [
            'id' => 'pk', 'email' => 'varchar:190:uq', 'password_hash' => 'varchar:255',
            'full_name' => 'varchar:120', 'role' => "enum:owner,editor:def=editor", 'status' => "enum:active,disabled:def=active",
            'failed_logins' => 'int:d0', 'locked_until' => 'dt:null', 'last_login_at' => 'dt:null',
            'totp_secret' => 'varchar:64:null', 'created_at' => 'ts',
        ];
        $T['categories'] = [
            'id' => 'pk', 'code' => 'varchar:40:uq', 'icon_key' => 'varchar:40',
            'accent' => "enum:green,amber,pine,leaf:def=green", 'sort_order' => 'int:d0', 'is_published' => 'bool:d1',
            'cover_media_id' => 'int:null', 'updated_at' => 'tsu',
        ];
        $T['category_i18n'] = [
            'category_id' => 'int:pkfk:categories', 'lang' => 'lang:pk', 'name' => 'varchar:160',
            'slug' => 'varchar:190', 'headline' => 'varchar:190', 'summary' => 'text',
            'meta_title' => 'varchar:70:null', 'meta_description' => 'varchar:165:null',
            '@uq' => ['lang', 'slug'],
        ];
        $T['products'] = [
            'id' => 'pk', 'category_id' => 'int:fk:categories', 'sku' => 'varchar:40:null',
            'source_index' => 'int:null', 'card_media_id' => 'int:null', 'gallery' => 'json:null',
            'sort_order' => 'int:d0', 'is_published' => 'bool:d1', 'is_featured' => 'bool:d0',
            'temp_min' => 'dec:null', 'temp_max' => 'dec:null', 'temp_unit' => 'varchar:2:def=C',
            'temp_note' => 'varchar:80:null', 'updated_at' => 'tsu',
        ];
        $T['product_i18n'] = [
            'product_id' => 'int:pkfk:products', 'lang' => 'lang:pk', 'name' => 'varchar:190',
            'slug' => 'varchar:220', 'description' => 'text',
            'label_varieties' => 'varchar:60', 'varieties' => 'text',
            'label_handling' => 'varchar:60', 'handling' => 'text',
            'label_packing' => 'varchar:60', 'packing' => 'text',
            'label_chain' => 'varchar:60', 'chain' => 'text',
            'meta_title' => 'varchar:70:null', 'meta_description' => 'varchar:165:null',
            '@uq' => ['lang', 'slug'],
        ];
        $T['services'] = [
            'id' => 'pk', 'code' => 'varchar:40:uq', 'icon_key' => 'varchar:40',
            'sort_order' => 'int:d0', 'is_published' => 'bool:d1', 'media_id' => 'int:null',
        ];
        $T['service_i18n'] = [
            'service_id' => 'int:pkfk:services', 'lang' => 'lang:pk', 'name' => 'varchar:160',
            'slug' => 'varchar:190', 'teaser' => 'varchar:220', 'body' => 'text', 'bullets' => 'json',
            'meta_title' => 'varchar:70:null', 'meta_description' => 'varchar:165:null',
            '@uq' => ['lang', 'slug'],
        ];
        $T['posts'] = [
            'id' => 'pk', 'author_user_id' => 'int:null', 'cover_media_id' => 'int:null',
            'published_at' => 'dt:null', 'status' => "enum:draft,published,archived:def=draft",
            'reading_minutes' => 'int:d3', 'updated_at' => 'tsu',
        ];
        $T['post_i18n'] = [
            'post_id' => 'int:pkfk:posts', 'lang' => 'lang:pk', 'title' => 'varchar:190',
            'slug' => 'varchar:220', 'excerpt' => 'varchar:300', 'body' => 'text',
            'meta_title' => 'varchar:70:null', 'meta_description' => 'varchar:165:null',
            '@uq' => ['lang', 'slug'],
        ];
        $T['blocks'] = [
            'id' => 'pk', 'zone' => 'varchar:60', 'kind' => "enum:text,list,table,stat,person,hero,markdown",
            'sort_order' => 'int:d0', 'payload' => 'json',
        ];
        $T['block_i18n'] = [
            'block_id' => 'int:pkfk:blocks', 'lang' => 'lang:pk',
            'title' => 'varchar:190:null', 'eyebrow' => 'varchar:120:null', 'payload' => 'json',
        ];
        $T['faqs'] = [
            'id' => 'pk', 'group_code' => 'varchar:40:def=general', 'sort_order' => 'int:d0', 'is_published' => 'bool:d1',
        ];
        $T['faq_i18n'] = [
            'faq_id' => 'int:pkfk:faqs', 'lang' => 'lang:pk', 'question' => 'varchar:250', 'answer' => 'text',
        ];
        $T['settings'] = ['s_key' => 'varchar:80:pk', 'lang' => 'varchar:2:pk:def=*', 'value' => 'text'];
        $T['media'] = [
            'id' => 'pk', 'filename' => 'varchar:190', 'mime' => 'varchar:60',
            'width' => 'int', 'height' => 'int', 'bytes' => 'int:d0',
            'focal_x' => 'dec3:d0.5', 'focal_y' => 'dec3:d0.5', 'source_ref' => 'varchar:120:null',
            'created_at' => 'ts',
        ];
        $T['media_variant'] = [
            'media_id' => 'int:pkfk:media', 'fmt' => "enum:pk:avif,webp,jpg", 'w' => 'int:pk',
            'path' => 'varchar:220', 'bytes' => 'int:d0',
        ];
        $T['media_i18n'] = ['media_id' => 'int:pkfk:media', 'lang' => 'lang:pk', 'alt' => 'varchar:220'];
        $T['seo_meta'] = [
            'entity_type' => "enum:pk:page,category,product,service,post,faq", 'entity_id' => 'int:pk:d0',
            'lang' => 'lang:pk', 'title' => 'varchar:70:null', 'description' => 'varchar:165:null',
            'canonical_override' => 'varchar:250:null', 'robots' => 'varchar:60:null', 'og_image_media_id' => 'int:null',
        ];
        $T['enquiries'] = [
            'id' => 'pk', 'full_name' => 'varchar:120', 'email' => 'varchar:190', 'phone' => 'varchar:40:null',
            'company' => 'varchar:160:null', 'country' => 'varchar:90:null', 'subject' => 'varchar:190',
            'product_interest' => 'varchar:190:null', 'message' => 'text', 'consent' => 'bool:d0',
            'lang' => 'lang', 'ip_hash' => 'char:64', 'ua' => 'varchar:250:null',
            'status' => "enum:new,read,replied,closed:def=new", 'mailed' => 'bool:d0', 'mail_tries' => 'int:d0',
            'created_at' => 'ts',
        ];
        $T['events'] = [
            'id' => 'pk', 'name' => 'varchar:60', 'lang' => 'lang', 'path' => 'varchar:250:null',
            'ip_hash' => 'char:64', 'created_at' => 'ts',
        ];
        $T['redirects'] = [
            'id' => 'pk', 'from_path' => 'varchar:250:uq', 'to_path' => 'varchar:250', 'code' => 'int:d301',
        ];
        $T['audit_log'] = [
            'id' => 'pk64', 'user_id' => 'int:null', 'action' => 'varchar:60',
            'entity_type' => 'varchar:40:null', 'entity_id' => 'int:null', 'diff' => 'json:null',
            'ip_hash' => 'char:64:null', 'created_at' => 'ts',
        ];
        $T['build_jobs'] = [
            'id' => 'pk', 'scope' => 'varchar:120', 'status' => "enum:queued,running,done,failed:def=queued",
            'pages' => 'int:d0', 'ms' => 'int:null', 'error' => 'text:null',
            'started_at' => 'dt:null', 'finished_at' => 'dt:null',
        ];
        $T['sessions'] = [
            'id' => 'char:64:pk', 'user_id' => 'int:fk:users', 'data' => 'text:null',
            'ip_hash' => 'char:64', 'ua' => 'varchar:250:null', 'created_at' => 'ts', 'expires_at' => 'dt',
        ];
        return $T;
    }

    /** index definitions: table => [ [type, name, columns[]] ] */
    public static function indexes(): array
    {
        return [
            'products'      => [['idx', 'idx_cat_sort', ['category_id', 'sort_order']]],
            'product_i18n'  => [['idx', 'idx_pi_lang', ['lang']]],
            'enquiries'     => [['idx', 'idx_enq_status', ['status', 'created_at']]],
            'audit_log'     => [['idx', 'idx_aud_entity', ['entity_type', 'entity_id']]],
            'blocks'        => [['idx', 'idx_blk_zone', ['zone', 'sort_order']]],
            'media_variant' => [['idx', 'idx_mv_path', ['path']]],
        ];
    }

    public static function isPk(string $spec): bool
    {
        $parts = explode(':', $spec);
        return in_array('pk', array_slice($parts, 1), true)
            || in_array('pkfk', array_slice($parts, 1), true)
            || in_array($parts[0], ['pk', 'pk64'], true);
    }

    public static function columnSql(string $driver, string $col, string $spec, bool $soloPk = false): string
    {
        $parts = explode(':', $spec);
        $type = $parts[0];
        $flags = array_slice($parts, 1);
        $null = in_array('null', $flags, true);
        $def = null;
        foreach ($flags as $f) {
            if (str_starts_with($f, 'def=')) { $def = substr($f, 4); }
            elseif (preg_match('/^d[0-9.]+$/', $f)) { $def = substr($f, 1); }
        }
        $pk = $soloPk;
        $mysql = $driver === 'mysql';
        switch ($type) {
            case 'pk':     $c = $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT' : 'INTEGER PRIMARY KEY AUTOINCREMENT'; break;
            case 'pk64':   $c = $mysql ? 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT' : 'INTEGER PRIMARY KEY AUTOINCREMENT'; break;
            case 'int':    $c = ($mysql ? 'INT' : 'INTEGER') . ($null ? ' NULL' : ' NOT NULL'); break;
            case 'bool':   $c = ($mysql ? 'TINYINT(1)' : 'INTEGER') . ($null ? ' NULL' : ' NOT NULL'); break;
            case 'dec':    $c = ($mysql ? 'DECIMAL(4,1)' : 'REAL') . ($null ? ' NULL' : ' NOT NULL'); break;
            case 'dec3':   $c = ($mysql ? 'DECIMAL(4,3)' : 'REAL') . ($null ? ' NULL' : ' NOT NULL'); break;
            case 'char':   $c = 'CHAR(' . (int) $parts[1] . ($null ? ') NULL' : ') NOT NULL'); break;
            case 'varchar':$c = 'VARCHAR(' . (int) $parts[1] . ($null ? ') NULL' : ') NOT NULL'); break;
            case 'text':   $c = $mysql ? 'TEXT' . ($null ? ' NULL' : ' NOT NULL') : 'TEXT' . ($null ? ' NULL' : ' NOT NULL'); break;
            case 'json':   $c = $mysql ? 'JSON' . ($null ? ' NULL' : ' NOT NULL') : 'TEXT' . ($null ? ' NULL' : ' NOT NULL'); break;
            case 'dt':     $c = ($mysql ? 'DATETIME' : 'TEXT') . ($null ? ' NULL' : ' NOT NULL'); break;
            case 'ts':     $c = $mysql ? 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP' : 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP'; break;
            case 'tsu':    $c = $mysql ? 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' : 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP'; break;
            case 'lang':   $c = ($mysql ? 'CHAR(2)' : 'TEXT') . ' NOT NULL'; break;
            case 'enum':   $raw = $parts[1] ?? '';
                           foreach (array_slice($parts, 1) as $p) { if (str_contains($p, ',')) { $raw = $p; break; } }
                           $vals = array_map(fn($v) => "'$v'", explode(',', $raw));
                           $c = $mysql ? 'ENUM(' . implode(',', $vals) . ')' . ($null ? ' NULL' : ' NOT NULL')
                                       : 'TEXT' . ($null ? ' NULL' : ' NOT NULL') . ' CHECK (' . $col . ' IN (' . implode(',', $vals) . '))';
                           break;
            default: throw new \RuntimeException("unknown column type $spec");
        }
        if ($pk && !in_array($type, ['pk', 'pk64'], true)) $c .= ' PRIMARY KEY';
        if ($def !== null && $def !== '' && !in_array($type, ['pk', 'pk64'], true)) {
            $c .= ' DEFAULT ' . (is_numeric($def) ? $def : "'" . $def . "'");
        }
        if (in_array('uq', $flags, true)) $c .= $mysql ? ' UNIQUE' : '';
        return $col . ' ' . $c;
    }

    public static function ddl(string $driver): array
    {
        $out = [];
        foreach (self::tables() as $table => $cols) {
            $defs = []; $pks = []; $fks = []; $uqs = [];
            foreach ($cols as $col => $spec) {
                if ($col === '@uq') { $uqs[] = $spec; continue; }
                $parts = explode(':', $spec); $flags = array_slice($parts, 1);
                if (self::isPk($spec)) $pks[] = $col;
                foreach ($flags as $i => $f) {
                    if ($f === 'fk' || $f === 'pkfk') { $fks[] = [$col, $flags[$i + 1]]; }
                }
                if (in_array('uq', $flags, true) && $driver === 'sqlite') $uqs[] = [$col];
            }
            /* a lone serial key (pk/pk64) stays inline; anything else gets a composite PRIMARY KEY */
            $soloPk = count($pks) === 1 && in_array(explode(':', $cols[$pks[0]])[0], ['pk', 'pk64'], true);
            foreach ($cols as $col => $spec) {
                if ($col === '@uq') continue;
                $defs[] = '  ' . self::columnSql($driver, $col, $spec, $soloPk && $col === $pks[0]);
            }
            if ($pks && !$soloPk) $defs[] = '  PRIMARY KEY (' . implode(',', $pks) . ')';
            foreach ($fks as [$c, $ref]) {
                $defs[] = $driver === 'mysql'
                    ? "  CONSTRAINT fk_{$table}_{$c} FOREIGN KEY ($c) REFERENCES $ref(id) ON DELETE CASCADE"
                    : "  FOREIGN KEY ($c) REFERENCES $ref(id) ON DELETE CASCADE";
            }
            foreach ($uqs as $u) {
                $defs[] = '  UNIQUE (' . implode(',', $u) . ')';
            }
            $eng = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
            $q = $driver === 'mysql' ? "`$table`" : "\"$table\"";
            /* MySQL has no CREATE INDEX IF NOT EXISTS → ship secondary indexes inline as KEY (…) so the
               whole migration stays idempotent; SQLite uses standalone CREATE INDEX IF NOT EXISTS. */
            $inline = $standalone = [];
            foreach (self::indexes()[$table] ?? [] as [$t, $name, $cCols]) {
                if ($driver === 'mysql') {
                    $inline[] = "  KEY $name (" . implode(',', array_map(fn($c) => "`$c`", $cCols)) . ")";
                } else {
                    $standalone[] = "CREATE INDEX IF NOT EXISTS $name ON $q (" . implode(',', $cCols) . ")";
                }
            }
            $out[] = "CREATE TABLE IF NOT EXISTS $q (\n" . implode(",\n", array_merge($defs, $inline)) . "\n)$eng";
            foreach ($standalone as $i) $out[] = $i;
        }
        return $out;
    }
}
