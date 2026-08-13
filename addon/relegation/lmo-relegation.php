<?php
/**
 * Project: LMOnext
 * Filename: addon/relegation/lmo-relegation.php
 * Fileversion: 1.0.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * ── Einbindung ────────────────────────────────────────────────────────────────
 *
 * Variante 1 (empfohlen) – per include() aus eigenem PHP-Code:
 *
 *   <?php
 *   $rl_ligen = '3,7';   // Liga-IDs: Oberliga,Unterliga (Pflicht)
 *   include('/PfadZuLMOnext/addon/relegation/lmo-relegation.php');
 *
 * Variante 2 – per direkter URL / IFrame:
 *
 *   <iframe src="https://.../addon/relegation/lmo-relegation.php?rl_ligen=3,7"
 *           frameborder="0" width="900" height="900" scrolling="auto"></iframe>
 *
 * Steuerparameter (GET hat immer Vorrang vor vorher gesetzten PHP-Variablen):
 *   rl_ligen       Zwei Liga-IDs, Komma-getrennt: "Oberliga,Unterliga"
 *                  (z.B. "3,7" – Liga 3 ist die höherklassige Liga, aus der
 *                  ein Team relegiert werden kann; Liga 7 die niederklassige,
 *                  in die ein Team aufsteigen kann). Pflicht.
 *   rl_rang_oben   Optional: Tabellenrang in der Oberliga, um den es bei der
 *                  Abstiegsrelegation geht (1-basiert). Ohne Angabe wird der
 *                  Rang automatisch aus der Liga-Einstellung "AB" (feststehende
 *                  Absteiger, siehe Admin → Liga-Einstellungen → Tabelle)
 *                  ermittelt: Rang = Gesamtteams - AB (der Platz direkt über
 *                  den festen Absteigern). Beispiel: 18 Teams, AB=2 → Rang 16.
 *   rl_rang_unten  Optional: Tabellenrang in der Unterliga, um den es bei der
 *                  Aufstiegsrelegation geht (1-basiert). Ohne Angabe wird der
 *                  Rang automatisch aus der Liga-Einstellung "CL" (direkte
 *                  Aufsteiger/Champions-League-Plätze) ermittelt: Rang = CL+1
 *                  (der Platz direkt nach den direkten Aufsteigern). Beispiel:
 *                  CL=2 → Rang 3.
 *   rl_count       Optional: Anzahl der Plätze, die oberhalb/unterhalb des
 *                  jeweiligen Relegationsrangs zusätzlich angezeigt werden.
 *                  Standard: 2 (siehe Vorbild-Grafik: 2 Teams drüber, 2 drunter).
 *   rl_template    Template-Name ohne Endung (Standard: "standard"), Datei
 *                  muss unter /template/addon/relegation/{name}.tpl.php liegen.
 *
 * ── Funktionsweise ────────────────────────────────────────────────────────────
 *
 * Zeigt die aktuelle Tabellensituation rund um eine Relegation zwischen zwei
 * Ligen: In der Oberliga die Plätze rund um den Abstiegsrelegationsrang, in
 * der Unterliga die Plätze rund um den Aufstiegsrelegationsrang, dazwischen
 * zwei Hinweisbanner mit den jeweils betroffenen Teams. Reine Momentaufnahme
 * auf Basis der aktuellen Tabellenstände – das Ergebnis eines eventuell schon
 * gespielten oder noch bevorstehenden echten Relegationsspiels wird nicht
 * berücksichtigt (das System kennt kein Relegationsspiel-Konzept), daher die
 * bewusst neutrale Formulierung ("spielt Relegation um ...").
 */
declare(strict_types=1);

use LMOnext\Liga\LigaService;

// Wird diese Datei direkt aufgerufen (URL/IFrame) oder per include()?
$rlIsDirectCall = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'lmo-relegation.php';

require_once __DIR__ . '/../../frontend/bootstrap.php';

// Dieses Addon ist bewusst zum Einbetten via iframe auf fremden Websites
// gedacht (siehe Docblock oben) - die von frontend/bootstrap.php gesetzten
// Frame-Schutz-Header (X-Frame-Options/CSP frame-ancestors) werden hier
// deshalb wieder entfernt, sonst würde jede Einbettung blockiert.
if (!headers_sent()) {
    header_remove('X-Frame-Options');
    header_remove('Content-Security-Policy');
}

/**
 * Berechnet das URL-Präfix zum Projekt-Root (siehe miniProjectRootUrlPrefix()
 * in lmo-minitab.php für die ausführliche Begründung – gleiche Logik, eigener
 * Funktionsname, damit mehrere Addons parallel eingebunden werden können).
 */
function rlProjectRootUrlPrefix() : string
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

/**
 * Logo-<img> mit korrektem URL-Präfix.
 */
function rlLogoImgWrapped(int $teamId, bool $showLogos) : string
{
    if (!$showLogos || $teamId <= 0) {
        return '';
    }
    $path = findTeamLogoPathFrontend($teamId) ?? 'assets/img/nopic-team.svg';
    $img = '<img src="' . h(rlProjectRootUrlPrefix() . $path) . '" alt="" class="team-logo-inline">';
    return '<span class="st-team-logo-wrap">' . $img . '</span>';
}

// ── Parameter einlesen ───────────────────────────────────────────────────────
$rlLigenRaw   = $_REQUEST['rl_ligen'] ?? ($rl_ligen ?? '');
$rlLigenParts = is_array($rlLigenRaw) ? $rlLigenRaw : preg_split('/[,\s]+/', (string)$rlLigenRaw);
$rlLigenIds   = array_values(array_filter(array_map('intval', $rlLigenParts), fn ($v) => $v > 0));

$rlTemplate = isset($_REQUEST['rl_template']) ? (string)$_REQUEST['rl_template'] : (string)($rl_template ?? 'standard');
$rlTemplate = str_replace('..', '', basename($rlTemplate)); // Path-Traversal-Schutz

$rlCount = isset($_REQUEST['rl_count']) ? max(1, (int)$_REQUEST['rl_count']) : max(1, (int)($rl_count ?? 2));

$rlRangObenOverride  = isset($_REQUEST['rl_rang_oben'])  ? (int)$_REQUEST['rl_rang_oben']  : (isset($rl_rang_oben)  ? (int)$rl_rang_oben  : null);
$rlRangUntenOverride = isset($_REQUEST['rl_rang_unten']) ? (int)$_REQUEST['rl_rang_unten'] : (isset($rl_rang_unten) ? (int)$rl_rang_unten : null);

if ($rlIsDirectCall) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>' . "\n" . '<html><head><meta charset="utf-8">'
        . '<title>Relegation</title>'
        . '<style>html,body{margin:0;padding:8px;background:transparent;}</style>'
        . '</head><body>' . "\n";
}

if (count($rlLigenIds) < 2) {
    echo '<p style="font-family:sans-serif;color:#697182;padding:12px">'
        . h(tf('liga_relegation_missing_liga')) . '</p>';
    if ($rlIsDirectCall) {
        echo "\n</body></html>";
    }
    return;
}

echo renderRelegationView($rlLigenIds[0], $rlLigenIds[1], $rlCount, $rlRangObenOverride, $rlRangUntenOverride, $rlTemplate);

if ($rlIsDirectCall) {
    echo "\n</body></html>";
}

// ════════════════════════════════════════════════════════════════════════════
//  Funktionen
// ════════════════════════════════════════════════════════════════════════════

/**
 * Laedt das Template, extrahiert die Wiederholungs-Bloecke fuer die
 * Team-Boxen (oben und unten benutzen dasselbe Box-Template).
 */
function rlLoadTemplate(string $templateName) : array
{
    $templatePath = __DIR__ . '/../../template/addon/relegation/' . $templateName . '.tpl.php';
    if (!is_file($templatePath)) {
        $templatePath = __DIR__ . '/../../template/addon/relegation/standard.tpl.php';
    }
    $src = (string)file_get_contents($templatePath);

    // Zwei getrennte Wiederholungs-Bloecke: BOX_OBEN (Reihe ueber der Oberliga)
    // und BOX_UNTEN (Reihe ueber der Unterliga). Beide haben in der Regel
    // identisches Markup, werden aber unabhaengig extrahiert, damit jede
    // Reihe im Template frei positioniert/gestylt werden kann.
    $boxObenTpl = '';
    if (preg_match('/<!-- BEGIN BOX_OBEN -->(.*?)<!-- END BOX_OBEN -->/s', $src, $m)) {
        $boxObenTpl = $m[1];
        $src = preg_replace('/<!-- BEGIN BOX_OBEN -->.*?<!-- END BOX_OBEN -->/s', '{OBEN_BOXES}', $src, 1);
    }

    $boxUntenTpl = '';
    if (preg_match('/<!-- BEGIN BOX_UNTEN -->(.*?)<!-- END BOX_UNTEN -->/s', $src, $m)) {
        $boxUntenTpl = $m[1];
        $src = preg_replace('/<!-- BEGIN BOX_UNTEN -->.*?<!-- END BOX_UNTEN -->/s', '{UNTEN_BOXES}', $src, 1);
    }

    return [
        'skeleton'    => $src,
        'boxObenTpl'  => $boxObenTpl,
        'boxUntenTpl' => $boxUntenTpl,
    ];
}

/**
 * Holt Liga-Name aus der Datenbank.
 */
function rlGetLigaName(int $ligaId) : string
{
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT name FROM ' . tbl('liga') . ' WHERE id = ?');
        $stmt->execute([$ligaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? (string)$row['name'] : ('Liga ' . $ligaId);
    } catch (\Throwable) {
        return 'Liga ' . $ligaId;
    }
}

/**
 * Berechnet die aktuelle Tabelle einer Liga (letzter gespielter Spieltag).
 * Gibt [rows, opts, totalTeams] zurueck. rows = computeStandings()-Ergebnis.
 */
function rlComputeCurrentStandings(int $ligaId) : array
{
    $allSpieltage = LigaService::getAllSpieltage($ligaId);
    $opts         = LigaService::getLigaOptions($ligaId);
    $teams        = LigaService::getLigaTeamsList($ligaId);
    $allPartien   = LigaService::getAllLigaPartien($allSpieltage);
    $maxNr        = LigaService::getMaxSpieltagNummer($allSpieltage);

    $rows = LigaService::computeStandings($teams, $allPartien, $opts, $ligaId, 'overall', $maxNr);

    return ['rows' => $rows, 'opts' => $opts, 'total' => count($rows)];
}

/**
 * Rendert die Team-Boxen fuer einen Rangbereich [$fromRank, $toRank] einer
 * Liga. $targetRank ist der eigentliche Relegationsrang (bekommt die
 * spezielle Headerfarbe $highlightColor). $arrowDirection bestimmt die
 * Pfeilrichtung fuer Teams, die laut computeStandingsMarkerColor() in
 * einer Auf-/Abstiegszone liegen ('up' fuer Unterliga, 'down' fuer Oberliga).
 */
function rlRenderBoxes(
    array $rows,
    array $opts,
    int $totalTeams,
    int $fromRank,
    int $toRank,
    int $targetRank,
    string $highlightColor,
    string $arrowDirection,
    string $boxTpl
) : string {
    $showLogos       = ($opts['ShowLogos'] ?? '0') === '1';
    $showMinuspunkte = ($opts['MinusPoints'] ?? '0') === '1';

    $html = '';
    for ($rank = $fromRank; $rank <= $toRank; $rank++) {
        $index = $rank - 1; // 0-basiert
        if (!isset($rows[$index])) {
            continue;
        }
        $r   = $rows[$index];
        $tid = (int)$r['id'];

        $isTarget    = ($rank === $targetRank);
        $markerColor = LigaService::computeStandingsMarkerColor($index, $totalTeams, $opts);
        $showArrow   = $markerColor !== '' || $isTarget;

        $headerStyle = $isTarget ? ('background:' . h($highlightColor) . ';color:#fff;') : '';
        $boxClass    = $isTarget ? ' rl-box-target' : '';

        $arrowHtml = '';
        if ($showArrow) {
            $arrowHtml = $arrowDirection === 'up'
                ? '<span class="rl-arrow rl-arrow-up">&#8599;</span>'
                : '<span class="rl-arrow rl-arrow-down">&#8600;</span>';
        }

        $pkt = $showMinuspunkte ? ($r['pkt'] . ':' . $r['minuspunkte']) : (string)$r['pkt'];

        $replacements = [
            '{PLATZ}'        => (string)$rank,
            '{HEADER_STYLE}' => $headerStyle,
            '{BOX_CLASS}'    => $boxClass,
            '{LOGO}'         => rlLogoImgWrapped($tid, $showLogos),
            '{TEAM}'         => h($r['name']),
            '{PKT}'          => h($pkt),
            '{ARROW}'        => $arrowHtml,
            '{PLATZ_LABEL}'  => h(tf('liga_relegation_platz')),
        ];
        $html .= strtr($boxTpl, $replacements);
    }
    return $html;
}

/**
 * Baut den kompletten Relegations-Block zwischen zwei Ligen.
 */
function renderRelegationView(int $ligaObenId, int $ligaUntenId, int $count, ?int $rangObenOverride, ?int $rangUntenOverride, string $templateName) : string
{
    if (LigaService::getLigaType($ligaObenId) === 1 || LigaService::getLigaType($ligaUntenId) === 1) {
        return '<p style="font-family:sans-serif;color:#697182;padding:12px">'
            . h(tf('liga_relegation_no_ko')) . '</p>';
    }

    $oben  = rlComputeCurrentStandings($ligaObenId);
    $unten = rlComputeCurrentStandings($ligaUntenId);

    if ($oben['total'] === 0 || $unten['total'] === 0) {
        return '<p style="font-family:sans-serif;color:#697182;padding:12px">'
            . h(tf('liga_relegation_missing_liga')) . '</p>';
    }

    // ── Relegationsrang Oberliga: Platz direkt über den festen Absteigern ──
    $abOben = max(0, (int)($oben['opts']['AB'] ?? 0));
    $rangOben = $rangObenOverride ?? ($oben['total'] - $abOben);
    $rangOben = max(1, min($oben['total'], $rangOben));

    // ── Relegationsrang Unterliga: Platz direkt nach den direkten Aufsteigern ──
    $clUnten = max(0, (int)($unten['opts']['CL'] ?? 0));
    $rangUnten = $rangUntenOverride ?? ($clUnten + 1);
    $rangUnten = max(1, min($unten['total'], $rangUnten));

    $fromOben = max(1, $rangOben - $count);
    $toOben   = min($oben['total'], $rangOben + $count);
    $fromUnten = max(1, $rangUnten - $count);
    $toUnten   = min($unten['total'], $rangUnten + $count);

    $ligaObenName  = rlGetLigaName($ligaObenId);
    $ligaUntenName = rlGetLigaName($ligaUntenId);

    $tpl = rlLoadTemplate($templateName);

    $obenBoxesHtml = rlRenderBoxes(
        $oben['rows'], $oben['opts'], $oben['total'],
        $fromOben, $toOben, $rangOben,
        '#22c55e', 'down', $tpl['boxObenTpl']
    );
    $untenBoxesHtml = rlRenderBoxes(
        $unten['rows'], $unten['opts'], $unten['total'],
        $fromUnten, $toUnten, $rangUnten,
        '#3b82f6', 'up', $tpl['boxUntenTpl']
    );

    // ── Banner-Texte ────────────────────────────────────────────────────────
    $teamOben  = $oben['rows'][$rangOben - 1]['name']  ?? '';
    $teamUnten = $unten['rows'][$rangUnten - 1]['name'] ?? '';

    $bannerAbHtml = strtr((string)tf('liga_relegation_banner_ab'), [
        '%TEAM%' => h($teamOben),
        '%RANG%' => (string)$rangOben,
        '%LIGA%' => h($ligaObenName),
    ]);
    $bannerAufHtml = strtr((string)tf('liga_relegation_banner_auf'), [
        '%TEAM%' => h($teamUnten),
        '%RANG%' => (string)$rangUnten,
        '%LIGA%' => h($ligaUntenName),
    ]);

    $skeleton = $tpl['skeleton'];
    $skeleton = str_replace('{LIGA_OBEN_NAME}',   h($ligaObenName), $skeleton);
    $skeleton = str_replace('{LIGA_UNTEN_NAME}',  h($ligaUntenName), $skeleton);
    $skeleton = str_replace('{OBEN_BOXES}',       $obenBoxesHtml, $skeleton);
    $skeleton = str_replace('{UNTEN_BOXES}',      $untenBoxesHtml, $skeleton);
    $skeleton = str_replace('{BANNER_ABSTIEG}',   $bannerAbHtml, $skeleton);
    $skeleton = str_replace('{BANNER_AUFSTIEG}',  $bannerAufHtml, $skeleton);
    $skeleton = str_replace('{COPYRIGHT}',        LigaService::renderCopyrightNotice('relegation'), $skeleton);

    return $skeleton;
}
