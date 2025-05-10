<?php
class TokenHandler {
    private static function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function generateToken() {
        self::ensureSession();
        
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        // Regenerate token if it's older than 2 hours
        if (time() - $_SESSION['csrf_token_time'] > 7200) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateToken($token) {
        return self::verifyToken($token);
    }

    public static function verifyToken($token) {
        self::ensureSession();

        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        if (!is_string($token) || !is_string($_SESSION['csrf_token'])) {
            return false;
        }

        // Token age validation - allow a grace period for refresh
        if (isset($_SESSION['csrf_token_time'])) {
            $tokenAge = time() - $_SESSION['csrf_token_time'];
            if ($tokenAge > 7200) { // 2 hours
                error_log("CSRF token expired. Age: $tokenAge seconds");
                return false;
            }
        }

        $valid = hash_equals($_SESSION['csrf_token'], $token);
        
        // Only regenerate token if it's older than 30 minutes
        if ($valid && isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time'] > 1800)) {
            self::refreshToken();
        }

        return $valid;
    }

    public static function removeToken() {
        self::ensureSession();
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
    }

    public static function refreshToken() {
        self::ensureSession();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        return $_SESSION['csrf_token'];
    }
}
?>