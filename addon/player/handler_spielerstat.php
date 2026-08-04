<?php
/**
 * Project: LMOnext
 * Filename: addon/player/handler_spielerstat.php
 * Fileversion: 1.2.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */

require_once __DIR__ . '/spielerstat_lib.php';
require_once __DIR__ . '/spielerstat_import.php';

// ── Spalte hinzufügen ─────────────────────────────────────────────────────────
if ($action === 'spst_addcolumn' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid  = (int)($_POST['liga_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $typ  = $_POST['typ'] ?? 'zahl';
    $formel = trim($_POST['formel'] ?? '');
    $rolle  = $_POST['rolle'] ?? 'normal';
    if ($name === '') {
        flash(t('spst_flash_name_required'), 'error');
    } else {
        addSpielerstatSpalte($lid, $name, $typ, $formel, $rolle);
        flash(t('spst_flash_column_added'));
    }
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Spalte löschen ─────────────────────────────────────────────────────────────
if ($action === 'spst_delcolumn' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    $cid = (int)($_POST['spalten_id'] ?? 0);
    if ($cid > 0) {
        deleteSpielerstatSpalte($cid);
        flash(t('spst_flash_column_deleted'));
    }
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Spieler hinzufügen ────────────────────────────────────────────────────────
if ($action === 'spst_addplayer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid  = (int)($_POST['liga_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        flash(t('spst_flash_name_required'), 'error');
    } else {
        addSpielerstatSpieler($lid, $name);
        flash(t('spst_flash_player_added'));
    }
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Spielerfoto hochladen/entfernen ───────────────────────────────────────────
if ($action === 'spst_upload_photo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    $gid = (int)($_POST['global_player_id'] ?? 0);
    if ($gid > 0) {
        if (!empty($_FILES['photo']['name'])) {
            $result = savePlayerPhotoUpload($gid, $_FILES['photo']);
            if (!$result['ok']) {
                flash($result['error'], 'error');
                redirect('?action=spielerstatistik&liga_id=' . $lid);
            }
            flash(t('spst_flash_photo_saved'));
        } elseif (isset($_POST['remove_photo'])) {
            deletePlayerPhoto($gid);
            flash(t('spst_flash_photo_removed'));
        }
    }
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Spieler löschen ────────────────────────────────────────────────────────────
if ($action === 'spst_delplayer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    $pid = (int)($_POST['spieler_id'] ?? 0);
    if ($pid > 0) {
        deleteSpielerstatSpieler($pid);
        flash(t('spst_flash_player_deleted'));
    }
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Bulk-Update aller Zellen + Spaltennamen/Formeln (Tabellenformular) ────────
if ($action === 'spst_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    $spalten = getSpielerstatSpalten($lid);
    foreach ($spalten as $sp) {
        $nameKey   = 'spalte_name_' . $sp['id'];
        $formelKey = 'spalte_formel_' . $sp['id'];
        if (isset($_POST[$nameKey])) {
            $newName   = trim($_POST[$nameKey]);
            $newFormel = $sp['typ'] === 'formel' ? trim($_POST[$formelKey] ?? '') : null;
            updateSpielerstatSpalte((int)$sp['id'], $newName !== '' ? $newName : $sp['name'], $newFormel);
        }
    }
    $players = getSpielerstatSpieler($lid);
    foreach ($players as $p) {
        foreach ($spalten as $sp) {
            if ($sp['typ'] === 'formel') { continue; } // Formelspalten werden berechnet, nicht manuell editiert
            $key = 'wert_' . $p['id'] . '_' . $sp['id'];
            if (isset($_POST[$key])) {
                setSpielerstatWert((int)$p['id'], (int)$sp['id'], trim($_POST[$key]));
            }
        }
    }
    recalcSpielerstatFormulas($lid);
    flash(t('spst_flash_updated'));
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Konfiguration speichern ────────────────────────────────────────────────────
if ($action === 'spst_saveconfig' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    saveSpielerstatConfig($lid, [
        'sort_column'            => (int)($_POST['sort_column'] ?? 0),
        'sort_direction'         => isset($_POST['sort_direction']) ? 1 : 0,
        'admin_sort_column'      => (int)($_POST['admin_sort_column'] ?? 0),
        'per_page'               => (int)($_POST['per_page'] ?? 17),
        'show_zero'              => isset($_POST['show_zero']) ? 1 : 0,
        'show_extra_sort_column' => isset($_POST['show_extra_sort_column']) ? 1 : 0,
        'show_per_club'          => isset($_POST['show_per_club']) ? 1 : 0,
        'link_label'             => trim($_POST['link_label'] ?? 'Spielerstatistik'),
    ]);
    flash(t('spst_flash_config_saved'));
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Import: Upload von .stat (+ optional .cfg) ────────────────────────────────
if ($action === 'spst_import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);

    // Nicht nur die Oberfläche blendet den Import aus, sobald mindestens eine
    // Spalte manuell angelegt wurde (siehe view_spielerstatistik.php) -
    // dieselbe Regel auch hier serverseitig durchsetzen, falls jemand die
    // Aktion direkt aufruft.
    if (!empty(getSpielerstatSpalten($lid))) {
        flash(t('spst_flash_import_blocked'), 'error');
        redirect('?action=spielerstatistik&liga_id=' . $lid);
    }

    $statFile = $_FILES['statfile'] ?? null;
    if (!$statFile || $statFile['error'] !== UPLOAD_ERR_OK) {
        flash(t('spst_flash_no_file'), 'error');
        redirect('?action=spielerstatistik&liga_id=' . $lid);
    }
    $statContent = file_get_contents($statFile['tmp_name']);
    if ($statContent === false) {
        flash(t('spst_flash_file_unreadable'), 'error');
        redirect('?action=spielerstatistik&liga_id=' . $lid);
    }

    $cfgContent = '';
    $cfgFile = $_FILES['cfgfile'] ?? null;
    if ($cfgFile && $cfgFile['error'] === UPLOAD_ERR_OK) {
        $cfgContent = (string)file_get_contents($cfgFile['tmp_name']);
    }

    $parsed = parseOldSpielerstatFile($statContent);
    $cfg    = $cfgContent !== '' ? parseOldSpielerstatConfig($cfgContent) : [];

    if (empty($parsed['spalten'])) {
        flash(t('spst_flash_parse_failed'), 'error');
        redirect('?action=spielerstatistik&liga_id=' . $lid);
    }

    $teamMatches = detectSpielerstatTeamMatches($parsed);
    if (!empty($teamMatches['ambiguous'])) {
        $_SESSION['spst_import_pending']       = $parsed;
        $_SESSION['spst_import_pending_cfg']   = $cfg;
        $_SESSION['spst_import_pending_liga']  = $lid;
        $_SESSION['spst_import_pending_exact'] = $teamMatches['exact'];
        $_SESSION['spst_import_pending_ambiguous'] = $teamMatches['ambiguous'];
        redirect('?action=spst_import_review');
    }

    $result = importOldSpielerstatIntoDB($lid, $parsed, $cfg, $teamMatches['exact']);
    flash($result['msg'], $result['ok'] ? 'success' : 'error');
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}

// ── Import-Abgleich abbrechen ──────────────────────────────────────────────────
if ($action === 'spst_import_cancel') {
    requireLogin();
    unset($_SESSION['spst_import_pending'], $_SESSION['spst_import_pending_cfg'],
          $_SESSION['spst_import_pending_liga'], $_SESSION['spst_import_pending_exact'],
          $_SESSION['spst_import_pending_ambiguous']);
    redirect('?action=spielerstatistik');
}

// ── Import bestätigen (nach Team-Abgleich) ─────────────────────────────────────
if ($action === 'spst_import_confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $parsed    = $_SESSION['spst_import_pending']           ?? null;
    $cfg       = $_SESSION['spst_import_pending_cfg']       ?? [];
    $lid       = (int)($_SESSION['spst_import_pending_liga'] ?? 0);
    $exact     = $_SESSION['spst_import_pending_exact']     ?? [];
    $ambiguous = $_SESSION['spst_import_pending_ambiguous'] ?? [];
    unset($_SESSION['spst_import_pending'], $_SESSION['spst_import_pending_cfg'],
          $_SESSION['spst_import_pending_liga'], $_SESSION['spst_import_pending_exact'],
          $_SESSION['spst_import_pending_ambiguous']);

    if ($parsed === null || $lid <= 0) {
        flash(t('imp_review_expired'), 'error');
        redirect('?action=spielerstatistik');
    }

    $adopt = $_POST['adopt'] ?? [];
    $overrides = $exact;
    foreach ($ambiguous as $rowIdx => $amb) {
        $selectedId = (int)($adopt[$rowIdx] ?? 0);
        if ($selectedId > 0) {
            $overrides[$rowIdx] = $selectedId;
        }
    }

    $result = importOldSpielerstatIntoDB($lid, $parsed, $cfg, $overrides);
    flash($result['msg'], $result['ok'] ? 'success' : 'error');
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}
