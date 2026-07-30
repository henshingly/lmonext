<?php
/**
 * Project: LMOnext
 * Filename: data_home.php
 * Fileversion: 2.0.1
 * Changelog: 2.0.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 2.0.0 - Kein HTML mehr direkt in dieser Datei: renderLigaLink() und
 *                     renderArchivFolderTree() nutzen jetzt renderPartial() mit
 *                     template/<aktiv>/partials/liga_list_item.tpl.php bzw.
 *                     archiv_folder.tpl.php. Diese Datei ist reines "Grundgerüst"
 *                     (Abfragen + Schleifen), das Markup steckt komplett im Template.
 * Changelog: 1.0.0 - Initiale Version: aktive Ligen, Archiv-Baum, wiederverwendbares
 *                     Rendering des Archiv-Baums (von jedem Template nutzbar)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Alle Abfragen und die dazugehörige Aufbereitung für die Besucher-Startseite
 * an einem Ort. Templates selbst enthalten kein PHP – sie bekommen fertige
 * HTML-Fragmente als Platzhalterwerte (siehe frontend/template_engine.php).
 */
declare(strict_types = 1);

/**
 * Aktive (nicht archivierte) Ligen, neueste zuerst.
 */
function getActiveLigenList() : array
{
    try {
        return getDB()->query(
            'SELECT l.id, l.name, l.datum, COALESCE(lo.option_value,\'0\') AS type
               FROM ' . tbl('liga') . ' l
               LEFT JOIN ' . tbl('liga_options') . ' lo ON lo.liga_id=l.id AND lo.option_key="Type"
              WHERE l.archiv_folder_id IS NULL
              ORDER BY l.datum DESC'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

/**
 * Archivierte Ligen, gruppiert nach Ordner-ID (0 = ohne Ordner/"Waisen",
 * analog zur Admin-Archivansicht).
 */
function getArchivedLigenByFolder() : array
{
    try {
        $rows = getDB()->query(
            'SELECT l.id, l.name, l.datum, l.archiv_folder_id, COALESCE(lo.option_value,\'0\') AS type
               FROM ' . tbl('liga') . ' l
               LEFT JOIN ' . tbl('liga_options') . ' lo ON lo.liga_id=l.id AND lo.option_key="Type"
              WHERE l.archiv_folder_id IS NOT NULL
              ORDER BY l.name'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
    $byFolder = [];
    foreach ($rows as $r) {
        $byFolder[(int)$r['archiv_folder_id']][] = $r;
    }
    return $byFolder;
}

/**
 * Archiv-Ordner, aufbereitet als Eltern→Kinder-Zuordnung (parent_id => [Ordner...]).
 */
function getArchivFolderTree() : array
{
    try {
        $folders = getDB()->query(
            'SELECT id, parent_id, name, beschreibung, sort FROM ' . tbl('liga_archiv_folders') . ' ORDER BY sort, name'
        )->fetchAll();
    } catch (Throwable) {
        return [];
    }
    $byParent = [];
    foreach ($folders as $f) {
        $byParent[(int)($f['parent_id'] ?? 0)][] = $f;
    }
    return $byParent;
}

/**
 * Baut den Archiv-Baum als HTML auf (verschachtelte <details>/<summary> – klappt
 * beim Anklicken auf, kein JavaScript nötig). Jeder Ordner wird über das Partial
 * "archiv_folder" gerendert (siehe template/<aktiv>/partials/archiv_folder.tpl.php);
 * diese Funktion kümmert sich nur um Rekursion + Datenzusammenstellung.
 *
 * @param array $byParent      Rückgabe von getArchivFolderTree()
 * @param array $ligenByFolder Rückgabe von getArchivedLigenByFolder()
 * @param int   $parentId      Interner Rekursionsparameter, beim ersten Aufruf 0 lassen
 */
function renderArchivFolderTree(array $byParent, array $ligenByFolder, int $parentId = 0) : string
{
    if (empty($byParent[$parentId])) {
        return '';
    }
    $html = '';
    foreach ($byParent[$parentId] as $folder) {
        $fid        = (int)$folder['id'];
        $ligen      = $ligenByFolder[$fid] ?? [];
        $childHtml  = renderArchivFolderTree($byParent, $ligenByFolder, $fid);
        $hasContent = !empty($ligen) || $childHtml !== '';

        $ligenHtml = '';
        foreach ($ligen as $l) {
            $ligenHtml .= renderLigaLink($l);
        }

        $folderContent = $hasContent
            ? '<div class="folder-content">' . $ligenHtml . $childHtml . '</div>'
            : '<div class="folder-content folder-empty">' . h(tf('home_folder_empty')) . '</div>';

        $folderDesc = !empty($folder['beschreibung'])
            ? '<span class="folder-desc"> – ' . h($folder['beschreibung']) . '</span>'
            : '';

        $html .= renderPartial('archiv_folder', [
            'FolderName'    => h($folder['name']),
            'FolderDesc'    => $folderDesc,
            'FolderContent' => $folderContent,
        ]);
    }
    return $html;
}

/**
 * Einzelner Liga-Link mit Typ-Chip (Liga/KO-Turnier), für aktive Ligen und
 * für Ligen im Archiv-Baum gleichermaßen genutzt. Markup steckt im Partial
 * "liga_list_item" (template/<aktiv>/partials/liga_list_item.tpl.php).
 */
function renderLigaLink(array $liga) : string
{
    $isKO = ($liga['type'] ?? '0') === '1';
    return renderPartial('liga_list_item', [
        'LigaId'     => (int)$liga['id'],
        'ChipClass'  => $isKO ? 'chip-yellow' : 'chip-blue',
        'TypeLabel'  => $isKO ? h(tf('home_type_ko')) : h(tf('home_type_liga')),
        'LigaName'   => h($liga['name']),
    ]);
}
