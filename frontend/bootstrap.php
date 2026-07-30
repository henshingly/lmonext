<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.6.0
 * Changelog: 1.6.0 - Performance-/Robustheitsverbesserungen: getAdminSetting() liest alle
 *                     Einstellungen jetzt in EINER Abfrage pro Request statt einer eigenen
 *                     Abfrage pro Schlüssel. pdf_export.php wird nicht mehr pauschal für jeden
 *                     Seitenaufruf eingebunden (belastete auch home.php/die Mini-Addons, die es
 *                     nie brauchen), sondern nur noch direkt in liga.php, dem einzigen
 *                     tatsächlichen Verwender. Session-Cookie jetzt mit HttpOnly, SameSite=Lax
 *                     und (bei HTTPS) Secure. Globaler Exception-Handler: unerwartete Fehler
 *                     landen im Server-Log, Besucher sehen nur eine schlichte, technikfreie
 *                     Meldung statt Stacktrace/Dateipfaden
 * Changelog: 1.5.0
 * Changelog: 1.5.0 - data_spielerstat.php eingebunden (Besucher-Ansicht für das neue
 *                     Spielerstatistik-Addon, siehe admin/spielerstat_lib.php)
 * Changelog: 1.4.1 - Bugfix: Die in den Admin-Einstellungen konfigurierte "Standardsprache"
 *                     wurde im gesamten Besucherbereich nie berücksichtigt (getCurrentLanguage()
 *                     wurde ganz am Anfang der Datei OHNE den Standardsprache-Parameter
 *                     aufgerufen, bevor getAdminSetting() überhaupt verfügbar war – betraf
 *                     dadurch nicht nur die neuen Addons, sondern auch home.php/liga.php
 *                     direkt). Sprachauflösung jetzt an das Ende der Funktionsdefinitionen
 *                     verschoben, mit getAdminSetting('language', DEFAULT_LANGUAGE) als
 *                     Standardsprache-Parameter (identisches Muster wie admin/bootstrap.php)
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

// ── Datenfunktionen (Abfragen) ────────────────────────────────────────────────
require_once __DIR__ . '/data_home.php';
require_once __DIR__ . '/data_liga.php';
require_once __DIR__ . '/../addon/player/frontend_spielerstat.php';
// pdf_export.php wird bewusst NICHT hier eingebunden: die Datei ist recht groß
// (PHP muss sie sonst bei JEDEM Seitenaufruf parsen, auch auf home.php und den
// Mini-Addons, die sie nie brauchen) und wird ausschließlich von liga.php
// tatsächlich verwendet - dort wird sie direkt eingebunden.
