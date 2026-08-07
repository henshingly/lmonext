<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/Eternal/EternalTableService.php
 * Fileversion: 1.1.0
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Ewige Tabelle + Mehrjahres-Vergleich (Teamvergleich über mehrere Jahre).
 *
 * Grundlage sind die vorhandenen Liga-Daten (lmonext_liga_partien /
 * lmonext_teams_global). Die eigentliche Tabellenberechnung je Liga
 * läuft über LigaService::computeStandings(), damit Punktewerte
 * (3/1/0, n.V., i.E.) und Status genau wie im normalen Ligabetrieb
 * behandelt werden. Dieser Service summiert nur die bereits berechneten
 * Liga-Zeilen über mehrere Ligen hinweg auf bzw. stellt sie pro Saison
 * als Matrix bereit.
 *
 * Eingebaut als eigenständige PSR-4-Klasse – bestehende Dateien bleiben
 * unangetastet.
 */
declare(strict_types=1);

namespace LMOnext\Liga\Eternal;

use LMOnext\Liga\LigaService;

final class EternalTableService
{
    public const POINTS_REAL = 0;     // historische Saisonwertung
    public const POINTS_3    = 1;     // alles 3/1/0
    public const POINTS_2    = 2;     // alles 2/1/0
    /**
     * Alle regulären Ligen (keine KO-Turniere), alphabetisch – Auswahl
     * für das Formular. Eine Liga gilt als regulär, wenn in liga_options
     * kein Type=1 gesetzt ist (alte Ligen ohne Type-Eintrag sind Liga=0).
     */
    public function allLeagues(): array
    {
        $db = getDB();
        $sql = 'SELECT l.id, l.name
                  FROM ' . tbl('liga') . ' l
                 WHERE NOT EXISTS (
                       SELECT 1 FROM ' . tbl('liga_options') . ' o
                        WHERE o.liga_id = l.id
                          AND o.option_key = \'Type\'
                          AND o.option_value = \'1\'
                 )
                 ORDER BY l.name';
        try {
            $rows = $db->query($sql)->fetchAll();
        } catch (\Throwable) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => (int)$r['id'], 'name' => $r['name']];
        }
        return $out;
    }

    /**
     * Teams einer Liga (id/name/kurz) – Startmenge für computeStandings,
     * damit auch Teams ohne gespielte Partie als Null-Zeile erscheinen.
     */
    private function leagueTeams(int $ligaId): array
    {
        $db = getDB();
        $sql = 'SELECT g.id, g.name, g.kurz
                  FROM ' . tbl('teams_global') . ' g
                  JOIN ' . tbl('liga_teams') . ' lt ON lt.team_id = g.id
                 WHERE lt.liga_id = ?
                 ORDER BY g.name';
        try {
            $s = $db->prepare($sql);
            $s->execute([$ligaId]);
            return $s->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Alle Partien einer Liga inkl. Teamnamen – Rohdaten für
     * computeStandings (heim_id/gast_id/h_tore/g_tore/status/heim_name/gast_name).
     */
    private function leagueMatches(int $ligaId): array
    {
        $db = getDB();
        $sql = 'SELECT p.heim_id, p.gast_id, p.h_tore, p.g_tore, p.status,
                       gh.name AS heim_name, gg.name AS gast_name
                  FROM ' . tbl('liga_partien') . ' p
                  JOIN ' . tbl('liga_spieltage') . ' s ON s.id = p.spieltag_id
             LEFT JOIN ' . tbl('teams_global') . ' gh ON gh.id = p.heim_id
             LEFT JOIN ' . tbl('teams_global') . ' gg ON gg.id = p.gast_id
                 WHERE s.liga_id = ?';
        try {
            $s = $db->prepare($sql);
            $s->execute([$ligaId]);
            return $s->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Berechnet die Liga-Tabelle für eine einzelne Liga (Saison) über
     * die vorhandene LigaService-Logik. Gibt die sortierten Zeilen mit
     * Rang (1-basiert) zurück.
     */
    public function leagueStandings(int $ligaId): array
    {
        $teams = $this->leagueTeams($ligaId);
        if (empty($teams)) {
            return [];
        }

        $matches = $this->leagueMatches($ligaId);
        $opts    = LigaService::getLigaOptions($ligaId);

        $rows = LigaService::computeStandings($teams, $matches, $opts, $ligaId);

        foreach ($rows as $i => &$r) {
            $r['rang'] = $i + 1;
            $r['diff'] = (int)$r['tore_h'] - (int)$r['tore_g'];
        }
        unset($r);

        return $rows;
    }
    /**
     * Ewige Tabelle: summiert Sp/S/U/N/Tore/Punkte je globalem Team
     * über alle ausgewählten Ligen. Sortierung wie bei computeStandings
     * (Punkte → Tordifferenz → geschossene Tore → Name).
     *
     * @param int[] $ligaIds
     */
    public function eternalStandings(array $ligaIds): array
    {
        $agg = [];

        foreach ($ligaIds as $lid) {
            // historische Tabelle der Saison
            foreach ($this->leagueStandings((int)$lid) as $r) {

                $id = (int)$r['id'];

                if (!isset($agg[$id])) {
                    $agg[$id] = [
                        'id'      => $id,
                        'name'    => $r['name'],
                        'kurz'    => $r['kurz'] ?? '',
                        'saisons' => 0,
                        'sp'      => 0,
                        's'       => 0,
                        'u'       => 0,
                        'n'       => 0,
                        'tore_h'  => 0,
                        'tore_g'  => 0,
                        // drei Punktesysteme
                        'pkt'     => 0, // historisch
                        'pkt2'    => 0, // immer 2 Punkte
                        'pkt3'    => 0, // immer 3 Punkte
                        'mpkt2'    => 0,   // Minuspunkte (2-Punkte-System)
                        'mpkt3'    => 0,   // Minuspunkte (3-Punkte-System)
                        // Strafen über Saisons aufsummiert (Beitrag: Torsten
                        // Hofmann) - "pkt"/"tore_h"/"tore_g" oben enthalten die
                        // Korrekturen bereits (computeStandings() wendet sie VOR
                        // der Aggregation an), diese Felder dienen nur der
                        // Anzeige/Fußnote (siehe ewigeStrafHinweis() im
                        // Ewige-Tabelle-Addon)
                        'strafpunkte'     => 0,
                        'straftore'       => 0,
                        'torekorrektur'   => 0,
                        'minuspunktekorrektur' => 0,
                        'strafgruende'    => [],  // ['Liganame: Grund', ...]
                    ];
                }

                $agg[$id]['saisons']++;
                $agg[$id]['sp']     += (int)$r['sp'];
                $agg[$id]['s']      += (int)$r['s'];
                $agg[$id]['u']      += (int)$r['u'];
                $agg[$id]['n']      += (int)$r['n'];
                $agg[$id]['tore_h'] += (int)$r['tore_h'];
                $agg[$id]['tore_g'] += (int)$r['tore_g'];
                // Historische Punkte (wie berechnet)
                $agg[$id]['pkt'] += (int)$r['pkt'];
                // Immer 2 Punkte
                $agg[$id]['pkt2'] += $r['s'] * 2 + $r['u'];
                $agg[$id]['mpkt2'] += $r['n'] * 2 + $r['u'];
                // Immer 3 Punkte
                $agg[$id]['pkt3'] += $r['s'] * 3 + $r['u'];
                $agg[$id]['mpkt3'] += $r['n'] * 3 + $r['u'];

                // Strafen aufsummieren (Beitrag: Torsten Hofmann)
                $agg[$id]['strafpunkte']          += (int)($r['strafpunkte'] ?? 0);
                $agg[$id]['straftore']            += (int)($r['straftore'] ?? 0);
                $agg[$id]['torekorrektur']        += (int)($r['torekorrektur'] ?? 0);
                $agg[$id]['minuspunktekorrektur'] += (int)($r['minuspunktekorrektur'] ?? 0);
                $grund = trim((string)($r['strafgrund'] ?? ''));
                if ($grund !== '') {
                    $ligaName = LigaService::getLigaById((int)$lid)['name'] ?? ('Liga ' . $lid);
                    $agg[$id]['strafgruende'][] = $ligaName . ': ' . $grund;
                }
            }
        }

        $rows = array_values($agg);

        // Standardsortierung = historische Punkte
        usort($rows, static function (array $a, array $b): int {

            if ($a['pkt'] !== $b['pkt']) {
                return $b['pkt'] <=> $a['pkt'];
            }

            $da = $a['tore_h'] - $a['tore_g'];
            $db = $b['tore_h'] - $b['tore_g'];

            if ($da !== $db) {
                return $db <=> $da;
            }

            if ($a['tore_h'] !== $b['tore_h']) {
                return $b['tore_h'] <=> $a['tore_h'];
            }

            return strcmp($a['name'], $b['name']);
        });

        foreach ($rows as $i => &$r) {
            $r['rang'] = $i + 1;
            $r['diff'] = $r['tore_h'] - $r['tore_g'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Mehrjahres-Vergleich: pro Liga (Saison) Rang + Punkte je Team.
     * Liefert ein assoziatives Array
     *   ['seasons' => [ligaId => ligaName], 'teams' => [teamId => name], 'matrix' => [teamId => [ligaId => ['rang'=>..,'pkt'=>..]]]]
     *
     * @param int[] $ligaIds  Reihenfolge = Spaltenreihenfolge (chronologisch)
     */
    public function seasonMatrix(array $ligaIds): array
    {
        $seasons = [];
        $teams   = [];
        $matrix  = [];

        foreach ($ligaIds as $lid) {
            $lid = (int)$lid;
            $info = LigaService::getLigaById($lid);
            $seasons[$lid] = $info['name'] ?? ('Liga ' . $lid);
            foreach ($this->leagueStandings($lid) as $r) {
                $tid = (int)$r['id'];
                $teams[$tid] = $r['name'];
                $matrix[$tid][$lid] = ['rang' => (int)$r['rang'], 'pkt' => (int)$r['pkt']];
            }
        }

        // Teams sortiert nach Name
        asort($teams);
        return ['seasons' => $seasons, 'teams' => $teams, 'matrix' => $matrix];
    }
}
