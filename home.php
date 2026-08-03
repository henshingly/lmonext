<?php
/**
 * Project: LMOnext
 * Filename: home.php
 * Fileversion: 2.3.1
 * Changelog: 2.3.1 - "ZurueckLinkBlock" (Link zur Liga-Übersicht) an die Tippspiel-Route
 *                     ergänzt - fehlte bisher komplett auf allen Tippspiel-Unterseiten (siehe
 *                     renderBackLinkBlock() jetzt in frontend/data_liga.php 3.0.1)
 * Changelog: 2.3.0 - Neue Route "?view=tippspiel": bindet das Tippspiel jetzt als View ins
 *                     Template-System ein (analog zur Spielerstatistik in liga.php), statt als
 *                     eigenständige Seite mit eigenem HTML/CSS. Läuft komplett getrennt von der
 *                     normalen Startseite, ruft tippspielHandleRequest() (kann per redirectTo()
 *                     umleiten) VOR renderTemplate() auf - siehe
 *                     addon/tipp/view_tippspiel_frontend.php. Ersetzt die bisherige
 *                     eigenständige addon/tipp/tipp.php
 * Changelog: 2.2.0 - Neuer Platzhalter "TippspielCard": wirbt auf der Startseite fürs
 *                     Tippspiel (tippRenderHomeCard(), siehe addon/tipp/tipp_lib.php 0.5.0),
 *                     bleibt leer wenn keine Liga freigegeben ist
 * Changelog: 2.1.0 - Die globale Einstellung "Liga-Übersicht anzeigen?" (Admin →
 *                     Einstellungen → Besucherbereich, bisher nur für den "← Zur Übersicht"-
 *                     Link auf der Liga-Detailseite) blendet jetzt auch die komplette
 *                     Liga-Auswahl hier auf der Startseite aus (aktive Ligen + Archiv) - gedacht
 *                     für Betreiber, die nur eine einzelne, feste Liga per iframe/include auf
 *                     einer fremden Webseite einbinden möchten, ohne dass Besucher zu einer
 *                     Gesamtübersicht gelangen können
 * Changelog: 2.0.1
 * Changelog: 2.0.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 2.0.0 - Umbau auf reine Platzhalter-Templates: baut jetzt fertige
 *                     HTML-Fragmente (aktive Ligen, Archiv-Bereich) und übergibt sie
 *                     als Platzhalterwerte an renderTemplate(). Die .tpl.php-Dateien
 *                     enthalten dadurch kein PHP mehr, nur noch Markup + Platzhalter.
 * Changelog: 1.0.0 - Initiale Version: Besucher-Startseite (aktive Ligen + Archiv-Baum)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

require_once __DIR__ . '/frontend/bootstrap.php';

// ── Tippspiel-View: läuft komplett getrennt von der normalen Startseite
// (aktive Ligen + Archiv), analog zur Spielerstatistik in liga.php. Muss VOR
// jeder anderen Ausgabe geprüft werden, da tippspielHandleRequest() umleiten
// kann (header()+exit, siehe dortiger Docblock) ────────────────────────────
if (($_GET['view'] ?? '') === 'tippspiel') {
    require_once __DIR__ . '/addon/tipp/view_tippspiel_frontend.php';
    $tippState = tippspielHandleRequest();

    renderTemplate($activeTemplate, 'tippspiel', [
        'Titel'            => h(tf('tf_tipp_seiten_titel')),
        'ZurueckLinkBlock' => renderBackLinkBlock(),
        'ViewInhalt'       => renderTippspielView($tippState),
    ]);
    exit;
}

// ── Normale Startseite (aktive Ligen + Archiv) ──────────────────────────────
$activeLigen         = getActiveLigenList();
$archivByParent      = getArchivFolderTree();
$archivLigenByFolder = getArchivedLigenByFolder();

// Dieselbe globale Einstellung wie der "← Zur Übersicht"-Link auf der
// Liga-Detailseite (Admin → Einstellungen → Besucherbereich): wenn aus,
// zeigt home.php gar keine Liga-Auswahl mehr an (weder aktive Ligen noch
// Archiv). Gedacht für Betreiber, die NUR eine einzelne, feste Liga über
// liga.php?id=LIGA_ID per iframe/include auf einer fremden Webseite
// einbinden möchten, ohne dass Besucher zu einer Gesamtübersicht mit allen
// Ligen gelangen können.
$showOverview = getAdminSetting('show_back_link', '1') === '1';

// ── Aktive Ligen: Liste oder Leer-Hinweis ────────────────────────────────────
if (!$showOverview) {
    $aktiveLigenInhalt = '<p class="empty-msg">' . h(tf('home_overview_disabled')) . '</p>';
} elseif (empty($activeLigen)) {
    $aktiveLigenInhalt = '<p class="empty-msg">' . h(tf('home_no_active_ligen')) . '</p>';
} else {
    $items = '';
    foreach ($activeLigen as $liga) {
        $items .= '<li>' . renderLigaLink($liga) . '</li>';
    }
    $aktiveLigenInhalt = '<ul class="liga-list">' . $items . '</ul>';
}

// ── Archiv: nur anzeigen, wenn überhaupt etwas archiviert ist (und die
// Übersicht insgesamt nicht deaktiviert ist) ─────────────────────────────────
$orphans   = $archivLigenByFolder[0] ?? [];
$hasArchiv = $showOverview && (!empty($archivByParent) || !empty($orphans));

$archivBereich = '';
if ($hasArchiv) {
    $orphansHtml = '';
    foreach ($orphans as $liga) {
        $orphansHtml .= '<div style="padding:2px 0">' . renderLigaLink($liga) . '</div>';
    }
    $archivBereich = renderPartial('archiv_card', [
        'Heading' => h(tf('home_heading_archiv')),
        'Orphans' => $orphansHtml,
        'Tree'    => renderArchivFolderTree($archivByParent, $archivLigenByFolder),
    ]);
}

renderTemplate($activeTemplate, 'home', [
    'Titel'                   => h(tf('site_title')),
    'UeberschriftAktiveLigen' => h(tf('home_heading_active_ligen')),
    'AktiveLigenInhalt'       => $aktiveLigenInhalt,
    'ArchivBereich'           => $archivBereich,
    'TippspielCard'           => tippRenderHomeCard(),
]);
