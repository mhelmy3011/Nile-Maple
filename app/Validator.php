<?php
namespace Nm;

/** Server-authoritative validation (doc 04 §7). Returns [errors[], sanitized[]]. */
final class Validator
{
    public static function make(array $data, array $rules): array
    {
        $errors = []; $clean = [];
        foreach ($rules as $field => $rule) {
            $v = $data[$field] ?? null;
            if (is_string($v)) $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', trim($v));
            $req = in_array('required', $rule, true);
            $empty = $v === null || $v === '' || $v === [];
            if ($req && $empty) { $errors[$field] = 'required'; continue; }
            if ($empty && !$req) { $clean[$field] = null; continue; }
            foreach ($rule as $r) {
                if ($r === 'required') continue;
                if ($r === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) { $errors[$field] = 'email'; break; }
                if ($r === 'url' && !filter_var($v, FILTER_VALIDATE_URL)) { $errors[$field] = 'url'; break; }
                if (str_starts_with($r, 'max:')) {
                    $n = (int) substr($r, 4);
                    if (mb_strlen((string) $v) > $n) { $errors[$field] = "max:$n"; break; }
                }
                if (str_starts_with($r, 'min:')) {
                    $n = (int) substr($r, 4);
                    if (mb_strlen((string) $v) < $n) { $errors[$field] = "min:$n"; break; }
                }
                if ($r === 'checked' && !$v) { $errors[$field] = 'checked'; break; }
                if ($r === 'int') { $v = (int) $v; }
                if ($r === 'num') { $v = is_numeric($v) ? (float) $v : null; if ($v === null) { $errors[$field] = 'num'; break; } }
                if (str_starts_with($r, 'in:')) {
                    $set = explode(',', substr($r, 3));
                    if (!in_array((string) $v, $set, true)) { $errors[$field] = 'in'; break; }
                }
            }
            if (!isset($errors[$field])) $clean[$field] = $v;
        }
        return [$errors, $clean];
    }
}
