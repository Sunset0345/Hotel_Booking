<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Simple flash message helper
 * Functions: flash_set($type, $message), flash_get($type = null), flash_has($type = null)
 * Stores messages in $_SESSION['_flash_messages'] as an array of ['type'=>..., 'message'=>...]
 */

if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['_flash_messages']) || !is_array($_SESSION['_flash_messages'])) {
            $_SESSION['_flash_messages'] = [];
        }
        $_SESSION['_flash_messages'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('flash_get')) {
    /**
     * Get and remove flash messages. If $type is provided, returns messages of that type only.
     * Returns an array of messages (each is ['type'=>..., 'message'=>...]).
     */
    function flash_get(string $type = null): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['_flash_messages']) || !is_array($_SESSION['_flash_messages'])) {
            return [];
        }
        $all = $_SESSION['_flash_messages'];
        if ($type === null) {
            // clear all
            unset($_SESSION['_flash_messages']);
            return $all;
        }
        $matches = array_values(array_filter($all, function ($m) use ($type) {
            return isset($m['type']) && $m['type'] === $type;
        }));
        // remove matched messages from session
        $remaining = array_values(array_filter($all, function ($m) use ($type) {
            return !(isset($m['type']) && $m['type'] === $type);
        }));
        if (empty($remaining)) {
            unset($_SESSION['_flash_messages']);
        } else {
            $_SESSION['_flash_messages'] = $remaining;
        }
        return $matches;
    }
}

if (!function_exists('flash_has')) {
    /**
     * Return true if there are any flash messages (or any of the given type).
     */
    function flash_has(string $type = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['_flash_messages']) || !is_array($_SESSION['_flash_messages'])) {
            return false;
        }
        if ($type === null) {
            return count($_SESSION['_flash_messages']) > 0;
        }
        foreach ($_SESSION['_flash_messages'] as $m) {
            if (isset($m['type']) && $m['type'] === $type) {
                return true;
            }
        }
        return false;
    }
}
