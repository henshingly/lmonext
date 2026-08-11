<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.12.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 */

// ── Globale Fehlerbehandlung ──────────────────────────────────────────────────
// Jede nicht abgefangene Exception landet im Server-Error-Log, im Adminbereich
// selbst (angemeldete Nutzer) wird die eigentliche Meldung trotzdem angezeigt,
// da das für die Fehlersuche during der Entwicklung/Wartung hilfreich ist -
// nur der Stacktrace/Dateipfad bleibt dem Besucherbereich vorbehalten (siehe
// frontend/bootstrap.php), hier reicht die Kurzfassung.
set_exception_handler(static function (Throwable $e) : void {
    error_log('LMOnext (admin) uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head>'
       . '<body style="font-family:system-ui;text-align:center;padding:60px 20px;color:#333">'
       . '<h2>⚠️ Ein unerwarteter Fehler ist aufgetreten</h2>'
       . '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</p>'
       . '<p style="color:#888;font-size:.85rem">Details wurden ins Server-Log geschrieben.</p>'
       . '</body></html>';
});

// ── Konfiguration ─────────────────────────────────────────────────────────────
// Lädt entweder die Composer/.env-Variante oder die klassische config.php,
// je nachdem was install.php beim Installieren angelegt hat (siehe
// config_loader.php im Projekt-Root für Details zu beiden Varianten).
require_once dirname(__DIR__) . '/config_loader.php';

defined('DB_PORT')      || define('DB_PORT',      3306);
defined('DB_CHARSET')   || define('DB_CHARSET',   'utf8mb4');
defined('DB_PREFIX')    || define('DB_PREFIX',    '');
defined('ADMIN_TITLE')  || define('ADMIN_TITLE',  'LMOnext Admin');
defined('SESSION_NAME') || define('SESSION_NAME', 'lmonext_admin');

session_name(SESSION_NAME);
session_set_cookie_params([
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

// ── Mehrsprachigkeit ─────────────────────────────────────────────────────────
require_once dirname(__DIR__) . '/lang/i18n.php';

// ── Sport-Profile (Beitrag: Torsten Hofmann) - bewusst NUR die Sport-Klassen,
// NICHT die Liga-Trait-Kette (LigaService, StandingsTrait, ...): Torstens
// Version band diese komplett in admin/bootstrap.php ein, was der bewussten
// Trennung von Admin- und Frontend-Bootstrap widerspricht (getDB()/tbl() sind
// hier komplett eigenständig definiert, siehe "Tipps nachtragen"-Feature).
// Die Sport-Profile selbst sind zustandslos (kein DB-Zugriff im Konstruktor,
// siehe src/Sport/INTEGRATION.md) und daher gefahrlos einbindbar.
require_once dirname(__DIR__) . '/src/Sport/SportProfile.php';
require_once dirname(__DIR__) . '/src/Sport/SportRegistry.php';
require_once dirname(__DIR__) . '/src/Sport/FootballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/VolleyballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/IceHockeyProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BasketballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/HandballProfile.php';
require_once dirname(__DIR__) . '/src/Sport/BadmintonProfile.php';

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
        // Startwerte, die eine Installation von Anfang an sinnvoll erwartbar
        // machen sollen (bei frischen Installationen bereits durch install.php
        // selbst gesetzt, siehe dort - hier zusätzlich für Bestandsinstallationen,
        // die diese install.php-Version nie durchlaufen haben)
        $db->exec('INSERT IGNORE INTO '.tbl('admin_settings') . ' (`key`, `value`) VALUES (\'timezone\', \'Europe/Berlin\')');
        $db->exec('INSERT IGNORE INTO '.tbl('admin_settings') . ' (`key`, `value`) VALUES (\'show_back_link\', \'1\')');
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

/**
 * Liefert das vorgefertigte DFB-League-Key-Spielplanmuster für die
 * angegebene Teamzahl, falls eines hinterlegt ist (siehe
 * admin/league-key_data.php) – sonst null. Anders als die per Zufall
 * erzeugte generateRoundRobin()-Reihenfolge folgt der League Key einer
 * traditionellen, festen Paarungslogik, wie sie deutsche Fußballverbände
 * für Ligen üblicher Größe verwenden.
 */
function getLeagueKeyPattern(int $teamCount) : ?array
{
    return LEAGUEKEYS_PATTERNS[$teamCount] ?? null;
}

/**
 * Baut den Spielplan für eine reguläre Liga gemäß gewähltem Modus:
 * 'leaguekey' (falls für die Teamzahl vorhanden, sonst automatisch
 * Rückfall auf 'random'), 'random' (bisheriges Verhalten, generateRoundRobin()
 * mit fortlaufender Team-Reihenfolge) oder 'none' (kein Spielplan).
 *
 * @return array<int,array<int,array{0:int,1:int}>>
 */
function buildScheduleForMode(int $teamCount, string $mode) : array
{
    if ($mode === 'none') {
        // Trotz "kein Spielplan" soll die Liga bereits die richtige Anzahl
        // Spieltage/Begegnungen bekommen (wie ein normaler Rundenplan für
        // diese Teamzahl) – nur eben mit einem Leerteam ("___", siehe
        // getOrCreateDummyTeam()) statt echter Paarungen. So kann der Admin
        // jede Begegnung anschließend einzeln manuell im Spieltag-Editor
        // eintragen, ohne vorher Spieltage/Partien-Zeilen selbst anlegen zu
        // müssen. -1 ist der von createLigaInDB() bereits unterstützte
        // Platzhalter-Wert für "Leerteam".
        $shape = generateRoundRobin(range(0, $teamCount - 1));
        $blank = [];
        foreach ($shape as $nr => $pairs) {
            $blank[$nr] = array_fill(0, count($pairs), [-1, -1]);
        }
        return $blank;
    }
    if ($mode === 'leaguekey') {
        $pattern = getLeagueKeyPattern($teamCount);
        if ($pattern !== null) {
            return $pattern;
        }
        // Kein Muster für diese Teamzahl hinterlegt -> auf Zufall zurückfallen
    }
    return generateRoundRobin(range(0, $teamCount - 1));
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

/**
 * Team-Verknüpfungen: nicht-destruktive Verbindung zwischen zwei
 * eigenständigen Team-Datensätzen (im Unterschied zu mergeTeams(), das einen
 * Datensatz permanent löscht). Deckt drei Fälle ab, ohne sie technisch
 * unterscheiden zu müssen (siehe resolveLinkedTeamIds() in data_liga.php für
 * die transitive Auflösung):
 *   - Umbenennung: A↔B (eine Verknüpfung)
 *   - Fusion: A↔C, B↔C (zwei Verknüpfungen zum neuen Verein)
 *   - Abspaltung: A↔B, A↔C (zwei Verknüpfungen vom ursprünglichen Verein)
 * Wird u.a. vom Teamvergleich (H2H) genutzt, um Spiele unter allen
 * verknüpften (historischen) Namen mit anzuzeigen.
 */
function ensureTeamLinksSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('team_links').' (
            `id`             INT AUTO_INCREMENT PRIMARY KEY,
            `team_a_id`      INT NOT NULL,
            `team_b_id`      INT NOT NULL,
            `type`           ENUM(\'umbenennung\',\'fusion\',\'abspaltung\',\'sonstige\') NOT NULL DEFAULT \'umbenennung\',
            `note`           VARCHAR(255) NULL DEFAULT NULL,
            `newer_team_id`  INT NULL DEFAULT NULL,
            `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `team_a_id` (`team_a_id`),
            KEY `team_b_id` (`team_b_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        // Migration für bereits bestehende Installationen (Tabelle existierte
        // schon vor Einführung von newer_team_id, siehe Changelog 1.9.0)
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('team_links'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('newer_team_id', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('team_links').' ADD COLUMN `newer_team_id` INT NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

/**
 * Alle direkten Verknüpfungen eines Teams (für die Anzeige/Verwaltung in
 * "Teams (global)"), inkl. Name des jeweils anderen Teams. Liefert NUR die
 * direkten Verknüpfungen (nicht transitiv aufgelöst) – für die
 * Vergleichsauflösung siehe resolveLinkedTeamIds() in data_liga.php.
 */
function getTeamLinksForTeam(int $teamId) : array
{
    ensureTeamLinksSchema();
    try {
        $s = getDB()->prepare(
            'SELECT tl.id, tl.type, tl.note, tl.newer_team_id,
                    CASE WHEN tl.team_a_id = ? THEN tl.team_b_id ELSE tl.team_a_id END AS other_id,
                    tg.name AS other_name
               FROM '.tbl('team_links').' tl
               JOIN '.tbl('teams_global').' tg
                 ON tg.id = CASE WHEN tl.team_a_id = ? THEN tl.team_b_id ELSE tl.team_a_id END
              WHERE tl.team_a_id = ? OR tl.team_b_id = ?
              ORDER BY tg.name'
        );
        $s->execute([$teamId, $teamId, $teamId, $teamId]);
        return $s->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Legt eine neue Team-Verknüpfung an. Verhindert Duplikate (in beide
 * Richtungen geprüft) und Selbstverknüpfung. $newerTeamId (optional) legt
 * fest, welches der beiden Teams der heutige/aktuelle Name ist – wichtig für
 * die "(heute TEAM_HEUTE)"-Kennzeichnung im Teamvergleich, die sonst vom
 * zufällig angeklickten Team abhinge statt von einer festen Richtung
 * (z.B. bei einer Umbenennung: der alte Name soll IMMER als "heute
 * NEUER_NAME" gekennzeichnet werden, unabhängig davon, von welchem Spiel aus
 * man den Vergleich öffnet). Muss einer der beiden Team-IDs entsprechen
 * oder null sein (= Richtung unbekannt/nicht festgelegt).
 */
function addTeamLink(int $teamAId, int $teamBId, string $type, string $note, ?int $newerTeamId = null) : bool
{
    if ($teamAId <= 0 || $teamBId <= 0 || $teamAId === $teamBId) {
        return false;
    }
    if (!in_array($type, ['umbenennung', 'fusion', 'abspaltung', 'sonstige'], true)) {
        $type = 'sonstige';
    }
    if ($newerTeamId !== null && $newerTeamId !== $teamAId && $newerTeamId !== $teamBId) {
        $newerTeamId = null;
    }
    ensureTeamLinksSchema();
    try {
        $db = getDB();
        $exists = $db->prepare(
            'SELECT id FROM '.tbl('team_links').'
              WHERE (team_a_id = ? AND team_b_id = ?) OR (team_a_id = ? AND team_b_id = ?)'
        );
        $exists->execute([$teamAId, $teamBId, $teamBId, $teamAId]);
        if ($exists->fetch() !== false) {
            return false; // schon verknüpft
        }
        $db->prepare('INSERT INTO '.tbl('team_links').' (team_a_id,team_b_id,type,note,newer_team_id) VALUES (?,?,?,?,?)')
           ->execute([$teamAId, $teamBId, $type, $note !== '' ? $note : null, $newerTeamId]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Ändert nachträglich, welches Team einer bestehenden Verknüpfung der
 * heutige/aktuelle Name ist (siehe addTeamLink()). $newerTeamId muss einem
 * der beiden verknüpften Teams entsprechen, oder null (= zurücksetzen auf
 * "unbekannt").
 */
function setTeamLinkDirection(int $linkId, ?int $newerTeamId) : bool
{
    ensureTeamLinksSchema();
    try {
        $db = getDB();
        $s = $db->prepare('SELECT team_a_id, team_b_id FROM '.tbl('team_links').' WHERE id = ?');
        $s->execute([$linkId]);
        $row = $s->fetch();
        if ($row === false) {
            return false;
        }
        if ($newerTeamId !== null && $newerTeamId !== (int)$row['team_a_id'] && $newerTeamId !== (int)$row['team_b_id']) {
            return false;
        }
        $db->prepare('UPDATE '.tbl('team_links').' SET newer_team_id = ? WHERE id = ?')->execute([$newerTeamId, $linkId]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/** Löscht eine Team-Verknüpfung anhand ihrer ID. */
function deleteTeamLink(int $linkId) : bool
{
    ensureTeamLinksSchema();
    try {
        getDB()->prepare('DELETE FROM '.tbl('team_links').' WHERE id = ?')->execute([$linkId]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Stellt sicher, dass teams_global.url existiert (Website-/Vereinslink,
 * siehe "Teams (global)" → Logo & Link). Das Team-Logo selbst braucht keine
 * eigene Spalte – es liegt einfach unter assets/img/teams/{id}.{ext} und
 * wird beim Anzeigen per Dateisystem-Check gefunden (siehe
 * findTeamLogoPath() in handler_liga.php).
 */
function ensureTeamUrlSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db   = getDB();
        $cols = $db->query('SHOW COLUMNS FROM '.tbl('teams_global'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('url', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('teams_global').' ADD COLUMN `url` VARCHAR(500) NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

// Reihenfolge = Priorität bei mehreren vorhandenen Dateien für dieselbe
// Team-ID (z.B. team.svg UND team.png) - SVG zuerst (beste Qualität), dann
// Rasterformate. Diese Konstante wird nur für die Admin-Vorschau (Browser-
// Ausgabe) und zum Löschen genutzt, NIE für den PDF-Export - der hat seine
// eigene, unabhängige Priorität (Rasterformate zuerst), siehe
// TeamFormattingTrait::TEAM_LOGO_EXT_LIST_PDF für die ausführliche
// Begründung. Admin-Vorschau soll dasselbe zeigen wie die echte Website.
const TEAM_LOGO_ALLOWED_EXT  = ['svg', 'png', 'jpg', 'jpeg', 'gif'];
const TEAM_LOGO_MIN_HEIGHT_PX = 50;

/** Absoluter Dateisystempfad zum Team-Logo-Verzeichnis (wird bei Bedarf angelegt). */
function teamLogoDir() : string
{
    $dir = dirname(__DIR__) . '/assets/img/teams';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Sucht ein hochgeladenes Logo für die angegebene Team-ID (Dateiname
 * "{id}.{ext}", siehe TEAM_LOGO_ALLOWED_EXT). Gibt den Web-Pfad relativ zum
 * Projekt-Root zurück (z.B. "assets/img/teams/42.png"), oder null wenn kein
 * Logo hinterlegt ist.
 */
function findTeamLogoPath(int $teamId) : ?string
{
    $dir = teamLogoDir();
    foreach (TEAM_LOGO_ALLOWED_EXT as $ext) {
        if (is_file($dir . '/' . $teamId . '.' . $ext)) {
            return 'assets/img/teams/' . $teamId . '.' . $ext;
        }
    }
    return null;
}

/** Entfernt ein evtl. vorhandenes Logo (alle möglichen Endungen) für die Team-ID. */
function deleteTeamLogo(int $teamId) : void
{
    $dir = teamLogoDir();
    foreach (TEAM_LOGO_ALLOWED_EXT as $ext) {
        $path = $dir . '/' . $teamId . '.' . $ext;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * Prüft und speichert ein hochgeladenes Team-Logo ($_FILES-Eintrag). Erlaubt
 * sind SVG, JPEG/JPG, PNG und GIF, mit einer Mindesthöhe von
 * TEAM_LOGO_MIN_HEIGHT_PX Pixeln. Bei SVG lässt sich die Höhe nicht immer
 * zuverlässig aus der Datei auslesen (Vektorgrafik, oft ohne feste
 * Pixelmaße) – dort wird nur geprüft, wenn width/height/viewBox tatsächlich
 * vorhanden sind und explizit zu klein wären; ansonsten wird SVG nicht
 * pauschal abgelehnt. Ein evtl. vorhandenes altes Logo (auch mit anderer
 * Dateiendung) wird vorher entfernt, damit nicht mehrere Logo-Dateien für
 * dieselbe Team-ID gleichzeitig existieren.
 *
 * @return array{ok:bool, error:?string}
 */
function saveTeamLogoUpload(int $teamId, array $file) : array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'error' => null]; // nichts hochgeladen -> kein Fehler, einfach nichts tun
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => t('teams_logo_err_upload')];
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') { $ext = 'jpg'; }
    if (!in_array($ext, ['svg', 'jpg', 'png', 'gif'], true)) {
        return ['ok' => false, 'error' => t('teams_logo_err_format')];
    }

    $tmpPath = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => t('teams_logo_err_upload')];
    }

    if ($ext === 'svg') {
        $content = (string)file_get_contents($tmpPath);
        // Grobe Inhaltsprüfung statt reiner Endungs-Prüfung: muss ein
        // <svg>-Wurzelelement enthalten (schützt vor umbenannten Dateien, die
        // gar kein SVG sind).
        if (stripos($content, '<svg') === false) {
            return ['ok' => false, 'error' => t('teams_logo_err_invalid')];
        }
        // Höhe nur prüfen, wenn sie sich eindeutig aus width/height/viewBox
        // ergibt – SVGs ohne feste Pixelmaße werden nicht abgelehnt.
        if (preg_match('/height=["\']?([\d.]+)/i', $content, $hm)) {
            if ((float)$hm[1] < TEAM_LOGO_MIN_HEIGHT_PX) {
                return ['ok' => false, 'error' => t('teams_logo_err_too_small', ['min' => TEAM_LOGO_MIN_HEIGHT_PX])];
            }
        }
    } else {
        $info = @getimagesize($tmpPath);
        if ($info === false) {
            return ['ok' => false, 'error' => t('teams_logo_err_invalid')];
        }
        $detectedExt = image_type_to_extension((int)$info[2], false);
        $detectedExt = $detectedExt === 'jpeg' ? 'jpg' : $detectedExt;
        if ($detectedExt !== $ext) {
            return ['ok' => false, 'error' => t('teams_logo_err_invalid')];
        }
        if ((int)$info[1] < TEAM_LOGO_MIN_HEIGHT_PX) {
            return ['ok' => false, 'error' => t('teams_logo_err_too_small', ['min' => TEAM_LOGO_MIN_HEIGHT_PX])];
        }
    }

    deleteTeamLogo($teamId); // altes Logo (ggf. andere Endung) zuerst entfernen
    $destPath = teamLogoDir() . '/' . $teamId . '.' . $ext;
    if (!move_uploaded_file($tmpPath, $destPath)) {
        return ['ok' => false, 'error' => t('teams_logo_err_upload')];
    }
    @chmod($destPath, 0644);
    return ['ok' => true, 'error' => null];
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

/**
 * On-demand-Migration für die Sport-Profile-Erweiterung (Beitrag: Torsten
 * Hofmann) - sport_type auf liga, extra_data (Sätze/Drittel/Viertel) auf
 * liga_partien. Bugfix: fehlte hier ursprünglich komplett - führte auf jeder
 * Installation, die install.php seit der Erweiterung noch nicht erneut
 * ausgeführt hatte, zu "Unknown column 'sport_type'" beim .l98-Import UND
 * beim Öffnen der Liga-Einstellungen (admin/data_loader.php liest
 * sport_type direkt, ohne eigene Absicherung). Analog zu
 * ensureSpielstatusColumns() bewusst zentral hier statt einzeln in jedem
 * Aufrufer geprüft.
 */
function ensureSportProfileColumns() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $ligaCols = $db->query('SHOW COLUMNS FROM '.tbl('liga'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('sport_type', $ligaCols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga')." ADD COLUMN `sport_type` VARCHAR(20) NOT NULL DEFAULT 'football' AFTER `archiv_folder_id`");
        }
        $partienCols = $db->query('SHOW COLUMNS FROM '.tbl('liga_partien'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('extra_data', $partienCols, true)) {
            $db->exec('ALTER TABLE '.tbl('liga_partien').' ADD COLUMN `extra_data` JSON NULL DEFAULT NULL AFTER `g_tore`');
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

