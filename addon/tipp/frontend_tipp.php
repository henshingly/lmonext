<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/frontend_tipp.php
 * Fileversion: 0.1.0
 * Changelog: 0.1.0 - Initiale (vorläufige) Version der Tipper-Ansicht: Session-Verwaltung,
 *                     Anmeldung, Login/Logout, Tippabgabe (nur Ligenweise-Modus, nur
 *                     Ergebnis-Tippmodus vollständig getestet) und eine einfache Live-
 *                     Punkteberechnung fürs Anzeigen der erzielten Punkte je Spiel. Bewusst
 *                     KOMPLETT neu geschrieben statt vom alten Flatfile-Addon übernommen (siehe
 *                     Projekt-Historie: genau die verstreute, typunsichere Berechnung dort war
 *                     die Hauptfehlerquelle) - eine einzige zentrale Funktion
 *                     (calculateTippPunkte()) für die gesamte Punktelogik, durchgehend
 *                     strict_types und ===-Vergleiche.
 *                     Noch NICHT enthalten (folgt in weiteren Schritten): Datumsweise
 *                     Tippabgabe, Tendenz-Tippmodus, Tippeinsicht, Tipp-Tabelle/Rangliste,
 *                     Team-Beitritt/-Gründung durch den Tipper selbst, Passwort-Vergessen,
 *                     E-Mail-Bestätigungscode-Freischaltung (nur "sofort" und "admin" fertig
 *                     getestet).
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

require_once __DIR__ . '/tipp_lib.php';

/**
 * Eigener, schlanker Ersatz für admin/bootstrap.php's getSiteBaseUrl() (dort
 * nicht verfügbar, da diese Datei nur frontend/bootstrap.php einbindet).
 * Liefert die Basis-URL bis einschließlich addon/tipp/.
 */
function tippSiteBaseUrl() : string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/addon/tipp/tipp.php'));
    $dir    = rtrim($dir, '/');
    return $scheme . '://' . $host . $dir;
}

// ── Session-Helfer ────────────────────────────────────────────────────────────
// Nutzt bewusst dieselbe Frontend-Session (session_name 'lmonext_frontend' aus
// frontend/bootstrap.php), nur mit einem zusätzlichen Schlüssel für den
// eingeloggten Tipper - keine dritte, separate Session nötig.

function tippCurrentUserId() : ?int
{
    return isset($_SESSION['tipp_user_id']) ? (int)$_SESSION['tipp_user_id'] : null;
}

function tippCurrentUser() : ?array
{
    $id = tippCurrentUserId();
    if ($id === null) {
        return null;
    }
    try {
        $stmt = getDB()->prepare('SELECT * FROM ' . tbl('tipp_user') . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable) {
        return null;
    }
}

function tippRequireLogin() : void
{
    if (tippCurrentUserId() === null) {
        redirectTo('?action=login');
    }
}

function redirectTo(string $target) : never
{
    header('Location: tipp.php' . $target);
    exit;
}

// ── Registrierung ────────────────────────────────────────────────────────────

/**
 * Registriert einen neuen Tipper gemäß der Admin-Einstellung "Freischaltung"
 * (sofort/email/admin). Liefert ein Array mit 'ok' und ggf. 'error' (Lang-
 * Schlüssel) bzw. 'freigeschaltet' (bool, für die Erfolgsmeldung).
 */
function tippRegister(string $nickname, string $email, string $password, string $passwordWdh, array $extra) : array
{
    $nickname = trim($nickname);
    $email    = trim($email);

    if ($nickname === '' || mb_strlen($nickname) > 50) {
        return ['ok' => false, 'error' => 'tf_tipp_err_nickname'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'tf_tipp_err_email'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'tf_tipp_err_passwort_kurz'];
    }
    if ($password !== $passwordWdh) {
        return ['ok' => false, 'error' => 'tf_tipp_err_passwort_mismatch'];
    }
    if (getTipperByNickname($nickname) !== null) {
        return ['ok' => false, 'error' => 'tf_tipp_err_nickname_vergeben'];
    }

    $modus = getTippSetting('anmeldung_freischaltung', 'sofort');
    $freigeschaltet = $modus === 'sofort' ? '1' : '0';
    $freischaltCode = $modus === 'email' ? bin2hex(random_bytes(24)) : null;

    ensureTippSchema();
    try {
        $db = getDB();
        $db->prepare(
            'INSERT INTO ' . tbl('tipp_user') . '
             (nickname, password_hash, email, vorname, nachname, freigeschaltet, freischalt_code)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $nickname, password_hash($password, PASSWORD_DEFAULT), $email,
            $extra['vorname'] ?? null, $extra['nachname'] ?? null,
            $freigeschaltet, $freischaltCode,
        ]);
    } catch (Throwable) {
        return ['ok' => false, 'error' => 'tf_tipp_err_speichern'];
    }

    $tipper = getTipperByNickname($nickname);

    if ($modus === 'email' && $tipper !== null) {
        $link = tippSiteBaseUrl() . '/tipp.php?action=confirm&code=' . $freischaltCode;
        sendTippMail($email, tf('tf_tipp_mail_confirm_betreff'), tf('tf_tipp_mail_confirm_text', ['link' => $link, 'nick' => $nickname]));
    } elseif ($tipper !== null && getTippSetting('anmeldung_bestaetigungsmail', '1') === '1') {
        sendTippMail($email, tf('tf_tipp_mail_welcome_betreff'), tf('tf_tipp_mail_welcome_text', ['nick' => $nickname]));
    }
    if ($tipper !== null && getTippSetting('anmeldung_admin_benachrichtigen', '1') === '1') {
        $adminEmail = getAdminSetting('email', '');
        if ($adminEmail !== '') {
            sendTippMail($adminEmail, tf('tf_tipp_mail_admin_betreff'), tf('tf_tipp_mail_admin_text', ['nick' => $nickname]));
        }
    }

    return ['ok' => true, 'freigeschaltet' => $freigeschaltet === '1', 'modus' => $modus];
}

function tippConfirmEmail(string $code) : bool
{
    if ($code === '') {
        return false;
    }
    try {
        $stmt = getDB()->prepare('SELECT id FROM ' . tbl('tipp_user') . ' WHERE freischalt_code = ?');
        $stmt->execute([$code]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            return false;
        }
        getDB()->prepare('UPDATE ' . tbl('tipp_user') . ' SET freigeschaltet=1, freischalt_code=NULL WHERE id=?')->execute([$id]);
        return true;
    } catch (Throwable) {
        return false;
    }
}

// ── Login ────────────────────────────────────────────────────────────────────

function tippLogin(string $nickname, string $password) : array
{
    $tipper = getTipperByNickname(trim($nickname));
    if ($tipper === null || !password_verify($password, $tipper['password_hash'])) {
        return ['ok' => false, 'error' => 'tf_tipp_err_login'];
    }
    if ((int)$tipper['freigeschaltet'] !== 1) {
        return ['ok' => false, 'error' => 'tf_tipp_err_nicht_freigeschaltet'];
    }
    $_SESSION['tipp_user_id'] = (int)$tipper['id'];
    return ['ok' => true];
}

function tippLogout() : void
{
    unset($_SESSION['tipp_user_id']);
}

// ── Tippabgabe-Frist ─────────────────────────────────────────────────────────

/**
 * Ermittelt, ob ein bestimmtes Spiel noch tippbar ist (Frist noch nicht
 * erreicht), anhand der Admin-Einstellungen "abgabe_minuten" und
 * "abgabeschluss_ohne_termin".
 */
function tippIstAenderbar(?string $zeit, ?string $spieltagStart) : bool
{
    $minuten = (int)getTippSetting('abgabe_minuten', '15');
    $bezug = $zeit ?: $spieltagStart;
    if ($bezug === null) {
        return true; // Kein Termin bekannt -> vorsichtshalber tippbar lassen
    }
    $deadline = strtotime($bezug) - ($minuten * 60);
    return time() < $deadline;
}

// ── Tipps speichern ──────────────────────────────────────────────────────────

/**
 * Speichert die Tipps eines Tippers für eine Liste von Partien (Ligenweise-
 * Modus, ein Spieltag). $eingaben ist [partieId => ['heim'=>int,'gast'=>int,'joker'=>bool]].
 * Partien, deren Frist bereits abgelaufen ist, werden übersprungen (auch
 * bei manipuliertem POST - serverseitige Absicherung).
 */
function tippSaveAbgabe(int $tipperId, array $partien, array $eingaben) : void
{
    ensureTippSchema();
    $db = getDB();
    $jokerGesetzt = false;
    foreach ($partien as $p) {
        $pid = (int)$p['id'];
        if (!isset($eingaben[$pid])) {
            continue;
        }
        if (!tippIstAenderbar($p['zeit'] ?? null, $p['spieltag_start'] ?? null)) {
            continue;
        }
        $e = $eingaben[$pid];
        $heim = $e['heim'] ?? null;
        $gast = $e['gast'] ?? null;
        if ($heim === null || $gast === null || $heim === '' || $gast === '') {
            continue; // leeres Feld -> kein Tipp abgegeben, nichts speichern
        }
        $heim = max(0, (int)$heim);
        $gast = max(0, (int)$gast);
        $joker = !empty($e['joker']) && !$jokerGesetzt && getTippSetting('joker_zulassen', '1') === '1';
        if ($joker) {
            $jokerGesetzt = true; // nur ein Joker pro Spieltag, siehe Vorgespräche
        }
        try {
            $db->prepare(
                'INSERT INTO ' . tbl('tipp_tipp') . ' (tipper_id, partie_id, tipp_heim, tipp_gast, ist_joker)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE tipp_heim=VALUES(tipp_heim), tipp_gast=VALUES(tipp_gast), ist_joker=VALUES(ist_joker)'
            )->execute([$tipperId, $pid, $heim, $gast, $joker ? 1 : 0]);
        } catch (Throwable) {}
    }
}

/**
 * Liefert die abgegebenen Tipps eines Tippers für eine Liste von
 * Partie-IDs, indiziert nach partie_id.
 */
function tippGetAbgabeFuerPartien(int $tipperId, array $partieIds) : array
{
    if (empty($partieIds)) {
        return [];
    }
    try {
        $ph = implode(',', array_fill(0, count($partieIds), '?'));
        $stmt = getDB()->prepare(
            'SELECT * FROM ' . tbl('tipp_tipp') . ' WHERE tipper_id = ? AND partie_id IN (' . $ph . ')'
        );
        $stmt->execute(array_merge([$tipperId], $partieIds));
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['partie_id']] = $row;
        }
        return $result;
    } catch (Throwable) {
        return [];
    }
}

// ── Live-Punkteberechnung ────────────────────────────────────────────────────

/**
 * Die zentrale, einzige Punkteberechnung für einen einzelnen Tipp im
 * Ergebnis-Modus - siehe Projekt-Historie für die genaue Herleitung der
 * Regeln (inkl. des bestätigten Testfalls: 1:2-Tipp bei 2:1-Ergebnis zählt
 * NICHT als "eine Toranzahl richtig", da seitengetreu verglichen wird).
 * Nutzt durchgehend strict_types und ===-Vergleiche - genau die Klarheit,
 * die im alten Addon gefehlt hat.
 */
function calculateTippPunkte(int $tippHeim, int $tippGast, ?int $ergHeim, ?int $ergGast, bool $istJoker) : int
{
    if ($ergHeim === null || $ergGast === null) {
        return 0; // Spiel noch nicht ausgewertet
    }

    $ergTendenz  = $ergHeim <=> $ergGast;   // -1 Auswärtssieg, 0 Unentschieden, 1 Heimsieg
    $tippTendenz = $tippHeim <=> $tippGast;

    $punkte = 0;

    if ($tippHeim === $ergHeim && $tippGast === $ergGast) {
        $punkte = (int)getTippSetting('pkt_ergebnis', '4');
        if ($ergTendenz === 0) {
            $punkte += (int)getTippSetting('pkt_unentschieden_bonus', '1');
        }
    } elseif ($tippTendenz === $ergTendenz) {
        $tordiffGleich = ($tippHeim - $tippGast) === ($ergHeim - $ergGast);
        if ($ergTendenz === 0) {
            // Unentschieden-Tendenz richtig, aber nicht exaktes Ergebnis
            $regel = getTippSetting('pkt_unentschieden_regel', 'nur_tendenz');
            $punkte = (int)getTippSetting('pkt_tendenz', '2');
            if ($regel === 'tendenz_plus_toranzahl' && ($tippHeim === $ergHeim || $tippGast === $ergGast)) {
                $punkte += (int)getTippSetting('pkt_toranzahl', '1');
            }
        } elseif ($tordiffGleich) {
            $punkte = (int)getTippSetting('pkt_tendenz_tordiff', '3');
        } else {
            $punkte = (int)getTippSetting('pkt_tendenz', '2');
            $regel = getTippSetting('pkt_tendenz_toranzahl_regel', 'addieren');
            if ($regel === 'addieren' && ($tippHeim === $ergHeim || $tippGast === $ergGast)) {
                $punkte += (int)getTippSetting('pkt_toranzahl', '1');
            }
        }
    } elseif ($tippHeim === $ergHeim || $tippGast === $ergGast) {
        // Seitengetreuer Vergleich - siehe Projekt-Historie (1:2 bei 2:1 zählt NICHT)
        $punkte = (int)getTippSetting('pkt_toranzahl', '1');
    }

    if ($istJoker && $punkte > 0) {
        $multiplikator = (float)getTippSetting('joker_multiplikator', '2');
        $punkte = (int)round($punkte * $multiplikator);
    }

    return $punkte;
}
