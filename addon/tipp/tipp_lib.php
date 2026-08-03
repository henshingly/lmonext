<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/tipp_lib.php
 * Fileversion: 0.5.1
 * Changelog: 0.5.1 - Nav-Link/Startseiten-Karte zeigen jetzt auf home.php?view=tippspiel statt
 *                     auf die entfernte eigenständige addon/tipp/tipp.php - Tippspiel läuft
 *                     jetzt als View innerhalb des Templates, analog zur Spielerstatistik
 *                     (siehe view_tippspiel_frontend.php). Nebenbei: falsche CSS-Klasse "btn"
 *                     in der Startseiten-Karte korrigiert (nur "btn-primary" existiert)
 * Changelog: 0.5.0 - Neue Funktionen tippIstAktiv() (mind. eine Liga fürs Tippspiel
 *                     freigegeben?), tippRenderSiteLink() (Header-/Footer-Link, je nach
 *                     Template - siehe layout.tpl.php) und tippRenderHomeCard() (Werbe-Karte
 *                     auf der Startseite, siehe home.tpl.php). Behebt eine echte Lücke: das
 *                     Tippspiel war bislang nirgends von der Besucherseite aus verlinkt, nur
 *                     per direkter URL erreichbar
 * Changelog: 0.4.1 - Bugfix: getAllTippSettings() cachte die Einstellungen statisch pro Request,
 *                     ohne dass setTippSetting()/setTippSettings() diesen Cache invalidierten -
 *                     ein erneuter getTippSetting()-Aufruf im selben Request (z.B. bei einer
 *                     Live-Neuberechnung ohne zwischenzeitlichen Redirect) lieferte dadurch
 *                     stille alte Werte. Cache liegt jetzt per Referenz in
 *                     tippSettingsCacheRef() und wird von beiden Setter-Funktionen über die
 *                     neue resetTippSettingsCache() gezielt geleert. Admin-Options-Tabs waren
 *                     nicht betroffen (Post/Redirect/Get-Muster in handler_tipp.php lädt den
 *                     Cache ohnehin bei jedem Request neu), gefunden beim Testen von
 *                     calculateTippPunkte() in frontend_tipp.php
 * Changelog: 0.4.0 - Mail-Versand für "Newsletter/Reminder": sendTippMail() (exakt nach dem
 *                     Muster von sendPasswordResetEmail() in admin/bootstrap.php, bewusst ohne
 *                     externe Mail-Bibliothek), replaceTippPlaceholders() ([nick]/[name]/
 *                     [spiele], bewusst kein [pass]), getTippReminderSpiele()/
 *                     formatSpieleListe() für die echte Ermittlung noch nicht getippter Spiele
 *                     je Tipper im gewählten Zeitfenster
 * Changelog: 0.3.0
 * Changelog: 0.3.0 - Vollständiges Tipper/Team-CRUD für die Userverwaltung: getAllTipper()
 *                     (mit live abgeleitetem "letzter Tipp" per MAX(updated_at)),
 *                     getTipperByNickname(), getAllTeamsWithCount() (live per COUNT(*)),
 *                     createTippTeam(), saveTipper() (Anlegen/Bearbeiten, Passwort nur bei
 *                     Angabe überschrieben), deleteTipper(), getTipperAboLigaIds()/
 *                     setTipperAbos()
 * Changelog: 0.2.0
 * Changelog: 0.2.0 - Neue Funktionen für "Tippbare Ligen": getTippbareLigenKandidaten() (Ligen
 *                     aus dem obersten Ordner, nicht archiviert - eigene, einfache Abfrage statt
 *                     frontend/data_home.php einzubinden), getTippLigaFreigabeIds()/
 *                     setTippLigaFreigabe() für tipp_liga_freigabe
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
 * Liefert alle Ligen aus dem obersten Ordner der Ligenübersicht (nicht
 * archiviert) - genau die Kandidaten, aus denen der Admin bei "Tippbare
 * Ligen" auswählen kann, falls "immer alle" nicht aktiv ist. Bewusst eine
 * eigene, einfache Abfrage statt frontend/data_home.php einzubinden, um die
 * bestehende Trennung zwischen Admin- und Frontend-Bootstrap nicht
 * aufzuweichen.
 */
function getTippbareLigenKandidaten() : array
{
    try {
        return getDB()->query(
            'SELECT id, name FROM ' . tbl('liga') . ' WHERE archiv_folder_id IS NULL ORDER BY datum DESC'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Liefert die Liga-IDs, die aktuell konkret für das Tippspiel freigegeben
 * sind (nur relevant, wenn "immer alle" NICHT aktiv ist).
 *
 * @return array<int,int>
 */
function getTippLigaFreigabeIds() : array
{
    ensureTippSchema();
    try {
        return array_map('intval', getDB()->query(
            'SELECT liga_id FROM ' . tbl('tipp_liga_freigabe')
        )->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable) {
        return [];
    }
}

/**
 * Ist das Tippspiel gerade "aktiv" (mind. eine Liga fürs Tippen freigegeben)?
 * Steuert, ob der Tippspiel-Link im Header/Footer und die Startseiten-Karte
 * überhaupt erscheinen - ein leeres Tippspiel ohne freigegebene Liga soll
 * Besuchern nicht als Sackgasse präsentiert werden.
 */
function tippIstAktiv() : bool
{
    if (getTippSetting('tippbare_immer_alle', '1') === '1') {
        return !empty(getTippbareLigenKandidaten());
    }
    return !empty(getTippLigaFreigabeIds());
}

/**
 * Kleiner Text-Link fürs Grundgerüst (Header bei "default", Footer bei den
 * übrigen vier Templates - siehe jeweiliges layout.tpl.php), analog zu
 * renderLanguageSwitcher(). Gibt einen leeren String zurück, wenn
 * tippIstAktiv() false ist - Aufrufer muss das selbst NICHT prüfen.
 */
function tippRenderSiteLink() : string
{
    if (!tippIstAktiv()) {
        return '';
    }
    return '<a class="tipp-site-link" href="home.php?view=tippspiel">'
         . htmlspecialchars(tf('tf_tipp_header_link'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
}

/**
 * HTML-Karte für die Startseite (nur home.php, siehe home.tpl.php je
 * Template) - wirbt fürs Tippspiel, sofern aktiv. Bewusst als eigene
 * Funktion statt einer weiteren Fallunterscheidung in home.php selbst, da
 * das Markup templateübergreifend identisch ist (nur die umgebende
 * .card-Klasse wird bereits von den .tpl.php-Dateien gestellt).
 */
function tippRenderHomeCard() : string
{
    if (!tippIstAktiv()) {
        return '';
    }
    $esc = static fn(string $v) : string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<div class="card tipp-home-card">'
         . '<h2>' . $esc(tf('tf_tipp_home_card_titel')) . '</h2>'
         . '<p>' . $esc(tf('tf_tipp_home_card_text')) . '</p>'
         . '<p><a class="btn-primary" href="home.php?view=tippspiel">' . $esc(tf('tf_tipp_home_card_button')) . '</a></p>'
         . '</div>';
}

/**
 * Setzt die Liga-Freigaben komplett neu (löscht alle bisherigen und trägt
 * die übergebenen IDs neu ein).
 *
 * @param array<int,int> $ligaIds
 */
function setTippLigaFreigabe(array $ligaIds) : bool
{
    ensureTippSchema();
    try {
        $db = getDB();
        $db->exec('DELETE FROM ' . tbl('tipp_liga_freigabe'));
        $stmt = $db->prepare('INSERT INTO ' . tbl('tipp_liga_freigabe') . ' (liga_id) VALUES (?)');
        foreach ($ligaIds as $id) {
            $stmt->execute([(int)$id]);
        }
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Liefert alle Tipper für die Userverwaltungs-Liste, inkl. Teamname (falls
 * zugeordnet) und "letzter Tipp" - Letzteres bewusst NICHT als eigenes Feld
 * gespeichert, sondern live per MAX(updated_at) aus tipp_tipp abgeleitet
 * (siehe Projekt-Historie: entspricht dem alten LMO-Verhalten, wo dieser
 * Wert aus dem Datei-Zeitstempel der Flatfile gelesen wurde).
 */
function getAllTipper() : array
{
    ensureTippSchema();
    try {
        return getDB()->query(
            'SELECT u.id, u.nickname, u.email, u.vorname, u.nachname, u.team_id, u.freigeschaltet,
                    t.name AS team_name,
                    (SELECT MAX(tt.updated_at) FROM ' . tbl('tipp_tipp') . ' tt WHERE tt.tipper_id = u.id) AS letzter_tipp
               FROM ' . tbl('tipp_user') . ' u
               LEFT JOIN ' . tbl('tipp_team') . ' t ON t.id = u.team_id
              ORDER BY u.nickname ASC'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function getTipperByNickname(string $nickname) : ?array
{
    ensureTippSchema();
    try {
        $stmt = getDB()->prepare('SELECT * FROM ' . tbl('tipp_user') . ' WHERE nickname = ?');
        $stmt->execute([$nickname]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Liefert alle Teams inkl. aktueller Mitgliederzahl (live per COUNT(*),
 * nicht gespeichert - siehe Projekt-Historie).
 */
function getAllTeamsWithCount() : array
{
    ensureTippSchema();
    try {
        return getDB()->query(
            'SELECT t.id, t.name, COUNT(u.id) AS mitglieder
               FROM ' . tbl('tipp_team') . ' t
               LEFT JOIN ' . tbl('tipp_user') . ' u ON u.team_id = t.id
              GROUP BY t.id, t.name
              ORDER BY t.name ASC'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Legt bei Bedarf ein neues Team an und liefert dessen ID (oder null bei
 * Fehler/leerem Namen). $createdByTipperId ist der Tipper, dem das neue
 * Team direkt im Anschluss zugeordnet wird (siehe saveTipper()).
 */
function createTippTeam(string $name, int $createdByTipperId) : ?int
{
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    ensureTippSchema();
    try {
        $db = getDB();
        $db->prepare('INSERT INTO ' . tbl('tipp_team') . ' (name, created_by) VALUES (?, ?)')
           ->execute([$name, $createdByTipperId]);
        return (int)$db->lastInsertId();
    } catch (Throwable) {
        return null;
    }
}

/**
 * Legt einen neuen Tipper an oder aktualisiert einen bestehenden (abhängig
 * davon, ob $originalNickname gesetzt ist). Passwort wird nur überschrieben,
 * wenn $password nicht leer ist (siehe Original-Verhalten: "Passwort leer
 * lassen um keine Änderung durchzuführen"). Liefert true bei Erfolg.
 */
function saveTipper(?string $originalNickname, array $data, ?string $password) : bool
{
    ensureTippSchema();
    try {
        $db = getDB();

        if ($originalNickname === null) {
            // Neuanlage
            if ($password === null || $password === '') {
                return false; // Passwort ist bei Neuanlage Pflicht
            }
            $stmt = $db->prepare(
                'INSERT INTO ' . tbl('tipp_user') . '
                 (nickname, password_hash, email, vorname, nachname, strasse, plz, ort, team_id, freigeschaltet)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            return $stmt->execute([
                $data['nickname'], password_hash($password, PASSWORD_DEFAULT), $data['email'],
                $data['vorname'], $data['nachname'], $data['strasse'], $data['plz'], $data['ort'],
                $data['team_id'], $data['freigeschaltet'],
            ]);
        }

        // Bearbeiten bestehender Tipper
        if ($password !== null && $password !== '') {
            $stmt = $db->prepare(
                'UPDATE ' . tbl('tipp_user') . ' SET
                    password_hash=?, email=?, vorname=?, nachname=?, strasse=?, plz=?, ort=?,
                    team_id=?, freigeschaltet=?, newsletter=?, reminder=?
                 WHERE nickname=?'
            );
            return $stmt->execute([
                password_hash($password, PASSWORD_DEFAULT), $data['email'], $data['vorname'], $data['nachname'],
                $data['strasse'], $data['plz'], $data['ort'], $data['team_id'], $data['freigeschaltet'],
                $data['newsletter'], $data['reminder'], $originalNickname,
            ]);
        }
        $stmt = $db->prepare(
            'UPDATE ' . tbl('tipp_user') . ' SET
                email=?, vorname=?, nachname=?, strasse=?, plz=?, ort=?,
                team_id=?, freigeschaltet=?, newsletter=?, reminder=?
             WHERE nickname=?'
        );
        return $stmt->execute([
            $data['email'], $data['vorname'], $data['nachname'], $data['strasse'], $data['plz'], $data['ort'],
            $data['team_id'], $data['freigeschaltet'], $data['newsletter'], $data['reminder'], $originalNickname,
        ]);
    } catch (Throwable) {
        return false;
    }
}

function deleteTipper(string $nickname) : bool
{
    ensureTippSchema();
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM ' . tbl('tipp_user') . ' WHERE nickname = ?');
        $stmt->execute([$nickname]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            return false;
        }
        $db->prepare('DELETE FROM ' . tbl('tipp_tipp') . ' WHERE tipper_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM ' . tbl('tipp_abo') . ' WHERE tipper_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM ' . tbl('tipp_user') . ' WHERE id = ?')->execute([$id]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Liefert die Liga-IDs, in die ein Tipper eingetragen (abonniert) ist.
 *
 * @return array<int,int>
 */
function getTipperAboLigaIds(int $tipperId) : array
{
    ensureTippSchema();
    try {
        $stmt = getDB()->prepare('SELECT liga_id FROM ' . tbl('tipp_abo') . ' WHERE tipper_id = ?');
        $stmt->execute([$tipperId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable) {
        return [];
    }
}

/**
 * Setzt die Liga-Abos eines Tippers komplett neu.
 *
 * @param array<int,int> $ligaIds
 */
function setTipperAbos(int $tipperId, array $ligaIds) : bool
{
    ensureTippSchema();
    try {
        $db = getDB();
        $db->prepare('DELETE FROM ' . tbl('tipp_abo') . ' WHERE tipper_id = ?')->execute([$tipperId]);
        $stmt = $db->prepare('INSERT INTO ' . tbl('tipp_abo') . ' (tipper_id, liga_id) VALUES (?, ?)');
        foreach ($ligaIds as $ligaId) {
            $stmt->execute([$tipperId, (int)$ligaId]);
        }
        return true;
    } catch (Throwable) {
        return false;
    }
}


/**
 * Liest alle tipp_settings-Zeilen einmal pro Request in einen statischen
 * Speicher-Cache, analog zu getAdminSetting() in frontend/bootstrap.php -
 * vermeidet eine einzelne Abfrage pro Einstellung. Der Cache liegt in
 * tippSettingsCacheRef() (per Referenz), damit setTippSetting()/
 * setTippSettings() ihn nach dem Schreiben gezielt invalidieren können -
 * andernfalls würde ein erneuter getTippSetting()-Aufruf im selben Request
 * (z.B. bei einer Live-Neuberechnung ohne Redirect dazwischen) den alten,
 * bereits überholten Wert liefern.
 */
function &tippSettingsCacheRef() : ?array
{
    static $cache = null;
    return $cache;
}

function getAllTippSettings() : array
{
    $cache = &tippSettingsCacheRef();
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
 * Invalidiert den Settings-Cache, damit der nächste getTippSetting()-/
 * getAllTippSettings()-Aufruf im selben Request wieder frisch aus der DB
 * liest. Wird von setTippSetting()/setTippSettings() automatisch aufgerufen.
 */
function resetTippSettingsCache() : void
{
    $cache = &tippSettingsCacheRef();
    $cache = null;
}

/**
 * Speichert eine einzelne Tippspiel-Einstellung (INSERT ... ON DUPLICATE KEY
 * UPDATE) und invalidiert danach den Zwischenspeicher, damit nachfolgende
 * getTippSetting()-Aufrufe im selben Request den neuen Wert sehen.
 */
function setTippSetting(string $key, string $value) : bool
{
    ensureTippSchema();
    try {
        getDB()->prepare(
            'INSERT INTO ' . tbl('tipp_settings') . ' (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        )->execute([$key, $value]);
        resetTippSettingsCache();
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Speichert mehrere Einstellungen auf einmal (z.B. beim Absenden eines
 * ganzen Formulars) und invalidiert danach den Zwischenspeicher.
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
        resetTippSettingsCache();
        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Verschickt eine Tippspiel-Mail über die eingebaute mail()-Funktion, exakt
 * nach demselben Muster wie sendPasswordResetEmail() in admin/bootstrap.php
 * (bewusst ohne externe Mail-Bibliothek).
 */
function sendTippMail(string $toEmail, string $subject, string $body) : bool
{
    $siteTitle = defined('ADMIN_TITLE') ? ADMIN_TITLE : 'LMOnext';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host      = preg_replace('/:\d+$/', '', $host);
    $fromAddr  = 'no-reply@' . $host;

    $headers = "From: {$siteTitle} <{$fromAddr}>\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($toEmail, $subject, $body, $headers);
}

/**
 * Ersetzt die Platzhalter [nick]/[name]/[spiele] in einem Mailtext. [pass]
 * gibt es bewusst nicht (siehe Projekt-Historie: mit gehashten Passwörtern
 * technisch nicht möglich und auch nicht wünschenswert).
 */
function replaceTippPlaceholders(string $text, array $tipper, string $spieleListe = '') : string
{
    $name = trim(($tipper['vorname'] ?? '') . ' ' . ($tipper['nachname'] ?? ''));
    return str_replace(
        ['[nick]', '[name]', '[spiele]'],
        [$tipper['nickname'] ?? '', $name, $spieleListe],
        $text
    );
}

/**
 * Ermittelt für einen Tipper die noch nicht getippten Spiele (für den
 * [spiele]-Platzhalter im Tipp-Reminder). Berücksichtigt Liga(en),
 * optional einen bestimmten Spieltag/Runde, und ein Zeitfenster
 * "in den nächsten X Tagen" (anhand des Anstoßtermins der Partie, ersatzweise
 * des Spieltag-Starttermins).
 *
 * @param array<int,int> $ligaIds
 */
function getTippReminderSpiele(array $ligaIds, ?int $spieltagNr, int $tageVoraus, int $tipperId) : array
{
    if (empty($ligaIds)) {
        return [];
    }
    try {
        $db = getDB();
        $placeholders = implode(',', array_fill(0, count($ligaIds), '?'));
        $sql = 'SELECT p.id, p.heim_label, p.gast_label, p.heim_id, p.gast_id, COALESCE(p.zeit, st.start) AS termin
                  FROM ' . tbl('liga_partien') . ' p
                  JOIN ' . tbl('liga_spieltage') . ' st ON st.id = p.spieltag_id
                  LEFT JOIN ' . tbl('tipp_tipp') . ' tt ON tt.partie_id = p.id AND tt.tipper_id = ?
                 WHERE st.liga_id IN (' . $placeholders . ')
                   AND tt.id IS NULL
                   AND COALESCE(p.zeit, st.start) IS NOT NULL
                   AND COALESCE(p.zeit, st.start) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)';
        $params = array_merge([$tipperId], $ligaIds, [$tageVoraus]);
        if ($spieltagNr !== null) {
            $sql .= ' AND st.nummer = ?';
            $params[] = $spieltagNr;
        }
        $sql .= ' ORDER BY termin ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Baut aus den Spielen von getTippReminderSpiele() den Text für den
 * [spiele]-Platzhalter (eine Zeile je Spiel). Team-Namen werden anhand von
 * heim_id/gast_id nachgeschlagen, sofern vorhanden, sonst heim_label/
 * gast_label verwendet (KO-Platzhalter).
 */
function formatSpieleListe(array $spiele) : string
{
    if (empty($spiele)) {
        return '';
    }
    try {
        $db = getDB();
        $teamIds = [];
        foreach ($spiele as $s) {
            if ($s['heim_id']) $teamIds[] = (int)$s['heim_id'];
            if ($s['gast_id']) $teamIds[] = (int)$s['gast_id'];
        }
        $teamNamen = [];
        if (!empty($teamIds)) {
            $ph = implode(',', array_fill(0, count($teamIds), '?'));
            $stmt = $db->prepare('SELECT id, name FROM ' . tbl('teams_global') . ' WHERE id IN (' . $ph . ')');
            $stmt->execute($teamIds);
            foreach ($stmt->fetchAll() as $t) {
                $teamNamen[(int)$t['id']] = $t['name'];
            }
        }
        $lines = [];
        foreach ($spiele as $s) {
            $heim = $s['heim_id'] ? ($teamNamen[(int)$s['heim_id']] ?? $s['heim_label']) : $s['heim_label'];
            $gast = $s['gast_id'] ? ($teamNamen[(int)$s['gast_id']] ?? $s['gast_label']) : $s['gast_label'];
            $termin = $s['termin'] ? date('d.m.Y H:i', strtotime($s['termin'])) : '';
            $lines[] = trim("$termin  $heim - $gast");
        }
        return implode("\n", $lines);
    } catch (Throwable) {
        return '';
    }
}
