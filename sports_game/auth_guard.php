<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/lang.php';

if (!function_exists('flash_enqueue')) {
    function flash_enqueue(string $type, string $message): void
    {
        if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }

        $_SESSION['flash_messages'][] = [
            'type' => $type === 'error' ? 'error' : 'success',
            'message' => $message,
            'timestamp' => time(),
        ];
    }
}

if (!function_exists('flash_success')) {
    function flash_success(string $message): void
    {
        flash_enqueue('success', $message);
    }
}

if (!function_exists('flash_error')) {
    function flash_error(string $message): void
    {
        flash_enqueue('error', $message);
    }
}

if (!function_exists('flash_consume')) {
    function flash_consume(): array
    {
        if (empty($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
            return [];
        }

        $messages = $_SESSION['flash_messages'];
        $_SESSION['flash_messages'] = [];
        return $messages;
    }
}

if (!function_exists('render_flash_toasts')) {
    function render_flash_toasts(): void
    {
        $messages = flash_consume();
        if (empty($messages)) {
            return;
        }

        echo '<div class="toast-stack">';
        foreach ($messages as $flash) {
            $type = $flash['type'] ?? 'success';
            $message = $flash['message'] ?? '';
            $title = $type === 'error'
                ? __('操作失败', 'Operation failed')
                : __('操作成功', 'Operation successful');
            $class = $type === 'error' ? 'toast toast-error' : 'toast toast-success';
            echo '<div class="' . $class . '">';
            echo '<strong>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong>';
            echo '<span>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }
}

if (empty($_SESSION['admin_user'])) {
    $redirectTarget = $_SERVER['REQUEST_URI'] ?? 'admin_dashboard.php';
    $lower = strtolower($redirectTarget);
    $safeTarget = (strpos($lower, 'http://') === 0 || strpos($lower, 'https://') === 0)
        ? 'admin_dashboard.php'
        : $redirectTarget;
    header('Location: admin_login.php?redirect=' . rawurlencode($safeTarget));
    exit;
}
