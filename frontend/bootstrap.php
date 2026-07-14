<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.4.0
 * Changelog: 1.4.0 - pdf_export.php eingebunden (Ergebnisse-als-PDF-Export für reguläre Ligen)
 * Changelog: 1.3.0 - getAppVersion() ergänzt (liest Version aus composer.json)
 * Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.2.0 - Kein funktionaler Unterschied hier, aber Teil des Umbaus auf reine
 *                     Platzhalter-Templates (siehe frontend/template_engine.php v2.0.0)
 * Changelog: 1.1.0 - data_liga.php eingebunden (Liga-Detailseite: letzte Ergebnisse)
 * Changelog: 1.0.0 - Initiale Version: eigenständiger Bootstrap für den Besucherbereich,
 *                     komplett getrennt vom Adminbereich (eigene Session, eigene Sprache,
 *                     eigenes Template).
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Wird von home.php (und künftigen Besucherseiten wie liga.php) eingebunden.
 * Bewusst unabhängig von admin/bootstrap.php: eigene Session (damit ein
 * Admin-Login nichts mit der Besucher-Session zu tun hat), eigene
 * Spracheinstellung (Domain "frontend" in lang/i18n.php) und eigenes Template.
 */
declare(strict_types = 1);

session_name('lmonext_frontend');
session_start();

// ── Mehrsprachigkeit (Besucherbereich, unabhängig vom Adminbereich) ──────────
require_once dirname(__DIR__) . '/lang/i18n.php';
getCurrentLanguage('frontend');

// ── Konfiguration ─────────────────────────────────────────────────────────────
$_configFile = dirname(__DIR__) . '/config.php';
if (!is_file($_configFile)) {
    http_response_code(503);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Nicht konfiguriert</title></head>'
      . '<body style="font-family:system-ui;text-align:center;padding:60px 20px;color:#333">'
      . '<h2>⚠️ config.php nicht gefunden</h2>'
      . '<p>Bitte zuerst den <a href="install.php">Installer</a> ausführen.</p>'
      . '</body></html>');
}
require_once $_configFile;

// ── PDO ──────────────────────────────────────────────────────────────────────
function getDB() : PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function tbl(string $base) : string
{
    return '`' . DB_PREFIX . $base . '`';
}

function h(mixed $v) : string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Liest die Versionsnummer aus der composer.json im Projekt-Root (Feld "version").
 * Liefert einen leeren String, falls die Datei fehlt oder ungültig ist.
 */
function getAppVersion() : string
{
    static $version = null;
    if ($version !== null) {
        return $version;
    }
    try {
        $path = dirname(__DIR__) . '/composer.json';
        $data = json_decode((string)file_get_contents($path), true);
        return $version = (string)($data['version'] ?? '');
    } catch (Throwable) {
        return $version = '';
    }
}

function getAdminSetting(string $key, string $default = '') : string
{
    try {
        $s = getDB()->prepare('SELECT `value` FROM ' . tbl('admin_settings') . ' WHERE `key`=?');
        $s->execute([$key]);
        $v = $s->fetchColumn();
        return $v !== false ? (string)$v : $default;
    } catch (Throwable) {
        return $default;
    }
}

// ── Template-Engine + aktives Template ermitteln ─────────────────────────────
require_once __DIR__ . '/template_engine.php';

$activeTemplateDefault = getAdminSetting('active_template', DEFAULT_TEMPLATE);
$allowTemplateSwitch   = getAdminSetting('allow_template_switch', '0') === '1';
$activeTemplate        = resolveActiveTemplate($activeTemplateDefault, $allowTemplateSwitch);

// ── Datenfunktionen (Abfragen) ────────────────────────────────────────────────
require_once __DIR__ . '/data_home.php';
require_once __DIR__ . '/data_liga.php';
require_once __DIR__ . '/pdf_export.php';
