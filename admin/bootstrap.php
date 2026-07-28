<?php
/**
 * Project: LMOnext
 * Filename: bootstrap.php
 * Fileversion: 1.10.0
 * Changelog: 1.10.0 - Session-Cookie jetzt mit HttpOnly, SameSite=Lax und (bei HTTPS) Secure,
 *                     analog zu frontend/bootstrap.php 1.6.0. Globaler Exception-Handler:
 *                     unerwartete Fehler landen zusätzlich im Server-Log (Kurzfassung bleibt im
 *                     Adminbereich sichtbar, da für Fehlersuche durch den Betreiber hilfreich)
 * Changelog: 1.9.0
 * Changelog: 1.9.0 - Bugfix: die "(heute TEAM_HEUTE)"-Kennzeichnung im Teamvergleich hing
 *                     bisher vom zufällig angeklickten Team ab (z.B. je nachdem von welcher
 *                     Liga/welchem Spiel aus man den Vergleich öffnete, zeigte mal der eine,
 *                     mal der andere Name als "heute"). Neue Spalte team_links.newer_team_id
 *                     legt jetzt fest, welches Team der tatsächlich aktuelle Name ist –
 *                     unabhängig vom Aufrufkontext. addTeamLink() nimmt das jetzt optional
 *                     entgegen, neue Funktion setTeamLinkDirection() zum nachträglichen Ändern.
 *                     Bestehende Verknüpfungen ohne Richtungsangabe (newer_team_id NULL)
 *                     fallen weiterhin auf das alte, kontextabhängige Verhalten zurück, bis die
 *                     Richtung nachträglich gesetzt wird
 * Changelog: 1.8.0
 * Changelog: 1.8.0 - Neue Funktionen für Team-Verknüpfungen ergänzt: ensureTeamLinksSchema()/
 *                     getTeamLinksForTeam()/addTeamLink()/deleteTeamLink() (Tabelle
 *                     team_links). Nicht-destruktive Alternative zu mergeTeams() für
 *                     Umbenennung/Fusion/Abspaltung – beide Team-Datensätze bleiben
 *                     eigenständig, nur eine Verknüpfung wird gespeichert. Wird von
 *                     resolveLinkedTeamIds() in data_liga.php für den Teamvergleich genutzt
 * Changelog: 1.7.4
 * Changelog: 1.7.4 - Umbenennung auf Nutzerwunsch: interne Bezeichnungen jetzt durchgehend auf
 *                     Englisch ("League Key" statt der vorherigen deutschen Bezeichnung, die
 *                     hier nicht mehr vorkommen soll). Der sichtbare UI-Text hieß schon vorher
 *                     "Schlüsselplan" und ist unverändert. Funktionsname, Konstante und interner
 *                     Modus-Wert entsprechend angepasst (siehe league-key_data.php)
 * Changelog: 1.7.3
 * Changelog: 1.7.3 - Logo-Ordner von assets/img/Teams auf assets/img/teams umbenannt
 *                     (kleingeschrieben)
 * Changelog: 1.7.2
 * Changelog: 1.7.2 - Neues Feature "Teams (global)": Logo & Vereinslink. ensureTeamUrlSchema()
 *                     ergänzt teams_global.url. Neue Funktionen findTeamLogoPath()/
 *                     deleteTeamLogo()/saveTeamLogoUpload(): Team-Logos liegen als
 *                     assets/img/teams/{team-id}.{ext} (SVG/JPG/PNG/GIF, Mindesthöhe 50px,
 *                     Inhalts-/MIME-Prüfung statt reiner Endungs-Prüfung), keine eigene
 *                     DB-Spalte nötig, da einfach übers Dateisystem gefunden
 * Changelog: 1.7.1
 * Changelog: 1.7.1 - buildScheduleForMode('none'): legt jetzt trotzdem die korrekte Anzahl
 *                     Spieltage/Begegnungen an (wie ein normaler Rundenplan für die Teamzahl),
 *                     nur mit Leerteam-Platzhaltern (-1, siehe createLigaInDB()) statt echter
 *                     Paarungen – vorher wurden bei "kein Spielplan" gar keine Spieltage/
 *                     Partien-Zeilen angelegt
 * Changelog: 1.7.0 - Neue Funktionen getLeagueKeyPattern()/buildScheduleForMode(): Spielplan
 *                     für reguläre Ligen kann jetzt wahlweise nach dem DFB-League-Key-Muster
 *                     (siehe admin/league-key_data.php, für 6/8/10/12/14/16/18 Teams), per
 *                     Zufall (bisheriges generateRoundRobin()) oder gar nicht erstellt werden
 * Changelog: 1.6.0
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
session_set_cookie_params([
    'httponly' => true,
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
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

const TEAM_LOGO_ALLOWED_EXT  = ['svg', 'jpg', 'jpeg', 'png', 'gif'];
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

