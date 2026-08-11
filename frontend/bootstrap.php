<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.7.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Wird von home.php (und künftigen Besucherseiten wie liga.php) eingebunden.
 * Bewusst unabhängig von admin/bootstrap.php: eigene Session (damit ein
 * Admin-Login nichts mit der Besucher-Session zu tun hat), eigene
 * Spracheinstellung (Domain "frontend" in lang/i18n.php) und eigenes Template.
 */
declare(strict_types = 1);

// ── Globale Fehlerbehandlung ──────────────────────────────────────────────────
// Jede nicht abgefangene Exception landet im Server-Error-Log (für Admins/
// Entwickler einsehbar), Besucher sehen nur eine schlichte, technikfreie
// Fehlermeldung statt Stacktrace/Dateipfaden - unabhängig davon, wie
// display_errors auf dem jeweiligen Hosting konfiguriert ist.
set_exception_handler(static function (Throwable $e) : void {
    error_log('LMOnext (frontend) uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head>'
       . '<body style="font-family:system-ui;text-align:center;padding:60px 20px;color:#333">'
       . '<h2>⚠️ Ein unerwarteter Fehler ist aufgetreten</h2>'
       . '<p>Bitte versuche es später erneut.</p>'
       . '</body></html>';
});

// ── Session ────────────────────────────────────────────────────────────────
// Cookie-Parameter schützen die Besucher-Session zusätzlich gegen
// clientseitigen Zugriff (HttpOnly) und Cross-Site-Requests (SameSite=Lax);
// Secure wird nur gesetzt, wenn die Seite tatsächlich über HTTPS läuft
// (sonst würde das Cookie auf einer reinen HTTP-Installation nie ankommen).
session_name('lmonext_frontend');
session_set_cookie_params([
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

// ── Mehrsprachigkeit (Besucherbereich, unabhängig vom Adminbereich) ──────────
// Die eigentliche Sprachauflösung (inkl. der in den Admin-Einstellungen
// konfigurierten Standardsprache) passiert weiter unten, NACHDEM
// getAdminSetting() verfügbar ist (siehe dort) – hier nur die
// Funktionsdefinitionen laden, noch nichts auflösen.
require_once dirname(__DIR__) . '/lang/i18n.php';

// ── Konfiguration ─────────────────────────────────────────────────────────────
// Lädt entweder die Composer/.env-Variante oder die klassische config.php,
// je nachdem was install.php beim Installieren angelegt hat (siehe
// config_loader.php im Projekt-Root für Details zu beiden Varianten).
require_once dirname(__DIR__) . '/config_loader.php';

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
    } catch (Throwable $e) {
        error_log('LMOnext getAppVersion(): ' . $e->getMessage());
        return $version = '';
    }
}

/**
 * Liest alle admin_settings-Zeilen einmal pro Request in einen statischen
 * Speicher-Cache (siehe getAdminSetting()) - vorher löste jeder einzelne
 * getAdminSetting()-Aufruf eine eigene SQL-Abfrage aus, obwohl derselbe
 * Aufruf (z.B. 'active_template', 'language', 'show_pdf_buttons' usw.)
 * innerhalb eines Seitenaufrufs oft mehrfach vorkommt.
 */
function getAdminSetting(string $key, string $default = '') : string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = getDB()->query('SELECT `key`, `value` FROM ' . tbl('admin_settings'))->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['key']] = (string)$row['value'];
            }
        } catch (Throwable $e) {
            error_log('LMOnext getAdminSetting(): ' . $e->getMessage());
            // $cache bleibt ein leeres Array -> jede einzelne Abfrage fällt
            // unten auf ihren jeweiligen $default zurück, kein Fataler Fehler
        }
    }
    return $cache[$key] ?? $default;
}

// ── Sprache jetzt WIRKLICH auflösen (siehe Kommentar weiter oben): erst jetzt
// ist getAdminSetting() verfügbar, um die in den Admin-Einstellungen
// konfigurierte Standardsprache zu berücksichtigen. Muss auch VOR
// resolveActiveTemplate() passieren, damit t()/tf() im weiteren Verlauf
// (inkl. der Datenfunktionen) korrekt auflösen.
getCurrentLanguage('frontend', getAdminSetting('language', DEFAULT_LANGUAGE));

// ── Template-Engine + aktives Template ermitteln ─────────────────────────────
require_once __DIR__ . '/template_engine.php';

$activeTemplateDefault = getAdminSetting('active_template', DEFAULT_TEMPLATE);
$allowTemplateSwitch   = getAdminSetting('allow_template_switch', '0') === '1';
$activeTemplate        = resolveActiveTemplate($activeTemplateDefault, $allowTemplateSwitch);

// ── Sport-Profile (Beitrag: Torsten Hofmann) - müssen VOR data_liga.php
// geladen sein, da StandingsTrait/RenderViewsTrait (Teil der Kette dort)
// LMOnext\Sport\SportRegistry referenzieren ─────────────────────────────────
require_once dirname(__DIR__) . '/src/Sport/SportProfile.php';
require_once dirname(__DIR__) . '/src/Sport/SportRegistry.php';
require_once dirname(__DIR__) . '/src/Sport/FootballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/VolleyballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/IceHockeyProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BasketballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/HandballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BadmintonProfile.php';

// ── Datenfunktionen (Abfragen) ────────────────────────────────────────────────
require_once __DIR__ . '/data_home.php';
require_once __DIR__ . '/data_liga.php';
require_once __DIR__ . '/../addon/player/frontend_spielerstat.php';
require_once __DIR__ . '/../addon/tipp/tipp_lib.php';
// pdf_export.php wird bewusst NICHT hier eingebunden: die Datei ist recht groß
// (PHP muss sie sonst bei JEDEM Seitenaufruf parsen, auch auf home.php und den
// Mini-Addons, die sie nie brauchen) und wird ausschließlich von liga.php
// tatsächlich verwendet - dort wird sie direkt eingebunden.
