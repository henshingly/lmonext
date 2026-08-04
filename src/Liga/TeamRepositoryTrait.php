<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/TeamRepositoryTrait.php
 * Fileversion: 1.0.0
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
trait TeamRepositoryTrait
{
    /**
     * "Team-Nummer" (Position in der nach Name sortierten Teamliste dieser Liga)
     * zu einer Team-ID auflösen - z.B. für favTeam/selTeam. Cacht die sortierte
     * ID-Liste pro Liga, da diese Funktion innerhalb eines Requests oft mehrfach
     * für dieselbe Liga aufgerufen wird (favTeam, selTeam usw.).
     */
    public static function resolveTeamNumberToId(int $ligaId, int $number) : ?int
    {
        if ($number <= 0) {
            return null;
        }
        static $cache = [];
        if (!array_key_exists($ligaId, $cache)) {
            try {
                $s = getDB()->prepare(
                    'SELECT g.id
                       FROM ' . tbl('teams_global') . ' g
                       JOIN ' . tbl('liga_teams') . ' lt ON lt.team_id = g.id
                      WHERE lt.liga_id = ?
                      ORDER BY g.name'
                );
                $s->execute([$ligaId]);
                $cache[$ligaId] = $s->fetchAll(\PDO::FETCH_COLUMN);
            } catch (\Throwable) {
                $cache[$ligaId] = [];
            }
        }
        $ids = $cache[$ligaId];
        return isset($ids[$number - 1]) ? (int)$ids[$number - 1] : null;
    }
    /**
     * Ermittelt die Team-ID des Dummy-Platzhalter-Teams ("___"), falls vorhanden.
     * Wird beim Umsortieren des Turnierbaums ausgeschlossen, damit nicht mehrere
     * unabhängige Platzhalter-Paarungen fälschlich als "dieselbe" Zuführung
     * erkannt werden (der Dummy-Team-Datensatz wird für alle Platzhalter geteilt).
     */
    public static function getDummyTeamId() : int
    {
        static $id = null;
        if ($id !== null) {
            return $id;
        }
        try {
            $s = getDB()->prepare('SELECT id FROM ' . tbl('teams_global') . ' WHERE name=? LIMIT 1');
            $s->execute(['___']);
            $v = $s->fetchColumn();
            return $id = ($v !== false ? (int)$v : 0);
        } catch (\Throwable) {
            return $id = 0;
        }
    }
    /**
     * Wird pro Seitenaufruf oft mehrfach für dieselbe Liga aufgerufen (Tabelle,
     * Kreuztabelle, Spielplan-Sidebar, PDF-Export, Mini-Addons usw.) - Speicher-
     * Cache pro Liga-ID, damit die (teils zweistufige, siehe Fallback unten)
     * Abfrage innerhalb eines Requests nur einmal läuft.
     */
    public static function getLigaTeamsList(int $ligaId) : array
    {
        static $cache = [];
        if (array_key_exists($ligaId, $cache)) {
            return $cache[$ligaId];
        }
        return $cache[$ligaId] = self::getLigaTeamsListUncached($ligaId);
    }
    public static function getLigaTeamsListUncached(int $ligaId) : array
    {
        try {
            $s = getDB()->prepare(
                'SELECT tg.id, tg.name, tg.kurz, tg.mittel
                   FROM ' . tbl('liga_teams') . ' lt
                   JOIN ' . tbl('teams_global') . ' tg ON tg.id = lt.team_id
                  WHERE lt.liga_id = ?
                  ORDER BY lt.id'
            );
            $s->execute([$ligaId]);
            $rows = $s->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable) {
            // fällt durch zum Fallback unten
        }
    
        // Fallback: liga_teams ist (noch) leer – z.B. bei älteren importierten
        // Ligen, wo diese Zuordnungstabelle nie befüllt wurde. Teams stattdessen
        // direkt aus den vorhandenen Partien ableiten (Dummy-Team "___" ausschließen).
        try {
            $dummyId = self::getDummyTeamId();
            $s = getDB()->prepare(
                'SELECT DISTINCT tg.id, tg.name, tg.kurz, tg.mittel
                   FROM ' . tbl('liga_partien') . ' p
                   JOIN ' . tbl('liga_spieltage') . ' st ON st.id = p.spieltag_id
                   JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.heim_id
                  WHERE st.liga_id = ? AND tg.id <> ?
                  UNION
                 SELECT DISTINCT tg.id, tg.name, tg.kurz, tg.mittel
                   FROM ' . tbl('liga_partien') . ' p
                   JOIN ' . tbl('liga_spieltage') . ' st ON st.id = p.spieltag_id
                   JOIN ' . tbl('teams_global') . ' tg ON tg.id = p.gast_id
                  WHERE st.liga_id = ? AND tg.id <> ?
                  ORDER BY name'
            );
            $s->execute([$ligaId, $dummyId, $ligaId, $dummyId]);
            return $s->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }
    /**
     * Ermittelt für einen Spieltag alle Teams der Liga, die an diesem Spieltag
     * KEINE Partie haben ("Spielfrei"). Kommt typischerweise bei ungerader
     * Teamzahl vor, kann aber auch bei gerader Teamzahl auftreten (z.B. wenn ein
     * Team im Spielplan schlicht nicht eingeteilt wurde). Ermittlung durch
     * Abwesenheit, genau wie im alten LMO: es gibt keinen expliziten
     * "Spielfrei"-Eintrag im Datenmodell, das betroffene Team taucht einfach in
     * keiner Paarung des Spieltags auf.
     *
     * @return array<int,array> Liste der betroffenen Teams (id,name,kurz,mittel)
     */
    public static function findSpielfreiTeams(int $ligaId, array $partien) : array
    {
        $scheduledIds = [];
        foreach ($partien as $p) {
            if (self::partieIsEmptyPlaceholder($p)) {
                continue; // "kein Spielplan"-Platzhalterpaarung zählt nicht als Termin
            }
            if ((int)($p['heim_id'] ?? 0) > 0) {
                $scheduledIds[(int)$p['heim_id']] = true;
            }
            if ((int)($p['gast_id'] ?? 0) > 0) {
                $scheduledIds[(int)$p['gast_id']] = true;
            }
        }
    
        $spielfrei = [];
        foreach (self::getLigaTeamsList($ligaId) as $team) {
            $tid  = (int)$team['id'];
            $name = trim((string)($team['name'] ?? ''));
            if (!isset($scheduledIds[$tid]) && $name !== '' && $name !== '___') {
                $spielfrei[] = $team;
            }
        }
        return $spielfrei;
    }
}
