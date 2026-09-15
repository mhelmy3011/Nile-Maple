<?php
namespace Nm;

/** Tiny template engine: layouts + partials + auto-escape helper (doc 04 §2.1). */
final class View
{
    private static array $shared = [];

    public static function share(string $k, mixed $v): void { self::$shared[$k] = $v; }

    public static function render(string $template, array $data = []): string
    {
        $f = cfg('paths.app') . "/templates/$template.php";
        if (!is_file($f)) throw new \RuntimeException("template missing: $template");
        extract(self::$shared, EXTR_SKIP);
        extract($data, EXTR_SKIP);
        ob_start();
        include $f;
        return ob_get_clean();
    }

    public static function page(string $template, array $data = [], string $layout = 'layouts/base'): string
    {
        $content = self::render($template, $data);
        return self::render($layout, array_merge($data, ['content' => $content]));
    }

    public static function e(?string $s): string { return Util::e($s); }
}
