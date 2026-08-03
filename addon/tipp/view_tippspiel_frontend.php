<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/view_tippspiel_frontend.php
 * Fileversion: 1.0.0
 * Changelog: 1.0.0 - Initiale Version: löst die bisherige eigenständige addon/tipp/tipp.php ab
 *                     (eigenes <html>/<head>/CSS) und bindet das Tippspiel stattdessen als View
 *                     ins bestehende Template-System ein - analog zur Spielerstatistik
 *                     (renderSpielerstatistikView() in addon/player/frontend_spielerstat.php),
 *                     erreichbar über home.php?view=tippspiel&action=... Läuft dadurch
 *                     automatisch im vom Besucher/Betreiber gewählten Template (default,
 *                     colored, dark, light, matchday) statt in einem eigenen, unveränderlichen
 *                     Design. tippspielHandleRequest() übernimmt die Rolle von "Phase 1" aus der
 *                     alten tipp.php (POST-/Redirect-Verarbeitung vor jeder HTML-Ausgabe, siehe
 *                     dortiger Changelog 0.3.0/0.3.1) - muss von home.php VOR renderTemplate()
 *                     aufgerufen werden. Die gesamte Geschäftslogik (tippLogin(), tippRegister(),
 *                     tippSaveAbgabe(), tippGetEinsichtDaten(), tippGetRangliste(),
 *                     calculateTippPunkte() usw.) bleibt unverändert in frontend_tipp.php -
 *                     diese Datei ist reine Präsentation.
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

require_once __DIR__ . '/frontend_tipp.php';

// ── URL-Hilfsfunktion ────────────────────────────────────────────────────────

/**
 * Baut eine Tippspiel-URL relativ zu home.php (?view=tippspiel&action=...).
 * Zentrale Stelle für das URL-Schema, damit es bei Bedarf an einer einzigen
 * Stelle geändert werden kann.
 */
function tippUrl(string $action, array $params = []) : string
{
    $qs = 'view=tippspiel&action=' . rawurlencode($action);
    foreach ($params as $k => $v) {
        $qs .= '&' . rawurlencode($k) . '=' . rawurlencode((string)$v);
    }
    return '?' . $qs;
}

// ── Phase 1: Aktions-/Redirect-Verarbeitung ─────────────────────────────────

/**
 * Muss von home.php aufgerufen werden, BEVOR irgendeine HTML-Ausgabe passiert
 * (also vor renderTemplate()) - analog zu admin.php ("POST-Handler laufen vor
 * HTML-Ausgabe") und der alten tipp.php Phase 1. Verarbeitet Login/Logout/
 * Speichern inkl. aller Redirects (header() schlägt sonst mit "headers
 * already sent" fehl, siehe frontend_tipp.php redirectTo()) und stellt für
 * eingeloggt-erforderliche Ansichten sicher, dass ein Tipper eingeloggt ist.
 *
 * @return array{action:string,flashMsg:?string,flashType:string,tipper:?array}
 */
function tippspielHandleRequest() : array
{
    $action    = $_GET['action'] ?? 'abgabe';
    $flashMsg  = null;
    $flashType = 'success';

    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = tippLogin($_POST['nickname'] ?? '', $_POST['password'] ?? '');
        if ($result['ok']) {
            redirectTo('?action=abgabe');
        }
        $flashMsg  = tf($result['error']);
        $flashType = 'error';
    }

    if ($action === 'logout') {
        tippLogout();
        redirectTo('?action=login');
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        tippRequireLogin();
        $tipper     = tippCurrentUser();
        $ligaId     = (int)($_POST['liga_id'] ?? 0);
        $spieltagNr = (int)($_POST['spieltag'] ?? 1);
        $liga       = getLigaById($ligaId);
        if ($liga !== null && $tipper !== null) {
            $spieltage = getAllSpieltage($ligaId);
            $spieltag  = getSpieltagByNummer($spieltage, $spieltagNr);
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
    }

    // Alle übrigen Ansichten außer login/register/confirm setzen einen
    // eingeloggten Tipper voraus - der Redirect zu ?action=login (falls
    // nicht eingeloggt) muss ebenfalls hier passieren, VOR der HTML-Ausgabe.
    if (!in_array($action, ['login', 'register', 'confirm'], true)) {
        tippRequireLogin();
    }

    return [
        'action'    => $action,
        'flashMsg'  => $flashMsg,
        'flashType' => $flashType,
        'tipper'    => tippCurrentUser(),
    ];
}

// ── Tab-Leiste ───────────────────────────────────────────────────────────────

/**
 * Reiter-Navigation für die drei eingeloggt-erreichbaren Ansichten - baut
 * bewusst direkt HTML statt den liga-spezifischen tab_item-Partial zu
 * nutzen (der fest auf "liga.php?id=..." verlinkt), nutzt aber dieselben
 * CSS-Klassen (.tabs-bar/.tab-item/.tab-item-active), die in jedem Template
 * ohnehin schon für liga.php vorhanden sind.
 */
function renderTippspielTabsBar(string $currentAction) : string
{
    $tabs = [
        'abgabe'    => tf('tf_tipp_tab_abgabe'),
        'einsicht'  => tf('tf_tipp_tab_einsicht'),
        'rangliste' => tf('tf_tipp_tab_rangliste'),
    ];
    $html = '<div class="tabs-bar">';
    foreach ($tabs as $key => $label) {
        $activeClass = $key === $currentAction ? ' tab-item-active' : '';
        $html .= '<a class="tab-item' . $activeClass . '" href="' . h(tippUrl($key)) . '">' . h($label) . '</a>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Statuszeile "Eingeloggt als X — Logout", über den Tabs bei allen
 * eingeloggt-erforderlichen Ansichten.
 */
function renderTippspielUserBar(array $tipper) : string
{
    return '<p style="font-size:.85rem;color:var(--muted);margin-bottom:12px">'
         . h(tf('tf_tipp_eingeloggt_als')) . ' <strong>' . h($tipper['nickname']) . '</strong>'
         . ' — <a href="' . h(tippUrl('logout')) . '">' . h(tf('tf_tipp_logout')) . '</a></p>';
}

// ── Hauptdispatcher ──────────────────────────────────────────────────────────

/**
 * Baut den kompletten Seiteninhalt (ViewInhalt-Platzhalter in
 * tippspiel.tpl.php) für die aktuelle Aktion. Wird von home.php NACH
 * tippspielHandleRequest() aufgerufen (Phase 2 - hier ist ein Redirect nicht
 * mehr möglich/nötig).
 */
function renderTippspielView(array $state) : string
{
    return match ($state['action']) {
        'login'     => renderTippLoginView($state),
        'register'  => renderTippRegisterView(),
        'confirm'   => renderTippConfirmView(),
        'einsicht'  => renderTippEinsichtView($state),
        'rangliste' => renderTippRanglisteView($state),
        default     => renderTippAbgabeView($state),
    };
}

// ── LOGIN ────────────────────────────────────────────────────────────────────

function renderTippLoginView(array $state) : string
{
    ob_start();
    ?>
<?php if ($state['flashMsg']) { ?>
  <div class="flash flash-<?= h($state['flashType']) ?>"><?= h($state['flashMsg']) ?></div>
<?php } ?>
  <div class="card">
  <form class="tipp-form" method="post" action="<?= h(tippUrl('login')) ?>">
    <label><?= h(tf('tf_tipp_nickname')) ?></label>
    <input type="text" name="nickname" required>
    <label><?= h(tf('tf_tipp_passwort')) ?></label>
    <input type="password" name="password" required>
    <button type="submit" class="btn-primary"><?= h(tf('tf_tipp_einloggen')) ?></button>
  </form>
  <p style="margin-top:16px;font-size:.85rem"><?= h(tf('tf_tipp_noch_kein_konto')) ?> <a href="<?= h(tippUrl('register')) ?>"><?= h(tf('tf_tipp_registrieren')) ?></a></p>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── REGISTRIEREN ─────────────────────────────────────────────────────────────

function renderTippRegisterView() : string
{
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

    ob_start();
    if ($result !== null && $result['ok']) {
        ?>
  <div class="card">
  <div class="flash flash-success">
<?php if ($result['modus'] === 'sofort') { ?>
    <?= h(tf('tf_tipp_registrierung_sofort')) ?>
<?php } elseif ($result['modus'] === 'email') { ?>
    <?= h(tf('tf_tipp_registrierung_email')) ?>
<?php } else { ?>
    <?= h(tf('tf_tipp_registrierung_admin')) ?>
<?php } ?>
  </div>
  <p><a href="<?= h(tippUrl('login')) ?>"><?= h(tf('tf_tipp_zum_login')) ?></a></p>
  </div>
        <?php
    } else { ?>
  <div class="card">
<?php if ($result !== null && !$result['ok']) { ?>
  <div class="flash flash-error"><?= h(tf($result['error'])) ?></div>
<?php } ?>
  <form class="tipp-form" method="post" action="<?= h(tippUrl('register')) ?>">
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
    <button type="submit" class="btn-primary"><?= h(tf('tf_tipp_registrieren')) ?></button>
  </form>
  </div>
    <?php }
    return (string)ob_get_clean();
}

// ── E-MAIL-BESTÄTIGUNG ───────────────────────────────────────────────────────

function renderTippConfirmView() : string
{
    $ok = tippConfirmEmail($_GET['code'] ?? '');
    ob_start();
    ?>
  <div class="card">
  <div class="flash flash-<?= $ok ? 'success' : 'error' ?>"><?= h(tf($ok ? 'tf_tipp_bestaetigung_erfolg' : 'tf_tipp_bestaetigung_fehler')) ?></div>
  <p><a href="<?= h(tippUrl('login')) ?>"><?= h(tf('tf_tipp_zum_login')) ?></a></p>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── TIPPABGABE ───────────────────────────────────────────────────────────────

function renderTippAbgabeView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIds = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();

    ob_start();
    echo renderTippspielUserBar($tipper);
    echo renderTippspielTabsBar('abgabe');

    if (empty($ligaIds)) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        return (string)ob_get_clean();
    }

    $ligaId = (int)($_GET['liga'] ?? $ligaIds[0]);
    if (!in_array($ligaId, $ligaIds, true)) {
        $ligaId = $ligaIds[0];
    }
    $liga        = getLigaById($ligaId);
    $spieltage   = getAllSpieltage($ligaId);
    $maxNr       = getMaxSpieltagNummer($spieltage);
    $spieltagNr  = (int)($_GET['spieltag'] ?? 1);
    if ($spieltagNr < 1) { $spieltagNr = 1; }
    if ($spieltagNr > $maxNr) { $spieltagNr = $maxNr; }
    $spieltag = getSpieltagByNummer($spieltage, $spieltagNr);
    ?>
  <div class="card">
  <h2><?= h($liga['name'] ?? '') ?></h2>
<?php if (count($ligaIds) > 1) { ?>
  <div style="margin-bottom:10px">
<?php foreach ($ligaIds as $lid) {
    $l = getLigaById($lid); ?>
    <a href="<?= h(tippUrl('abgabe', ['liga' => $lid])) ?>" style="margin-right:10px;<?= $lid === $ligaId ? 'font-weight:700' : '' ?>"><?= h($l['name'] ?? '') ?></a>
<?php } ?>
  </div>
<?php } ?>
  <div style="margin-bottom:10px">
<?php for ($n = 1; $n <= $maxNr; $n++) { ?>
    <a href="<?= h(tippUrl('abgabe', ['liga' => $ligaId, 'spieltag' => $n])) ?>" style="margin-right:6px;font-size:.82rem;<?= $n === $spieltagNr ? 'font-weight:700' : '' ?>"><?= $n ?></a>
<?php } ?>
  </div>
<?php if ($spieltag === null) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_kein_spieltag')) ?></p>
<?php } else {
        $partien = getSpieltagPartien((int)$spieltag['id']);
        $abgaben = tippGetAbgabeFuerPartien((int)$tipper['id'], array_map(fn($p) => (int)$p['id'], $partien));
        $jokerZulassen = getTippSetting('joker_zulassen', '1') === '1';
        ?>
  <form method="post" action="<?= h(tippUrl('save')) ?>">
    <input type="hidden" name="liga_id" value="<?= $ligaId ?>">
    <input type="hidden" name="spieltag" value="<?= $spieltagNr ?>">
    <table class="tipp-table">
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
        <td class="tipp-col-datum"><?= $p['zeit'] ? h(date('d.m. H:i', strtotime($p['zeit']))) : '' ?></td>
        <td><?= h($heimName) ?> - <?= h($gastName) ?></td>
        <td>
<?php if ($aenderbar) { ?>
          <input type="number" name="heim_<?= $pid ?>" min="0" max="99" value="<?= $abgabe !== null ? (int)$abgabe['tipp_heim'] : '' ?>">
          :
          <input type="number" name="gast_<?= $pid ?>" min="0" max="99" value="<?= $abgabe !== null ? (int)$abgabe['tipp_gast'] : '' ?>">
<?php } else { ?>
          <span class="tipp-readonly"><?= $abgabe !== null ? (int)$abgabe['tipp_heim'] . ' : ' . (int)$abgabe['tipp_gast'] : '–' ?></span>
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
    <button type="submit" class="btn-primary"><?= h(tf('tf_tipp_speichern')) ?></button>
  </form>
<?php }
    ?>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── TIPPEINSICHT ─────────────────────────────────────────────────────────────

function renderTippEinsichtView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIds = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();

    ob_start();
    echo renderTippspielUserBar($tipper);
    echo renderTippspielTabsBar('einsicht');

    if (empty($ligaIds)) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        return (string)ob_get_clean();
    }

    $ligaId = (int)($_GET['liga'] ?? $ligaIds[0]);
    if (!in_array($ligaId, $ligaIds, true)) {
        $ligaId = $ligaIds[0];
    }
    $liga       = getLigaById($ligaId);
    $spieltage  = getAllSpieltage($ligaId);
    $maxNr      = getMaxSpieltagNummer($spieltage);
    $spieltagNr = (int)($_GET['spieltag'] ?? 1);
    if ($spieltagNr < 1) { $spieltagNr = 1; }
    if ($spieltagNr > $maxNr) { $spieltagNr = $maxNr; }
    $spieltag = getSpieltagByNummer($spieltage, $spieltagNr);
    ?>
  <div class="card">
  <h2><?= h($liga['name'] ?? '') ?></h2>
  <div style="margin-bottom:10px">
<?php for ($n = 1; $n <= $maxNr; $n++) { ?>
    <a href="<?= h(tippUrl('einsicht', ['liga' => $ligaId, 'spieltag' => $n])) ?>" style="margin-right:6px;font-size:.82rem;<?= $n === $spieltagNr ? 'font-weight:700' : '' ?>"><?= $n ?></a>
<?php } ?>
  </div>
<?php if ($spieltag === null) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_kein_spieltag')) ?></p>
<?php } else {
        $partien = getSpieltagPartien((int)$spieltag['id']);
        foreach ($partien as &$p) { $p['spieltag_start'] = $spieltag['start'] ?? null; }
        unset($p);
        $einsicht = tippGetEinsichtDaten($partien);
        foreach ($partien as $p) {
            $pid = (int)$p['id'];
            $heimName = $p['heim_name'] ?? $p['heim_label'] ?? '';
            $gastName = $p['gast_name'] ?? $p['gast_label'] ?? '';
            ?>
  <h3 style="font-size:.9rem;margin:16px 0 4px"><?= h($heimName) ?> - <?= h($gastName) ?></h3>
<?php if (!isset($einsicht[$pid])) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_einsicht_noch_nicht_sichtbar')) ?></p>
<?php } else { ?>
  <table class="tipp-table">
    <tr><th><?= h(tf('tf_tipp_col_nickname')) ?></th><th><?= h(tf('tf_tipp_col_tipp')) ?></th><th><?= h(tf('tf_tipp_col_joker')) ?></th></tr>
<?php foreach ($einsicht[$pid] as $row) {
    $istIch = (int)$row['tipper_id'] === (int)$tipper['id']; ?>
    <tr<?= $istIch ? ' class="tipp-row-me"' : '' ?>>
      <td><?= h($row['nickname']) ?></td>
      <td><?= (int)$row['tipp_heim'] ?>:<?= (int)$row['tipp_gast'] ?></td>
      <td><?= (int)$row['ist_joker'] === 1 ? '🃏' : '' ?></td>
    </tr>
<?php } ?>
  </table>
<?php } ?>
<?php } } ?>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── RANGLISTE ────────────────────────────────────────────────────────────────

function renderTippRanglisteView(array $state) : string
{
    $tipper    = $state['tipper'];
    $rangliste = tippGetRangliste();

    ob_start();
    echo renderTippspielUserBar($tipper);
    echo renderTippspielTabsBar('rangliste');
    ?>
  <div class="card">
<?php if (empty($rangliste)) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_rangliste_leer')) ?></p>
<?php } else { ?>
  <table class="tipp-table">
    <tr>
      <th><?= h(tf('tf_tipp_col_platz')) ?></th>
      <th><?= h(tf('tf_tipp_col_nickname')) ?></th>
      <th><?= h(tf('tf_tipp_col_punkte')) ?></th>
      <th><?= h(tf('tf_tipp_col_spiele_getippt')) ?></th>
      <th><?= h(tf('tf_tipp_col_quote')) ?></th>
      <th><?= h(tf('tf_tipp_col_spieltagssiege')) ?></th>
    </tr>
<?php
    $platz = 0; $vorherigePunkte = null; $angezeigterPlatz = 0;
    foreach ($rangliste as $eintrag) {
        $platz++;
        if ($vorherigePunkte === null || $eintrag['punkte'] !== $vorherigePunkte) {
            $angezeigterPlatz = $platz;
        }
        $vorherigePunkte = $eintrag['punkte'];
        $istIch = $tipper && (int)$eintrag['tipper_id'] === (int)$tipper['id'];
    ?>
    <tr<?= $istIch ? ' class="tipp-row-me"' : '' ?>>
      <td><?= $angezeigterPlatz ?>.</td>
      <td><?= h($eintrag['nickname']) ?></td>
      <td><?= $eintrag['punkte'] ?></td>
      <td><?= $eintrag['spiele_getippt'] ?></td>
      <td><?= $eintrag['ausgewertete_spiele'] > 0 ? round($eintrag['quote'] * 100) . '%' : '—' ?></td>
      <td><?= $eintrag['spieltagswertungen'] ?></td>
    </tr>
<?php } ?>
  </table>
<?php } ?>
  </div>
    <?php
    return (string)ob_get_clean();
}
