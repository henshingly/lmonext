<?php
/**
 * Project: LMOnext
 * Filename: addon/mini/lmo-minitab.php
 * Fileversion: 1.1.0
 * Changelog: 1.1.0 - Bugfix: <!--ligaDatum--> zeigte immer das heutige Tagesdatum statt des
 *                     tatsächlichen letzten Speicherdatums der Liga (im alten LMO das
 *                     Änderungsdatum der .l98-Datei). Liest jetzt liga.datum aus der DB, das
 *                     save_ergebnisse (siehe handler_liga.php 1.6.2) bei jeder
 *                     Ergebnis-Speicherung aktualisiert
 * Changelog: 1.0.0
 * Changelog: 1.0.0 - Initiale Version: Portierung des alten LMO-Addons "Minitabellen" (siehe
 *                     doc/help/addons/minitabellen.html + template/mini/standard.tpl.php im
 *                     alten LMO). Zeigt einen Ausschnitt der Tabelle einer Liga (z.B. "3 Plätze
 *                     über und 2 unter der Lieblingsmannschaft") zum Einbinden auf externen
 *                     Webseiten, entweder per include() oder direkt als URL/IFrame. Nutzt
 *                     bewusst dieselben Berechnungsfunktionen wie die normale Tabellenansicht
 *                     (computeStandings()/computeStandingsMarkerColor() aus data_liga.php),
 *                     damit Platzierungen und Randmarkierungen immer exakt übereinstimmen.
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * ── Einbindung ────────────────────────────────────────────────────────────────
 *
 * Variante 1 (empfohlen) – per include() aus eigenem PHP-Code:
 *
 *   <?php
 *   $mini_liga     = 3;        // Liga-ID (Pflicht)
 *   $mini_platz    = null;     // Zentrum des Ausschnitts; leer = Lieblingsmannschaft
 *   $mini_ueber    = 3;        // Plätze über dem Zentrum
 *   $mini_unter    = 2;        // Plätze unter dem Zentrum
 *   $mini_template = 'standard'; // Template aus /template/addon/mini/{name}.tpl.php
 *   include('/PfadZuLMOnext/addon/mini/lmo-minitab.php');
 *
 * Variante 2 – per IFrame/direkter URL (z.B. wenn kein PHP auf dem externen
 * Server verfügbar ist):
 *
 *   <iframe src="https://.../addon/mini/lmo-minitab.php?mini_liga=3&mini_ueber=3&mini_unter=2"
 *           frameborder="0" width="220" height="260" scrolling="no"></iframe>
 *
 * Steuerparameter (GET hat immer Vorrang vor vorher gesetzten PHP-Variablen):
 *   mini_liga      Liga-ID (Pflicht)
 *   mini_platz     Tabellenplatz, um den herum der Ausschnitt zentriert wird.
 *                  Leer/0 = um die Lieblingsmannschaft der Liga zentrieren.
 *   mini_ueber     Anzahl Plätze oberhalb des Zentrums (Standard: 2)
 *   mini_unter     Anzahl Plätze unterhalb des Zentrums (Standard: 2)
 *   mini_template  Name der Template-Datei ohne Endung (Standard: "standard"),
 *                  Datei muss unter /template/addon/mini/{name}.tpl.php liegen
 */
declare(strict_types=1);

// Wird diese Datei direkt aufgerufen (URL/IFrame) oder per include() aus
// einer anderen Datei eingebunden? Nur im direkten Fall wird ein
// vollständiges HTML-Grundgerüst drumherum gebaut.
$miniIsDirectCall = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'lmo-minitab.php';

require_once __DIR__ . '/../../frontend/bootstrap.php';

// ── Parameter einlesen (GET überschreibt vorher per include() gesetzte
// PHP-Variablen, die wiederum Vorrang vor den Standardwerten haben) ──────────
$m_liga     = isset($_GET['mini_liga']) ? (int)$_GET['mini_liga'] : (int)($mini_liga ?? 0);
$m_platz    = isset($_GET['mini_platz']) && $_GET['mini_platz'] !== ''
    ? (int)$_GET['mini_platz']
    : (isset($mini_platz) && $mini_platz !== null && $mini_platz !== '' ? (int)$mini_platz : null);
$m_ueber    = isset($_GET['mini_ueber']) ? max(0, (int)$_GET['mini_ueber']) : max(0, (int)($mini_ueber ?? 2));
$m_unter    = isset($_GET['mini_unter']) ? max(0, (int)$_GET['mini_unter']) : max(0, (int)($mini_unter ?? 2));
$m_template = isset($_GET['mini_template']) ? (string)$_GET['mini_template'] : (string)($mini_template ?? 'standard');
$m_template = str_replace('..', '', basename($m_template)); // Path-Traversal-Schutz

if ($miniIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\"><title>Minitabelle</title>"
        . "<style>html,body{margin:0;padding:0;background:transparent;}</style></head><body>\n";
}

/**
 * Baut den HTML-Code der Minitabelle. Eigene Funktion (statt alles inline),
 * damit sich der Rückgabewert bei Bedarf auch cachen/weiterverarbeiten ließe.
 */
function renderMinitabelle(int $ligaId, ?int $platz, int $ueber, int $unter, string $templateName) : string
{
    try {
        $liga = getDB()->prepare('SELECT id, name, datum FROM ' . tbl('liga') . ' WHERE id = ?');
        $liga->execute([$ligaId]);
        $ligaRow = $liga->fetch();
    } catch (Throwable) {
        $ligaRow = null;
    }
    if ($ligaRow === null) {
        return '<p style="font-family:sans-serif;color:#a33">Liga nicht gefunden (mini_liga=' . h($ligaId) . ')</p>';
    }

    $opts        = getLigaOptions($ligaId);
    $teamsList   = getLigaTeamsList($ligaId);
    $allSpieltage = getAllSpieltage($ligaId);
    $partien     = getAllLigaPartien($allSpieltage);
    $rows        = computeStandings($teamsList, $partien, $opts);
    $totalTeams  = count($rows);
    if ($totalTeams === 0) {
        return '<p style="font-family:sans-serif;color:#a33">Keine Teams in dieser Liga</p>';
    }

    $mittelById = [];
    foreach ($teamsList as $t) {
        $mittelById[(int)$t['id']] = $t['mittel'] ?? '';
    }
    $favTeamId = resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));

    // ── Zentrum des Ausschnitts bestimmen: expliziter Platz > Lieblingsteam > Platz 1 ──
    $viewPosition = 1;
    if ($platz !== null && $platz > 0 && $platz <= $totalTeams) {
        $viewPosition = $platz;
    } elseif ($favTeamId !== null) {
        foreach ($rows as $i => $r) {
            if ($r['id'] === $favTeamId) { $viewPosition = $i + 1; break; }
        }
    }

    // ── Anzeigefenster (gleiche Verschiebe-Logik wie im alten LMO: reicht das
    // Fenster über den Rand hinaus, wird es soweit möglich verschoben, statt
    // einfach abgeschnitten zu werden, damit man wenn möglich immer die volle
    // angeforderte Anzahl Zeilen sieht) ─────────────────────────────────────
    $begin = $viewPosition - $unter;
    $end   = $viewPosition + $ueber;
    if ($begin <= 0) {
        $begin = 1;
        $end   = min($totalTeams, $unter + 1 + $ueber);
    }
    if ($end > $totalTeams) {
        $end   = $totalTeams;
        $begin = max(1, $totalTeams - $unter - $ueber);
    }

    // ── Template laden ───────────────────────────────────────────────────────
    $templatePath = __DIR__ . '/../../template/addon/mini/' . $templateName . '.tpl.php';
    if (!is_file($templatePath)) {
        $templatePath = __DIR__ . '/../../template/addon/mini/standard.tpl.php';
    }
    $templateSrc = (string)file_get_contents($templatePath);

    // Wiederholten Zeilen-Block ("<!-- BEGIN Inhalt -->...<!-- END Inhalt -->",
    // Leerzeichen um "Inhalt" beachten, wie im alten LMO) aus dem Template
    // herausschneiden.
    if (!preg_match('/<!-- BEGIN Inhalt -->(.*?)<!-- END Inhalt -->/s', $templateSrc, $m)) {
        return '<p style="font-family:sans-serif;color:#a33">Ungültiges Template (kein Inhalt-Block gefunden)</p>';
    }
    $rowTemplate = $m[1];
    $before      = substr($templateSrc, 0, strpos($templateSrc, $m[0]));
    $after       = substr($templateSrc, strpos($templateSrc, $m[0]) + strlen($m[0]));

    $rowsHtml = '';
    foreach ($rows as $i => $r) {
        $platzNr = $i + 1;
        if ($platzNr < $begin || $platzNr > $end) {
            continue;
        }
        $diff = $r['tore_h'] - $r['tore_g'];
        $diffText = ($diff > 0 ? '+' : '') . $diff;
        $markerColor = computeStandingsMarkerColor($i, $totalTeams, $opts);
        $isFav = $favTeamId !== null && $r['id'] === $favTeamId;
        $style = $markerColor !== '' ? 'border-left:4px solid ' . $markerColor . ';' : '';
        if ($isFav) {
            $style .= 'font-weight:bold;';
        }

        $replacements = [
            '<!--Platz-->'        => (string)$platzNr,
            '<!--Team-->'         => h($r['kurz'] !== '' ? $r['kurz'] : $r['name']),
            '<!--TeamLang-->'     => h($r['name']),
            '<!--TeamMittel-->'   => h($mittelById[$r['id']] ?? ''),
            '<!--Spiele-->'       => (string)$r['sp'],
            '<!--Punkte-->'       => (string)$r['pkt'],
            '<!--PlusTore-->'     => (string)$r['tore_h'],
            '<!--MinusTore-->'    => (string)$r['tore_g'],
            '<!--Tordifferenz-->' => h($diffText),
            '<!--Siege-->'        => (string)$r['s'],
            '<!--Unentschieden-->'=> (string)$r['u'],
            '<!--Niederlagen-->'  => (string)$r['n'],
            '<!--Style-->'        => h($style),
            '<!--Class-->'        => $isFav ? 'mini-fav' : '',
        ];
        $rowsHtml .= strtr($rowTemplate, $replacements);
    }

    $outer = [
        '<!--Link-->'     => h('liga.php?id=' . $ligaId . '&view=tabelle'),
        '<!--Tabelle-->'  => h($ligaRow['name']),
        '<!--ligaDatum-->'=> h(tf('liga_stand_datum', ['datum' => ($ligaTs = strtotime((string)($ligaRow['datum'] ?? ''))) !== false ? date('d.m.Y', $ligaTs) : date('d.m.Y')])),
    ];
    $before = strtr($before, $outer);
    $after  = strtr($after, $outer);

    return $before . $rowsHtml . $after;
}

if ($m_liga <= 0) {
    echo '<p style="font-family:sans-serif;color:#a33">Fehlender oder ungültiger Parameter "mini_liga"</p>';
} else {
    echo renderMinitabelle($m_liga, $m_platz, $m_ueber, $m_unter, $m_template);
}

if ($miniIsDirectCall) {
    echo "\n</body></html>\n";
}
