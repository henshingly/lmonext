<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/StandingsTrait.php
 * Fileversion: 1.3.0
 * Changelog: 1.3.0 - Kundenwunsch (Mobile-Rückmeldung): (1) Vierten Korrekturwert
 *                     "minuspunkte_korrektur" ergänzt, damit die separate Minuspunkte-Anzeige
 *                     ebenfalls (z.B. auf 0) korrigierbar ist - vorher blieb sie bei einer
 *                     Punkte-Korrektur unverändert bestehen; (2) die eigentliche Vorzeichen-
 *                     Eingabe (Minuszeichen auf Mobilgeräten oft nicht erreichbar) wurde in
 *                     admin/view_liga_settings.php auf ein Dropdown (+/−) plus Betragsfeld
 *                     umgestellt - hier nur die Datenschicht dafür erweitert
 * Changelog: 1.2.0 - Kundenwunsch (2 Punkte): (1) Minuspunkte-Anzeige (Admin-Einstellung
 *                     "MinusPoints" existierte schon lange, wurde aber nirgends gelesen) - neue
 *                     Berechnung der klassischen "Gewinnpunkte:Verlustpunkte"-Darstellung je
 *                     Team, respektiert das jeweils konfigurierte Punktesystem statt fest 2/1/0
 *                     anzunehmen; (2) Strafen/Bonus-Feature erweitert: dritter Korrekturwert
 *                     "tore_korrektur" (erzielte Tore) ergänzt, damit Punkte UND beide Tor-Werte
 *                     unabhängig voneinander mit +/- korrigiert werden können (z.B. Lizenzentzug:
 *                     Team komplett auf 0:0/0 setzen). Alle drei Werte jetzt klar als
 *                     vorzeichenbehaftet (Bonus/Strafe) dokumentiert und im Tooltip entsprechend
 *                     mit korrektem Vorzeichen angezeigt
 * Changelog: 1.1.0 - Neues Feature "Strafpunkte/Straftore": computeStandings() bekommt einen
 *                     optionalen $ligaId-Parameter (Rückwärtskompatibel, Standard null = kein
 *                     Verhaltenswechsel für bestehende Aufrufer ohne Liga-Bezug) und zieht damit
 *                     admin-seitig hinterlegte Strafpunkte von den regulär berechneten Punkten
 *                     ab bzw. addiert Straftore zu den Gegentoren, VOR der finalen Sortierung -
 *                     wirkt sich also korrekt auf Tabellenplatz und Tordifferenz aus. Neue
 *                     Funktionen getLigaStrafpunkte()/setLigaStrafpunkte() plus neue Tabelle
 *                     liga_strafpunkte (per ensureStrafpunkteSchema() bei Bedarf angelegt, auch
 *                     auf Bestandsinstallationen ohne erneuten install.php-Lauf)
 * Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in
 *                     fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen
 *                     Kontext der Umstellung). Tabellenberechnung (computeStandings, computeStandingsMarkerColor, renderStandingsView).
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <https://bastel-code.de/>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types=1);

namespace LMOnext\Liga;

/**
 * Extracted from the legacy frontend/data_liga.php.
 * Behavior is intentionally preserved; public compatibility wrappers live in frontend/data_liga.php.
 */
trait StandingsTrait
{
    /**
     * Statistik für einen Spieltag: Schnitt Heim-/Gast-Tore, Gesamttore, Tore/Spiel
     * – nur aus tatsächlich gespielten Partien berechnet.
     */
    public static function computeSpieltagStats(array $partien) : array
    {
        $heimTore = 0;
        $gastTore = 0;
        $gespielt = 0;
        foreach ($partien as $p) {
            if ($p['h_tore'] !== null && $p['g_tore'] !== null) {
                $heimTore += (int)$p['h_tore'];
                $gastTore += (int)$p['g_tore'];
                $gespielt++;
            }
        }
        $gesamtTore = $heimTore + $gastTore;
        return [
            'schnittHeim'  => $gespielt > 0 ? round($heimTore / $gespielt, 2) : 0,
            'schnittGast'  => $gespielt > 0 ? round($gastTore / $gespielt, 2) : 0,
            'tore'         => $gesamtTore,
            'toreProSpiel' => $gespielt > 0 ? round($gesamtTore / $gespielt, 2) : 0,
        ];
    }
    /**
     * Berechnet die Tabelle: Sp/S/U/N/Tore/Diff/Pkt je Team, sortiert nach
     * Punkte → Tordifferenz → Tore (wie im Adminbereich). Startet mit allen
     * gemeldeten Teams (auch ohne gespielte Partie, dann mit lauter Nullen).
     */
    public static function computeStandings(array $teamsList, array $partien, array $ligaOptions, ?int $ligaId = null) : array
    {
        $ptW = (int)($ligaOptions['PointsForWin']  ?? 3);
        $ptD = (int)($ligaOptions['PointsForDraw'] ?? 1);
        $ptL = (int)($ligaOptions['PointsForLost'] ?? 0);
        // Eigene Punktwerte für "nach Verlängerung" (status=2, "n.V.") und "nach
        // Elfmeterschießen" (status=1, "i.E."), analog zum alten LMO. Fallen
        // mangels expliziter Einstellung auf die normalen Werte zurück – damit
        // ändert sich für alle Ligen, die diese neuen Felder noch nie gesetzt
        // haben, an der Punktevergabe nichts (volle Rückwärtskompatibilität).
        $ptWET = (int)($ligaOptions['PointsForWinET']  ?? $ptW);
        $ptDET = (int)($ligaOptions['PointsForDrawET'] ?? $ptD);
        $ptLET = (int)($ligaOptions['PointsForLostET'] ?? $ptL);
        $ptWPS = (int)($ligaOptions['PointsForWinPS']  ?? $ptW);
        $ptDPS = (int)($ligaOptions['PointsForDrawPS'] ?? $ptD);
        $ptLPS = (int)($ligaOptions['PointsForLostPS'] ?? $ptL);
    
        $rows = [];
        foreach ($teamsList as $t) {
            $rows[(int)$t['id']] = [
                'id' => (int)$t['id'], 'name' => $t['name'], 'kurz' => $t['kurz'] ?? '',
                'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0,
                'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0, 'minuspunkte' => 0,
                'strafpunkte' => 0, 'straftore' => 0, 'torekorrektur' => 0, 'minuspunktekorrektur' => 0, 'strafgrund' => '',
            ];
        }
    
        foreach ($partien as $p) {
            if ($p['h_tore'] === null || $p['g_tore'] === null) {
                continue;
            }
            $hId = (int)($p['heim_id'] ?? 0);
            $gId = (int)($p['gast_id'] ?? 0);
            if ($hId <= 0 || $gId <= 0) {
                continue;
            }
            if (!isset($rows[$hId])) {
                $rows[$hId] = ['id' => $hId, 'name' => $p['heim_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0, 'minuspunkte' => 0, 'strafpunkte' => 0, 'straftore' => 0, 'torekorrektur' => 0, 'minuspunktekorrektur' => 0, 'strafgrund' => ''];
            }
            if (!isset($rows[$gId])) {
                $rows[$gId] = ['id' => $gId, 'name' => $p['gast_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0, 'minuspunkte' => 0, 'strafpunkte' => 0, 'straftore' => 0, 'torekorrektur' => 0, 'minuspunktekorrektur' => 0, 'strafgrund' => ''];
            }
    
            $ht = (int)$p['h_tore'];
            $gt = (int)$p['g_tore'];
    
            $rows[$hId]['sp']++;
            $rows[$gId]['sp']++;
            $rows[$hId]['tore_h'] += $ht;
            $rows[$hId]['tore_g'] += $gt;
            $rows[$gId]['tore_h'] += $gt;
            $rows[$gId]['tore_g'] += $ht;
    
            // status: 0 = regulär, 1 = i.E. (Elfmeterschießen), 2 = n.V. (nach
            // Verlängerung) – siehe statusSuffix(). Je nachdem gilt eine andere
            // Sieg/Unentschieden/Niederlage-Punktetabelle.
            [$curW, $curD, $curL] = match ((int)($p['status'] ?? 0)) {
                1       => [$ptWPS, $ptDPS, $ptLPS],
                2       => [$ptWET, $ptDET, $ptLET],
                default => [$ptW, $ptD, $ptL],
            };
    
            if ($ht > $gt) {
                $rows[$hId]['s']++;
                $rows[$hId]['pkt'] += $curW;
                $rows[$hId]['minuspunkte'] += $curL;
                $rows[$gId]['n']++;
                $rows[$gId]['pkt'] += $curL;
                $rows[$gId]['minuspunkte'] += $curW;
            } elseif ($ht < $gt) {
                $rows[$gId]['s']++;
                $rows[$gId]['pkt'] += $curW;
                $rows[$gId]['minuspunkte'] += $curL;
                $rows[$hId]['n']++;
                $rows[$hId]['pkt'] += $curL;
                $rows[$hId]['minuspunkte'] += $curW;
            } else {
                $rows[$hId]['u']++;
                $rows[$hId]['pkt'] += $curD;
                $rows[$hId]['minuspunkte'] += $curD;
                $rows[$gId]['u']++;
                $rows[$gId]['pkt'] += $curD;
                $rows[$gId]['minuspunkte'] += $curD;
            }
        }
    
        // Straftore/Strafpunkte bzw. Bonuspunkte/Bonustore anwenden (Admin →
        // Liga-Einstellungen → Strafen, siehe getLigaStrafpunkte()) - NACH der
        // regulären Punkteberechnung, damit sie unabhängig vom gewählten
        // Punktesystem (2er/3er, n.V./i.E.) als fester Zu-/Abschlag on top
        // wirken. Alle drei Werte sind VORZEICHENBEHAFTET: positiv = Bonus,
        // negativ = Strafe - z.B. um einem Team wegen Lizenzentzugs alle
        // Saisonwerte auf 0:0/0 zu korrigieren, unabhängig vom aktuellen
        // Spielstand. $ligaId ist optional (null), damit computeStandings()
        // für Kontexte ohne Liga-Bezug (z.B. reine Was-wäre-wenn-Berechnungen)
        // unverändert ohne DB-Zugriff nutzbar bleibt.
        if ($ligaId !== null) {
            $strafen = self::getLigaStrafpunkte($ligaId);
            foreach ($strafen as $teamId => $s) {
                if (!isset($rows[$teamId])) {
                    continue; // Team ist nicht (mehr) Teil dieser Liga
                }
                $rows[$teamId]['strafpunkte']    = $s['strafpunkte'];
                $rows[$teamId]['straftore']      = $s['straftore'];
                $rows[$teamId]['torekorrektur']  = $s['tore_korrektur'];
                $rows[$teamId]['minuspunktekorrektur'] = $s['minuspunkte_korrektur'];
                $rows[$teamId]['strafgrund']     = $s['grund'];
                $rows[$teamId]['pkt']           += $s['strafpunkte'];
                $rows[$teamId]['tore_g']        += $s['straftore'];
                $rows[$teamId]['tore_h']        += $s['tore_korrektur'];
                $rows[$teamId]['minuspunkte']   += $s['minuspunkte_korrektur'];
            }
        }

        $standings = array_values($rows);
        usort($standings, static function (array $a, array $b) : int {
            if ($a['pkt'] !== $b['pkt']) {
                return $b['pkt'] <=> $a['pkt'];
            }
            $diffA = $a['tore_h'] - $a['tore_g'];
            $diffB = $b['tore_h'] - $b['tore_g'];
            if ($diffA !== $diffB) {
                return $diffB <=> $diffA;
            }
            return $b['tore_h'] <=> $a['tore_h'];
        });
    
        return $standings;
    }

    /**
     * Legt die Strafpunkte-Tabelle bei Bedarf an (analog zum ensureTippSchema()-
     * Muster im Tippspiel-Addon) - so funktioniert die Funktion auch auf
     * bereits bestehenden Installationen, die vor Einführung dieses Features
     * angelegt wurden, ohne dass install.php erneut laufen müsste.
     */
    private static function ensureStrafpunkteSchema() : void
    {
        static $done = false; if ($done) return; $done = true;
        try {
            $db = getDB();
            $db->exec('CREATE TABLE IF NOT EXISTS ' . tbl('liga_strafpunkte') . ' (
                `id`          INT AUTO_INCREMENT PRIMARY KEY,
                `liga_id`     INT NOT NULL,
                `team_id`     INT NOT NULL,
                `strafpunkte` INT NOT NULL DEFAULT 0,
                `straftore`   INT NOT NULL DEFAULT 0,
                `grund`       VARCHAR(255) NULL DEFAULT NULL,
                `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `liga_team` (`liga_id`, `team_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            // Migration: Korrektur der "erzielten Tore" (tore_h) - ursprünglich
            // gab es nur "straftore" (wirkt auf die Gegentore/tore_g). Ergänzt
            // um die Bielefeld-Lizenzentzug-Situation abzudecken (Punkte UND
            // beide Tor-Werte auf 0 korrigieren zu können), siehe Changelog.
            $cols = $db->query('SHOW COLUMNS FROM ' . tbl('liga_strafpunkte'))->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('tore_korrektur', $cols, true)) {
                $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `tore_korrektur` INT NOT NULL DEFAULT 0 AFTER `straftore`');
            }
            if (!in_array('minuspunkte_korrektur', $cols, true)) {
                $db->exec('ALTER TABLE ' . tbl('liga_strafpunkte') . ' ADD COLUMN `minuspunkte_korrektur` INT NOT NULL DEFAULT 0 AFTER `tore_korrektur`');
            }
        } catch (\Throwable) {
            // Wird bei jedem Aufruf erneut versucht (static $done bleibt auf
            // dieser Instanz zwar true, aber ein neuer Request versucht es
            // erneut) - getLigaStrafpunkte()/setLigaStrafpunkte() fangen einen
            // fehlenden Tabellenzugriff ohnehin selbst ab.
        }
    }

    /**
     * Liefert alle für eine Liga hinterlegten Korrekturen (Strafpunkte/
     * Bonuspunkte, Straftore/Bonustore bei den Gegentoren, sowie eine
     * Korrektur der erzielten Tore), indiziert nach Team-ID. Alle drei Werte
     * sind vorzeichenbehaftet - positiv = Bonus, negativ = Strafe/Abzug. Teams
     * ohne Eintrag fehlen im Ergebnis (kein Datensatz angelegt für 0/0/0 -
     * siehe setLigaStrafpunkte()).
     *
     * @return array<int,array{strafpunkte:int,straftore:int,tore_korrektur:int,minuspunkte_korrektur:int,grund:string}>
     */
    public static function getLigaStrafpunkte(int $ligaId) : array
    {
        self::ensureStrafpunkteSchema();
        try {
            $stmt = getDB()->prepare(
                'SELECT team_id, strafpunkte, straftore, tore_korrektur, minuspunkte_korrektur, grund FROM ' . tbl('liga_strafpunkte') . ' WHERE liga_id = ?'
            );
            $stmt->execute([$ligaId]);
            $result = [];
            foreach ($stmt->fetchAll() as $r) {
                $result[(int)$r['team_id']] = [
                    'strafpunkte'    => (int)$r['strafpunkte'],
                    'straftore'      => (int)$r['straftore'],
                    'tore_korrektur' => (int)($r['tore_korrektur'] ?? 0),
                    'minuspunkte_korrektur' => (int)($r['minuspunkte_korrektur'] ?? 0),
                    'grund'          => (string)($r['grund'] ?? ''),
                ];
            }
            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Speichert die Korrektur-Einträge (Punkte/erzielte Tore/Gegentore, alle
     * vorzeichenbehaftet) für eine Liga (ein Formular-Submit mit einer Zeile
     * je Team, siehe Admin → Liga-Einstellungen → Strafen). Teams mit 0 in
     * allen drei Werten und leerem Grund werden aus der Tabelle entfernt
     * statt als Leerzeile gespeichert - hält die Tabelle sauber und "kein
     * Eintrag" bedeutet eindeutig "keine Korrektur".
     *
     * @param array<int,array{strafpunkte?:int|string,straftore?:int|string,tore_korrektur?:int|string,minuspunkte_korrektur?:int|string,grund?:string}> $eintraege team_id => Werte
     */
    public static function setLigaStrafpunkte(int $ligaId, array $eintraege) : bool
    {
        self::ensureStrafpunkteSchema();
        try {
            $db = getDB();
            $upsert = $db->prepare(
                'INSERT INTO ' . tbl('liga_strafpunkte') . ' (liga_id, team_id, strafpunkte, straftore, tore_korrektur, minuspunkte_korrektur, grund)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE strafpunkte = VALUES(strafpunkte), straftore = VALUES(straftore),
                                         tore_korrektur = VALUES(tore_korrektur),
                                         minuspunkte_korrektur = VALUES(minuspunkte_korrektur), grund = VALUES(grund)'
            );
            $delete = $db->prepare('DELETE FROM ' . tbl('liga_strafpunkte') . ' WHERE liga_id = ? AND team_id = ?');

            foreach ($eintraege as $teamId => $e) {
                $teamId = (int)$teamId;
                $sp     = (int)($e['strafpunkte'] ?? 0);
                $st     = (int)($e['straftore'] ?? 0);
                $tk     = (int)($e['tore_korrektur'] ?? 0);
                $mk     = (int)($e['minuspunkte_korrektur'] ?? 0);
                $grund  = trim((string)($e['grund'] ?? ''));

                if ($sp === 0 && $st === 0 && $tk === 0 && $mk === 0 && $grund === '') {
                    $delete->execute([$ligaId, $teamId]);
                    continue;
                }
                $upsert->execute([$ligaId, $teamId, $sp, $st, $tk, $mk, $grund !== '' ? $grund : null]);
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
    /**
     * Kleiner Hinweis-Marker (⚠ mit Tooltip) für die Tabellenzeile eines
     * Teams mit Strafpunkten/Straftoren - leerer String, wenn keine Strafe
     * vorliegt. Erwartet eine Zeile aus computeStandings() (mit den Feldern
     * strafpunkte/straftore/strafgrund).
     */
    public static function renderStrafHinweis(array $row) : string
    {
        $sp = (int)($row['strafpunkte'] ?? 0);
        $st = (int)($row['straftore'] ?? 0);
        $tk = (int)($row['torekorrektur'] ?? 0);
        $mk = (int)($row['minuspunktekorrektur'] ?? 0);
        if ($sp === 0 && $st === 0 && $tk === 0 && $mk === 0) {
            return '';
        }
        $teile = [];
        if ($sp !== 0) {
            $teile[] = ($sp > 0 ? '+' : '') . $sp . ' ' . tf('liga_standings_col_pkt');
        }
        if ($tk !== 0) {
            $teile[] = ($tk > 0 ? '+' : '') . $tk . ' ' . tf('liga_standings_straf_erzielt');
        }
        if ($st !== 0) {
            $teile[] = ($st > 0 ? '+' : '') . $st . ' ' . tf('liga_standings_straf_gegentore');
        }
        if ($mk !== 0) {
            $teile[] = ($mk > 0 ? '+' : '') . $mk . ' ' . tf('liga_standings_straf_minuspunkte');
        }
        $tooltip = implode(', ', $teile);
        $grund = trim((string)($row['strafgrund'] ?? ''));
        if ($grund !== '') {
            $tooltip .= ' (' . $grund . ')';
        }
        return ' <span class="st-straf-hinweis" title="' . h($tooltip) . '">⚠</span>';
    }
    /**
     * Ermittelt die Randfarbe (Tabellenmarkierung, siehe Admin → Liga-
     * Einstellungen → Tabelle) für eine Tabellenzeile anhand ihres Rangs
     * (0-basiert). Von oben nach unten: Meister (nur Rang 1, falls aktiviert,
     * zählt zum CL-Kontingent dazu) → Champions League → CL-Qualifikation →
     * Euroleague. Von unten nach oben: feststehende Absteiger → Relegation.
     * Gibt einen Hex-Farbwert zurück, oder '' wenn dieser Rang keine
     * Markierung hat.
     */
    public static function computeStandingsMarkerColor(int $index, int $totalTeams, array $opts) : string
    {
        $champEnabled = ($opts['Champ'] ?? '0') !== '0';
        $cl = (int)($opts['CL'] ?? 0);
        $ck = (int)($opts['CK'] ?? 0);
        $uc = (int)($opts['UC'] ?? 0);
        $ar = (int)($opts['AR'] ?? 0);
        $ab = (int)($opts['AB'] ?? 0);
    
        $champColor = ($opts['ChampColor'] ?? '') !== '' ? $opts['ChampColor'] : '#22c55e';
        $clColor    = ($opts['CLColor']  ?? '') !== '' ? $opts['CLColor']  : '#3b82f6';
        $ckColor    = ($opts['CKColor']  ?? '') !== '' ? $opts['CKColor']  : '#0ea5e9';
        $ucColor    = ($opts['UCColor']  ?? '') !== '' ? $opts['UCColor']  : '#f59e0b';
        $arColor    = ($opts['ARColor']  ?? '') !== '' ? $opts['ARColor']  : '#f97316';
        $abColor    = ($opts['ABColor']  ?? '') !== '' ? $opts['ABColor']  : '#ef4444';
    
        if ($champEnabled && $index === 0) {
            return $champColor;
        }
        if ($index < $cl) {
            return $clColor;
        }
        if ($index < $cl + $ck) {
            return $ckColor;
        }
        if ($index < $cl + $ck + $uc) {
            return $ucColor;
        }
    
        $fromBottom = $totalTeams - 1 - $index; // 0 = letzter Platz
        if ($fromBottom < $ab) {
            return $abColor;
        }
        if ($fromBottom < $ab + $ar) {
            return $arColor;
        }
    
        return '';
    }
}
