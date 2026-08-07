<?php
/**
 * Project: LMOnext
 * Filename: config_loader.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

$_lmoRoot           = __DIR__;
$_lmoVendorAutoload = $_lmoRoot . '/vendor/autoload.php';
$_lmoEnvFile        = $_lmoRoot . '/.env';
$_lmoConfigFile     = $_lmoRoot . '/config.php';

if (is_file($_lmoVendorAutoload) && is_file($_lmoEnvFile)) {
    // ── Composer/.env-Variante ──────────────────────────────────────────────
    require_once $_lmoVendorAutoload;
    \LMOnext\Core\Env::load($_lmoRoot);

    if (!defined('DB_HOST')) {
        define('DB_HOST', \LMOnext\Core\Env::get('DB_HOST', 'localhost') ?? 'localhost');
    }
    if (!defined('DB_PORT')) {
        define('DB_PORT', (int)(\LMOnext\Core\Env::get('DB_PORT', '3306') ?? '3306'));
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', \LMOnext\Core\Env::get('DB_NAME', '') ?? '');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', \LMOnext\Core\Env::get('DB_USER', '') ?? '');
    }
    if (!defined('DB_PASS')) {
        define('DB_PASS', \LMOnext\Core\Env::get('DB_PASS', '') ?? '');
    }
    if (!defined('DB_CHARSET')) {
        define('DB_CHARSET', \LMOnext\Core\Env::get('DB_CHARSET', 'utf8mb4') ?? 'utf8mb4');
    }
    if (!defined('DB_PREFIX')) {
        define('DB_PREFIX', \LMOnext\Core\Env::get('DB_PREFIX', 'lmonext_') ?? 'lmonext_');
    }
} elseif (is_file($_lmoConfigFile)) {
    // ── Klassische config.php-Variante (Standard) ───────────────────────────
    require_once $_lmoConfigFile;
} else {
    http_response_code(503);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Nicht konfiguriert</title></head>'
      . '<body style="font-family:system-ui;text-align:center;padding:60px 20px;color:#333">'
      . '<h2>⚠️ Noch nicht installiert</h2>'
      . '<p>Weder <code>config.php</code> noch eine gültige <code>.env</code>-Konfiguration gefunden.</p>'
      . '<p>Bitte zuerst den <a href="install.php">Installer</a> ausführen.</p>'
      . '</body></html>');
}

unset($_lmoRoot, $_lmoVendorAutoload, $_lmoEnvFile, $_lmoConfigFile);
