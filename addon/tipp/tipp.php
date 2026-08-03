<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/tipp.php
 * Fileversion: 0.1.0
 * Changelog: 0.1.0 - Initiale (vorläufige) Version der Tipper-Ansicht. Einzelner Einstiegspunkt
 *                     mit ?action=-Steuerung (login/register/confirm/logout/abgabe/save), analog
 *                     zu liga.php/admin.php. Bewusst schlankes, eigenständiges HTML ohne
 *                     Template-Engine-Anbindung - das folgt, sobald der Funktionsumfang steht.
 *                     Nur Ligenweise-Tippabgabe im Ergebnis-Tippmodus vollständig getestet
 *                     (siehe frontend_tipp.php für den genauen Stand).
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

require_once dirname(__DIR__, 2) . '/frontend/bootstrap.php';
require_once __DIR__ . '/frontend_tipp.php';

$action = $_GET['action'] ?? 'abgabe';
$flashMsg = null;
$flashType = 'success';

?><!DOCTYPE html>
<html lang="<?= h(getCurrentLanguage('frontend')) ?>">
<head>
<meta charset="UTF-8">
<title>Tippspiel</title>
<style>
  body { font-family: system-ui, sans-serif; background: #0f1117; color: #e2e8f0; margin: 0; padding: 20px; }
  .box { max-width: 760px; margin: 0 auto; background: #1a1d27; border: 1px solid #2e3247; border-radius: 8px; padding: 24px 28px; }
  h1 { font-size: 1.3rem; margin-top: 0; }
  label { display: block; margin: 10px 0 4px; font-size: .85rem; color: #94a3b8; }
  input[type=text], input[type=email], input[type=password], input[type=number] {
    width: 100%; box-sizing: border-box; background: #0f1117; border: 1px solid #2e3247;
    color: #e2e8f0; border-radius: 6px; padding: 8px 10px; font-size: .9rem;
  }
  input[type=number] { width: 60px; text-align: center; }
  .btn { background: #3b82f6; color: #fff; border: none; border-radius: 6px; padding: 9px 18px;
         font-size: .9rem; cursor: pointer; margin-top: 14px; }
  .btn:hover { background: #2563eb; }
  .flash { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: .87rem; }
  .flash.success { background: #0a2d0f; border: 1px solid #16a34a; color: #86efac; }
  .flash.error { background: #2d0a0a; border: 1px solid #ef4444; color: #fca5a5; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: .87rem; }
  td, th { padding: 6px 8px; text-align: left; border-bottom: 1px solid #2e3247; }
  td.spielinfo { color: #94a3b8; font-size: .8rem; }
  a { color: #60a5fa; }
  .readonly { color: #94a3b8; }
</style>
</head>
<body>
<div class="box">
<?php

// ═══════════════════════════════════════════════════════════════════════
// LOGIN
// ═══════════════════════════════════════════════════════════════════════
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = tippLogin($_POST['nickname'] ?? '', $_POST['password'] ?? '');
        if ($result['ok']) {
            redirectTo('?action=abgabe');
        }
        $flashMsg = tf($result['error']);
        $flashType = 'error';
    }
    ?>
  <h1><?= h(tf('tf_tipp_login_titel')) ?></h1>
<?php if ($flashMsg) { ?>
  <div class="flash <?= h($flashType) ?>"><?= h($flashMsg) ?></div>
<?php } ?>
  <form method="post" action="?action=login">
    <label><?= h(tf('tf_tipp_nickname')) ?></label>
    <input type="text" name="nickname" required>
    <label><?= h(tf('tf_tipp_passwort')) ?></label>
    <input type="password" name="password" required>
    <button type="submit" class="btn"><?= h(tf('tf_tipp_einloggen')) ?></button>
  </form>
  <p style="margin-top:16px;font-size:.85rem"><?= h(tf('tf_tipp_noch_kein_konto')) ?> <a href="?action=register"><?= h(tf('tf_tipp_registrieren')) ?></a></p>
<?php

// ═══════════════════════════════════════════════════════════════════════
// REGISTRIEREN
// ═══════════════════════════════════════════════════════════════════════
} elseif ($action === 'register') {
    $showAdresse  = getTippSetting('anmeldung_adresse_abfragen') === '1';
    $showRealname = getTippSetting('anmeldung_realname_abfragen') === '1';
    $result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = tippRegister(
            $_POST['nickname'] ?? '', $_POST['email'] ?? '',
            $_POST['password'] ?? '', $_POST['password_wdh'] ?? '',
            ['vorname' => trim($_POST['vorname'] ?? '') ?: null, 'nachname' => trim($_POST['nachname'] ?? '') ?: null]
        );
    }
    if ($result !== null && $result['ok']) {
        ?>
  <h1><?= h(tf('tf_tipp_registrierung_erfolgreich_titel')) ?></h1>
  <div class="flash success">
<?php if ($result['modus'] === 'sofort') { ?>
    <?= h(tf('tf_tipp_registrierung_sofort')) ?>
<?php } elseif ($result['modus'] === 'email') { ?>
    <?= h(tf('tf_tipp_registrierung_email')) ?>
<?php } else { ?>
    <?= h(tf('tf_tipp_registrierung_admin')) ?>
<?php } ?>
  </div>
  <p><a href="?action=login"><?= h(tf('tf_tipp_zum_login')) ?></a></p>
<?php } else { ?>
  <h1><?= h(tf('tf_tipp_registrieren_titel')) ?></h1>
<?php if ($result !== null && !$result['ok']) { ?>
  <div class="flash error"><?= h(tf($result['error'])) ?></div>
<?php } ?>
  <form method="post" action="?action=register">
    <label><?= h(tf('tf_tipp_nickname')) ?></label>
    <input type="text" name="nickname" maxlength="50" required value="<?= h($_POST['nickname'] ?? '') ?>">
    <label><?= h(tf('tf_tipp_email')) ?></label>
    <input type="email" name="email" maxlength="150" required value="<?= h($_POST['email'] ?? '') ?>">
    <label><?= h(tf('tf_tipp_passwort')) ?></label>
    <input type="password" name="password" required>
    <label><?= h(tf('tf_tipp_passwort_wdh')) ?></label>
    <input type="password" name="password_wdh" required>
<?php if ($showRealname) { ?>
    <label><?= h(tf('tf_tipp_vorname')) ?></label>
    <input type="text" name="vorname" maxlength="50">
    <label><?= h(tf('tf_tipp_nachname')) ?></label>
    <input type="text" name="nachname" maxlength="50">
<?php } ?>
<?php if ($showAdresse) { ?>
    <label><?= h(tf('tf_tipp_strasse')) ?></label>
    <input type="text" name="strasse" maxlength="100">
    <label><?= h(tf('tf_tipp_plz')) ?></label>
    <input type="text" name="plz" maxlength="10">
    <label><?= h(tf('tf_tipp_ort')) ?></label>
    <input type="text" name="ort" maxlength="80">
<?php } ?>
    <button type="submit" class="btn"><?= h(tf('tf_tipp_registrieren')) ?></button>
  </form>
<?php }

// ═══════════════════════════════════════════════════════════════════════
// E-MAIL-BESTÄTIGUNG
// ═══════════════════════════════════════════════════════════════════════
} elseif ($action === 'confirm') {
    $ok = tippConfirmEmail($_GET['code'] ?? '');
    ?>
  <h1><?= h(tf('tf_tipp_bestaetigung_titel')) ?></h1>
  <div class="flash <?= $ok ? 'success' : 'error' ?>"><?= h(tf($ok ? 'tf_tipp_bestaetigung_erfolg' : 'tf_tipp_bestaetigung_fehler')) ?></div>
  <p><a href="?action=login"><?= h(tf('tf_tipp_zum_login')) ?></a></p>
<?php

// ═══════════════════════════════════════════════════════════════════════
// LOGOUT
// ═══════════════════════════════════════════════════════════════════════
} elseif ($action === 'logout') {
    tippLogout();
    redirectTo('?action=login');

// ═══════════════════════════════════════════════════════════════════════
// TIPPABGABE (Ligenweise, Standardansicht)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($action === 'save') {
    tippRequireLogin();
    $tipper = tippCurrentUser();
    $ligaId = (int)($_POST['liga_id'] ?? 0);
    $spieltagNr = (int)($_POST['spieltag'] ?? 1);
    $liga = getLigaById($ligaId);
    if ($liga !== null && $tipper !== null) {
        $spieltage = getAllSpieltage($ligaId);
        $spieltag = getSpieltagByNummer($spieltage, $spieltagNr);
        if ($spieltag !== null) {
            $partien = getSpieltagPartien((int)$spieltag['id']);
            foreach ($partien as &$p) { $p['spieltag_start'] = $spieltag['start'] ?? null; }
            unset($p);
            $eingaben = [];
            foreach ($partien as $p) {
                $pid = (int)$p['id'];
                $eingaben[$pid] = [
                    'heim'  => $_POST['heim_' . $pid] ?? null,
                    'gast'  => $_POST['gast_' . $pid] ?? null,
                    'joker' => isset($_POST['joker_' . $pid]),
                ];
            }
            tippSaveAbgabe((int)$tipper['id'], $partien, $eingaben);
        }
    }
    redirectTo('?action=abgabe&liga=' . $ligaId . '&spieltag=' . $spieltagNr);

} else {
    // ── Tippabgabe-Ansicht ─────────────────────────────────────────────
    tippRequireLogin();
    $tipper = tippCurrentUser();

    $ligaIds = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();

    if (empty($ligaIds)) {
        echo '<h1>' . h(tf('tf_tipp_abgabe_titel')) . '</h1>';
        echo '<p>' . h(tf('tf_tipp_keine_ligen')) . '</p>';
    } else {
        $ligaId = (int)($_GET['liga'] ?? $ligaIds[0]);
        if (!in_array($ligaId, $ligaIds, true)) {
            $ligaId = $ligaIds[0];
        }
        $liga = getLigaById($ligaId);
        $spieltage = getAllSpieltage($ligaId);
        $maxNr = getMaxSpieltagNummer($spieltage);
        $spieltagNr = (int)($_GET['spieltag'] ?? 1);
        if ($spieltagNr < 1) { $spieltagNr = 1; }
        if ($spieltagNr > $maxNr) { $spieltagNr = $maxNr; }
        $spieltag = getSpieltagByNummer($spieltage, $spieltagNr);
        ?>
  <h1><?= h(tf('tf_tipp_abgabe_titel')) ?> — <?= h($liga['name'] ?? '') ?></h1>
  <p style="font-size:.85rem"><?= h(tf('tf_tipp_eingeloggt_als')) ?> <strong><?= h($tipper['nickname']) ?></strong> — <a href="?action=logout"><?= h(tf('tf_tipp_logout')) ?></a></p>

<?php if (count($ligaIds) > 1) { ?>
  <div style="margin-bottom:10px">
<?php foreach ($ligaIds as $lid) {
    $l = getLigaById($lid); ?>
    <a href="?action=abgabe&liga=<?= $lid ?>" style="margin-right:10px;<?= $lid === $ligaId ? 'font-weight:700' : '' ?>"><?= h($l['name'] ?? '') ?></a>
<?php } ?>
  </div>
<?php } ?>

  <div style="margin-bottom:10px">
<?php for ($n = 1; $n <= $maxNr; $n++) { ?>
    <a href="?action=abgabe&liga=<?= $ligaId ?>&spieltag=<?= $n ?>" style="margin-right:6px;font-size:.82rem;<?= $n === $spieltagNr ? 'font-weight:700' : '' ?>"><?= $n ?></a>
<?php } ?>
  </div>

<?php if ($spieltag === null) { ?>
  <p><?= h(tf('tf_tipp_kein_spieltag')) ?></p>
<?php } else {
        $partien = getSpieltagPartien((int)$spieltag['id']);
        $abgaben = tippGetAbgabeFuerPartien((int)$tipper['id'], array_map(fn($p) => (int)$p['id'], $partien));
        $jokerZulassen = getTippSetting('joker_zulassen', '1') === '1';
        ?>
  <form method="post" action="?action=save">
    <input type="hidden" name="liga_id" value="<?= $ligaId ?>">
    <input type="hidden" name="spieltag" value="<?= $spieltagNr ?>">
    <table>
      <tr>
        <th><?= h(tf('tf_tipp_col_termin')) ?></th>
        <th><?= h(tf('tf_tipp_col_spiel')) ?></th>
        <th><?= h(tf('tf_tipp_col_tipp')) ?></th>
<?php if ($jokerZulassen) { ?>
        <th><?= h(tf('tf_tipp_col_joker')) ?></th>
<?php } ?>
        <th><?= h(tf('tf_tipp_col_ergebnis')) ?></th>
        <th><?= h(tf('tf_tipp_col_punkte')) ?></th>
      </tr>
<?php foreach ($partien as $p) {
        $pid = (int)$p['id'];
        $heimName = $p['heim_name'] ?? $p['heim_label'] ?? '';
        $gastName = $p['gast_name'] ?? $p['gast_label'] ?? '';
        $abgabe = $abgaben[$pid] ?? null;
        $aenderbar = tippIstAenderbar($p['zeit'] ?? null, $spieltag['start'] ?? null);
        $ergHeim = $p['h_tore'] !== null ? (int)$p['h_tore'] : null;
        $ergGast = $p['g_tore'] !== null ? (int)$p['g_tore'] : null;
        $punkte = ($abgabe !== null) ? calculateTippPunkte((int)$abgabe['tipp_heim'], (int)$abgabe['tipp_gast'], $ergHeim, $ergGast, (bool)$abgabe['ist_joker']) : null;
        ?>
      <tr>
        <td class="spielinfo"><?= $p['zeit'] ? h(date('d.m. H:i', strtotime($p['zeit']))) : '' ?></td>
        <td><?= h($heimName) ?> - <?= h($gastName) ?></td>
        <td>
<?php if ($aenderbar) { ?>
          <input type="number" name="heim_<?= $pid ?>" min="0" max="99" value="<?= $abgabe !== null ? (int)$abgabe['tipp_heim'] : '' ?>">
          :
          <input type="number" name="gast_<?= $pid ?>" min="0" max="99" value="<?= $abgabe !== null ? (int)$abgabe['tipp_gast'] : '' ?>">
<?php } else { ?>
          <span class="readonly"><?= $abgabe !== null ? (int)$abgabe['tipp_heim'] . ' : ' . (int)$abgabe['tipp_gast'] : '–' ?></span>
<?php } ?>
        </td>
<?php if ($jokerZulassen) { ?>
        <td><input type="checkbox" name="joker_<?= $pid ?>" value="1"<?= ($abgabe !== null && (int)$abgabe['ist_joker'] === 1) ? ' checked' : '' ?><?= $aenderbar ? '' : ' disabled' ?>></td>
<?php } ?>
        <td><?= $ergHeim !== null ? (int)$ergHeim . ':' . (int)$ergGast : '–' ?></td>
        <td><?= $punkte !== null ? $punkte : '–' ?></td>
      </tr>
<?php } ?>
    </table>
    <button type="submit" class="btn"><?= h(tf('tf_tipp_speichern')) ?></button>
  </form>
<?php }
    }
}
?>
</div>
</body>
</html>
