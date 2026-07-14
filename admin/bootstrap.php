<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.6.0
 * Changelog: 1.6.0 - "Passwort vergessen"-Grundlagen ergänzt: ensurePasswordResetSchema()
 *                     (email-Spalte + admin_password_resets-Tabelle, Migration für bestehende
 *                     Installationen), getSiteBaseUrl(), sendPasswordResetEmail() (reine
 *                     PHP-mail()-Funktion, keine externe Mail-Bibliothek)
 * Changelog: 1.5.0 - getAppVersion() ergänzt (liest Version aus composer.json)
 * Changelog: 1.4.4 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.4.3 - lang/i18n.php domain-fähig (admin/frontend getrennt): getCurrentLanguage()-Aufruf angepasst ('admin' explizit übergeben)
 * Changelog: 1.4.2 - koModusLabel() ergänzt (übersetzte KO-Modus-Bezeichnungen; KO_MODUS-Werte bleiben interne Keys)
 * Changelog: 1.4.1 - Standardsprache aus admin_settings ("language") wird an getCurrentLanguage() übergeben
 * Changelog: 1.4.0 - Mehrsprachigkeit: lang/i18n.php eingebunden, initLanguage() aufgerufen
 * Changelog: 1.3.4 - tsToDatetime: konfigurierbare Zeitzone statt UTC; getAdminTimezone(); ensureAdminSettings()
 * Changelog: 1.3.2 - ensureSpielstatusColumns(): status (n.V./i.E.) + bericht_url Spalten
 * Changelog: 1.3.1 - ensureLastLoginColumn() Migration für admin_users.last_login
 * Changelog: 1.3.0 - ensureArchivColumns() hinzugefügt
 * Changelog: 1.2.0 - config.php Pfad: __DIR__ -> dirname(__DIR__)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── Konfiguration aus config.php laden ───────────────────────────────────────
$_configFile = dirname(__DIR__) . '/config.php';
if (!file_exists($_configFile)) {
    http_response_code(503);
    die('<html><head><meta charset="UTF-8"><title>Nicht konfiguriert</title>'
      . '<style>body{font-family:system-ui;background:#0f1117;color:#e2e8f0;display:flex;'
      . 'align-items:center;justify-content:center;min-height:100vh;margin:0}'
      . '.box{background:#1a1d27;border:1px solid #2e3247;border-radius:8px;padding:32px 40px;text-align:center}'
      . 'h2{color:#ef4444;margin-bottom:12px}p{color:#64748b;font-size:.9rem}'
      . 'a{color:#3b82f6}</style></head><body><div class="box">'
      . '<h2>⚠️ config.php nicht gefunden</h2>'
      . '<p>Bitte zuerst den <a href="install.php">Installer</a> ausführen.</p>'
      . '</div></body></html>');
}
require_once $_configFile;

defined('DB_PORT')      || define('DB_PORT',      3306);
defined('DB_CHARSET')   || define('DB_CHARSET',   'utf8mb4');
defined('DB_PREFIX')    || define('DB_PREFIX',    '');
defined('ADMIN_TITLE')  || define('ADMIN_TITLE',  'LMOnext Admin');
defined('SESSION_NAME') || define('SESSION_NAME', 'lmonext_admin');

session_name(SESSION_NAME);
session_start();

// ── Mehrsprachigkeit ─────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/lang/i18n.php';
ensureAdminSettings(); // legt admin_settings ggf. an (Funktionsdefinition weiter unten, aber dank Hoisting hier schon nutzbar)
getCurrentLanguage('admin', getAdminSetting('language', DEFAULT_LANGUAGE)); // ermittelt/persistiert Sprache; kann bei ?lang=xx redirecten

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

function isLoggedIn() : bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function requireLogin() : void
{
    if (!isLoggedIn()) {
        header('Location: ?action=login');
        exit;
    }
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

/**
 * Absolute Basis-URL des Projekt-Roots (ohne abschließenden Slash), aus dem
 * aktuellen Request abgeleitet – z.B. "https://www.example.org/lmo". Wird für
 * den Link im "Passwort vergessen"-Mail gebraucht, da config.php keine
 * eigene Site-URL-Einstellung hat.
 */
function getSiteBaseUrl() : string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin.php'));
    $dir    = rtrim($dir, '/');
    return $scheme . '://' . $host . $dir;
}

/**
 * Verschickt die "Passwort vergessen"-E-Mail über die eingebaute mail()-
 * Funktion (bewusst ohne externe Mail-Bibliothek, wie beim Rest des
 * Projekts). Gibt zurück, ob mail() den Versand an den lokalen Mailserver
 * angenommen hat (keine Garantie für tatsächliche Zustellung).
 */
function sendPasswordResetEmail(string $toEmail, string $resetLink) : bool
{
    $siteTitle = defined('ADMIN_TITLE') ? ADMIN_TITLE : 'LMOnext';
    $subject   = t('mail_reset_subject', ['site' => $siteTitle]);
    $body      = t('mail_reset_body', ['link' => $resetLink, 'hours' => 4]);

    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host      = preg_replace('/:\d+$/', '', $host); // Port entfernen, falls vorhanden
    $fromAddr  = 'no-reply@' . $host;

    $headers   = "From: {$siteTitle} <{$fromAddr}>\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($toEmail, $subject, $body, $headers);
}

function flash(string $msg, string $type = 'success') : void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash() : ? array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url) : never
{
    header('Location: ' . $url);
    exit;
}

// ── Timestamp → DATETIME (unterstützt negative Werte, d.h. vor 1970) ─────────
/**
 * Konvertiert einen Unix-Timestamp (auch negativ) sicher in einen
 * MySQL-DATETIME-String. LMO speichert Timestamps in Lokalzeit → UTC-Offset
 * wird NICHT angewendet; der Wert wird direkt als UTC-äquivalent behandelt,
 * da der LMO selbst keine Zeitzonenkorrektur vornimmt.
 */
function ensureAdminSettings() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('admin_settings').' (
            `key`   VARCHAR(64)   NOT NULL PRIMARY KEY,
            `value` VARCHAR(255)  NOT NULL DEFAULT \'\'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        // Standard: Europe/Berlin
        $db->exec('INSERT IGNORE INTO '.tbl('admin_settings') . ' (`key`, `value`) VALUES (\'timezone\', \'Europe/Berlin\')');
    } catch (Throwable) {}
}

function getAdminSetting(string $key, string $default = '') : string
{
    try {
        $s = getDB()->prepare('SELECT `value` FROM '.tbl('admin_settings') . ' WHERE `key`=?');
        $s->execute([$key]);
        $v = $s->fetchColumn();
        return $v !== false ? (string)$v : $default;
    } catch (Throwable) { return $default; }
}

function getAdminTimezone(): DateTimeZone
{
    static $tz = null;
    if ($tz !== null) return $tz;
    try {
        $tzName = getAdminSetting('timezone', 'Europe/Berlin');
        $tz = new DateTimeZone($tzName);
    } catch (Throwable) {
        $tz = new DateTimeZone('Europe/Berlin');
    }
    return $tz;
}

function tsToDatetime(string|int $ts) : ? string
{
    $ts = (int)$ts;
    if ($ts === 0) {
        return null;
    }
    try {
        $dt = new DateTime('@' . $ts); // UTC-Basis
        $dt->setTimezone(getAdminTimezone()); // In konfigurierte Zeitzone konvertieren
        return $dt->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return null;
    }
}

function generateRoundRobin(array $teamIds) : array
{
    $n = count($teamIds);
    if ($n % 2 !== 0) {
        $teamIds[] = -1;
        $n++;
    }  // -1 = Dummy (spielfrei); 0 ist ein gueltiger Team-Index!

    $rounds = [];
    $fixed  = $teamIds[0];
    $rotate = array_slice($teamIds, 1);
    $half   = $n / 2;

    for ($r = 0; $r < $n - 1; $r++) {
        $ring  = array_merge([$fixed], $rotate);
        $pairs = [];
        for ($i = 0; $i < $half; $i++) {
            $h = $ring[$i];
            $g = $ring[$n - 1 - $i];
            if ($h !== -1 && $g !== -1) {
                $pairs[] = [$h, $g];
            }
        }
        $rounds[$r + 1] = $pairs;
        $last = array_pop($rotate);
        array_unshift($rotate, $last);
    }

    // Rueckrunde: Heimrecht tauschen
    $hinRunden = count($rounds);
    $hinKeys   = array_keys($rounds);
    foreach ($hinKeys as $nr) {
        $rueck = [];
        foreach ($rounds[$nr] as [$h, $g]) { $rueck[] = [$g, $h]; }
        $rounds[$nr + $hinRunden] = $rueck;
    }

    return $rounds;
}

// ── Routing ── (existing code continues below)
function ensureArchivColumns() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('liga_archiv_folders').' (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `parent_id`    INT          NULL DEFAULT NULL,
            `name`         VARCHAR(120) NOT NULL DEFAULT \'\',
            `beschreibung` VARCHAR(255) NOT NULL DEFAULT \'\',
            `sort`         SMALLINT     NOT NULL DEFAULT 0,
            KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('liga'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('archiv_folder_id', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga').' ADD COLUMN `archiv_folder_id` INT NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

function ensureLastLoginColumn() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('admin_users'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('last_login', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('admin_users').' ADD COLUMN `last_login` DATETIME NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

/**
 * Legt die admin_users.email-Spalte + die admin_password_resets-Tabelle an,
 * falls sie fehlen ("Passwort vergessen"-Funktion). Frische Installationen
 * bekommen beides schon direkt über install.php – dieselbe Migration hier
 * sorgt dafür, dass es auch für bereits bestehende Installationen ohne
 * erneuten Install-Lauf funktioniert (gleiches Muster wie
 * ensureLastLoginColumn()).
 */
function ensurePasswordResetSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('admin_users'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('email', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('admin_users').' ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL');
        }
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('admin_password_resets').' (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`    INT          NOT NULL,
            `token`      VARCHAR(64)  NOT NULL,
            `expires_at` DATETIME     NOT NULL,
            `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `token` (`token`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (Throwable) {}
}

function ensureSpielstatusColumns() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('liga_partien'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('status', $cols, true)) {
            // 0 = normal, 1 = nach Elfmeterschießen (i.E.), 2 = nach Verlängerung (n.V.) — LMO-Original-Mapping
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `status` TINYINT NOT NULL DEFAULT 0');
        }
        if (!in_array('bericht_url', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `bericht_url` VARCHAR(500) NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

function ensureKoLabelColumns() : void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $db  = getDB();
        $tbl = DB_PREFIX . 'liga_partien';
        $cols = $db->query("SHOW COLUMNS FROM `{$tbl}`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('heim_label', $cols, true)) {
            $db->exec("ALTER TABLE `{$tbl}` ADD COLUMN `heim_label` VARCHAR(120) NULL DEFAULT NULL AFTER `heim_id`");
        }
        if (!in_array('gast_label', $cols, true)) {
            $db->exec("ALTER TABLE `{$tbl}` ADD COLUMN `gast_label` VARCHAR(120) NULL DEFAULT NULL AFTER `gast_id`");
        }
        // heim_id / gast_id dürfen jetzt NULL sein (Platzhalter)
        $db->exec("ALTER TABLE `{$tbl}` MODIFY COLUMN `heim_id` INT NULL, MODIFY COLUMN `gast_id` INT NULL");
    } catch (Throwable) { /* nicht-kritisch – läuft schon */ }
}


// Wert wird in liga_spieltage.modus gespeichert.
// 0 = normale Liga-Runde (kein KO)
// KO-Modi:
const KO_MODUS = [
    1 => 'Einzelspiel',
    2 => 'Hin- und Rückspiel',
    3 => 'Best of 3',
    5 => 'Best of 5',
    7 => 'Best of 7',
];
// Standard-KO-Modus bei Neu-Erstellung
const KO_MODUS_DEFAULT = 1;

// Übersetzte Anzeige-Bezeichnung für einen KO-Modus-Wert (KO_MODUS-Keys bleiben
// stabile interne Werte, nur das Label wird über t() lokalisiert).
function koModusLabel(int $val) : string
{
    return match ($val) {
        1 => t('ko_mode_1'),
        2 => t('ko_mode_2'),
        3 => t('ko_mode_3'),
        5 => t('ko_mode_5'),
        7 => t('ko_mode_7'),
        default => (string)$val,
    };
}

