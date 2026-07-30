<?php
/**
 * Project: LMOnext
 * Filename: install.php
 * Fileversion: 1.9.0
 * Changelog: 1.9.0 - Zeitzonen-Auswahl direkt im Installationsformular (bei den Admin-
 *                     Zugangsdaten), gruppiert nach Kontinent über PHPs eingebaute
 *                     DateTimeZone::listIdentifiers() (buildTimezoneGroups()) - die gewählte
 *                     Zeitzone wird sofort mit in admin_settings geschrieben, statt erst
 *                     nachträglich über die Einstellungsseite gesetzt werden zu müssen
 * Changelog: 1.8.0
 * Changelog: 1.8.0 - admin_settings wird jetzt explizit bei der Installation angelegt und mit
 *                     sinnvollen Startwerten befüllt (show_back_link=1: Liga-Übersicht bei einer
 *                     frischen Installation sichtbar, timezone=Europe/Berlin), statt sich erst
 *                     beim ersten Admin-Seitenaufruf implizit auf die PHP-seitigen
 *                     Standardwerte zu verlassen. INSERT IGNORE - überschreibt bei erneuter
 *                     Installation über eine vorhandene DB keine bereits gesetzten Werte
 * Changelog: 1.7.0
 * Changelog: 1.7.0 - Zwei fehlende Voraussetzungsprüfungen ergänzt: Schreibrecht für store/
 *                     (Datenbank-Backups, wird bei Bedarf automatisch angelegt wie der
 *                     Team-Logo-Ordner) und die optionale bzip2-Erweiterung (zweites
 *                     Backup-Kompressionsformat neben gzip). Außerdem: DB-Verbindungsfehler
 *                     werden jetzt über translateDbError() in klare, handlungsleitende
 *                     Meldungen übersetzt (nicht erreichbarer Host, falscher Zugang, fehlende
 *                     Berechtigung zum Anlegen der Datenbank, unbekannter Host) statt der oft
 *                     kryptischen rohen PDO-Fehlermeldung
 * Changelog: 1.6.1
 * Changelog: 1.6.1 - Neue empfohlene (nicht blockierende) Prüfung für die ZipArchive-
 *                     Erweiterung ergänzt – wird für die Team-Logo-Mitsicherung bei
 *                     Backup/Wiederherstellung benötigt (siehe handler_backup.php 1.2.0)
 * Changelog: 1.6.0
 * Changelog: 1.6.0 - Systemprüfung um die seither hinzugekommenen Anforderungen ergänzt:
 *                     GD-Erweiterung (Team-Logo-Uploads/PNG-GIF-Einbettung in PDFs),
 *                     Imagick/rsvg-convert (SVG-Rasterisierung für PDF-Export, rein
 *                     informativ), Schreibrecht für assets/img/teams/ (wird bei Bedarf
 *                     automatisch angelegt). mbstring ist nicht mehr zwingend erforderlich –
 *                     der Code funktioniert inzwischen überall auch ohne (siehe
 *                     data_loader.php/handler_import_export.php/pdf_export.php), zählt daher
 *                     jetzt nur noch als Empfehlung statt die Installation zu blockieren. Neues
 *                     'required'-Feld pro Prüfung (Standard: true) unterscheidet Pflicht- von
 *                     Empfehlungs-Prüfungen; allChecksPassed() blockiert nur noch bei
 *                     fehlgeschlagenen Pflicht-Prüfungen
 * Changelog: 1.5.0
 * Changelog: 1.5.0 - Optionales E-Mail-Feld im Administrator-Konto ergänzt (für "Passwort
 *                     vergessen"), neue Spalte admin_users.email + Tabelle
 *                     admin_password_resets, inkl. Migration für bestehende Installationen
 * Changelog: 1.4.4 - Favicon-Dateien nach assets/favicon/ verschoben, Links angepasst
 * Changelog: 1.4.3 - Favicon-Verlinkung ergänzt (Basisset: apple-icon-180, favicon-32/16)
 * Changelog: 1.4.2 - Standard-Datenbank-Präfix von "olv_" auf "lmonext_" umgestellt
 *                     (gilt nur für neue Installationen, siehe Hinweis zu bestehenden DBs)
 * Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.4.0 - Mehrsprachigkeit: lang/i18n.php eingebunden, alle Texte über t() übersetzt, Sprachauswahl-Dropdown
 * Changelog: 1.3.0 - Tabelle liga_archiv_folders; archiv_folder_id in liga
 * Changelog: 1.2.0 - config.php-Generator; 2-Schritt-Assistent; Selbstlöschung
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * LMOnext – Installationsskript
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. Prüft Serverumgebung (PHP-Version, PDO, Extensions, Schreibrechte)
 * 2. Nimmt DB-Verbindung und Admin-Zugangsdaten entgegen
 * 3. Legt alle Tabellen an / migriert bestehende
 * 4. Erstellt den ersten Administrator (bcrypt)
 * 5. Schreibt config.php
 * 6. Löscht sich selbst
 * 7. Leitet zur Admin-Anmeldeseite weiter
 */
declare(strict_types=1);

// ── Mehrsprachigkeit ──────────────────────────────────────────────────────────
session_start();
require_once __DIR__ . '/lang/i18n.php';
getCurrentLanguage(); // ermittelt/persistiert Sprache; kann bei ?lang=xx redirecten

define('INSTALL_TITLE',   t('install_title'));
define('ADMIN_FILE',      __DIR__ . '/admin.php');
define('CONFIG_FILE',     __DIR__ . '/config.php');
define('MIN_PHP',         '8.2.0');
define('INSTALL_VERSION', '1.2.0');

// ── Hilfsfunktionen ───────────────────────────────────────────────────────────
function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function selfUrl(string $query = ''): string {
    $s    = $_SERVER['HTTPS'] ?? 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = $_SERVER['SCRIPT_NAME'] ?? '/install.php';
    $base = ($s !== 'off' ? 'https' : 'http') . '://' . $host . $path;
    return $query ? $base . '?' . $query : $base;
}
function adminUrl(): string {
    $s    = $_SERVER['HTTPS'] ?? 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/install.php'), '/');
    return ($s !== 'off' ? 'https' : 'http') . '://' . $host . $dir . '/admin.php?action=login';
}

// ── Systemprüfung ─────────────────────────────────────────────────────────────
function checkEnvironment(): array {
    $checks = [];
    $phpOk = version_compare(PHP_VERSION, MIN_PHP, '>=');
    $checks[] = ['label'=>t('install_check_php_version'), 'ok'=>$phpOk, 'required'=>true,
                 'info'=>PHP_VERSION.($phpOk?'':t('install_check_php_version_fail', ['min'=>MIN_PHP]))];
    $checks[] = ['label'=>t('install_check_pdo'),     'ok'=>extension_loaded('pdo'), 'required'=>true,
                 'info'=>extension_loaded('pdo')?t('install_available'):t('install_missing_ini')];
    $checks[] = ['label'=>t('install_check_pdo_mysql'), 'ok'=>extension_loaded('pdo_mysql'), 'required'=>true,
                 'info'=>extension_loaded('pdo_mysql')?t('install_available'):t('install_missing_pdo_mysql')];
    // mbstring ist NICHT mehr zwingend erforderlich: der Code fällt an allen
    // Stellen, die mbstring nutzen könnten (Team-Namens-Duplikaterkennung,
    // Fuzzy-Matching beim Import, PDF-Textkodierung), automatisch auf eine
    // mbstring-freie Alternative zurück (strtolower()+Umlaut-Ersetzung,
    // preg_split('//u',...), iconv()). Nur noch als Empfehlung geführt,
    // blockiert die Installation nicht mehr.
    $checks[] = ['label'=>t('install_check_mbstring'), 'ok'=>extension_loaded('mbstring'), 'required'=>false,
                 'info'=>extension_loaded('mbstring')?t('install_available'):t('install_recommended_missing')];
    // GD wird für Team-Logo-Uploads (PNG/GIF/JPG-Validierung) und für die
    // Einbettung von PNG/GIF-Logos in PDF-Exporte benötigt. Ohne GD
    // funktioniert LMOnext weiterhin vollständig – nur JPG-Logos lassen
    // sich dann in PDFs einbetten, PNG/GIF-Logos werden dort einfach
    // übersprungen (kein Absturz, siehe pdf_export.php).
    $checks[] = ['label'=>t('install_check_gd'), 'ok'=>extension_loaded('gd'), 'required'=>false,
                 'info'=>extension_loaded('gd')?t('install_available'):t('install_recommended_missing')];
    // Imagick + externes Tool "rsvg-convert" sind zwei unabhängige,
    // optionale Zusatzwege, um hochgeladene SVG-Team-Logos für den
    // PDF-Export zu rastern (die PDF-Engine kann SVG nicht selbst als
    // Vektorgrafik rendern). Rein informativ, da mindestens einer von
    // beiden ausreicht und beide komplett fehlen dürfen.
    $hasImagick    = class_exists('Imagick');
    $hasRsvgTool   = function_exists('shell_exec') && trim((string)@shell_exec('command -v rsvg-convert 2>/dev/null')) !== '';
    $svgRasterInfo = ($hasImagick || $hasRsvgTool)
        ? t('install_available') . ' (' . implode(' + ', array_filter([$hasImagick ? 'Imagick' : null, $hasRsvgTool ? 'rsvg-convert' : null])) . ')'
        : t('install_recommended_missing');
    $checks[] = ['label'=>t('install_check_svg_raster'), 'ok'=>($hasImagick || $hasRsvgTool), 'required'=>false,
                 'info'=>$svgRasterInfo];
    // ZipArchive wird benötigt, damit die Wartung-Seite (Backup/Wiederherstellung)
    // den Team-Logo-Ordner (assets/img/teams/) als begleitendes ZIP mitsichern
    // kann. Ohne diese Erweiterung funktioniert die Datenbank-Sicherung selbst
    // unverändert weiter – nur die Logos werden dann bei einem Backup nicht
    // mitgesichert (siehe handler_backup.php).
    $checks[] = ['label'=>t('install_check_zip'), 'ok'=>class_exists('ZipArchive'), 'required'=>false,
                 'info'=>class_exists('ZipArchive')?t('install_available'):t('install_recommended_missing')];
    $wr = is_writable(__DIR__);
    $checks[] = ['label'=>t('install_check_writable', ['dir'=>basename(__DIR__)]),'ok'=>$wr, 'required'=>true,
                 'info'=>$wr?t('install_writable_ok'):t('install_writable_fail')];
    // Eigener Ordner für Team-Logo-Uploads (siehe Admin → Teams (global)).
    // Wird bei Bedarf automatisch angelegt (analog zu teamLogoDir() in
    // admin/bootstrap.php); nur relevant, wenn das übergeordnete
    // Projektverzeichnis überhaupt schreibbar ist.
    $teamsDir = __DIR__ . '/assets/img/teams';
    if (!is_dir($teamsDir) && $wr) {
        @mkdir($teamsDir, 0755, true);
    }
    $teamsDirOk = is_dir($teamsDir) && is_writable($teamsDir);
    $checks[] = ['label'=>t('install_check_teams_dir'), 'ok'=>$teamsDirOk, 'required'=>false,
                 'info'=>$teamsDirOk?t('install_writable_ok'):t('install_recommended_missing')];
    // Eigener Ordner für Datenbank-Backups (siehe Admin → Wartung). Wird bei
    // Bedarf automatisch angelegt, analog zum Teams-Logo-Ordner oben; nur
    // relevant, wenn das übergeordnete Projektverzeichnis überhaupt
    // schreibbar ist. Ohne diesen Ordner funktioniert LMOnext an sich
    // vollständig – nur die Backup/Wiederherstellen-Funktion nicht.
    $storeDir = __DIR__ . '/store';
    if (!is_dir($storeDir) && $wr) {
        @mkdir($storeDir, 0755, true);
    }
    $storeDirOk = is_dir($storeDir) && is_writable($storeDir);
    $checks[] = ['label'=>t('install_check_store_dir'), 'ok'=>$storeDirOk, 'required'=>false,
                 'info'=>$storeDirOk?t('install_writable_ok'):t('install_recommended_missing')];
    // bzip2 ist eine von zwei optionalen Kompressionsarten für Datenbank-
    // Backups (neben gzip, das praktisch immer verfügbar ist). Fehlt bzip2,
    // steht beim Erstellen eines Backups im Admin-Bereich einfach nur die
    // gzip-Option zur Auswahl (siehe handler_backup.php, backupBzip2Available()).
    $checks[] = ['label'=>t('install_check_bzip2'), 'ok'=>function_exists('bzcompress'), 'required'=>false,
                 'info'=>function_exists('bzcompress')?t('install_available'):t('install_recommended_missing')];
    $checks[] = ['label'=>t('install_check_adminphp'),'ok'=>file_exists(ADMIN_FILE), 'required'=>true,
                 'info'=>file_exists(ADMIN_FILE)?t('install_adminphp_found'):t('install_adminphp_missing')];
    return $checks;
}
function allChecksPassed(array $checks): bool {
    foreach ($checks as $c) { if (($c['required'] ?? true) && !$c['ok']) return false; }
    return true;
}

// ── Datenbank-Setup ───────────────────────────────────────────────────────────
function setupDatabase(array $cfg): array {
    $errors = [];
    try {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4',
            $cfg['db_host'], (int)($cfg['db_port'] ?? 3306));
        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $db = preg_replace('/[^a-zA-Z0-9_]/', '', $cfg['db_name']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db}`");

        $p = $cfg['db_prefix'];

        $tables = [

            "CREATE TABLE IF NOT EXISTS `{$p}liga_archiv_folders` (
                `id`          INT AUTO_INCREMENT PRIMARY KEY,
                `parent_id`   INT          NULL DEFAULT NULL,
                `name`        VARCHAR(120) NOT NULL DEFAULT '',
                `beschreibung`VARCHAR(255) NOT NULL DEFAULT '',
                `sort`        SMALLINT     NOT NULL DEFAULT 0,
                KEY `parent_id` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}liga` (
                `id`               INT AUTO_INCREMENT PRIMARY KEY,
                `name`             VARCHAR(255) NOT NULL DEFAULT '',
                `datum`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `archiv_folder_id` INT          NULL DEFAULT NULL,
                UNIQUE KEY `uniq_liga_name` (`name`),
                KEY `archiv_folder_id` (`archiv_folder_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}teams_global` (
                `id`     INT AUTO_INCREMENT PRIMARY KEY,
                `name`   VARCHAR(255) NOT NULL DEFAULT '',
                `kurz`   VARCHAR(30)  NOT NULL DEFAULT '',
                `mittel` VARCHAR(80)  NOT NULL DEFAULT '',
                UNIQUE KEY `uniq_team_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}liga_teams` (
                `id`      INT AUTO_INCREMENT PRIMARY KEY,
                `liga_id` INT UNSIGNED NOT NULL,
                `team_id` INT UNSIGNED NOT NULL,
                UNIQUE KEY `liga_team` (`liga_id`, `team_id`),
                KEY `liga_id` (`liga_id`),
                KEY `team_id` (`team_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}liga_team_values` (
                `id`        INT AUTO_INCREMENT PRIMARY KEY,
                `liga_id`   INT          NOT NULL,
                `team_id`   INT          NOT NULL,
                `key_name`  VARCHAR(80)  NOT NULL,
                `key_value` TEXT         NOT NULL DEFAULT '',
                UNIQUE KEY `liga_team_key` (`liga_id`, `team_id`, `key_name`),
                KEY `liga_id` (`liga_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}liga_options` (
                `id`           INT AUTO_INCREMENT PRIMARY KEY,
                `liga_id`      INT          NOT NULL,
                `option_key`   VARCHAR(80)  NOT NULL,
                `option_value` TEXT         NOT NULL DEFAULT '',
                UNIQUE KEY `liga_option` (`liga_id`, `option_key`),
                KEY `liga_id` (`liga_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // modus: 0=Liga-Spieltag, 1-7=KO-Spielmodus
            "CREATE TABLE IF NOT EXISTS `{$p}liga_spieltage` (
                `id`      INT AUTO_INCREMENT PRIMARY KEY,
                `liga_id` INT      NOT NULL,
                `nummer`  SMALLINT NOT NULL DEFAULT 1,
                `start`   DATETIME NULL     DEFAULT NULL,
                `modus`   TINYINT  NOT NULL DEFAULT 0,
                UNIQUE KEY `liga_nummer` (`liga_id`, `nummer`),
                KEY `liga_id` (`liga_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // heim_id/gast_id NULL = KO-Platzhalter (Name in heim_label/gast_label)
            // spiel_nr: Liga='1','2',...  KO='Paarung_Spiel' z.B. '1_1','1_2'
            "CREATE TABLE IF NOT EXISTS `{$p}liga_partien` (
                `id`          INT AUTO_INCREMENT PRIMARY KEY,
                `spieltag_id` INT          NOT NULL,
                `heim_id`     INT          NULL DEFAULT NULL,
                `heim_label`  VARCHAR(120) NULL DEFAULT NULL,
                `gast_id`     INT          NULL DEFAULT NULL,
                `gast_label`  VARCHAR(120) NULL DEFAULT NULL,
                `h_tore`      TINYINT      NULL DEFAULT NULL,
                `g_tore`      TINYINT      NULL DEFAULT NULL,
                `zeit`        DATETIME     NULL DEFAULT NULL,
                `notiz`       VARCHAR(255) NULL DEFAULT NULL,
                `spiel_nr`    VARCHAR(20)  NOT NULL DEFAULT '1',
                KEY `spieltag_id` (`spieltag_id`),
                KEY `heim_id` (`heim_id`),
                KEY `gast_id` (`gast_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}admin_users` (
                `id`       INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(80)  NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `email`    VARCHAR(255) NULL DEFAULT NULL,
                UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}admin_password_resets` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `user_id`    INT          NOT NULL,
                `token`      VARCHAR(64)  NOT NULL,
                `expires_at` DATETIME     NOT NULL,
                `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `token` (`token`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Gleiches Schema wie die Lazy-Erstellung in admin/bootstrap.php
            // (ensureAdminSettings()) - hier zusätzlich explizit angelegt UND
            // mit sinnvollen Startwerten befüllt (siehe INSERT IGNORE unten),
            // damit eine frische Installation von Anfang an einen
            // vollständigen, erwartbaren Zustand hat (Liga-Übersicht
            // sichtbar usw.), statt sich erst beim ersten Admin-Seitenaufruf
            // implizit auf die PHP-seitigen Standardwerte zu verlassen.
            "CREATE TABLE IF NOT EXISTS `{$p}admin_settings` (
                `key`   VARCHAR(64)   NOT NULL PRIMARY KEY,
                `value` VARCHAR(255)  NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($tables as $sql) { $pdo->exec($sql); }

        // ── Sinnvolle Startwerte für admin_settings bei frischer Installation ──
        // INSERT IGNORE: bestehende, bereits explizit gesetzte Werte (z.B. bei
        // einer erneuten Installation über eine vorhandene DB) werden nicht
        // überschrieben - nur wirklich NEUE Installationen bekommen diese
        // Startwerte.
        $defaultSettings = [
            'show_back_link' => '1', // Liga-Übersicht (home.php + "Zur Übersicht"-Link) sichtbar
            'timezone'       => $cfg['timezone'] ?? 'Europe/Berlin', // vom Installationsformular gewählt
        ];
        $seedStmt = $pdo->prepare("INSERT IGNORE INTO `{$p}admin_settings` (`key`, `value`) VALUES (?, ?)");
        foreach ($defaultSettings as $settingKey => $settingValue) {
            $seedStmt->execute([$settingKey, $settingValue]);
        }

        // ── Migration bestehender Installationen ──────────────────────────────
        $partienCols = $pdo->query("SHOW COLUMNS FROM `{$p}liga_partien`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('heim_label', $partienCols, true)) {
            $pdo->exec("ALTER TABLE `{$p}liga_partien` ADD COLUMN `heim_label` VARCHAR(120) NULL DEFAULT NULL AFTER `heim_id`");
        }
        if (!in_array('gast_label', $partienCols, true)) {
            $pdo->exec("ALTER TABLE `{$p}liga_partien` ADD COLUMN `gast_label` VARCHAR(120) NULL DEFAULT NULL AFTER `gast_id`");
        }
        $pdo->exec("ALTER TABLE `{$p}liga_partien` MODIFY COLUMN `heim_id` INT NULL, MODIFY COLUMN `gast_id` INT NULL");

        // password_hash → password (Spaltenname-Migration)
        $userCols = $pdo->query("SHOW COLUMNS FROM `{$p}admin_users`")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('password_hash', $userCols, true) && !in_array('password', $userCols, true)) {
            $pdo->exec("ALTER TABLE `{$p}admin_users` CHANGE `password_hash` `password` VARCHAR(255) NOT NULL");
        }
        if (!in_array('email', $userCols, true)) {
            $pdo->exec("ALTER TABLE `{$p}admin_users` ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL");
        }

        // ── Admin-Benutzer anlegen / Passwort (und E-Mail, falls angegeben) aktualisieren ─
        $hash = password_hash($cfg['admin_pass'], PASSWORD_BCRYPT);
        $pdo->prepare(
            "INSERT INTO `{$p}admin_users` (username, `password`, `email`) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE `password`=VALUES(`password`), `email`=VALUES(`email`)"
        )->execute([$cfg['admin_user'], $hash, $cfg['admin_email'] !== '' ? $cfg['admin_email'] : null]);

    } catch (Throwable $e) {
        $errors[] = translateDbError($e);
    }
    return $errors;
}

/**
 * Übersetzt die häufigsten PDO-Verbindungsfehler beim Installieren in eine
 * klare, handlungsleitende Meldung statt der oft kryptischen rohen
 * Treiber-Fehlermeldung (z.B. "SQLSTATE[HY000] [2002] Connection refused").
 * Fällt auf die Originalmeldung zurück, wenn keine der bekannten
 * Fehlersituationen zutrifft.
 */
function translateDbError(Throwable $e): string {
    $msg = $e->getMessage();

    // MySQL/MariaDB-Fehlercodes (identisch für beide, da derselbe
    // Client-Treiber/dasselbe Protokoll verwendet wird). Bei
    // Verbindungsfehlern (schlägt schon in "new PDO(...)" fehl, bevor
    // überhaupt eine Anweisung läuft) steckt der Fehlercode meist direkt in
    // der Meldung selbst (z.B. "SQLSTATE[HY000] [2002] Connection refused"),
    // daher wird hier bewusst auf den Meldungstext gematcht statt auf
    // errorInfo (das ist für reine Verbindungsfehler oft nicht befüllt).
    if (str_contains($msg, '[2002]') || str_contains($msg, 'Connection refused') || str_contains($msg, 'php_network_getaddresses')) {
        return t('install_db_error_unreachable');
    }
    if (str_contains($msg, '[1045]') || str_contains($msg, 'Access denied')) {
        return t('install_db_error_access_denied');
    }
    if (str_contains($msg, '[1044]')) {
        return t('install_db_error_no_db_permission');
    }
    if (str_contains($msg, '[2005]') || str_contains($msg, 'known to the server') || str_contains($msg, 'getaddrinfo')) {
        return t('install_db_error_unknown_host');
    }
    return t('install_db_error_generic', ['msg' => $msg]);
}

/**
 * Baut die Zeitzonen-Auswahl fürs Installationsformular, gruppiert nach
 * Kontinent (z.B. "Europe" => ["Europe/Berlin", "Europe/Paris", ...]) -
 * genau wie die Zeitzonen-Auswahl in admin/view_settings.php, aber über
 * PHPs eingebaute DateTimeZone::listIdentifiers() erzeugt statt eine zweite,
 * riesige Liste zu pflegen, die mit der Zeit auseinanderlaufen könnte.
 *
 * @return array<string,array<int,string>>
 */
function buildTimezoneGroups() : array {
    $groups = [];
    foreach (DateTimeZone::listIdentifiers() as $tz) {
        $slashPos = strpos($tz, '/');
        $group = $slashPos !== false ? substr($tz, 0, $slashPos) : 'Other';
        $groups[$group][] = $tz;
    }
    ksort($groups);
    return $groups;
}

// ── config.php schreiben ──────────────────────────────────────────────────────
function writeConfig(array $cfg): bool {
    $host   = addslashes($cfg['db_host']);
    $port   = (int)($cfg['db_port'] ?? 3306);
    $name   = addslashes(preg_replace('/[^a-zA-Z0-9_]/', '', $cfg['db_name']));
    $user   = addslashes($cfg['db_user']);
    $pass   = addslashes($cfg['db_pass']);
    $prefix = preg_replace('/[^a-zA-Z0-9_]/', '', $cfg['db_prefix'] ?? 'lmonext_');
    $title  = addslashes($cfg['site_title'] ?? 'LMOnext Admin');
    $ts     = date('Y-m-d H:i:s');
    $ver    = INSTALL_VERSION;

    $content = <<<PHP
<?php
/**
 * LMOnext – Konfiguration
 * Generiert: {$ts} (LMOnext Installer v{$ver})
 * ─────────────────────────────────────────────
 * Diese Datei wurde automatisch erstellt.
 * Manuelle Änderungen sind möglich.
 */

// Datenbankverbindung
define('DB_HOST',    '{$host}');
define('DB_PORT',    {$port});
define('DB_NAME',    '{$name}');
define('DB_USER',    '{$user}');
define('DB_PASS',    '{$pass}');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX',  '{$prefix}');

// Anwendung
define('ADMIN_TITLE',   '{$title}');
define('SESSION_NAME',  'lmonext_admin');
define('APP_VERSION',   '{$ver}');
PHP;

    return file_put_contents(CONFIG_FILE, $content) !== false;
}

// ── Selbst löschen + Weiterleiten ─────────────────────────────────────────────
function selfDestructAndRedirect(): never {
    $url = adminUrl();
    register_shutdown_function(function () {
        if (file_exists(__FILE__)) { @unlink(__FILE__); }
    });
    header('Location: ' . $url);
    exit;
}

// ── Request-Verarbeitung ──────────────────────────────────────────────────────
$step   = (int)($_GET['step'] ?? 1);
$errors = [];
$checks = checkEnvironment();
$cfg = [
    'db_host'    => 'localhost',
    'db_port'    => '3306',
    'db_name'    => 'lmonext',
    'db_user'    => '',
    'db_prefix'  => 'lmonext_',
    'admin_user' => 'admin',
    'admin_email'=> '',
    'site_title' => 'LMOnext Admin',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $cfg = [
        'db_host'     => trim($_POST['db_host']    ?? 'localhost'),
        'db_port'     => trim($_POST['db_port']    ?? '3306'),
        'db_name'     => trim($_POST['db_name']    ?? ''),
        'db_user'     => trim($_POST['db_user']    ?? ''),
        'db_pass'     =>      $_POST['db_pass']    ?? '',
        'db_prefix'   => preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['db_prefix'] ?? 'lmonext_')),
        'admin_user'  => trim($_POST['admin_user'] ?? ''),
        'admin_email' => trim($_POST['admin_email'] ?? ''),
        'admin_pass'  =>      $_POST['admin_pass'] ?? '',
        'admin_pass2' =>      $_POST['admin_pass2']?? '',
        'site_title'  => trim($_POST['site_title'] ?? 'LMOnext Admin'),
        'timezone'    => trim($_POST['timezone']   ?? 'Europe/Berlin'),
    ];
    // Ungültige/manipulierte Zeitzonen-Werte auf den sicheren Standard zurücksetzen
    try { new DateTimeZone($cfg['timezone']); } catch (Throwable) { $cfg['timezone'] = 'Europe/Berlin'; }
    if ($cfg['db_name']   === '')          $errors[] = t('err_dbname_required');
    if ($cfg['db_user']   === '')          $errors[] = t('err_dbuser_required');
    if ($cfg['admin_user']=== '')          $errors[] = t('err_adminuser_required');
    if ($cfg['admin_email'] !== '' && !filter_var($cfg['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('err_adminemail_invalid');
    }
    if (strlen($cfg['admin_pass']) < 8)    $errors[] = t('err_adminpass_minlen');
    if ($cfg['admin_pass'] !== $cfg['admin_pass2']) $errors[] = t('err_adminpass_mismatch');
    if (!allChecksPassed($checks))         $errors[] = t('err_requirements_not_met');

    if (empty($errors)) {
        $dbErrors = setupDatabase($cfg);
        if (!empty($dbErrors)) {
            $errors = array_merge($errors, $dbErrors);
        } elseif (!writeConfig($cfg)) {
            $errors[] = t('err_config_write_failed');
        } else {
            selfDestructAndRedirect();
        }
    }
    $step = 2;
    unset($cfg['admin_pass'], $cfg['admin_pass2'], $cfg['db_pass']);
}
?>
<!DOCTYPE html>
<html lang="<?= h(getCurrentLanguage()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(INSTALL_TITLE) ?></title>
<link rel="shortcut icon" href="assets/favicon/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0f1117;--surface:#1a1d27;--border:#2e3247;--accent:#3b82f6;
      --green:#22c55e;--red:#ef4444;--yellow:#f59e0b;
      --text:#e2e8f0;--muted:#64748b;--radius:8px}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);
     min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px 60px}
.wrap{width:100%;max-width:620px}
.header{text-align:center;margin-bottom:32px}
.header h1{font-size:1.6rem;font-weight:700;color:var(--accent)}
.header h1 span{color:var(--text);font-weight:400}
.header p{color:var(--muted);font-size:.9rem;margin-top:6px}
.steps{display:flex;gap:0;margin-bottom:24px;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.step-item{flex:1;padding:10px 16px;font-size:.83rem;text-align:center;background:var(--surface);color:var(--muted)}
.step-item.active{background:var(--accent);color:#fff;font-weight:600}
.step-item.done{color:var(--green)}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;margin-bottom:16px}
.card h2{font-size:1rem;font-weight:600;margin-bottom:16px;color:var(--text)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{display:flex;flex-direction:column;gap:4px;margin-bottom:12px}
label{font-size:.82rem;color:var(--muted)}
label span{color:var(--red)}
input[type=text],input[type=password],input[type=number]{
  background:var(--bg);border:1px solid var(--border);color:var(--text);
  border-radius:var(--radius);padding:8px 12px;font-size:.9rem;width:100%}
input:focus{outline:none;border-color:var(--accent)}
.form-hint{font-size:.76rem;color:var(--muted);margin-top:2px}
.btn{display:inline-block;padding:10px 20px;border-radius:var(--radius);font-size:.9rem;
     border:none;cursor:pointer;text-decoration:none;font-weight:500}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:#2563eb}
.btn-muted{background:var(--border);color:var(--text)}
.alert{border-radius:var(--radius);padding:12px 16px;margin-bottom:16px;font-size:.85rem}
.alert.error{background:#ef444418;border:1px solid #ef444466;color:#fca5a5}
.alert.warning{background:#f59e0b18;border:1px solid #f59e0b44;color:#fcd34d}
.check-list{list-style:none;display:flex;flex-direction:column;gap:8px}
.check-list li{display:flex;align-items:center;gap:10px;font-size:.85rem}
.check-icon{font-size:1rem;flex-shrink:0}
.check-label{font-weight:500;min-width:180px}
.check-info{color:var(--muted);font-size:.8rem}
.warn-box{display:flex;gap:10px;background:#f59e0b18;border:1px solid #f59e0b44;
          border-radius:var(--radius);padding:12px 16px;font-size:.83rem;color:#fcd34d;margin-bottom:16px}
.warn-box .icon{flex-shrink:0;font-size:1.1rem}
code{background:var(--bg);padding:1px 5px;border-radius:4px;font-size:.8rem}
.lang-switch{display:flex;justify-content:center;margin-bottom:16px}
.lang-switch select{background:var(--surface);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.82rem;font-family:inherit;cursor:pointer;outline:none}
.lang-switch select:focus{border-color:var(--accent)}
.lang-switch select option{background:var(--surface)}
</style>
</head>
<body>
<div class="wrap">

  <?= renderLanguageSwitcher() ?>

  <div class="header">
    <img src="assets/logo.svg" alt="LMOnext" style="height:70px;width:auto;display:block;margin:0 auto 8px">
    <p><?= h(t('install_header_subtitle', ['ver' => INSTALL_VERSION])) ?></p>
  </div>

  <div class="steps">
    <div class="step-item <?= $step === 1 ? 'active' : 'done' ?>"><?= $step > 1 ? '✓ ' : '' ?>1 <?= h(t('install_step1')) ?></div>
    <div class="step-item <?= $step === 2 ? 'active' : '' ?>">2 <?= h(t('install_step2')) ?></div>
  </div>

  <?php if (!empty($errors)) { ?>
  <div class="alert error">
    <strong><?= h(t('install_errors_heading')) ?></strong>
    <ul style="margin-top:6px;padding-left:18px">
      <?php foreach ($errors as $e) { ?><li><?= h($e) ?></li><?php } ?>
    </ul>
  </div>
  <?php } ?>

  <?php if ($step === 1) {
    $allOk = allChecksPassed($checks); ?>

  <div class="card">
    <h2><?= h(t('install_requirements_heading')) ?></h2>
    <ul class="check-list">
    <?php foreach ($checks as $c) {
        $required = $c['required'] ?? true;
        $icon = $c['ok'] ? '✅' : ($required ? '❌' : '⚠️'); ?>
      <li>
        <span class="check-icon"><?= $icon ?></span>
        <span class="check-label"><?= h($c['label']) ?><?= (!$required && !$c['ok']) ? ' <em style="opacity:.7">(' . h(t('install_optional')) . ')</em>' : '' ?></span>
        <span class="check-info"><?= h($c['info']) ?></span>
      </li>
    <?php } ?>
    </ul>
  </div>

  <?php if (!$allOk) { ?>
  <div class="alert warning"><?= h(t('install_fix_issues')) ?></div>
  <?php } ?>

  <div class="warn-box">
    <span class="icon">⚠️</span>
    <span><?= t('install_selfdestruct_notice') ?></span>
  </div>

  <?php if ($allOk) { ?>
  <a href="<?= h(selfUrl('step=2')) ?>" class="btn btn-primary"><?= h(t('install_continue')) ?></a>
  <?php } else { ?>
  <button class="btn btn-muted" disabled><?= h(t('install_check_failed')) ?></button>
  <?php } ?>

  <?php } elseif ($step === 2) {
    $v = fn(string $k, string $d='') => h($cfg[$k] ?? $d); ?>

  <form method="post" action="<?= h(selfUrl('step=2')) ?>">
    <div class="card">
      <h2><?= h(t('install_db_heading')) ?></h2>
      <div class="form-row">
        <div class="form-group">
          <label><?= h(t('install_label_host')) ?> <span>*</span></label>
          <input type="text" name="db_host" value="<?= $v('db_host','localhost') ?>" required>
        </div>
        <div class="form-group">
          <label><?= h(t('install_label_port')) ?></label>
          <input type="number" name="db_port" value="<?= $v('db_port','3306') ?>" min="1" max="65535">
        </div>
      </div>
      <div class="form-group">
        <label><?= h(t('install_label_dbname')) ?> <span>*</span></label>
        <input type="text" name="db_name" value="<?= $v('db_name','lmonext') ?>" required>
        <p class="form-hint"><?= h(t('install_hint_dbname')) ?></p>
      </div>
      <div class="form-group">
        <label><?= h(t('install_label_prefix')) ?></label>
        <input type="text" name="db_prefix" value="<?= $v('db_prefix','lmonext_') ?>" id="pfx">
        <p class="form-hint"><?= t('install_hint_prefix') ?></p>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= h(t('install_label_dbuser')) ?> <span>*</span></label>
          <input type="text" name="db_user" value="<?= $v('db_user') ?>" required autocomplete="username">
        </div>
        <div class="form-group">
          <label><?= h(t('install_label_dbpass')) ?></label>
          <input type="password" name="db_pass" autocomplete="current-password">
        </div>
      </div>
    </div>

    <div class="card">
      <h2><?= h(t('install_admin_heading')) ?></h2>
      <div class="form-row">
        <div class="form-group">
          <label><?= h(t('install_label_username')) ?> <span>*</span></label>
          <input type="text" name="admin_user" value="<?= $v('admin_user','admin') ?>" required autocomplete="off">
        </div>
        <div class="form-group">
          <label><?= h(t('install_label_email')) ?></label>
          <input type="email" name="admin_email" value="<?= $v('admin_email','') ?>" autocomplete="email">
          <p class="form-hint"><?= h(t('install_hint_email')) ?></p>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= h(t('install_label_password')) ?> <span>*</span></label>
          <input type="password" name="admin_pass" placeholder="<?= h(t('install_placeholder_pass')) ?>" required autocomplete="new-password">
        </div>
        <div class="form-group">
          <label><?= h(t('install_label_password2')) ?> <span>*</span></label>
          <input type="password" name="admin_pass2" required autocomplete="new-password">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= h(t('install_label_timezone')) ?></label>
          <select name="timezone">
            <?php foreach (buildTimezoneGroups() as $tzGroupName => $tzList) { ?>
            <optgroup label="<?= h($tzGroupName) ?>">
              <?php foreach ($tzList as $tz) { ?>
              <option value="<?= h($tz) ?>"<?= $v('timezone', 'Europe/Berlin') === $tz ? ' selected' : '' ?>><?= h($tz) ?></option>
              <?php } ?>
            </optgroup>
            <?php } ?>
          </select>
          <p class="form-hint"><?= h(t('install_hint_timezone')) ?></p>
        </div>
      </div>
    </div>

    <div class="card">
      <h2><?= h(t('install_app_heading')) ?></h2>
      <div class="form-group">
        <label><?= h(t('install_label_sitetitle')) ?></label>
        <input type="text" name="site_title" value="<?= $v('site_title','LMOnext Admin') ?>">
        <p class="form-hint"><?= h(t('install_hint_sitetitle')) ?></p>
      </div>
    </div>

    <div class="warn-box">
      <span class="icon">⚠️</span>
      <span><?= t('install_final_warning') ?></span>
    </div>

    <div style="display:flex;gap:12px;align-items:center">
      <a href="<?= h(selfUrl('step=1')) ?>" class="btn btn-muted"><?= h(t('install_back')) ?></a>
      <button type="submit" class="btn btn-primary"><?= h(t('install_submit')) ?></button>
    </div>
  </form>

  <script>
  const pfx = document.getElementById('pfx');
  if (pfx) pfx.addEventListener('input', () => {
    const v = pfx.value.replace(/[^a-zA-Z0-9_]/g,'');
    document.querySelectorAll('#pfx_prev,#pfx_prev2').forEach(el => el.textContent = v);
  });
  </script>

  <?php } ?>

  <p style="text-align:center;font-size:.75rem;color:var(--muted);margin-top:32px">
    <?= h(t('install_footer', ['ver' => INSTALL_VERSION, 'phpver' => PHP_VERSION])) ?>
  </p>
</div>
</body>
</html>
