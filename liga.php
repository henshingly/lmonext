<?php
/**
 * Project: LMOnext
 * Filename: liga.php
 * Fileversion: 3.10.2
 * Changelog: 3.10.2 - PDF-Export der Ergebnisse übergibt jetzt die "Spielfrei"-Teams pro
 *                     Spieltag mit (siehe pdf_export.php 1.6.7)
 * Changelog: 3.10.1
 * Changelog: 3.10.1 - Neuer "Spielfrei: TEAMNAME"-Hinweis unterhalb der Ergebnistabelle eines
 *                     Spieltags (siehe renderSpielfreiNote() in data_liga.php 2.17.0), analog
 *                     zum alten LMO
 * Changelog: 3.10.0
 * Changelog: 3.10.0 - "spielerstatistik"-Reiter ergänzt (neues Addon, siehe
 *                     frontend/data_spielerstat.php + admin/spielerstat_lib.php)
 * Changelog: 3.9.4 - Neue globale Einstellung "PDF-Export für Besucher anzeigen?" (Admin →
 *                     Einstellungen → Besucherbereich) ausgewertet: blendet bei Deaktivierung
 *                     nicht nur die PDF-Buttons aus (Ergebnisse/Tabelle/Spielplan), sondern
 *                     blockiert auch den direkten Aufruf über ?pdf=1 bzw. ?h2h_pdf=1, gilt für
 *                     KO- und reguläre Ligen gleichermaßen
 * Changelog: 3.9.3
 * Changelog: 3.9.3 - Alle PDF-Export-Aufrufe (Ergebnisse/Tabelle/Spielplan/Teamvergleich)
 *                     übergeben jetzt $showLogos, damit Team-Logos auch im PDF erscheinen, wenn
 *                     die Liga-Einstellung "Logo anzeigen" aktiv ist. Der Teamvergleich-PDF-Link
 *                     ist teamübergreifend (kein Liga-Kontext an der Stelle) – bekommt das Flag
 *                     deshalb über einen neuen "&logos=1"-Query-Parameter mit, den das Modal
 *                     selbst anhand seines eigenen Payloads setzt
 * Changelog: 3.9.2
 * Changelog: 3.9.2 - Name-zuerst-dann-Logo für die Heim-Spalte gilt jetzt auch bei KO-Ligen in
 *                     der Ergebnisse-Ansicht (vorher nur reguläre Ligen). Der KO-Turnierbaum
 *                     (Spielpläne) bleibt bewusst unverändert bei Logo-zuerst, da dort die
 *                     Teams untereinander geschrieben werden
 * Changelog: 3.9.1
 * Changelog: 3.9.1 - renderResultsTable()-Aufrufe übergeben jetzt !$isKO als reverseHeim, damit
 *                     die Heim-Spalte bei regulären Ligen Name-zuerst-dann-Logo zeigt (KO bleibt
 *                     unverändert Logo-zuerst)
 * Changelog: 3.9.0
 * Changelog: 3.9.0 - Neue liga-weite Variable $showLogos (aus der Einstellung ShowLogos), wird
 *                     an renderResultsTable() weitergereicht, damit Ergebnisse/Finale+Platz3
 *                     bei aktiviertem "Logo anzeigen" die Team-Logos einblenden
 * Changelog: 3.8.9
 * Changelog: 3.8.9 - PDF-Export bei Finale + Spiel um Platz 3: baut jetzt zwei getrennte
 *                     Abschnitte (sectionSpecs) mit jeweils eigenem Datum, statt beider
 *                     Begegnungen in einer gemeinsamen Tabelle mit einem (falschen) über beide
 *                     Spiele gemittelten Datumsbereich. Spiegelt exakt dieselbe Bedingung wie
 *                     die HTML-Ansicht (siehe exportErgebnissePdf()/buildResultsPdf() in
 *                     pdf_export.php)
 * Changelog: 3.8.8 - Bugfix: Finale + Spiel um Platz 3 zeigten in der Überschrift beide den
 *                     Datumsbereich der GESAMTEN Runde (z.B. "18.07.2026 - 19.07.2026"), obwohl
 *                     jede Paarung nur ein eigenes Einzeldatum hat. Datumsbereich wird jetzt pro
 *                     Paarung berechnet (spieltagDateRange() nur mit den Partien dieser
 *                     Paarung), zeigt dadurch bei Einzelspielen korrekt nur das eine Datum
 * Changelog: 3.8.7 - Neue Route "?h2h_pdf=1&a=X&b=Y": PDF-Export des Team-Vergleichs
 *                     (Direkter-Vergleich-Modal). Teamübergreifend, deshalb vor der normalen
 *                     id/view-Auflösung abgefangen (keine gültige Liga-ID nötig). Siehe
 *                     exportH2hPdf() in pdf_export.php und den neuen PDF-Button im Modal
 *                     (data_liga.php, renderH2hModalAssets())
 * Changelog: 3.8.6 - "Als PDF exportieren"-Button jetzt auch unter dem Spielplan einer
 *                     Mannschaft (reguläre Ligen) – erscheint nur, wenn tatsächlich ein Team
 *                     ausgewählt ist (nicht bei der "Bitte wählen Sie..."-Leeranzeige). Neue
 *                     exportSpielplanPdf() in pdf_export.php
 * Changelog: 3.8.5 - PDF-Export-Buttons (Ergebnisse + Tabelle) neu gestaltet: nur noch "PDF"
 *                     als Text statt der übersetzten Beschriftung (spart die Übersetzung, ist
 *                     universell verständlich), neues Dokumenten-Icon, Übersetzung bleibt als
 *                     title-Tooltip erhalten. Passendes Hell/Blau-Hover-Styling siehe
 *                     layout.tpl.php
 * Changelog: 3.8.4 - "Als PDF exportieren"-Button jetzt auch für KO-Turniere (Rundenname wie
 *                     "Achtelfinale"/"Runde 1" statt "Spieltag N" als PDF-Titel); Button-HTML in
 *                     eine gemeinsame Variable ausgelagert, damit er sowohl im normalen Zweig
 *                     als auch im KO-Mehrgruppen-Zweig (Finale + Spiel um Platz 3 auf einer
 *                     Seite) erscheint
 * Changelog: 3.8.3 - Ergebnisse-Ansicht: reine Leer-Begegnungen (kein Team, kein Label auf
 *                     beiden Seiten) werden jetzt herausgefiltert, bevor sie gerendert werden
 *                     (siehe partieIsEmptyPlaceholder() in data_liga.php) – relevant für
 *                     KO-Turniere, deren Teilnehmerzahl im alten LMO auf die nächste
 *                     Zweierpotenz aufgefüllt werden musste
 * Changelog: 3.8.2 - "Als PDF exportieren"-Button auch unter der Tabelle (Standings) ergänzt
 *                     (?pdf=1 löst exportTabellePdf() aus). Der Tabelle-Reiter ist ohnehin nur
 *                     für reguläre Ligen erreichbar (bei KO-Turnieren wird er über $flags
 *                     komplett ausgeblendet), daher kein zusätzlicher !$isKO-Check nötig
 * Changelog: 3.8.1 - exportErgebnissePdf()-Aufruf an neue Signatur angepasst (Spieltag-Nummer
 *                     + Datumsbereich statt fertigem Überschrift-String, siehe pdf_export.php
 *                     v1.1.0 für das überarbeitete PDF-Layout mit Logo/Tore-Schnitt)
 * Changelog: 3.8.0 - "Als PDF exportieren"-Button unter der Ergebnistabelle für reguläre
 *                     (Round-Robin-)Ligen ergänzt (?pdf=1 löst Download über
 *                     exportErgebnissePdf() aus, danach exit; kein Button bei KO-Turnieren)
 * Changelog: 3.7.0 - Bugfix: favTeam/selTeam aus den Liga-Einstellungen werden jetzt tatsächlich
 *                     verwendet. "Spielpläne" wählt ohne ?team=-Parameter automatisch das
 *                     selTeam-Team; "Ergebnisse" hebt die favTeam-Mannschaft fett hervor
 *                     (resolveTeamNumberToId() löst die gespeicherte Team-Nummer in die echte
 *                     team_id auf)
 * Changelog: 3.6.0 - "ligastatistik"-Reiter ergänzt (Team-Auswahl per team1/team2 GET-Parameter);
 *                     für KO-Ligen ausgeblendet
 * Changelog: 3.5.0 - "fieberkurve"-Reiter ergänzt; für KO-Ligen ausgeblendet
 * Changelog: 3.4.0 - "kreuztabelle"-Reiter ergänzt; Tabelle + Kreuztabelle für KO-Ligen
 *                     ausgeblendet (ergeben dort keinen Sinn)
 * Changelog: 3.3.0 - "spielplaene"-Reiter jetzt auch für reguläre Ligen aktiv (Team-Spielplan
 *                     statt Turnierbaum); Einschränkung "nur für KO-Ligen" entfernt
 * Changelog: 3.2.0 - "tabelle"-Reiter ergänzt (Liga-Tabelle für reguläre Ligen)
 * Changelog: 3.1.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 3.1.0 - renderInfoView()-Aufruf vereinfacht: Info zeigt jetzt "Über LMOnext"
 *                     statt Liga-Metadaten, braucht daher keine Liga-Parameter mehr
 * Changelog: 3.0.0 - Reiter-Navigation ergänzt: neben "Ergebnisse" jetzt auch "Kalender"
 *                     (Monatskalender mit klickbaren Spieltagen/Runden), "Spielpläne"
 *                     (klassischer Turnierbaum, vorerst nur für KO-Ligen) und "Info"
 *                     (Kerndaten zur Liga). Reiter werden nur gezeigt, wenn in den
 *                     Liga-Einstellungen aktiviert (Kalender/Ergebnis/Plan). Auswahl über
 *                     ?view=kalender|ergebnisse|spielplaene|info.
 * Changelog: 2.0.0 - Umbau auf reine Platzhalter-Templates
 * Changelog: 1.4.2 - "Heim"-Spaltenüberschrift bekommt class="col-heim" (für Rechtsbündigkeit)
 * Changelog: 1.4.1 - koRoundName()-Aufruf um Rundennummer ergänzt
 * Changelog: 1.4.0 - Kleine Überschrift "Statistik {label}:" über der Statistikzeile ergänzt
 * Changelog: 1.3.0 - KO-Rundennamen nach Teamanzahl statt "Runde N"
 * Changelog: 1.2.0 - Umbau auf Tabellen-Ansicht wie alte LMO-Ergebnisseite
 * Changelog: 1.1.0 - Auswahl/Navigation zwischen allen Spieltagen/Runden (?nr=N)
 * Changelog: 1.0.0 - Initiale Version: zeigt die letzten Ergebnisse einer Liga
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

require_once __DIR__ . '/frontend/bootstrap.php';

// ── PDF-Export des Team-Vergleichs (Direkter Vergleich) ──────────────────────
// Teamübergreifend, nicht an eine bestimmte Liga gebunden – deshalb hier vor
// der normalen id/view-Auflösung abgefangen.
if (isset($_GET['h2h_pdf']) && getAdminSetting('show_pdf_buttons', '1') === '1') {
    $teamAId = (int)($_GET['a'] ?? 0);
    $teamBId = (int)($_GET['b'] ?? 0);
    if ($teamAId > 0 && $teamBId > 0) {
        exportH2hPdf($teamAId, $teamBId, ($_GET['logos'] ?? '0') === '1');
    }
    exit;
}

$ligaId = (int)($_GET['id'] ?? 0);
$liga   = $ligaId > 0 ? getLigaById($ligaId) : null;

if ($liga === null) {
    renderTemplate($activeTemplate, 'liga_not_found', [
        'Titel'             => h(tf('liga_not_found')),
        'ZurueckLink'       => h(tf('liga_back_link')),
        'NichtGefundenText' => h(tf('liga_not_found')),
    ]);
    exit;
}

$isKO         = getLigaType($ligaId) === 1;
$opts         = getLigaOptions($ligaId);
$flags        = getLigaViewFlags($opts);
$showLogos    = ($opts['ShowLogos'] ?? '0') === '1';
// Globale Einstellung (Admin → Einstellungen → Besucherbereich), gilt für
// alle Liga-Typen und alle PDF-Exporte gleichermaßen. Blockiert bei
// Deaktivierung nicht nur den Button, sondern auch den direkten Aufruf über
// ?pdf=1 (sonst wäre die Datei trotz ausgeblendetem Button weiter abrufbar).
$showPdfButtons = getAdminSetting('show_pdf_buttons', '1') === '1';
// Tabelle und Kreuztabelle ergeben bei KO-Turnieren (Ausscheidungsmodus) keinen
// Sinn – nur für reguläre (Round-Robin-)Ligen anzeigen.
if ($isKO) {
    $flags['tabelle']       = false;
    $flags['kreuztabelle']  = false;
    $flags['fieberkurve']   = false;
    $flags['ligastatistik'] = false;
}

$allSpieltage = getAllSpieltage($ligaId);
$maxNr        = getMaxSpieltagNummer($allSpieltage);
$favTeamId    = resolveTeamNumberToId($ligaId, (int)($opts['favTeam'] ?? 0));

// ── Aktuellen Reiter bestimmen (?view=…, sonst ersten aktivierten Reiter) ────
$viewOrder   = ['ergebnisse', 'kalender', 'tabelle', 'spielplaene', 'kreuztabelle', 'fieberkurve', 'ligastatistik', 'spielerstatistik', 'info'];
$currentView = $_GET['view'] ?? 'ergebnisse';
if (empty($flags[$currentView])) {
    $currentView = 'ergebnisse';
    foreach ($viewOrder as $candidate) {
        if (!empty($flags[$candidate])) {
            $currentView = $candidate;
            break;
        }
    }
}

switch ($currentView) {

    case 'kalender':
        $year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        $viewInhalt = renderKalenderView($allSpieltage, $ligaId, $isKO, $maxNr, $year, $month);
        break;

    case 'tabelle':
        if (isset($_GET['pdf']) && $showPdfButtons) {
            exportTabellePdf($liga['name'], $ligaId, $allSpieltage, $showLogos);
            exit;
        }
        $viewInhalt = renderStandingsView($ligaId, $allSpieltage);
        if ($showPdfButtons) {
            $viewInhalt .= '<div class="pdf-export-row"><a class="btn-pdf-export" href="?id=' . $ligaId . '&view=tabelle&pdf=1" title="' . h(tf('liga_pdf_export_button')) . '">'
                . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . '<rect x="7" y="3" width="13" height="16" rx="2"/><path d="M4 7v13a2 2 0 0 0 2 2h11"/>'
                . '</svg>'
                . 'PDF</a></div>';
        }
        break;

    case 'spielplaene':
        if ($isKO) {
            $viewInhalt = renderBracketView($ligaId, $allSpieltage, $isKO, $maxNr);
        } else {
            $selectedTeamId = isset($_GET['team'])
                ? (int)$_GET['team']
                : resolveTeamNumberToId($ligaId, (int)($opts['selTeam'] ?? 0));
            if ($selectedTeamId !== null && isset($_GET['pdf']) && $showPdfButtons) {
                exportSpielplanPdf($liga['name'], $ligaId, $allSpieltage, $selectedTeamId, $showLogos);
                exit;
            }
            $viewInhalt = renderTeamScheduleView($ligaId, $allSpieltage, $selectedTeamId);
            if ($selectedTeamId !== null && $showPdfButtons) {
                $pdfUrl = '?id=' . $ligaId . '&view=spielplaene&team=' . $selectedTeamId . '&pdf=1';
                $viewInhalt .= '<div class="pdf-export-row"><a class="btn-pdf-export" href="' . h($pdfUrl) . '" title="' . h(tf('liga_pdf_export_button')) . '">'
                    . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                    . '<rect x="7" y="3" width="13" height="16" rx="2"/><path d="M4 7v13a2 2 0 0 0 2 2h11"/>'
                    . '</svg>'
                    . 'PDF</a></div>';
            }
        }
        break;

    case 'kreuztabelle':
        $viewInhalt = renderKreuztabelleView($ligaId, $allSpieltage);
        break;

    case 'fieberkurve':
        $viewInhalt = renderFieberkurveView($ligaId, $allSpieltage);
        break;

    case 'ligastatistik':
        $team1 = isset($_GET['team1']) && (int)$_GET['team1'] > 0 ? (int)$_GET['team1'] : null;
        $team2 = isset($_GET['team2']) && (int)$_GET['team2'] > 0 ? (int)$_GET['team2'] : null;
        $viewInhalt = renderLigastatistikView($ligaId, $allSpieltage, $team1, $team2);
        break;

    case 'spielerstatistik':
        $viewInhalt = renderSpielerstatistikView($ligaId);
        break;

    case 'info':
        $viewInhalt = renderInfoView();
        break;

    case 'ergebnisse':
    default:
        // ── Aktuell gewählten Spieltag/Runde ermitteln (?nr=N, sonst letzter mit Ergebnis) ──
        $requestedNr = isset($_GET['nr']) ? (int)$_GET['nr'] : null;
        $spieltag    = $requestedNr !== null ? getSpieltagByNummer($allSpieltage, $requestedNr) : null;
        if ($spieltag === null) {
            $spieltag = getLatestSpieltagWithResults($allSpieltage);
        }

        $currentNr   = $spieltag['nummer'] ?? null;
        $currentName = $spieltag !== null ? roundDisplayName($spieltag, $isKO, $maxNr) : '';
        $partien     = $spieltag !== null ? getSpieltagPartien((int)$spieltag['id']) : [];
        // Reine Leer-Begegnungen (kein Team, kein Label auf beiden Seiten – z.B.
        // Freilos-Auffüllplätze bei KO-Turnieren) werden nicht angezeigt, siehe
        // partieIsEmptyPlaceholder().
        $partien     = array_values(array_filter($partien, static fn(array $p) => !partieIsEmptyPlaceholder($p)));
        $dateRange   = $spieltag !== null ? spieltagDateRange($partien, $spieltag['start'] ?? null) : '';

        // ── PDF-Export (reguläre Ligen: "Spieltag N", KO-Turniere: Rundenname
        // wie "Achtelfinale"/"Runde 1"; bei Finale+Spiel um Platz 3 zwei
        // getrennte Abschnitte mit jeweils eigenem Datum statt einem
        // gemeinsamen Datumsbereich über beide Begegnungen hinweg) ────────────
        if ($spieltag !== null && isset($_GET['pdf']) && $showPdfButtons) {
            if ($isKO && (int)$currentNr === $maxNr && count(groupPartienByPairing($partien)) > 1) {
                $pdfGroups = groupPartienByPairing($partien);
                $pdfGroupHeadings = [tf('liga_round_finale'), tf('liga_heading_platz3')];
                $sectionSpecs = [];
                foreach ($pdfGroups as $i => $groupPartien) {
                    $heading = $pdfGroupHeadings[$i] ?? tf('liga_round_finale');
                    $groupDateRange = spieltagDateRange($groupPartien, $spieltag['start'] ?? null);
                    $sectionSpecs[] = [
                        'label'         => $heading . ($groupDateRange !== '' ? ' · ' . $groupDateRange : ''),
                        'partien'       => $groupPartien,
                        'spieltagStart' => $spieltag['start'] ?? null,
                    ];
                }
            } else {
                $pdfRoundLabel = $isKO ? $currentName : tf('liga_pdf_title_matchday', ['n' => $currentNr]);
                $sectionSpecs = [[
                    'label'         => $pdfRoundLabel . ($dateRange !== '' ? ' · ' . $dateRange : ''),
                    'partien'       => $partien,
                    'spieltagStart' => $spieltag['start'] ?? null,
                    'spielfrei'     => findSpielfreiTeams($ligaId, $partien),
                ]];
            }
            exportErgebnissePdf($liga['name'], $sectionSpecs, $showLogos);
            exit;
        }

        $subtitle = '';
        if ($spieltag !== null) {
            $subtitleText = $isKO
                ? tf('liga_subtitle_round', ['name' => $currentName])
                : tf('liga_subtitle_matchday', ['n' => $currentNr]);
            $subtitle = '<h2 class="liga-subtitle">' . h($subtitleText) . '</h2>';
        }

        $pdfUrl = '?id=' . $ligaId . '&view=ergebnisse&nr=' . (int)$currentNr . '&pdf=1';
        $pdfButtonHtml = !$showPdfButtons ? '' : '<div class="pdf-export-row"><a class="btn-pdf-export" href="' . h($pdfUrl) . '" title="' . h(tf('liga_pdf_export_button')) . '">'
            . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<rect x="7" y="3" width="13" height="16" rx="2"/><path d="M4 7v13a2 2 0 0 0 2 2h11"/>'
            . '</svg>'
            . 'PDF</a></div>';

        if ($spieltag === null) {
            $ergebnisInhalt = '<p class="empty-msg">' . h(tf('liga_no_results_yet')) . '</p>';
        } elseif ($isKO && (int)$currentNr === $maxNr && count(groupPartienByPairing($partien)) > 1) {
            $ergebnisInhalt = '';
            $groups         = groupPartienByPairing($partien);
            $groupHeadings  = [tf('liga_round_finale'), tf('liga_heading_platz3')];
            foreach ($groups as $i => $groupPartien) {
                $heading = $groupHeadings[$i] ?? tf('liga_round_finale');
                $groupDateRange = spieltagDateRange($groupPartien, $spieltag['start'] ?? null);
                $headingWithRange = $heading . ($groupDateRange !== '' ? ' ' . $groupDateRange : '');
                $ergebnisInhalt .= '<h3 class="spieltag-heading">' . h($headingWithRange) . '</h3>';
                $ergebnisInhalt .= renderResultsTable($groupPartien, $spieltag['start'] ?? null, $favTeamId, $showLogos, true);
                $ergebnisInhalt .= renderStatsBlock($heading, $groupPartien);
            }
            $ergebnisInhalt .= $pdfButtonHtml;
        } else {
            $headingText = $isKO
                ? $currentName . ($dateRange !== '' ? ' ' . $dateRange : '')
                : tf('liga_heading_matchday_range', ['n' => $currentNr, 'range' => $dateRange]);
            $ergebnisInhalt  = '<h3 class="spieltag-heading">' . h($headingText) . '</h3>';
            $ergebnisInhalt .= renderResultsTable($partien, $spieltag['start'] ?? null, $favTeamId, $showLogos, true);
            $ergebnisInhalt .= renderSpielfreiNote($ligaId, $partien);
            $ergebnisInhalt .= renderStatsBlock($currentName, $partien);
            $ergebnisInhalt .= $pdfButtonHtml;
        }

        $picker = renderSpieltagPicker($allSpieltage, $ligaId, $currentNr !== null ? (int)$currentNr : null, $isKO, $maxNr);

        $viewInhalt = $subtitle . $picker . $ergebnisInhalt;
        break;
}

$tabsBar = renderTabsBar($flags, $ligaId, $currentView);

renderTemplate($activeTemplate, 'liga', [
    'Titel'        => h($liga['name']),
    'ZurueckLink'  => h(tf('liga_back_link')),
    'LigaName'     => h($liga['name']),
    'TypChipClass' => $isKO ? 'chip-yellow' : 'chip-blue',
    'TypLabel'     => $isKO ? h(tf('home_type_ko')) : h(tf('home_type_liga')),
    'TabsBar'      => $tabsBar,
    'ViewInhalt'   => $viewInhalt,
]);
