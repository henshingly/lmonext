<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/StandingsTrait.php
 * Fileversion: 1.0.0
 * Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in
 *                     fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen
 *                     Kontext der Umstellung). Tabellenberechnung (computeStandings, computeStandingsMarkerColor, renderStandingsView).
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
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
    public static function computeStandings(array $teamsList, array $partien, array $ligaOptions) : array
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
                'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0,
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
                $rows[$hId] = ['id' => $hId, 'name' => $p['heim_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0];
            }
            if (!isset($rows[$gId])) {
                $rows[$gId] = ['id' => $gId, 'name' => $p['gast_name'] ?? '', 'kurz' => '', 'sp' => 0, 's' => 0, 'u' => 0, 'n' => 0, 'tore_h' => 0, 'tore_g' => 0, 'pkt' => 0];
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
                $rows[$gId]['n']++;
                $rows[$gId]['pkt'] += $curL;
            } elseif ($ht < $gt) {
                $rows[$gId]['s']++;
                $rows[$gId]['pkt'] += $curW;
                $rows[$hId]['n']++;
                $rows[$hId]['pkt'] += $curL;
            } else {
                $rows[$hId]['u']++;
                $rows[$hId]['pkt'] += $curD;
                $rows[$gId]['u']++;
                $rows[$gId]['pkt'] += $curD;
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
