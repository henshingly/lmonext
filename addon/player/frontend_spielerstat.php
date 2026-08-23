<?php
/**
 * Project: LMOnext
 * Filename: addon/player/frontend_spielerstat.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */

require_once __DIR__ . '/spielerstat_lib.php';

/** Vergleichbarer Wert für eine Zelle: numerisch falls möglich, sonst normalisierter String (Umlaute etc.). */
function spielerstatSortValue(string $raw, bool $numeric) : int|float|string
{
    if ($numeric) {
        return is_numeric($raw) ? (float)$raw : 0.0;
    }
    $map = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','Ä'=>'ae','Ö'=>'oe','Ü'=>'ue'];
    return strtolower(strtr($raw, $map));
}

function renderSpielerstatistikView(int $ligaId) : string
{
    $spalten = getSpielerstatSpalten($ligaId);
    $spieler = getSpielerstatSpieler($ligaId);
    $cfg     = getSpielerstatConfig($ligaId);

    if (empty($spalten) || empty($spieler)) {
        return '<div class="card"><p>' . h(tf('spst_view_empty')) . '</p></div>';
    }

    $sortCol = isset($_GET['sort']) ? (int)$_GET['sort'] : (int)($cfg['sort_column'] ?? 0);
    if (!isset($spalten[$sortCol])) { $sortCol = 0; }
    $dir     = isset($_GET['dir']) ? (int)$_GET['dir'] : (int)($cfg['sort_direction'] ?? 0); // 1=aufsteigend, 0=absteigend
    $team    = $_GET['team'] ?? '';
    $perPage = (int)($cfg['per_page'] ?? 17);
    $begin   = max(0, (int)($_GET['begin'] ?? 0));

    $vereinColIdx = null;
    foreach ($spalten as $i => $sp) {
        if ($sp['rolle'] === 'verein') { $vereinColIdx = $i; break; }
    }
    $linkColIdx = null;
    foreach ($spalten as $i => $sp) {
        if ($sp['rolle'] === 'spielerlink') { $linkColIdx = $i; break; }
    }

    // Vereinsfilter anwenden
    if (!empty($cfg['show_per_club']) && $vereinColIdx !== null && $team !== '') {
        $spieler = array_values(array_filter($spieler, static function ($p) use ($spalten, $vereinColIdx, $team) {
            return ($p['werte'][$spalten[$vereinColIdx]['id']] ?? '') === $team;
        }));
    }

    // Nullwerte ausfiltern (nur wenn die Sortierspalte numerisch ist, wie im Original)
    $sortIsNumeric = $spalten[$sortCol]['typ'] !== 'text';
    if (empty($cfg['show_zero']) && $sortIsNumeric) {
        $sortColId = $spalten[$sortCol]['id'];
        $spieler = array_values(array_filter($spieler, static fn($p) => (float)($p['werte'][$sortColId] ?? 0) != 0.0));
    }

    // Sortieren
    $sortColId = $spalten[$sortCol]['id'];
    usort($spieler, static function ($a, $b) use ($sortColId, $sortIsNumeric, $dir) {
        $va = spielerstatSortValue((string)($a['werte'][$sortColId] ?? ''), $sortIsNumeric);
        $vb = spielerstatSortValue((string)($b['werte'][$sortColId] ?? ''), $sortIsNumeric);
        $cmp = $va <=> $vb;
        return $dir === 1 ? $cmp : -$cmp;
    });

    $total = count($spieler);
    $pageItems = $perPage > 0 ? array_slice($spieler, $begin, $perPage) : $spieler;

    // ── Vereinsfilter-Sidebar ─────────────────────────────────────────────────
    $sidebarHtml = '';
    if (!empty($cfg['show_per_club']) && $vereinColIdx !== null) {
        $vereinColId = $spalten[$vereinColIdx]['id'];
        $clubs = [];
        foreach (getSpielerstatSpieler($ligaId) as $p) {
            $c = $p['werte'][$vereinColId] ?? '';
            if ($c !== '') { $clubs[$c] = true; }
        }
        ksort($clubs);
        $items = '<a href="?id=' . $ligaId . '&view=spielerstatistik" class="' . ($team === '' ? 'active' : '') . '">' . h(tf('spst_view_all_clubs')) . '</a><br>';
        foreach (array_keys($clubs) as $c) {
            $items .= '<a href="?id=' . $ligaId . '&view=spielerstatistik&team=' . urlencode($c) . '" class="' . ($team === $c ? 'active' : '') . '">' . h($c) . '</a><br>';
        }
        $sidebarHtml = '<div class="card" style="max-width:220px"><div style="font-size:.85rem;line-height:2">' . $items . '</div></div>';
    }

    // ── Kopfzeile mit Sortierlinks ────────────────────────────────────────────
    $headHtml = '<tr>';
    foreach ($spalten as $i => $sp) {
        if ($sp['rolle'] === 'spielerlink') { continue; } // eigene Spalte wird nicht separat angezeigt
        $baseUrl = '?id=' . $ligaId . '&view=spielerstatistik&team=' . urlencode($team) . '&sort=' . $i;
        $upUrl   = $baseUrl . '&dir=1';
        $downUrl = $baseUrl . '&dir=0';
        $active  = $i === $sortCol;
        $alignClass = $sp['typ'] === 'text' ? '' : ' st-num';
        $colImage = findSpielerstatColumnImage($sp['name']);
        $labelHtml = $colImage !== null
            ? '<img src="' . h($colImage) . '" alt="' . h($sp['name']) . '" title="' . h($sp['name']) . '">'
            : h($sp['name']);
        $headHtml .= '<th class="' . trim($alignClass) . '"' . ($active ? ' style="background:#33405c"' : '') . '>'
            . '<a href="' . h($downUrl) . '" title="' . h(tf('spst_sort_desc')) . '">▼</a> '
            . $labelHtml
            . ' <a href="' . h($upUrl) . '" title="' . h(tf('spst_sort_asc')) . '">▲</a>'
            . '</th>';
    }
    $headHtml .= '</tr>';

    // ── Datenzeilen ────────────────────────────────────────────────────────────
    $rowsHtml = '';
    foreach ($pageItems as $p) {
        $rowsHtml .= '<tr>';
        foreach ($spalten as $i => $sp) {
            if ($sp['rolle'] === 'spielerlink') { continue; }
            $val = (string)($p['werte'][$sp['id']] ?? '');
            if ($i === 0) {
                $photo = !empty($p['global_player_id']) ? findPlayerPhotoPath((int)$p['global_player_id']) : null;
                $nameHtml = $photo !== null
                    ? '<img src="' . h($photo) . '" alt="' . h($val) . '" title="' . h($val) . '" style="height:28px;width:28px;object-fit:cover;border-radius:50%;vertical-align:middle;margin-right:6px">'
                    : '';
                if ($linkColIdx !== null && !empty($p['werte'][$spalten[$linkColIdx]['id']])) {
                    $url = $p['werte'][$spalten[$linkColIdx]['id']];
                    $rowsHtml .= '<td>' . $nameHtml . h($val) . ' <a href="' . h($url) . '" target="_blank" rel="noopener" title="' . h(tf('spst_player_link_title')) . '">🔗</a></td>';
                } else {
                    $rowsHtml .= '<td>' . $nameHtml . h($val) . '</td>';
                }
            } else {
                $align = $sp['typ'] === 'text' ? '' : ' class="st-num"';
                $rowsHtml .= '<td' . $align . '>' . h($val) . '</td>';
            }
        }
        $rowsHtml .= '</tr>';
    }

    // ── Pagination ─────────────────────────────────────────────────────────────
    $pagerHtml = '';
    if ($perPage > 0 && $total > $perPage) {
        $baseUrl = '?id=' . $ligaId . '&view=spielerstatistik&team=' . urlencode($team) . '&sort=' . $sortCol . '&dir=' . $dir;
        $pagerHtml = '<div style="text-align:center;margin-top:10px;font-size:.85rem">';
        if ($begin > 0) {
            $pagerHtml .= '<a href="' . h($baseUrl . '&begin=' . max(0, $begin - $perPage)) . '">&laquo; ' . h(tf('spst_pager_prev')) . '</a> | ';
        }
        $pagerHtml .= h(tf('spst_pager_range', ['from' => $begin + 1, 'to' => min($begin + $perPage, $total), 'total' => $total]));
        if ($begin + $perPage < $total) {
            $pagerHtml .= ' | <a href="' . h($baseUrl . '&begin=' . ($begin + $perPage)) . '">' . h(tf('spst_pager_next')) . ' &raquo;</a>';
        }
        $pagerHtml .= '</div>';
    }

    $tableHtml = '<div class="card"><div class="table-scroll"><table class="standings-table">'
        . '<thead>' . $headHtml . '</thead><tbody>' . $rowsHtml . '</tbody></table></div>' . $pagerHtml . '</div>';

    if ($sidebarHtml !== '') {
        return '<div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap">'
            . $sidebarHtml . '<div style="flex:1;min-width:280px">' . $tableHtml . '</div></div>';
    }
    return $tableHtml;
}
