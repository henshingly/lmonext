<?php
/**
 * Project: LMOnext
 * Filename: addon/mini/lmo-ewigetab.php
 * Fileversion: 1.0.1
 * Changelog: 1.0.1 - Review-Korrekturen (Dietmar Kersting): (1) fehlender
 *                     require_once für src/Liga/Eternal/EternalTableService.php ergänzt (Klasse
 *                     war nicht auffindbar, jeder Aufruf endete in einem Fatal Error); (2)
 *                     Standard-Sortierung von 'pkt3' auf 'pkt' (historische Original-Punkte)
 *                     geändert - entspricht dem traditionellen "Ewige Tabelle"-Ansatz; (3) die im
 *                     Docblock dokumentierte PHP-Variable $wertung für den include()-Modus wurde
 *                     vom Code nie gelesen (nur $_GET) - jetzt konsistent mit den anderen drei
 *                     Parametern ($_REQUEST, dann vorher gesetzte Variable, dann Standard); (4)
 *                     $wertung-Dokumentationsbeispiel im Docblock korrigiert (fehlende
 *                     Anführungszeichen) und als Steuerparameter ergänzt (fehlte komplett)
 * Changelog: 1.0.0 - Initiale Version: Ewige Tabelle (aufsummierte Stände über
 *                     mehrere Ligen) + Mehrjahres-Vergleich (Rang/Punkte je
 *                     Saison) als Addon.
 *                     Nutzt dieselbe Mechanik (Direktaufruf vs. include(),
 *                     ProjektRoot-URL-Präfix, Template-System mit
 *                     "<!-- BEGIN ... -->"-Blöcken). Die eigentliche Optik
 *                     steckt in template/addon/ewige/{standard,matrix}.tpl.php
 *                     Berechnung über LMOnext\Liga\Eternal\EternalTableService,
 *                     der wiederum LigaService::computeStandings() je Liga
 *                     aufruft – gleiche Punktwerte wie im normalen Ligabetrieb.
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <webmaster@liga-manager-online.org>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── Einbindung ────────────────────────────────────────────────────────────────
 *
 * Variante 1 (empfohlen) – per include() aus eigenem PHP-Code:
 *
 *   <?php
 *   $ewige_ligas    = '3,5,7';     // Liga-IDs (Pflicht), Komma oder Array
 *   $ewige_view     = 'eternal';  // 'eternal' (Summe) oder 'verlauf' (Matrix)
 *   $ewige_template = 'standard'; // optional, Standard je nach view
 *   $wertung        = 'pkt2';    // optional, Standard 'pkt' (historische Punkte)
 *   include('/PfadZuLMOnext/addon/mini/lmo-ewigetab.php');
 *
 * Variante 2 – per IFrame/direkter URL:
 *
 *   <iframe src="https://.../addon/mini/lmo-ewigetab.php?ewige_ligas=3,5,7&ewige_view=eternal"
 *           frameborder="0" width="860" height="600" scrolling="auto"></iframe>
 *
 * Steuerparameter (GET hat immer Vorrang vor vorher gesetzten PHP-Variablen):
 *   ewige_ligas    Liga-IDs, Komma-getrennt (z.B. "3,5,7") oder Array. Pflicht.
 *   ewige_view     'eternal' = ewige Tabelle (Summe), 'verlauf' = Matrix pro
 *                  Saison. Standard: 'eternal'.
 *   ewige_template Template-Name ohne Endung (Standard je nach view:
 *                  eternal -> 'standard', verlauf -> 'matrix'), Datei muss
 *                  unter /template/addon/ewige/{name}.tpl.php liegen.
 *   wertung        Nur für ewige_view='eternal': 'pkt' (historische Original-
 *                  Punkte, Standard), 'pkt2' (immer 2-Punkte-System) oder
 *                  'pkt3' (immer 3-Punkte-System) - bestimmt Sortierung/Rang.
 */
declare(strict_types=1);

// Wird diese Datei direkt aufgerufen (URL/IFrame) oder per include() aus
// einer anderen Datei eingebunden? Nur im direkten Fall wird ein
// vollständiges HTML-Grundgerüst drumherum gebaut.
$ewigeIsDirectCall = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'lmo-ewigetab.php';

require_once __DIR__ . '/../../frontend/bootstrap.php';
require_once __DIR__ . '/../../frontend/data_liga.php';
require_once __DIR__ . '/../../src/Liga/Eternal/EternalTableService.php';

use LMOnext\Liga\Eternal\EternalTableService;

$service = new EternalTableService();
$leagues = $service->allLeagues();
/**
 * Berechnet das URL-Präfix zum Projekt-Root, egal ob diese Datei direkt
 * aufgerufen ODER per include() aus einer beliebig platzierten Wrapper-Datei
 * eingebunden wird. Siehe miniProjectRootUrlPrefix() in lmo-minitab.php –
 * gleiche Logik, eigener Funktionsname, damit beide Addons parallel
 * eingebunden werden können, ohne sich gegenseitig zu überschreiben.
 */
function ewigeProjectRootUrlPrefix() : string
{
    static $prefix = null;
    if ($prefix !== null) {
        return $prefix;
    }

    $projectRootDisk = rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/');
    $scriptFilename  = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptName      = (string)($_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptFilename !== '' && $scriptName !== '' && str_ends_with($scriptFilename, $scriptName)) {
        $documentRootDisk = substr($scriptFilename, 0, -strlen($scriptName));
        $documentRootDisk = rtrim($documentRootDisk, '/');
        if ($documentRootDisk !== '' && str_starts_with($projectRootDisk, $documentRootDisk)) {
            $prefix = substr($projectRootDisk, strlen($documentRootDisk)) . '/';
            return $prefix;
        }
    }

    $isDirectCall = basename($_SERVER['SCRIPT_NAME'] ?? '') === basename(__FILE__);
    $prefix = $isDirectCall ? '../../' : '';
    return $prefix;
}

// ── Parameter einlesen (GET überschreibt vorher per include() gesetzte
// PHP-Variablen, die wiederum Vorrang vor den Standardwerten haben) ──────────
$e_ligasRaw = $_REQUEST['ewige_ligas'] ?? ($ewige_ligas ?? '');
$e_ligasParts = is_array($e_ligasRaw) ? $e_ligasRaw : preg_split('/[,\s]+/', (string)$e_ligasRaw);
$e_ligas   = array_values(array_unique(array_filter(array_map('intval', $e_ligasParts), fn ($v) => $v > 0)));
$e_view    = ($_REQUEST['ewige_view'] ?? ($ewige_view ?? 'eternal')) === 'verlauf' ? 'verlauf' : 'eternal';
$e_template = str_replace('..', '', basename((string)($_REQUEST['ewige_template'] ?? ($ewige_template ?? ''))));
if ($e_template === '') {
    $e_template = $e_view === 'verlauf' ? 'matrix' : 'standard';
}

if ($ewigeIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\"><title>Ewige Tabelle</title>"
        . "<style>html,body{margin:0;padding:0;background:transparent;}</style></head><body>\n";
}

/**
 * Team-Logo mit korrektem URL-Präfix (wie miniLogoImg in lmo-minitab.php,
 * aber für die ewige Tabelle). findTeamLogoPathFrontend() liefert Pfade
 * relativ zum Projekt-Root, dieses Addon liegt aber unter addon/mini/.
 */
function ewigeLogoImg(int $teamId) : string
{
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
    return '<img src="' . h(ewigeProjectRootUrlPrefix() . $path) . '" alt="" style="height:18px;width:auto;vertical-align:middle">';
}

/**
 * Schneidet einen "<!-- BEGIN {name} -->...<!-- END {name} -->"-Block aus
 * $src heraus. Liefert den Block-Inhalt und setzt $before/$after auf die
 * Teile vor bzw. nach dem Block (wie in lmo-minitab.php).
 */
function ewige_extract_block(string $src, string $name, ?string &$before, ?string &$after) : string
{
    $pat = '/<!-- BEGIN ' . preg_quote($name, '/') . ' -->(.*?)<!-- END ' . preg_quote($name, '/') . ' -->/s';
    if (!preg_match($pat, $src, $m)) {
        $before = $src;
        $after  = '';
        return '';
    }
    $pos    = strpos($src, $m[0]);
    $before = substr($src, 0, $pos);
    $after  = substr($src, $pos + strlen($m[0]));
    return $m[1];
}

/**
 * Baut den HTML-Code der ewigen Tabelle bzw. des Mehrjahres-Vergleichs.
 *
 * @param int[]  $ligaIds      Liga-IDs (Reihenfolge = Spaltenreihenfolge)
 * @param string $view         'eternal' oder 'verlauf'
 * @param string $templateName Template-Name ohne Endung
 */
function renderEwigeTabelle(array $ligaIds, string $view, string $templateName): string
{
    if (empty($ligaIds)) {
        return '<p style="font-family:sans-serif;color:#a33">Fehlender oder ungültiger Parameter "ewige_ligas"</p>';
    }

    $service = new EternalTableService();

    // Template laden
    $defaultName = $view === 'verlauf' ? 'matrix' : 'standard';

    $candidates = [
        __DIR__ . '/../../template/addon/ewige/' . $templateName . '.tpl.php',
        __DIR__ . '/../../template/addon/ewige/' . $defaultName . '.tpl.php',
        __DIR__ . '/../../template/addon/mini/ewige_' . $templateName . '.tpl.php',
        __DIR__ . '/../../template/addon/mini/ewige_' . $defaultName . '.tpl.php',
        __DIR__ . '/ewige_' . $defaultName . '.tpl.php',
    ];

    $templatePath = '';
    foreach ($candidates as $c) {
        if (is_file($c)) {
            $templatePath = $c;
            break;
        }
    }

    if ($templatePath === '') {
        return '<p style="font-family:sans-serif;color:#a33">Vorlage nicht gefunden.<br>'
            . implode('<br>', array_map('h', $candidates))
            . '</p>';
    }

    $templateSrc = (string)file_get_contents($templatePath);

    if ($templateSrc === '') {
        return '<p style="font-family:sans-serif;color:#a33">Vorlage ist leer: '
            . h($templatePath)
            . '</p>';
    }

    if ($view === 'verlauf') {
        $data = $service->seasonMatrix($ligaIds);
        return ewige_render_matrix($templateSrc, $data, $ligaIds);
    }

    // gewünschte Wertung - wie bei ewige_ligas/ewige_view/ewige_template hat ein
    // GET/POST-Parameter Vorrang vor einer vorher per include() gesetzten
    // PHP-Variablen $wertung (siehe Docblock oben); Standard ist die klassische
    // "Ewige Tabelle" nach historischen Original-Punkten der jeweiligen Saison
    $sort = $_REQUEST['wertung'] ?? ($wertung ?? 'pkt');

    // nur erlaubte Werte
    if (!in_array($sort, ['pkt', 'pkt2', 'pkt3'], true)) {
        $sort = 'pkt';
    }

    $rows = $service->eternalStandings($ligaIds);

    // gewünschte Tabelle sortieren
    usort($rows, static function (array $a, array $b) use ($sort): int {

        if ($a[$sort] !== $b[$sort]) {
            return $b[$sort] <=> $a[$sort];
        }

        $diffA = $a['tore_h'] - $a['tore_g'];
        $diffB = $b['tore_h'] - $b['tore_g'];

        if ($diffA !== $diffB) {
            return $diffB <=> $diffA;
        }

        if ($a['tore_h'] !== $b['tore_h']) {
            return $b['tore_h'] <=> $a['tore_h'];
        }

        return strcmp($a['name'], $b['name']);
    });

    // Rang nach neuer Sortierung vergeben
    foreach ($rows as $i => &$r) {
        $r['rang'] = $i + 1;
    }
    unset($r);

    return ewige_render_eternal($templateSrc, $rows, $ligaIds);
}

/** Ewige Tabelle (Summenansicht) über standard.tpl.php. */
function ewige_render_eternal(string $templateSrc, array $rows, array $ligaIds) : string
{
    $rowTemplate = ewige_extract_block($templateSrc, 'Inhalt', $before, $after);
    if ($rowTemplate === '') {
        return '<p style="font-family:sans-serif;color:#a33">Ungültiges Template (kein Inhalt-Block gefunden)</p>';
    }

    $rowsHtml = '';
    foreach ($rows as $r) {
        $diff = (int)$r['tore_h'] - (int)$r['tore_g'];
        $rowsHtml .= strtr($rowTemplate, [
            '<!--Platz-->'         => (string)$r['rang'],
            '<!--Logo-->'          => ewigeLogoImg((int)$r['id']),
            '<!--Team-->'          => h($r['kurz'] !== '' ? $r['kurz'] : $r['name']),
            '<!--TeamLang-->'      => h($r['name']),
            '<!--Saisons-->'       => (string)$r['saisons'],
            '<!--Spiele-->'        => (string)$r['sp'],
            '<!--Siege-->'         => (string)$r['s'],
            '<!--Unentschieden-->' => (string)$r['u'],
            '<!--Niederlagen-->'   => (string)$r['n'],
            '<!--PlusTore-->'      => (string)$r['tore_h'],
            '<!--MinusTore-->'     => (string)$r['tore_g'],
            '<!--Tordifferenz-->'  => h(($diff > 0 ? '+' : '') . $diff),
            '<!--Punkte-->'       => (string)$r['pkt'],     // historisch
            '<!--Punkte2-->'      => (string)$r['pkt2'],    // immer 2 Punkte
            '<!--Minuspunkte2-->' => (string)$r['mpkt2'],
            '<!--Punkte3-->'      => (string)$r['pkt3'],    // immer 3 Punkte
            '<!--Minuspunkte3-->' => (string)$r['mpkt3'],
            '<!--Style-->'         => '',
            '<!--Class-->'         => '',
        ]);
    }

    $outer = [
        '<!--Tabelle-->'  => h('Ewige Tabelle'),
        '<!--Fusszeile-->' => h(count($rows) . ' Teams · ' . count($ligaIds) . ' Liga(en)'),
    ];
    $before = strtr($before, $outer);
    $after  = strtr($after, $outer);

    return $before . $rowsHtml . $after;
}

/** Mehrjahres-Vergleich (Matrix) über matrix.tpl.php. */
function ewige_render_matrix(string $templateSrc, array $m, array $ligaIds) : string
{
    // Zeilen-Block (TeamZeile) herausschneiden; $before enthält den Kopf
    // mit dem Spalten-Block (Spalte).
    $rowTemplate = ewige_extract_block($templateSrc, 'TeamZeile', $before, $after);
    if ($rowTemplate === '') {
        return '<p style="font-family:sans-serif;color:#a33">Ungültiges Template (kein TeamZeile-Block gefunden)</p>';
    }
    $colTemplate = ewige_extract_block($before, 'Spalte', $colBefore, $colAfter);

    // Saison-Spalten (Kopf)
    $colsHtml = '';
    foreach ($m['seasons'] as $lid => $sname) {
        $colsHtml .= strtr($colTemplate, [
            '<!--SaisonName-->'  => h($sname),
            '<!--SaisonTitel-->' => h($sname),
        ]);
    }
    $before = $colBefore . $colsHtml . $colAfter;

    // Zellen-Block liegt innerhalb der Team-Zeile.
    $cellTemplate = ewige_extract_block($rowTemplate, 'Zelle', $rowBefore, $rowAfter);

    $rowsHtml = '';
    foreach ($m['teams'] as $tid => $tname) {
        $cellsHtml = '';
        foreach ($m['seasons'] as $lid => $_) {
            $cell = $m['matrix'][$tid][$lid] ?? null;
            if ($cell !== null) {
                $cellsHtml .= strtr($cellTemplate, [
                    '<!--ZelleKlasse-->'  => '',
                    '<!--ZelleInhalt-->'  => '<strong>#' . (int)$cell['rang'] . '</strong> · ' . (int)$cell['pkt'] . ' P.',
                ]);
            } else {
                $cellsHtml .= strtr($cellTemplate, [
                    '<!--ZelleKlasse-->'  => 'lmo-leer',
                    '<!--ZelleInhalt-->'  => '–',
                ]);
            }
        }
        $rowsHtml .= strtr($rowBefore . $cellsHtml . $rowAfter, [
            '<!--TeamName-->' => h($tname),
        ]);
    }

    $outer = [
        '<!--Tabelle-->'  => h('Mehrjahres-Vergleich'),
        '<!--Fusszeile-->' => 'Je Saison: <strong>Platzierung</strong> · Punkte. „–“ = Team in dieser Liga nicht dabei.',
    ];
    $before = strtr($before, $outer);
    $after  = strtr($after, $outer);

    return $before . $rowsHtml . $after;
}

echo renderEwigeTabelle($e_ligas, $e_view, $e_template);

if ($ewigeIsDirectCall) {
    echo "\n</body></html>\n";
}
