<?php
/**
 * Project: LMOnext
 * Filename: home.php
 * Fileversion: 2.3.1
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
