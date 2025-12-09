<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_LANG_DEFAULT', 'en');
define('APP_LANG_OPTIONS', ['zh', 'en']);

$GLOBALS['APP_LANG_TOGGLE_RENDERED'] = $GLOBALS['APP_LANG_TOGGLE_RENDERED'] ?? false;

function app_lang_normalize($value)
{
    $value = strtolower(trim((string) $value));
    return in_array($value, APP_LANG_OPTIONS, true) ? $value : null;
}

function app_lang_get()
{
    static $current = null;
    if ($current !== null) {
        return $current;
    }

    $current = APP_LANG_DEFAULT;
    $requested = $_GET['lang'] ?? null;

    if ($requested !== null) {
        $normalized = app_lang_normalize($requested);
        if ($normalized !== null) {
            $_SESSION['preferred_lang'] = $normalized;
            setcookie('preferred_lang', $normalized, time() + 31536000, '/');
            $current = $normalized;
            return $current;
        }
    }

    $sessionPref = $_SESSION['preferred_lang'] ?? null;
    if ($sessionPref !== null) {
        $normalized = app_lang_normalize($sessionPref);
        if ($normalized !== null) {
            $current = $normalized;
            return $current;
        }
    }

    $cookiePref = $_COOKIE['preferred_lang'] ?? null;
    if ($cookiePref !== null) {
        $normalized = app_lang_normalize($cookiePref);
        if ($normalized !== null) {
            $current = $normalized;
            return $current;
        }
    }

    return $current;
}

function __(string $zhText, ?string $enText = null): string
{
    return app_lang_get() === 'en' ? ($enText ?? $zhText) : $zhText;
}

function app_lang_html_attr(): string
{
    return app_lang_get() === 'en' ? 'en' : 'zh-CN';
}

function app_lang_build_url(string $lang): string
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? '/');
    $base = strtok($requestUri, '?') ?: '/';
    $params = $_GET;
    unset($params['lang']);
    $params['lang'] = $lang;
    $query = http_build_query($params);
    return $base . ($query ? ('?' . $query) : '');
}

function render_lang_toggle(): string
{
    $GLOBALS['APP_LANG_TOGGLE_RENDERED'] = true;
    $current = app_lang_get();
    $zhUrl = htmlspecialchars(app_lang_build_url('zh'));
    $enUrl = htmlspecialchars(app_lang_build_url('en'));

    $zhClass = $current === 'zh' ? 'active' : '';
    $enClass = $current === 'en' ? 'active' : '';

    return <<<HTML
<div class="lang-switch">
    <a class="lang-btn {$zhClass}" href="{$zhUrl}">中文</a>
    <span class="divider">/</span>
    <a class="lang-btn {$enClass}" href="{$enUrl}">EN</a>
</div>
HTML;
}

function app_lang_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $path = __DIR__ . '/lang_map.php';
    $map = file_exists($path) ? (require $path) : [];
    return is_array($map) ? $map : [];
}

function app_lang_buffer_handler(string $buffer): string
{
    if (app_lang_get() === 'en') {
        $translations = app_lang_map();
        if (!empty($translations)) {
            $buffer = strtr($buffer, $translations);
        }
    }

    if (empty($GLOBALS['APP_LANG_TOGGLE_RENDERED'])) {
        $floating = '<div class="lang-floating-toggle">' . render_lang_toggle() . '</div>';
        $GLOBALS['APP_LANG_TOGGLE_RENDERED'] = true;
        if (stripos($buffer, '</body>') !== false) {
            $buffer = preg_replace('/<\/body>/i', $floating . '</body>', $buffer, 1);
        } else {
            $buffer .= $floating;
        }
    }

    return $buffer;
}

if (!defined('APP_LANG_OUTPUT_BUFFER')) {
    define('APP_LANG_OUTPUT_BUFFER', true);
    ob_start('app_lang_buffer_handler');
}
