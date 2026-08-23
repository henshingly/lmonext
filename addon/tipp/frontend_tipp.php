<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/frontend_tipp.php
 * Fileversion: 0.10.0
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
    $dir    = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/home.php'));
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

/**
 * Baut eine Umleitungs-URL relativ zur neuen Tippspiel-Seite
 * (home.php?view=tippspiel) und leitet sofort um. $target hat unverändert
 * das Format "?action=login" bzw. "?action=abgabe&liga=5&spieltag=2" - nur
 * die Basis-URL hat sich geändert (vorher die eigenständige tipp.php, jetzt
 * eine View innerhalb von home.php, siehe addon/tipp/view_tippspiel_frontend.php).
 * Bestehende Aufrufstellen (tippRequireLogin() etc.) mussten dadurch NICHT
 * angepasst werden.
 */
function redirectTo(string $target) : never
{
    $qs = ltrim($target, '?');
    header('Location: home.php?view=tippspiel&' . $qs);
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
        $link = tippSiteBaseUrl() . '/home.php?view=tippspiel&action=confirm&code=' . $freischaltCode;
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
 * Liefert alle Tipps aller Tipper für eine Liste von Partien (Tippeinsicht),
 * respektiert dabei die Admin-Einstellung "Veröffentlichungszeitpunkt der
 * Tipps" (tippeinsicht_zeitpunkt: sofort/abgabeschluss/ergebnis) je Partie
 * einzeln - ein Spiel kann schon sichtbar sein, während ein anderes im
 * selben Spieltag es noch nicht ist.
 *
 * @return array<int,array<int,array>> [partieId => [tipperId => tippRow]]
 */
function tippGetEinsichtDaten(array $partien) : array
{
    $zeitpunkt = getTippSetting('tippeinsicht_zeitpunkt', 'abgabeschluss');
    $sichtbareIds = [];
    foreach ($partien as $p) {
        $sichtbar = match ($zeitpunkt) {
            'sofort' => true,
            'ergebnis' => $p['h_tore'] !== null && $p['g_tore'] !== null,
            default => !tippIstAenderbar($p['zeit'] ?? null, $p['spieltag_start'] ?? null),
        };
        if ($sichtbar) {
            $sichtbareIds[] = (int)$p['id'];
        }
    }
    if (empty($sichtbareIds)) {
        return [];
    }
    try {
        $ph = implode(',', array_fill(0, count($sichtbareIds), '?'));
        $stmt = getDB()->prepare(
            'SELECT tt.*, u.nickname FROM ' . tbl('tipp_tipp') . ' tt
               JOIN ' . tbl('tipp_user') . ' u ON u.id = tt.tipper_id
              WHERE tt.partie_id IN (' . $ph . ')
              ORDER BY u.nickname ASC'
        );
        $stmt->execute($sichtbareIds);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['partie_id']][(int)$row['tipper_id']] = $row;
        }
        return $result;
    } catch (Throwable) {
        return [];
    }
}

/**
 * Die zentrale, einzige Punkteberechnung für einen einzelnen Tipp im
 * Ergebnis-Modus - siehe Projekt-Historie für die genaue Herleitung der
 * Regeln (inkl. des bestätigten Testfalls: 1:2-Tipp bei 2:1-Ergebnis zählt
 * NICHT als "eine Toranzahl richtig", da seitengetreu verglichen wird).
 * Nutzt durchgehend strict_types und ===-Vergleiche - genau die Klarheit,
 * die im alten Addon gefehlt hat.
 */
/**
 * Liefert alle Daten für die kombinierte Rangliste+Tippübersicht EINES
 * Spieltags einer Liga (Beitrag: Vorbild kicktipp.de, siehe Kundenbild) -
 * pro Tipper: Rang, Rangveränderung zum vorherigen Spieltag, Punkte DIESES
 * Spieltags, kumulierte Gesamtpunkte bis einschließlich diesem Spieltag,
 * und die einzelnen Tipps für die Partien dieses Spieltags (für die
 * Matrix-Spalten). Berücksichtigt dieselbe Sichtbarkeits-Einstellung wie
 * tippGetEinsichtDaten() (sofort/nach Ergebnis/nach Abgabeschluss) - ein
 * noch nicht sichtbarer Tipp wird als "-:-" ohne Punkte geführt, fließt
 * aber (sobald er sichtbar wird) ganz normal in P/G ein.
 *
 * @param array $allSpieltage Ergebnis von getAllSpieltage($ligaId)
 * @return array{partien:array,rows:array}
 */
function tippGetSpieltagMatrix(int $ligaId, array $allSpieltage, int $spieltagNr) : array
{
    $partienAktuell = [];
    $spieltagIdAktuell = null;
    foreach ($allSpieltage as $st) {
        if ((int)$st['nummer'] === $spieltagNr) {
            $spieltagIdAktuell = (int)$st['id'];
            $partienAktuell = getSpieltagPartien($spieltagIdAktuell);
            foreach ($partienAktuell as &$p) { $p['spieltag_start'] = $st['start'] ?? null; }
            unset($p);
            break;
        }
    }

    // Alle Partien der Liga bis einschließlich diesem Spieltag (für G) sowie
    // bis einschließlich dem VORHERIGEN Spieltag (für die Rang-Veränderung).
    $partienIdsBisAktuell = [];
    $partienIdsBisVorherig = [];
    $partieToSpieltagNr = [];
    foreach ($allSpieltage as $st) {
        $nr = (int)$st['nummer'];
        if ($nr > $spieltagNr) {
            continue;
        }
        foreach (getSpieltagPartien((int)$st['id']) as $p) {
            $pid = (int)$p['id'];
            $partieToSpieltagNr[$pid] = $nr;
            $partienIdsBisAktuell[] = $pid;
            if ($nr < $spieltagNr) {
                $partienIdsBisVorherig[] = $pid;
            }
        }
    }

    $zeitpunkt = getTippSetting('tippeinsicht_zeitpunkt', 'abgabeschluss');
    $istSichtbar = static function (array $partie) use ($zeitpunkt) : bool {
        return match ($zeitpunkt) {
            'sofort'   => true,
            'ergebnis' => $partie['h_tore'] !== null && $partie['g_tore'] !== null,
            default    => !tippIstAenderbar($partie['zeit'] ?? null, $partie['spieltag_start'] ?? null),
        };
    };
    // Sichtbarkeit je Partie vorab bestimmen (braucht spieltag_start, siehe oben)
    $sichtbarByPartie = [];
    foreach ($allSpieltage as $st) {
        if ((int)$st['nummer'] > $spieltagNr) {
            continue;
        }
        foreach (getSpieltagPartien((int)$st['id']) as $p) {
            $p['spieltag_start'] = $st['start'] ?? null;
            $sichtbarByPartie[(int)$p['id']] = $istSichtbar($p);
        }
    }

    if (empty($partienIdsBisAktuell)) {
        return ['partien' => $partienAktuell, 'rows' => []];
    }

    try {
        $ph = implode(',', array_fill(0, count($partienIdsBisAktuell), '?'));
        $stmt = getDB()->prepare(
            'SELECT tt.tipper_id, u.nickname, tt.partie_id, tt.tipp_heim, tt.tipp_gast, tt.ist_joker,
                    p.h_tore, p.g_tore
               FROM ' . tbl('tipp_tipp') . ' tt
               JOIN ' . tbl('tipp_user') . ' u ON u.id = tt.tipper_id
               JOIN ' . tbl('liga_partien') . ' p ON p.id = tt.partie_id
              WHERE tt.partie_id IN (' . $ph . ')
                AND tt.tipp_heim IS NOT NULL AND tt.tipp_gast IS NOT NULL'
        );
        $stmt->execute($partienIdsBisAktuell);
        $tipps = $stmt->fetchAll();
    } catch (Throwable) {
        return ['partien' => $partienAktuell, 'rows' => []];
    }

    $tipper = []; // tid => ['nickname'=>..., 'punkte_aktuell'=>0, 'punkte_vorherig'=>0, 'punkte_spieltag'=>0, 'zellen'=>[partieId=>[...]]]
    foreach ($tipps as $t) {
        $tid = (int)$t['tipper_id'];
        $pid = (int)$t['partie_id'];
        if (!isset($sichtbarByPartie[$pid]) || !$sichtbarByPartie[$pid]) {
            continue; // dieser einzelne Tipp ist für Dritte noch nicht sichtbar
        }
        if (!isset($tipper[$tid])) {
            $tipper[$tid] = [
                'tipper_id' => $tid, 'nickname' => $t['nickname'],
                'punkte_aktuell' => 0, 'punkte_vorherig' => 0, 'punkte_spieltag' => 0,
                'zellen' => [],
            ];
        }
        $heim = (int)$t['tipp_heim'];
        $gast = (int)$t['tipp_gast'];
        $eh   = $t['h_tore'] !== null ? (int)$t['h_tore'] : null;
        $eg   = $t['g_tore'] !== null ? (int)$t['g_tore'] : null;
        $joker = (bool)$t['ist_joker'];
        $punkte = calculateTippPunkte($heim, $gast, $eh, $eg, $joker);

        $tipper[$tid]['punkte_aktuell'] += $punkte;
        if (in_array($pid, $partienIdsBisVorherig, true)) {
            $tipper[$tid]['punkte_vorherig'] += $punkte;
        }
        if (($partieToSpieltagNr[$pid] ?? null) === $spieltagNr) {
            $tipper[$tid]['punkte_spieltag'] += $punkte;
            $tipper[$tid]['zellen'][$pid] = ['heim' => $heim, 'gast' => $gast, 'joker' => $joker, 'punkte' => $punkte];
        }
    }

    $rangfolge = static function (array $liste, string $feld) : array {
        usort($liste, static fn(array $a, array $b) => $b[$feld] <=> $a[$feld] ?: strcasecmp((string)$a['nickname'], (string)$b['nickname']));
        $rang = [];
        foreach ($liste as $i => $r) { $rang[$r['tipper_id']] = $i + 1; }
        return $rang;
    };
    $rangAktuell  = $rangfolge(array_values($tipper), 'punkte_aktuell');
    $rangVorherig = $rangfolge(array_values($tipper), 'punkte_vorherig');

    $rows = [];
    foreach ($tipper as $tid => $r) {
        $r['rang'] = $rangAktuell[$tid];
        $r['rang_delta'] = ($rangVorherig[$tid] ?? $r['rang']) - $r['rang']; // positiv = aufgestiegen
        $rows[] = $r;
    }
    usort($rows, static fn(array $a, array $b) => $a['rang'] <=> $b['rang']);

    return ['partien' => $partienAktuell, 'rows' => $rows];
}

/**
 * Liefert die "Spieltagspunkte"-Gesamtübersicht einer Liga (Vorbild
 * kicktipp.de, Reiter "Gesamtübersicht" → "Spieltagspunkte") - pro Tipper
 * die je Spieltag erzielten Punkte (unabhängig von tippGetSpieltagMatrix(),
 * die nur EINEN Spieltag im Detail zeigt) sowie die Gesamtsumme. Zusätzlich
 * wird je Spieltag markiert, wer die (echt erreichte, >0) Höchstpunktzahl
 * hatte - analog zu "spieltagswertungen" in tippGetRangliste(), hier aber
 * je Spieltag statt aufsummiert.
 *
 * @return array{maxNr:int,rows:array} rows: tipper_id => ['nickname'=>...,
 *         'punkte'=>[spieltagNr=>int], 'gesamt'=>int, 'sieger'=>[spieltagNr=>bool]]
 */
function tippGetSpieltagspunkteUebersicht(int $ligaId, array $allSpieltage) : array
{
    $maxNr = getMaxSpieltagNummer($allSpieltage);
    if ($maxNr < 1) {
        return ['maxNr' => 0, 'rows' => []];
    }

    $partieIds = [];
    $partieToSpieltagNr = [];
    foreach ($allSpieltage as $st) {
        $nr = (int)$st['nummer'];
        foreach (getSpieltagPartien((int)$st['id']) as $p) {
            $pid = (int)$p['id'];
            $partieIds[] = $pid;
            $partieToSpieltagNr[$pid] = $nr;
        }
    }
    if (empty($partieIds)) {
        return ['maxNr' => $maxNr, 'rows' => []];
    }

    try {
        $ph = implode(',', array_fill(0, count($partieIds), '?'));
        $stmt = getDB()->prepare(
            'SELECT tt.tipper_id, u.nickname, tt.partie_id, tt.tipp_heim, tt.tipp_gast, tt.ist_joker,
                    p.h_tore, p.g_tore
               FROM ' . tbl('tipp_tipp') . ' tt
               JOIN ' . tbl('tipp_user') . ' u ON u.id = tt.tipper_id
               JOIN ' . tbl('liga_partien') . ' p ON p.id = tt.partie_id
              WHERE tt.partie_id IN (' . $ph . ')
                AND tt.tipp_heim IS NOT NULL AND tt.tipp_gast IS NOT NULL'
        );
        $stmt->execute($partieIds);
        $tipps = $stmt->fetchAll();
    } catch (Throwable) {
        return ['maxNr' => $maxNr, 'rows' => []];
    }

    $rows = [];
    $spieltagPunkte = []; // [spieltagNr => [tipperId => punkte]]
    foreach ($tipps as $t) {
        $tid = (int)$t['tipper_id'];
        $pid = (int)$t['partie_id'];
        $nr  = $partieToSpieltagNr[$pid] ?? null;
        if ($nr === null) {
            continue;
        }
        if (!isset($rows[$tid])) {
            $rows[$tid] = ['tipper_id' => $tid, 'nickname' => $t['nickname'], 'punkte' => [], 'gesamt' => 0, 'sieger' => []];
        }
        $eh = $t['h_tore'] !== null ? (int)$t['h_tore'] : null;
        $eg = $t['g_tore'] !== null ? (int)$t['g_tore'] : null;
        $punkte = calculateTippPunkte((int)$t['tipp_heim'], (int)$t['tipp_gast'], $eh, $eg, (bool)$t['ist_joker']);

        $rows[$tid]['punkte'][$nr] = ($rows[$tid]['punkte'][$nr] ?? 0) + $punkte;
        $rows[$tid]['gesamt'] += $punkte;
        $spieltagPunkte[$nr][$tid] = $rows[$tid]['punkte'][$nr];
    }

    foreach ($spieltagPunkte as $nr => $tipperPunkte) {
        $max = max($tipperPunkte);
        if ($max <= 0) {
            continue;
        }
        foreach ($tipperPunkte as $tid => $p) {
            if ($p === $max) {
                $rows[$tid]['sieger'][$nr] = true;
            }
        }
    }

    $liste = array_values($rows);
    usort($liste, static fn(array $a, array $b) => $b['gesamt'] <=> $a['gesamt'] ?: strcasecmp((string)$a['nickname'], (string)$b['nickname']));

    return ['maxNr' => $maxNr, 'rows' => $liste];
}

/**
 * Statistiken für EINEN Tipper einer Liga (Vorbild kicktipp.de "Statistik") -
 * Tipp-/Punkte-Verteilung nach Tendenz, Rang-Verlauf über die Saison,
 * Punkte je Mannschaft (für Top-3/Flop-3), sowie (zweite Ausbaustufe, Seite
 * 2 der Statistik-Ansicht): Verteilung der ECHTEN Ergebnisse der Liga nach
 * Tendenz, die eigene Tipp-Tendenz je Spieltag, Punkte je Spieltag und der
 * Punkte-Abstand zum jeweiligen Spieltagsführenden. Baut auf
 * tippGetSpieltagspunkteUebersicht() auf (keine zusätzliche Abfrage nötig
 * für den Rang-Verlauf/Punkte-zur-Spitze - die Spieltagspunkte aller Tipper
 * sind da schon vorhanden, hier wird daraus nur abgeleitet).
 *
 * @return array{tipps_tendenz:array,punkte_tendenz:array,rang_verlauf:array,anzahl_tipper:int,punkte_team:array,ergebnisse_tendenz:array,tipps_pro_spieltag:array,punkte_pro_spieltag:array,punkte_zur_spitze:array}
 */
function tippGetTipperStatistik(int $ligaId, array $allSpieltage, int $tipperId) : array
{
    $uebersicht = tippGetSpieltagspunkteUebersicht($ligaId, $allSpieltage);
    $maxNr = $uebersicht['maxNr'];

    // Rang-Verlauf + "Punkte zur Spitze" + Spieltagsplatzierung (Rang NUR
    // an diesem Spieltag, nicht kumuliert) + Vergleich mit dem
    // Spieltagssieger: aus den (nicht-kumulierten) Spieltagspunkten aller
    // Tipper eine laufende Summe bilden und je Spieltag neu sortieren. Der
    // Spieltagsführende (Gesamtwertung) ist der höchste kumulierte Wert an
    // diesem Spieltag, der Spieltagssieger (Tageswertung) der höchste
    // NICHT-kumulierte Wert an diesem einen Spieltag.
    $kumuliert = []; // tipperId => laufende Summe
    $rangVerlauf = [];
    $punkteZurSpitze = [];
    $punkteProSpieltag = [];
    $spieltagsplatzierungVerlauf = [];
    $vergleichSpieltagssieger = [];
    foreach ($uebersicht['rows'] as $r) {
        $kumuliert[$r['tipper_id']] = 0;
    }
    for ($n = 1; $n <= $maxNr; $n++) {
        $tagespunkte = []; // tipperId => Punkte NUR an diesem Spieltag
        foreach ($uebersicht['rows'] as $r) {
            $kumuliert[$r['tipper_id']] += $r['punkte'][$n] ?? 0;
            $tagespunkte[$r['tipper_id']] = $r['punkte'][$n] ?? 0;
            if ((int)$r['tipper_id'] === $tipperId) {
                $punkteProSpieltag[$n] = $r['punkte'][$n] ?? 0;
            }
        }
        $sortiert = $kumuliert;
        arsort($sortiert);
        $rang = 1;
        $spitzenwert = reset($sortiert);
        foreach ($sortiert as $tid => $punkte) {
            if ($tid === $tipperId) {
                $rangVerlauf[$n] = $rang;
                $punkteZurSpitze[$n] = ($spitzenwert !== false ? $spitzenwert : 0) - $punkte;
                break;
            }
            $rang++;
        }

        $tagesSortiert = $tagespunkte;
        arsort($tagesSortiert);
        $tagesRang = 1;
        foreach ($tagesSortiert as $tid => $punkte) {
            if ($tid === $tipperId) {
                $spieltagsplatzierungVerlauf[$n] = $tagesRang;
                break;
            }
            $tagesRang++;
        }
        $spieltagssieger = reset($tagesSortiert);
        $vergleichSpieltagssieger[$n] = [
            'mich'   => $tagespunkte[$tipperId] ?? 0,
            'sieger' => $spieltagssieger !== false ? $spieltagssieger : 0,
        ];
    }

    // Tipp-/Punkte-Verteilung nach Tendenz + Punkte je Mannschaft + Tipp-
    // Tendenz je Spieltag + häufigste getippte Ergebnisse je Tendenz: eigene,
    // schlanke Abfrage nur für diesen einen Tipper (die Rangliste oben
    // enthält keine Team-/Tendenz-/Ergebnis-Zuordnung).
    $tippsTendenz  = ['Heim' => 0, 'Remis' => 0, 'Gast' => 0];
    $punkteTendenz = ['Heim' => 0, 'Remis' => 0, 'Gast' => 0];
    $punkteTeam    = [];
    $tippsProSpieltag = [];
    $tippsErgebnisRoh = ['Heim' => [], 'Remis' => [], 'Gast' => []]; // "H:G" => Anzahl
    try {
        $stmt = getDB()->prepare(
            'SELECT tt.tipp_heim, tt.tipp_gast, tt.ist_joker, p.h_tore, p.g_tore, s.nummer AS spieltag_nr,
                    th.name AS heim_name, tg.name AS gast_name
               FROM ' . tbl('tipp_tipp') . ' tt
               JOIN ' . tbl('liga_partien') . ' p ON p.id = tt.partie_id
               JOIN ' . tbl('liga_spieltage') . ' s ON s.id = p.spieltag_id
               LEFT JOIN ' . tbl('teams_global') . ' th ON th.id = p.heim_id
               LEFT JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.gast_id
              WHERE tt.tipper_id = ? AND s.liga_id = ?
                AND tt.tipp_heim IS NOT NULL AND tt.tipp_gast IS NOT NULL'
        );
        $stmt->execute([$tipperId, $ligaId]);
        foreach ($stmt->fetchAll() as $t) {
            $tippTendenz = (int)$t['tipp_heim'] <=> (int)$t['tipp_gast'];
            $tendenzLabel = $tippTendenz > 0 ? 'Heim' : ($tippTendenz < 0 ? 'Gast' : 'Remis');
            $tippsTendenz[$tendenzLabel]++;

            $ergebnisStr = (int)$t['tipp_heim'] . ':' . (int)$t['tipp_gast'];
            $tippsErgebnisRoh[$tendenzLabel][$ergebnisStr] = ($tippsErgebnisRoh[$tendenzLabel][$ergebnisStr] ?? 0) + 1;

            $nr = (int)$t['spieltag_nr'];
            if (!isset($tippsProSpieltag[$nr])) {
                $tippsProSpieltag[$nr] = ['Heim' => 0, 'Remis' => 0, 'Gast' => 0];
            }
            $tippsProSpieltag[$nr][$tendenzLabel]++;

            $eh = $t['h_tore'] !== null ? (int)$t['h_tore'] : null;
            $eg = $t['g_tore'] !== null ? (int)$t['g_tore'] : null;
            if ($eh === null || $eg === null) {
                continue; // noch nicht ausgewertet - fließt nicht in Punkte/Team-Stats ein
            }
            $punkte = calculateTippPunkte((int)$t['tipp_heim'], (int)$t['tipp_gast'], $eh, $eg, (bool)$t['ist_joker']);
            $punkteTendenz[$tendenzLabel] += $punkte;

            foreach ([$t['heim_name'], $t['gast_name']] as $teamName) {
                if ($teamName === null || $teamName === '') {
                    continue;
                }
                $punkteTeam[$teamName] = ($punkteTeam[$teamName] ?? 0) + $punkte;
            }
        }
    } catch (Throwable) {
        // Rückgabe bleibt bei den Default-Werten (alles 0/leer)
    }
    $tippsTop3Ergebnis = [];
    foreach ($tippsErgebnisRoh as $tendenz => $werte) {
        arsort($werte);
        $tippsTop3Ergebnis[$tendenz] = array_slice($werte, 0, 3, true);
    }

    // Verteilung der ECHTEN Ergebnisse dieser Liga nach Tendenz + häufigste
    // tatsächliche Ergebnisse je Tendenz + Verlauf je Spieltag - unabhängig
    // vom Tipper, alle bereits gespielten Partien der Liga.
    $ergebnisseTendenz = ['Heim' => 0, 'Remis' => 0, 'Gast' => 0];
    $ergebnisseRoh = ['Heim' => [], 'Remis' => [], 'Gast' => []];
    $ergebnisseProSpieltag = [];
    try {
        $stmt = getDB()->prepare(
            'SELECT p.h_tore, p.g_tore, s.nummer AS spieltag_nr
               FROM ' . tbl('liga_partien') . ' p
               JOIN ' . tbl('liga_spieltage') . ' s ON s.id = p.spieltag_id
              WHERE s.liga_id = ? AND p.h_tore IS NOT NULL AND p.g_tore IS NOT NULL'
        );
        $stmt->execute([$ligaId]);
        foreach ($stmt->fetchAll() as $p) {
            $ergTendenz = (int)$p['h_tore'] <=> (int)$p['g_tore'];
            $label = $ergTendenz > 0 ? 'Heim' : ($ergTendenz < 0 ? 'Gast' : 'Remis');
            $ergebnisseTendenz[$label]++;
            $ergebnisStr = (int)$p['h_tore'] . ':' . (int)$p['g_tore'];
            $ergebnisseRoh[$label][$ergebnisStr] = ($ergebnisseRoh[$label][$ergebnisStr] ?? 0) + 1;

            $nr = (int)$p['spieltag_nr'];
            if (!isset($ergebnisseProSpieltag[$nr])) {
                $ergebnisseProSpieltag[$nr] = ['Heim' => 0, 'Remis' => 0, 'Gast' => 0];
            }
            $ergebnisseProSpieltag[$nr][$label]++;
        }
    } catch (Throwable) {
        // Rückgabe bleibt bei 0/0/0
    }
    $ergebnisseTop3Ergebnis = [];
    foreach ($ergebnisseRoh as $tendenz => $werte) {
        arsort($werte);
        $ergebnisseTop3Ergebnis[$tendenz] = array_slice($werte, 0, 3, true);
    }

    return [
        'tipps_tendenz'       => $tippsTendenz,
        'punkte_tendenz'      => $punkteTendenz,
        'rang_verlauf'        => $rangVerlauf,
        'anzahl_tipper'       => count($uebersicht['rows']),
        'punkte_team'         => $punkteTeam,
        'ergebnisse_tendenz'  => $ergebnisseTendenz,
        'tipps_pro_spieltag'  => $tippsProSpieltag,
        'punkte_pro_spieltag' => $punkteProSpieltag,
        'ergebnisse_pro_spieltag' => $ergebnisseProSpieltag,
        'punkte_zur_spitze'   => $punkteZurSpitze,
        'tipps_top3_ergebnis'      => $tippsTop3Ergebnis,
        'ergebnisse_top3_ergebnis' => $ergebnisseTop3Ergebnis,
        'spieltagsplatzierung_verlauf' => $spieltagsplatzierungVerlauf,
        'vergleich_spieltagssieger'    => $vergleichSpieltagssieger,
    ];
}

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

/**
 * Liefert die globale Rangliste (alle Tipper, alle jemals getippten Spiele,
 * unabhängig vom aktuellen Freigabe-Status einer Liga - die Rangliste zeigt
 * den historischen Datenbestand). Alles wird live aus den Rohdaten
 * berechnet (siehe Projekt-Historie: keine gespeicherten Punkte), inkl. der
 * drei in "Was zählt bei Punktgleichheit" konfigurierten Tie-Break-
 * Kriterien aus den acht bestätigten Original-Optionen.
 *
 * Nur der Ergebnis-Tippmodus wird aktuell ausgewertet - der Tendenz-Modus
 * hat noch keine eigene Punkteberechnung (siehe frontend_tipp.php-Changelog
 * 0.1.0/0.2.0, offen für eine spätere Sitzung); Tipps mit nur befülltem
 * tipp_tendenz-Feld (ohne tipp_heim/tipp_gast) werden hier übersprungen,
 * nicht falsch mit 0 Punkten gezählt.
 *
 * @return array<int,array> Liste, bereits sortiert (Platz 1 zuerst), jeder
 *                           Eintrag: tipper_id, nickname, punkte,
 *                           spiele_getippt, ausgewertete_spiele,
 *                           richtige_ergebnisse, richtige_tendenz_tordiff,
 *                           richtige_tendenz, joker_bonus_punkte, quote
 *                           (0.0-1.0), spieltagswertungen
 */
function tippGetRangliste() : array
{
    try {
        $rows = getDB()->query(
            'SELECT tt.tipper_id, u.nickname, tt.tipp_heim, tt.tipp_gast, tt.ist_joker,
                    p.h_tore, p.g_tore, p.spieltag_id
               FROM ' . tbl('tipp_tipp') . ' tt
               JOIN ' . tbl('tipp_user') . ' u ON u.id = tt.tipper_id
               JOIN ' . tbl('liga_partien') . ' p ON p.id = tt.partie_id
              WHERE tt.tipp_heim IS NOT NULL AND tt.tipp_gast IS NOT NULL'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }

    $stats = [];
    $spieltagPunkte = []; // [spieltagId => [tipperId => punkte]]

    foreach ($rows as $r) {
        $tid = (int)$r['tipper_id'];
        if (!isset($stats[$tid])) {
            $stats[$tid] = [
                'tipper_id'                => $tid,
                'nickname'                 => $r['nickname'],
                'punkte'                   => 0,
                'spiele_getippt'           => 0,
                'ausgewertete_spiele'      => 0,
                'richtige_ergebnisse'      => 0,
                'richtige_tendenz_tordiff' => 0,
                'richtige_tendenz'         => 0,
                'joker_bonus_punkte'       => 0,
                'treffer'                  => 0,
                'spieltagswertungen'       => 0,
            ];
        }
        $stats[$tid]['spiele_getippt']++;

        $ausgewertet = $r['h_tore'] !== null && $r['g_tore'] !== null;
        if (!$ausgewertet) {
            continue;
        }
        $heim = (int)$r['tipp_heim'];
        $gast = (int)$r['tipp_gast'];
        $eh   = (int)$r['h_tore'];
        $eg   = (int)$r['g_tore'];
        $joker = (bool)$r['ist_joker'];

        $punkte = calculateTippPunkte($heim, $gast, $eh, $eg, $joker);
        $stats[$tid]['ausgewertete_spiele']++;
        $stats[$tid]['punkte'] += $punkte;
        if ($punkte > 0) {
            $stats[$tid]['treffer']++;
        }
        if ($joker) {
            $ohneJoker = calculateTippPunkte($heim, $gast, $eh, $eg, false);
            $stats[$tid]['joker_bonus_punkte'] += ($punkte - $ohneJoker);
        }

        $exakt = ($heim === $eh && $gast === $eg);
        $ergTendenz  = $eh <=> $eg;
        $tippTendenz = $heim <=> $gast;
        $tendenzRichtig = !$exakt && $tippTendenz === $ergTendenz;
        $tordiffGleich  = $tendenzRichtig && ($heim - $gast) === ($eh - $eg);
        if ($exakt) {
            $stats[$tid]['richtige_ergebnisse']++;
        } elseif ($tendenzRichtig && $tordiffGleich) {
            $stats[$tid]['richtige_tendenz_tordiff']++;
        } elseif ($tendenzRichtig) {
            $stats[$tid]['richtige_tendenz']++;
        }

        $sid = (int)$r['spieltag_id'];
        $spieltagPunkte[$sid][$tid] = ($spieltagPunkte[$sid][$tid] ?? 0) + $punkte;
    }

    // Spieltagswertungen: pro Spieltag gewinnen alle Tipper mit der (echt
    // erreichten, > 0) Höchstpunktzahl - geteilte Siege sind möglich, ein
    // Spieltag ohne jegliche Punkte (z.B. noch nicht ausgewertet) zählt
    // nicht als "gewonnen"
    foreach ($spieltagPunkte as $tipperPunkte) {
        $max = max($tipperPunkte);
        if ($max <= 0) {
            continue;
        }
        foreach ($tipperPunkte as $tid => $p) {
            if ($p === $max) {
                $stats[$tid]['spieltagswertungen']++;
            }
        }
    }

    foreach ($stats as &$s) {
        $s['quote'] = $s['ausgewertete_spiele'] > 0 ? $s['treffer'] / $s['ausgewertete_spiele'] : 0.0;
    }
    unset($s);

    $kriterien = [
        getTippSetting('kriterium_1', 'hoehere_quote'),
        getTippSetting('kriterium_2', 'anzahl_spiele_getippt'),
        getTippSetting('kriterium_3', 'anzahl_richtige_ergebnisse'),
    ];
    $kriteriumFeld = [
        'hoehere_quote'                    => 'quote',
        'anzahl_spiele_getippt'            => 'spiele_getippt',
        'anzahl_richtige_ergebnisse'       => 'richtige_ergebnisse',
        'anzahl_richtige_tendenz_tordiff'  => 'richtige_tendenz_tordiff',
        'anzahl_richtige_tendenz'          => 'richtige_tendenz',
        'joker_punkte'                     => 'joker_bonus_punkte',
        'spieltagswertungen'               => 'spieltagswertungen',
    ];

    $liste = array_values($stats);
    usort($liste, function (array $a, array $b) use ($kriterien, $kriteriumFeld) : int {
        if ($a['punkte'] !== $b['punkte']) {
            return $b['punkte'] <=> $a['punkte'];
        }
        foreach ($kriterien as $k) {
            if ($k === 'kein_kriterium' || !isset($kriteriumFeld[$k])) {
                continue;
            }
            $feld = $kriteriumFeld[$k];
            if ($a[$feld] !== $b[$feld]) {
                return $b[$feld] <=> $a[$feld];
            }
        }
        return strcasecmp((string)$a['nickname'], (string)$b['nickname']);
    });

    return $liste;
}
