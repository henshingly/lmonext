<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/tipp_lib.php
 * Fileversion: 0.1.0
 * Changelog: 0.1.0 - Initiale Version: Datenbankschema für alle sechs in den Vorgesprächen
 *                     festgelegten Tabellen (tipp_user, tipp_team, tipp_liga_freigabe,
 *                     tipp_abo, tipp_tipp, tipp_settings), plus Zugriffsfunktionen für die
 *                     Einstellungen (getTippSetting()/setTippSetting()/getAllTippSettings()).
 *                     tipp_tipp bekommt sowohl Ergebnis- (tipp_heim/tipp_gast) als auch
 *                     Tendenz-Felder (tipp_tendenz) nebeneinander, je nach aktivem Tippmodus
 *                     wird nur eines der beiden befüllt - siehe Projekt-Historie für die
 *                     Begründung (eigenes Feld statt codierter Platzhalterwerte)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

/**
 * Legt alle Tippspiel-Tabellen an, falls sie noch nicht existieren. Wird
 * lazy beim ersten Zugriff aufgerufen (wie bei allen anderen Addons in
 * diesem Projekt), keine Änderung an install.php nötig.
 */
function ensureTippSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();

        // Tipper-Konten
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('tipp_user').' (
            `id`              INT AUTO_INCREMENT PRIMARY KEY,
            `nickname`        VARCHAR(50)   NOT NULL,
            `password_hash`   VARCHAR(255)  NOT NULL,
            `email`           VARCHAR(150)  NOT NULL,
            `vorname`         VARCHAR(50)   NULL DEFAULT NULL,
            `nachname`        VARCHAR(50)   NULL DEFAULT NULL,
            `strasse`         VARCHAR(100)  NULL DEFAULT NULL,
            `plz`             VARCHAR(10)   NULL DEFAULT NULL,
            `ort`             VARCHAR(80)   NULL DEFAULT NULL,
            `team_id`         INT           NULL DEFAULT NULL,
            `freigeschaltet`  TINYINT(1)    NOT NULL DEFAULT 0,
            `freischalt_code` VARCHAR(64)   NULL DEFAULT NULL,
            `newsletter`      TINYINT(1)    NOT NULL DEFAULT 1,
            `reminder`        TINYINT(1)    NOT NULL DEFAULT 1,
            `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `nickname` (`nickname`),
            KEY `team_id` (`team_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Frei gegründete Teams (Team-Wertung)
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('tipp_team').' (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(50) NOT NULL,
            `created_by` INT         NOT NULL,
            `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Welche Ligen fürs Tippspiel freigegeben sind (nur relevant, wenn
        // Einstellung "alle_ligen_freigegeben" nicht aktiv ist)
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('tipp_liga_freigabe').' (
            `liga_id`        INT PRIMARY KEY,
            `freigegeben_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Welcher Tipper sich in welcher Liga eingetragen hat
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('tipp_abo').' (
            `tipper_id` INT NOT NULL,
            `liga_id`   INT NOT NULL,
            PRIMARY KEY (`tipper_id`, `liga_id`),
            KEY `liga_id` (`liga_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Die eigentlichen Tipp-Rohdaten - KEINE Punkte, keine berechneten
        // Werte (siehe Projekt-Historie: alles wird live berechnet, damit
        // Änderungen an den Punkteregeln/am Joker-Multiplikator sofort und
        // ohne Neuberechnungslauf wirken). tipp_heim/tipp_gast für den
        // Ergebnis-Modus, tipp_tendenz (H/U/A) für den Tendenz-Modus -
        // je nach aktivem Tippmodus ist nur eine der beiden Feldgruppen
        // befüllt.
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('tipp_tipp').' (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `tipper_id`   INT      NOT NULL,
            `partie_id`   INT      NOT NULL,
            `tipp_heim`   INT      NULL DEFAULT NULL,
            `tipp_gast`   INT      NULL DEFAULT NULL,
            `tipp_tendenz` ENUM(\'H\',\'U\',\'A\') NULL DEFAULT NULL,
            `ist_joker`   TINYINT(1) NOT NULL DEFAULT 0,
            `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `tipper_partie` (`tipper_id`, `partie_id`),
            KEY `partie_id` (`partie_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Alle Admin-Einstellungen des Tippspiels als Schlüssel-Wert-Paare
        // (eine einzige, globale Konfiguration - siehe Projekt-Historie)
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('tipp_settings').' (
            `key`   VARCHAR(64)  NOT NULL PRIMARY KEY,
            `value` VARCHAR(255) NOT NULL DEFAULT \'\'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    } catch (Throwable) {}
}

/**
 * Liest alle tipp_settings-Zeilen einmal pro Request in einen statischen
 * Speicher-Cache, analog zu getAdminSetting() in frontend/bootstrap.php -
 * vermeidet eine einzelne Abfrage pro Einstellung.
 */
function getAllTippSettings() : array
{
    static $cache = null;
    if ($cache === null) {
        ensureTippSchema();
        $cache = [];
        try {
            $rows = getDB()->query('SELECT `key`, `value` FROM ' . tbl('tipp_settings'))->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['key']] = (string)$row['value'];
            }
        } catch (Throwable) {
            // $cache bleibt leer, einzelne getTippSetting()-Aufrufe fallen auf ihren Default zurück
        }
    }
    return $cache;
}

function getTippSetting(string $key, string $default = '') : string
{
    $settings = getAllTippSettings();
    return $settings[$key] ?? $default;
}

/**
 * Speichert eine einzelne Tippspiel-Einstellung (INSERT ... ON DUPLICATE KEY
 * UPDATE). Invalidiert den Zwischenspeicher nicht automatisch - innerhalb
 * desselben Requests nach dem Speichern lieber $forceValue direkt weiter-
 * verwenden, statt erneut getTippSetting() für denselben Schlüssel
 * aufzurufen.
 */
function setTippSetting(string $key, string $value) : bool
{
    ensureTippSchema();
    try {
        getDB()->prepare(
            'INSERT INTO ' . tbl('tipp_settings') . ' (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        )->execute([$key, $value]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Speichert mehrere Einstellungen auf einmal (z.B. beim Absenden eines
 * ganzen Formulars).
 *
 * @param array<string,string> $values
 */
function setTippSettings(array $values) : bool
{
    ensureTippSchema();
    try {
        $stmt = getDB()->prepare(
            'INSERT INTO ' . tbl('tipp_settings') . ' (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        foreach ($values as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        return true;
    } catch (Throwable) {
        return false;
    }
}
