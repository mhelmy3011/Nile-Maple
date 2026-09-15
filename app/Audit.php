<?php
namespace Nm;

final class Audit
{
    public static function log(string $action, ?string $type = null, ?int $id = null, array $diff = []): void
    {
        Db::run('INSERT INTO audit_log(user_id,action,entity_type,entity_id,diff,ip_hash) VALUES(?,?,?,?,?,?)', [
            (int) ($_SESSION['uid'] ?? 0), $action, $type, $id,
            $diff ? json_encode($diff, JSON_UNESCAPED_UNICODE) : null, Util::ipHash(),
        ]);
    }
}
