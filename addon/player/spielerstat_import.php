<?php
/**
 * Project: LMOnext
 * Filename: addon/player/spielerstat_import.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */

// findFuzzyTeamMatches()/teamNormalizeName() etc. stammen aus dem .l98-Import
// (handler_import_export.php). Explizit eingebunden statt sich auf die
// Ladereihenfolge in admin.php zu verlassen.
require_once dirname(__DIR__, 2) . '/admin/handler_import_export.php';

const SPIELERSTAT_FORMEL_MARKER = '*_*-*';

/** Ermittelt das in der ersten Zeile am häufigsten vorkommende der drei bekannten Trennzeichen. */
function detectSpielerstatDelimiter(string $content) : string
{
    $firstLine = strtok($content, "\n") ?: $content;
    $candidates = ['#', '|', '§'];
    $best = '#'; $bestCount = -1;
    foreach ($candidates as $d) {
        $count = substr_count($firstLine, $d);
        if ($count > $bestCount) { $bestCount = $count; $best = $d; }
    }
    return $best;
}

/**
 * Parst den Inhalt einer alten .stat-Datei (beliebiges der drei bekannten
 * Trennzeichen). Gibt die Rohstruktur zurück:
 *   spalten: Liste ['name'=>string,'formel'=>bool]
 *   formeln: Spalten-Index => Formel-String (nur falls formel=true)
 *   spieler: Liste von Zeilen (Spalten-Index => Wert)
 */
function parseOldSpielerstatFile(string $content) : array
{
    $delim = detectSpielerstatDelimiter($content);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $lines = array_values(array_filter($lines, static fn($l) => $l !== ''));
    if (empty($lines)) {
        return ['spalten' => [], 'formeln' => [], 'spieler' => []];
    }

    $headerRaw = explode($delim, array_shift($lines));
    $spalten = [];
    $hasFormula = false;
    foreach ($headerRaw as $i => $name) {
        $isFormula = str_ends_with($name, SPIELERSTAT_FORMEL_MARKER);
        if ($isFormula) {
            $name = substr($name, 0, -strlen(SPIELERSTAT_FORMEL_MARKER));
            $hasFormula = true;
        }
        $spalten[$i] = ['name' => trim($name), 'formel' => $isFormula];
    }

    $formeln = [];
    if ($hasFormula && !empty($lines)) {
        $formelRaw = explode($delim, array_shift($lines));
        foreach ($formelRaw as $i => $val) {
            if (!empty($spalten[$i]['formel']) && $val !== '0' && trim($val) !== '') {
                $formeln[$i] = trim($val);
            }
        }
    }

    $spieler = [];
    foreach ($lines as $line) {
        $row = explode($delim, $line);
        $spieler[] = $row;
    }

    return ['spalten' => $spalten, 'formeln' => $formeln, 'spieler' => $spieler];
}

/**
 * Parst den Inhalt einer alten .cfg-Datei (key=value, ein Eintrag pro Zeile,
 * deutsche Klartext-Keys wie im Original). Nur die für uns relevanten Werte
 * werden übernommen (Sortierung/Anzeige/Linkbezeichnung) – die alten
 * Hilfsadmin-Felder entfallen, da LMOnext aktuell keine Admin-Rollenstufen kennt.
 */
function parseOldSpielerstatConfig(string $content) : array
{
    $map = [
        'Vorsortierung User'      => 'sort_column',
        'Sortierung'              => 'sort_direction',
        'Vorsortierung Admin'     => 'admin_sort_column',
        'Anzeige pro Seite'       => 'per_page',
        'Nullwerte einblenden'    => 'show_zero',
        'Extra Sortierspalte'     => 'show_extra_sort_column',
        'Vereinsweise anzeigen'   => 'show_per_club',
        'Linkbezeichnung'         => 'link_label',
    ];
    $cfg = [];
    foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
        if ($line === '' || !str_contains($line, '=')) { continue; }
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        if (isset($map[$key])) {
            $cfg[$map[$key]] = $map[$key] === 'link_label' ? trim($val) : (int)$val;
        }
    }
    return $cfg;
}

/**
 * Baut aus der Rohstruktur (parseOldSpielerstatFile()) + optionalen
 * Team-Overrides (aus dem Review-Schritt, spielerId(Zeilenindex) => team_id)
 * die fertigen DB-Zeilen und schreibt sie für die angegebene Liga. Vorhandene
 * Spielerstatistik-Daten dieser Liga werden vorher gelöscht (Import ersetzt,
 * fügt nicht zusammen).
 *
 * @return array{ok:bool, msg:string}
 */
function importOldSpielerstatIntoDB(int $ligaId, array $parsed, array $cfg, array $teamOverridesByRow = []) : array
{
    ensureSpielerstatSchema();
    ensureSpielerGlobalSchema();
    $db = getDB();
    try {
        $db->beginTransaction();

        // Alte Daten dieser Liga entfernen (Ersatz-Import)
        $oldIds = $db->prepare('SELECT id FROM '.tbl('spielerstat_spieler').' WHERE liga_id=?');
        $oldIds->execute([$ligaId]);
        $ids = $oldIds->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare('DELETE FROM '.tbl('spielerstat_werte').' WHERE spieler_id IN ('.$in.')')->execute($ids);
            $db->prepare('DELETE FROM '.tbl('spielerstat_spieler').' WHERE liga_id=?')->execute([$ligaId]);
        }
        $db->prepare('DELETE FROM '.tbl('spielerstat_spalten').' WHERE liga_id=?')->execute([$ligaId]);

        // Spalten anlegen. "Verein"/"Spielerlink" werden anhand des Original-
        // Spaltennamens (Klartext, wie in der Quelldatei) einer festen Rolle
        // zugeordnet – abweichend benannte Spalten kann der Admin danach in
        // der Verwaltung manuell umstellen.
        $spaltenIds = [];
        foreach ($parsed['spalten'] as $i => $sp) {
            $rolle = 'normal';
            if (strcasecmp($sp['name'], 'Verein') === 0) { $rolle = 'verein'; $vereinIdx = $i; }
            if (strcasecmp($sp['name'], 'Spielerlink') === 0) { $rolle = 'spielerlink'; }
            $typ = $sp['formel'] ? 'formel' : 'zahl'; // vorläufig, wird unten anhand der Daten korrigiert
            $formel = $parsed['formeln'][$i] ?? null;
            $ins = $db->prepare('INSERT INTO '.tbl('spielerstat_spalten').' (liga_id,name,typ,formel,rolle,position) VALUES (?,?,?,?,?,?)');
            $ins->execute([$ligaId, $sp['name'], $typ, $formel, $rolle, $i]);
            $spaltenIds[$i] = (int)$db->lastInsertId();
        }

        // Spieler + Werte einfügen
        $nameColIdx = 0; // erste Spalte ist per Konvention immer der Name
        $insP = $db->prepare('INSERT INTO '.tbl('spielerstat_spieler').' (liga_id,team_id,global_player_id,position) VALUES (?,?,?,?)');
        $insW = $db->prepare('INSERT INTO '.tbl('spielerstat_werte').' (spieler_id,spalten_id,wert) VALUES (?,?,?)');
        $anyNonNumeric = [];
        foreach ($parsed['spieler'] as $rowIdx => $row) {
            $teamId = $teamOverridesByRow[$rowIdx] ?? null;
            $globalPlayerId = findOrCreateGlobalPlayer(trim($row[$nameColIdx] ?? ''));
            $insP->execute([$ligaId, $teamId, $globalPlayerId, $rowIdx]);
            $playerId = (int)$db->lastInsertId();
            foreach ($row as $i => $val) {
                if (!isset($spaltenIds[$i])) { continue; }
                $insW->execute([$playerId, $spaltenIds[$i], $val]);
                if ($val !== '' && !is_numeric($val)) { $anyNonNumeric[$i] = true; }
            }
        }

        // Spaltentyp nachträglich korrigieren: enthält eine Nicht-Formel-Spalte
        // irgendeinen nicht-numerischen Wert, ist es Typ "text" (gleiche
        // Erkennung wie im Original: is_numeric() über alle Werte der Spalte).
        foreach ($parsed['spalten'] as $i => $sp) {
            if ($sp['formel']) { continue; }
            if (!empty($anyNonNumeric[$i])) {
                $db->prepare('UPDATE '.tbl('spielerstat_spalten').' SET typ=\'text\' WHERE id=?')->execute([$spaltenIds[$i]]);
            }
        }

        saveSpielerstatConfig($ligaId, $cfg);
        $db->commit();
        recalcSpielerstatFormulas($ligaId);
        return ['ok' => true, 'msg' => t('spst_import_success', ['n' => count($parsed['spieler'])])];
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        return ['ok' => false, 'msg' => t('flash_error_prefix', ['msg' => $e->getMessage()])];
    }
}

/**
 * Prüft die Vereinsspalte der geparsten Datei gegen teams_global (exakte und
 * ungefähre Treffer), analog zu detectFuzzyTeamMatchesForImport() für den
 * .l98-Ligaimport. Gibt drei Listen zurück: exakte Treffer (sofort
 * übernehmbar, team_id => Zeilenindizes), ungefähre Treffer (Review nötig)
 * und Namen ohne jede Ähnlichkeit (werden als neues Team angelegt).
 *
 * @return array{exact:array<int,int>, ambiguous:array<int,array>, none:array<int,string>}
 */
function detectSpielerstatTeamMatches(array $parsed) : array
{
    $vereinIdx = null;
    foreach ($parsed['spalten'] as $i => $sp) {
        if (strcasecmp($sp['name'], 'Verein') === 0) { $vereinIdx = $i; break; }
    }
    if ($vereinIdx === null) {
        return ['exact' => [], 'ambiguous' => [], 'none' => []];
    }

    try {
        $existing = getDB()->query('SELECT id,name,mittel,kurz FROM '.tbl('teams_global'))->fetchAll();
    } catch (Throwable) {
        return ['exact' => [], 'ambiguous' => [], 'none' => []];
    }
    $existingByName = [];
    foreach ($existing as $t) { $existingByName[$t['name']] = (int)$t['id']; }

    $exact = []; $ambiguous = []; $none = [];
    foreach ($parsed['spieler'] as $rowIdx => $row) {
        $name = trim($row[$vereinIdx] ?? '');
        if ($name === '') { continue; }
        if (isset($existingByName[$name])) {
            $exact[$rowIdx] = $existingByName[$name];
            continue;
        }
        $matches = findFuzzyTeamMatches($name, $existing);
        if (empty($matches)) {
            $none[$rowIdx] = $name;
            continue;
        }
        $candidates = [];
        foreach ($matches as $m) {
            $candidates[] = ['id' => (int)$m['id'], 'name' => $m['name'], 'kurz' => $m['kurz'], 'mittel' => $m['mittel']];
        }
        $ambiguous[$rowIdx] = ['importName' => $name, 'candidates' => $candidates];
    }
    return ['exact' => $exact, 'ambiguous' => $ambiguous, 'none' => $none];
}
