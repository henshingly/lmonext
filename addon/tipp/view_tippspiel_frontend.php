<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/view_tippspiel_frontend.php
 * Fileversion: 1.19.1
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

    if ($action === 'konto' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        tippRequireLogin();
        $tipper = tippCurrentUser();
        if ($tipper !== null) {
            $pw1 = $_POST['password'] ?? '';
            $pw2 = $_POST['password_wdh'] ?? '';
            if ($pw1 !== '' || $pw2 !== '') {
                if ($pw1 !== $pw2) {
                    $flashMsg = tf('tf_tipp_err_passwort_mismatch');
                    $flashType = 'error';
                } elseif (strlen($pw1) < 6) {
                    $flashMsg = tf('tf_tipp_err_passwort_kurz');
                    $flashType = 'error';
                }
            }
            if ($flashMsg === null) {
                $teamId = null;
                $neuesTeam = trim($_POST['neues_team'] ?? '');
                if ($neuesTeam !== '') {
                    $teamId = createTippTeam($neuesTeam, (int)$tipper['id']);
                } elseif (!empty($_POST['team_id'])) {
                    $teamId = (int)$_POST['team_id'];
                }
                $ok = tippUpdateOwnAccount((int)$tipper['id'], [
                    'email'    => trim($_POST['email'] ?? ''),
                    'vorname'  => trim($_POST['vorname'] ?? '') ?: null,
                    'nachname' => trim($_POST['nachname'] ?? '') ?: null,
                    'strasse'  => trim($_POST['strasse'] ?? '') ?: null,
                    'plz'      => trim($_POST['plz'] ?? '') ?: null,
                    'ort'      => trim($_POST['ort'] ?? '') ?: null,
                    'team_id'  => $teamId,
                    'newsletter' => isset($_POST['newsletter']) ? 1 : 0,
                    'reminder'   => isset($_POST['reminder']) ? 1 : 0,
                ], $pw1 !== '' ? $pw1 : null);

                $ligaIds = array_map('intval', $_POST['abo'] ?? []);
                setTipperAbos((int)$tipper['id'], $ligaIds);

                $flashMsg  = tf($ok ? 'tf_tipp_konto_gespeichert' : 'tf_tipp_err_speichern');
                $flashType = $ok ? 'success' : 'error';
            }
        }
    }

    if ($action === 'passwort_vergessen' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = tippRequestPasswordReset($_POST['nickname'] ?? '', $_POST['email'] ?? '');
        if ($result['ok']) {
            $flashMsg  = tf('tf_tipp_reset_mail_verschickt');
            $flashType = 'success';
        } else {
            $flashMsg  = tf(match ($result['reason']) {
                'not_found_nickname' => 'tf_tipp_reset_nickname_nicht_gefunden',
                'not_found_email'    => 'tf_tipp_reset_email_nicht_gefunden',
                default               => 'tf_tipp_reset_beides_leer',
            });
            $flashType = 'error';
        }
    }

    if ($action === 'passwort_reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = $_POST['code'] ?? '';
        $pw1  = $_POST['password'] ?? '';
        $pw2  = $_POST['password_wdh'] ?? '';
        if ($pw1 !== $pw2) {
            $flashMsg  = tf('tf_tipp_err_passwort_mismatch');
            $flashType = 'error';
        } elseif (strlen($pw1) < 6) {
            $flashMsg  = tf('tf_tipp_err_passwort_kurz');
            $flashType = 'error';
        } elseif (tippResetPassword($code, $pw1)) {
            redirectTo('?action=login');
        } else {
            $flashMsg  = tf('tf_tipp_reset_ungueltig');
            $flashType = 'error';
        }
    }

    // Alle übrigen Ansichten außer login/register/confirm/passwort_vergessen/
    // passwort_reset/regeln setzen einen eingeloggten Tipper voraus - der
    // Redirect zu ?action=login (falls nicht eingeloggt) muss ebenfalls hier
    // passieren, VOR der HTML-Ausgabe. "regeln" (Spielregeln) enthält keine
    // persönlichen Daten und ist daher immer ohne Login erreichbar. Weitere
    // Ausnahme: "einsicht" (Tippeinsicht), wenn der Admin sie unter
    // Anzeigen/Darstellung öffentlich freigegeben hat
    // (anzeige_einsicht_oeffentlich=1) - dann ist sie auch ohne Login
    // erreichbar, siehe renderTippEinsichtView().
    $einsichtOeffentlich = in_array($action, ['einsicht', 'gesamt', 'gesamt_tipper', 'statistik', 'einsicht_tipper', 'tipptabelle'], true) && getTippSetting('anzeige_einsicht_oeffentlich', '0') === '1';
    if (!$einsichtOeffentlich && !in_array($action, ['login', 'register', 'confirm', 'passwort_vergessen', 'passwort_reset', 'regeln'], true)) {
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
/**
 * Baut die Tab-Leiste (Tippabgabe/Tippeinsicht/Rangliste/Gesamtübersicht).
 * $ligaId (falls gesetzt) wird als "&liga=X" an die liga-bezogenen Tabs
 * (abgabe/einsicht/gesamt) angehängt, damit ein Wechsel zwischen den
 * Reitern bei der gerade angeschauten Liga bleibt statt immer auf die
 * erste Liga zurückzuspringen. Rangliste/Konto sind nicht liga-bezogen,
 * bekommen daher nie den Parameter.
 */
function renderTippspielTabsBar(string $currentAction, ?int $ligaId = null) : string
{
    $tabs = [
        'abgabe'    => tf('tf_tipp_tab_abgabe'),
        'einsicht'  => tf('tf_tipp_tab_einsicht'),
        'rangliste' => tf('tf_tipp_tab_rangliste'),
        'gesamt'    => tf('tf_tipp_tab_gesamt'),
        // "statistik" bewusst NICHT im Hauptmenü - erreichbar über den Link
        // unter der Tipper-Spieltagsansicht (renderTippEinsichtTipperView()).
    ];
    if (getTippSetting('anzeige_spielregeln', '0') === '1') {
        $tabs['regeln'] = tf('tf_tipp_tab_regeln');
    }
    $ligaAwareTabs = ['abgabe', 'einsicht', 'gesamt', 'statistik'];
    $html = '<div class="tabs-bar">';
    foreach ($tabs as $key => $label) {
        $activeClass = $key === $currentAction ? ' tab-item-active' : '';
        $params = ($ligaId !== null && in_array($key, $ligaAwareTabs, true)) ? ['liga' => $ligaId] : [];
        $html .= '<a class="tab-item' . $activeClass . '" href="' . h(tippUrl($key, $params)) . '">' . h($label) . '</a>';
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
         . ' — <a href="' . h(tippUrl('konto')) . '">' . h(tf('tf_tipp_konto_link')) . '</a>'
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
        'login'              => renderTippLoginView($state),
        'register'           => renderTippRegisterView(),
        'confirm'            => renderTippConfirmView(),
        'passwort_vergessen' => renderTippPasswortVergessenView($state),
        'passwort_reset'     => renderTippPasswortResetView($state),
        'konto'              => renderTippKontoView($state),
        'einsicht'           => renderTippEinsichtView($state),
        'rangliste'          => renderTippRanglisteView($state),
        'gesamt'             => renderTippGesamtuebersichtView($state),
        'gesamt_tipper'      => renderTippGesamtuebersichtTipperView($state),
        'regeln'             => renderTippSpielregelnView($state),
        'statistik'          => renderTippStatistikView($state),
        'einsicht_tipper'    => renderTippEinsichtTipperView($state),
        'tipptabelle'        => renderTippTipptabelleView($state),
        default              => renderTippAbgabeView($state),
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
  <p style="margin-top:6px;font-size:.85rem"><a href="<?= h(tippUrl('passwort_vergessen')) ?>"><?= h(tf('tf_tipp_passwort_vergessen_link')) ?></a></p>
<?php if (getTippSetting('anzeige_einsicht_oeffentlich', '0') === '1') { ?>
  <p style="margin-top:6px;font-size:.85rem"><a href="<?= h(tippUrl('einsicht')) ?>"><?= h(tf('tf_tipp_einsicht_ohne_login_link')) ?></a></p>
<?php } ?>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── REGISTRIEREN ─────────────────────────────────────────────────────────────

function renderTippRegisterView() : string
{
    $showAdresse  = getTippSetting('anmeldung_adresse_abfragen') === '1';
    $showRealname = getTippSetting('anmeldung_realname_abfragen') === '1';
    $ligaIds = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = tippRegister(
            $_POST['nickname'] ?? '', $_POST['email'] ?? '',
            $_POST['password'] ?? '', $_POST['password_wdh'] ?? '',
            ['vorname' => trim($_POST['vorname'] ?? '') ?: null, 'nachname' => trim($_POST['nachname'] ?? '') ?: null]
        );
        if ($result['ok']) {
            $neuerTipper = getTipperByNickname($_POST['nickname'] ?? '');
            if ($neuerTipper !== null) {
                $abo = array_map('intval', $_POST['abo'] ?? []);
                setTipperAbos((int)$neuerTipper['id'], $abo);
            }
        }
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
<?php if (!empty($ligaIds)) { ?>
    <label style="margin-top:16px"><?= h(tf('tf_tipp_abonnierte_ligen')) ?></label>
<?php foreach ($ligaIds as $lid) {
    $l = getLigaById($lid); ?>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400;color:var(--text)">
      <input type="checkbox" name="abo[]" value="<?= $lid ?>" style="margin:0">
      <?= h($l['name'] ?? '') ?>
    </label>
<?php } ?>
<?php } ?>
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

// ── PASSWORT VERGESSEN ──────────────────────────────────────────────────────

function renderTippPasswortVergessenView(array $state) : string
{
    ob_start();
    ?>
<?php if ($state['flashMsg']) { ?>
  <div class="flash flash-<?= h($state['flashType']) ?>"><?= h($state['flashMsg']) ?></div>
<?php } ?>
  <div class="card">
  <form class="tipp-form" method="post" action="<?= h(tippUrl('passwort_vergessen')) ?>">
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:4px"><?= h(tf('tf_tipp_reset_hinweis')) ?></p>
    <label><?= h(tf('tf_tipp_nickname')) ?></label>
    <input type="text" name="nickname" maxlength="50">
    <label><?= h(tf('tf_tipp_email')) ?></label>
    <input type="email" name="email">
    <button type="submit" class="btn-primary"><?= h(tf('tf_tipp_reset_anfordern')) ?></button>
  </form>
  <p style="margin-top:16px;font-size:.85rem"><a href="<?= h(tippUrl('login')) ?>"><?= h(tf('tf_tipp_zum_login')) ?></a></p>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── PASSWORT ZURÜCKSETZEN ────────────────────────────────────────────────────

function renderTippPasswortResetView(array $state) : string
{
    $code = $_GET['code'] ?? ($_POST['code'] ?? '');
    ob_start();
    ?>
<?php if ($state['flashMsg']) { ?>
  <div class="flash flash-<?= h($state['flashType']) ?>"><?= h($state['flashMsg']) ?></div>
<?php } ?>
  <div class="card">
  <form class="tipp-form" method="post" action="<?= h(tippUrl('passwort_reset')) ?>">
    <input type="hidden" name="code" value="<?= h($code) ?>">
    <label><?= h(tf('tf_tipp_neues_passwort')) ?></label>
    <input type="password" name="password" required minlength="6">
    <label><?= h(tf('tf_tipp_passwort_wdh')) ?></label>
    <input type="password" name="password_wdh" required minlength="6">
    <button type="submit" class="btn-primary"><?= h(tf('tf_tipp_passwort_setzen')) ?></button>
  </form>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── KONTO (Self-Service, Nickname bewusst nicht editierbar) ────────────────

function renderTippKontoView(array $state) : string
{
    $tipper = tippCurrentUser(); // frisch nachladen (nicht $state['tipper'], das kann vor dem Speichern geladen sein)
    $teams  = getAllTeamsWithCount();
    $ligaIds = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $aboIds = getTipperAboLigaIds((int)$tipper['id']);

    ob_start();
    echo renderTippspielUserBar($tipper);
    echo renderTippspielTabsBar('konto');
    ?>
<?php if ($state['flashMsg']) { ?>
  <div class="flash flash-<?= h($state['flashType']) ?>"><?= h($state['flashMsg']) ?></div>
<?php } ?>
  <div class="card">
  <form class="tipp-form" method="post" action="<?= h(tippUrl('konto')) ?>" style="max-width:460px">
    <label><?= h(tf('tf_tipp_nickname')) ?></label>
    <p style="font-weight:700;margin:2px 0 0"><?= h($tipper['nickname']) ?></p>

    <label><?= h(tf('tf_tipp_email')) ?></label>
    <input type="email" name="email" maxlength="150" required value="<?= h($tipper['email']) ?>">

    <label><?= h(tf('tf_tipp_neues_passwort')) ?> (<?= h(tf('tf_tipp_leer_lassen_hinweis')) ?>)</label>
    <input type="password" name="password" minlength="6">
    <label><?= h(tf('tf_tipp_passwort_wdh')) ?></label>
    <input type="password" name="password_wdh" minlength="6">

    <label><?= h(tf('tf_tipp_vorname')) ?></label>
    <input type="text" name="vorname" maxlength="50" value="<?= h($tipper['vorname'] ?? '') ?>">
    <label><?= h(tf('tf_tipp_nachname')) ?></label>
    <input type="text" name="nachname" maxlength="50" value="<?= h($tipper['nachname'] ?? '') ?>">
    <label><?= h(tf('tf_tipp_strasse')) ?></label>
    <input type="text" name="strasse" maxlength="100" value="<?= h($tipper['strasse'] ?? '') ?>">
    <label><?= h(tf('tf_tipp_plz')) ?></label>
    <input type="text" name="plz" maxlength="10" value="<?= h($tipper['plz'] ?? '') ?>">
    <label><?= h(tf('tf_tipp_ort')) ?></label>
    <input type="text" name="ort" maxlength="80" value="<?= h($tipper['ort'] ?? '') ?>">

    <label><?= h(tf('tf_tipp_team')) ?></label>
    <select name="team_id" style="width:100%;box-sizing:border-box;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.9rem">
      <option value=""><?= h(tf('tf_tipp_kein_team')) ?></option>
<?php foreach ($teams as $t) { ?>
      <option value="<?= (int)$t['id'] ?>"<?= (int)($tipper['team_id'] ?? 0) === (int)$t['id'] ? ' selected' : '' ?>><?= h($t['name']) ?> (<?= (int)$t['mitglieder'] ?>)</option>
<?php } ?>
    </select>
    <label><?= h(tf('tf_tipp_neues_team_gruenden')) ?></label>
    <input type="text" name="neues_team" maxlength="50" placeholder="<?= h(tf('tf_tipp_neues_team_platzhalter')) ?>">

    <label style="display:flex;align-items:center;gap:8px;margin-top:16px">
      <input type="checkbox" name="newsletter" value="1"<?= (int)($tipper['newsletter'] ?? 1) === 1 ? ' checked' : '' ?> style="margin:0">
      <?= h(tf('tf_tipp_newsletter_erhalten')) ?>
    </label>
    <label style="display:flex;align-items:center;gap:8px">
      <input type="checkbox" name="reminder" value="1"<?= (int)($tipper['reminder'] ?? 1) === 1 ? ' checked' : '' ?> style="margin:0">
      <?= h(tf('tf_tipp_reminder_erhalten')) ?>
    </label>

<?php if (!empty($ligaIds)) { ?>
    <label style="margin-top:16px"><?= h(tf('tf_tipp_abonnierte_ligen')) ?></label>
<?php foreach ($ligaIds as $lid) {
    $l = getLigaById($lid); ?>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400;color:var(--text)">
      <input type="checkbox" name="abo[]" value="<?= $lid ?>"<?= in_array($lid, $aboIds, true) ? ' checked' : '' ?> style="margin:0">
      <?= h($l['name'] ?? '') ?>
    </label>
<?php } ?>
<?php } ?>

    <button type="submit" class="btn-primary"><?= h(tf('tf_tipp_speichern')) ?></button>
  </form>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── TIPPABGABE ───────────────────────────────────────────────────────────────

/**
 * Schränkt eine Liste tippbarer Liga-IDs auf die vom Tipper abonnierten
 * Ligen ein. Ohne jegliches Abo bleibt die Liste bewusst LEER - die
 * Registrierung fragt die Abos direkt mit ab (siehe renderTippRegisterView()),
 * daher sollte im Normalfall bei jedem Tipper mindestens ein Abo vorhanden
 * sein; ein Tipper ohne Abo soll gezielt zur Kontoseite geführt werden statt
 * standardmäßig alle Ligen angezeigt zu bekommen.
 *
 * @param array<int,int> $ligaIds
 * @return array<int,int>
 */
function tippFilterLigenByAbo(array $ligaIds, int $tipperId) : array
{
    $aboIds = getTipperAboLigaIds($tipperId);
    return array_values(array_intersect($ligaIds, $aboIds));
}

function renderTippAbgabeView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIdsAlle = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $ligaIds = tippFilterLigenByAbo($ligaIdsAlle, (int)$tipper['id']);
    $ligaIdFuerTabs = (int)($_GET['liga'] ?? ($ligaIds[0] ?? 0));

    ob_start();
    echo renderTippspielUserBar($tipper);
    echo renderTippspielTabsBar('abgabe', $ligaIdFuerTabs > 0 ? $ligaIdFuerTabs : null);

    if (empty($ligaIds)) {
        if (empty($ligaIdsAlle)) {
            echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        } else {
            echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_kein_abo'))
               . ' <a href="' . h(tippUrl('konto')) . '">' . h(tf('tf_tipp_konto_link')) . '</a></p></div>';
        }
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
      <thead>
      <tr>
        <th><?= h(tf('tf_tipp_col_termin')) ?></th>
        <th><?= h(tf('tf_tipp_col_spiel')) ?></th>
        <th><?= h(tf('tf_tipp_col_tipp')) ?></th>
<?php if ($jokerZulassen) { ?>
        <th><?= h(tf('tf_tipp_col_joker')) ?></th>
<?php } ?>
        <th style="text-align:center"><?= h(tf('tf_tipp_col_ergebnis')) ?></th>
        <th><?= h(tf('tf_tipp_col_punkte')) ?></th>
      </tr>
      </thead>
      <tbody>
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
        <td style="text-align:center"><?= $ergHeim !== null ? (int)$ergHeim . ':' . (int)$ergGast : '–' ?></td>
        <td><?= $punkte !== null ? $punkte : '–' ?></td>
      </tr>
<?php } ?>
      </tbody>
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
    $tipper = $state['tipper']; // kann null sein, wenn "Tippeinsicht öffentlich" aktiv ist (Admin → Tippspiel → Anzeigen/Darstellung)
    $ligaIdsAlle = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    // Die Liga-Abo-Filterung ist ein persönliches Konto-Merkmal und ergibt für
    // einen anonymen Besucher keinen Sinn - der sieht alle tippbaren Ligen.
    $ligaIds = $tipper !== null ? tippFilterLigenByAbo($ligaIdsAlle, (int)$tipper['id']) : $ligaIdsAlle;

    // Archivierte Ligen mit Tipp-Historie bleiben einsehbar (nur nicht mehr
    // tippbar) - siehe "Archivierte Tippsaisons"-Link weiter unten.
    $archivLigen = getArchivierteLigenMitTipps();
    $archivLigenIds = array_map(static fn($l) => (int)$l['id'], $archivLigen);
    $requestedLigaId = isset($_GET['liga']) ? (int)$_GET['liga'] : null;
    $istArchivLiga = $requestedLigaId !== null && in_array($requestedLigaId, $archivLigenIds, true);
    $zeigeArchivListe = isset($_GET['archiv']) && $requestedLigaId === null;

    $ligaIdFuerTabs = $requestedLigaId ?? ($ligaIds[0] ?? ($archivLigenIds[0] ?? 0));

    ob_start();
    if ($tipper !== null) {
        echo renderTippspielUserBar($tipper);
    } else {
        echo '<p style="font-size:.85rem;color:var(--muted);margin-bottom:12px">'
           . h(tf('tf_tipp_einsicht_oeffentlich_hinweis'))
           . ' <a href="' . h(tippUrl('login')) . '">' . h(tf('tf_tipp_zum_login')) . '</a></p>';
    }
    echo renderTippspielTabsBar('einsicht', $ligaIdFuerTabs > 0 ? $ligaIdFuerTabs : null);

    if ($zeigeArchivListe) {
        echo renderTippArchivLigenListe($archivLigen, 'einsicht');
        return (string)ob_get_clean();
    }

    if (empty($ligaIds) && !$istArchivLiga) {
        if (empty($ligaIdsAlle)) {
            echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        } else {
            echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_kein_abo'))
               . ' <a href="' . h(tippUrl('konto')) . '">' . h(tf('tf_tipp_konto_link')) . '</a></p></div>';
        }
        echo renderTippArchivLink($archivLigen, 'einsicht');
        return (string)ob_get_clean();
    }

    if ($istArchivLiga) {
        $ligaId = $requestedLigaId;
    } else {
        $ligaId = $requestedLigaId ?? $ligaIds[0];
        if (!in_array($ligaId, $ligaIds, true)) {
            $ligaId = $ligaIds[0];
        }
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
  <h2><?= h($liga['name'] ?? '') ?><?php if ($istArchivLiga) { ?> <span class="tipp-archiv-badge"><?= h(tf('tf_tipp_archiviert_badge')) ?></span><?php } ?></h2>
<?php if (!$istArchivLiga && count($ligaIds) > 1) { ?>
  <div style="margin-bottom:10px">
<?php foreach ($ligaIds as $lid) {
    $l = getLigaById($lid); ?>
    <a href="<?= h(tippUrl('einsicht', ['liga' => $lid])) ?>" style="margin-right:10px;<?= $lid === $ligaId ? 'font-weight:700' : '' ?>"><?= h($l['name'] ?? '') ?></a>
<?php } ?>
  </div>
<?php } ?>
  <div class="tipp-spieltag-nav" style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
<?php if ($spieltagNr > 1) { ?>
    <a href="<?= h(tippUrl('einsicht', ['liga' => $ligaId, 'spieltag' => $spieltagNr - 1])) ?>" aria-label="<?= h(tf('tf_tipp_voriger_spieltag')) ?>">&#9664;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9664;</span>
<?php } ?>
    <strong><?= h(tf('tf_tipp_spieltag_n', ['n' => $spieltagNr])) ?></strong>
<?php if ($spieltagNr < $maxNr) { ?>
    <a href="<?= h(tippUrl('einsicht', ['liga' => $ligaId, 'spieltag' => $spieltagNr + 1])) ?>" aria-label="<?= h(tf('tf_tipp_naechster_spieltag')) ?>">&#9654;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9654;</span>
<?php } ?>
  </div>
<?php if ($spieltag === null) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_kein_spieltag')) ?></p>
<?php } else {
        $matrix  = tippGetSpieltagMatrix($ligaId, $spieltage, $spieltagNr);
        $partien = $matrix['partien'];
        ?>
  <table class="tipp-table" style="margin-bottom:20px">
    <thead>
    <tr><th><?= h(tf('tf_tipp_col_termin')) ?></th><th style="text-align:right"><?= h(tf('tf_tipp_col_heim')) ?></th><th><?= h(tf('tf_tipp_col_gast')) ?></th><th style="text-align:center"><?= h(tf('tf_tipp_col_ergebnis')) ?></th></tr>
    </thead>
    <tbody>
<?php foreach ($partien as $p) {
    $heimName = $p['heim_name'] ?? $p['heim_label'] ?? '';
    $gastName = $p['gast_name'] ?? $p['gast_label'] ?? '';
    $erg = ($p['h_tore'] !== null && $p['g_tore'] !== null) ? ((int)$p['h_tore'] . ':' . (int)$p['g_tore']) : '-:-'; ?>
    <tr>
      <td><?= $p['zeit'] !== null ? h(date('d.m.y H:i', strtotime($p['zeit']))) : '' ?></td>
      <td style="text-align:right"><?= h($heimName) ?></td>
      <td><?= h($gastName) ?></td>
      <td style="text-align:center"><?= h($erg) ?></td>
    </tr>
<?php } ?>
    </tbody>
  </table>

<?php if (empty($matrix['rows'])) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_einsicht_noch_nicht_sichtbar')) ?></p>
<?php } else { ?>
  <div style="overflow-x:auto">
  <table class="tipp-matrix">
    <thead>
      <tr>
        <th colspan="3"></th>
<?php foreach ($partien as $p) { ?>
        <th class="tipp-matrix-team"><?= h($p['heim_kurz'] ?: mb_substr($p['heim_name'] ?? '', 0, 3)) ?><br><?= h($p['gast_kurz'] ?: mb_substr($p['gast_name'] ?? '', 0, 3)) ?></th>
<?php } ?>
        <th colspan="2"></th>
      </tr>
      <tr>
        <th colspan="3"></th>
<?php foreach ($partien as $p) {
    $erg = ($p['h_tore'] !== null && $p['g_tore'] !== null) ? ((int)$p['h_tore'] . ':' . (int)$p['g_tore']) : '-:-'; ?>
        <th class="tipp-matrix-erg"><?= h($erg) ?></th>
<?php } ?>
        <th colspan="2"></th>
      </tr>
      <tr>
        <th><?= h(tf('tf_tipp_col_pos')) ?></th>
        <th>+/-</th>
        <th><?= h(tf('tf_tipp_col_nickname')) ?></th>
<?php foreach ($partien as $p) { ?>
        <th></th>
<?php } ?>
        <th><?= h(tf('tf_tipp_col_p')) ?></th>
        <th><?= h(tf('tf_tipp_col_g')) ?></th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($matrix['rows'] as $row) {
    $istIch = $tipper !== null && (int)$row['tipper_id'] === (int)$tipper['id'];
    $delta = $row['rang_delta']; ?>
    <tr<?= $istIch ? ' class="tipp-row-me"' : '' ?>>
      <td><?= (int)$row['rang'] ?>.</td>
      <td class="<?= $delta > 0 ? 'tipp-rang-auf' : ($delta < 0 ? 'tipp-rang-ab' : '') ?>"><?= $delta > 0 ? '▲' . $delta : ($delta < 0 ? '▼' . abs($delta) : '') ?></td>
      <td><a href="<?= h(tippUrl('einsicht_tipper', ['liga' => $ligaId, 'tipper' => $row['tipper_id']])) ?>"><?= h($row['nickname']) ?></a></td>
<?php foreach ($partien as $p) {
    $pid = (int)$p['id'];
    $zelle = $row['zellen'][$pid] ?? null;
    if ($zelle === null) { ?>
      <td class="tipp-matrix-leer">-:-</td>
<?php   } else { ?>
      <td class="<?= $zelle['punkte'] > 0 ? 'tipp-matrix-treffer' : 'tipp-matrix-leer' ?>"><?= (int)$zelle['heim'] ?>:<?= (int)$zelle['gast'] ?><?php if ($zelle['punkte'] > 0) { ?><sub><?= (int)$zelle['punkte'] ?></sub><?php } ?><?= $zelle['joker'] ? ' 🃏' : '' ?></td>
<?php   } ?>
<?php } ?>
      <td><?= (int)$row['punkte_spieltag'] ?></td>
      <td><strong><?= (int)$row['punkte_aktuell'] ?></strong></td>
    </tr>
<?php } ?>
    </tbody>
  </table>
  </div>
<?php } } ?>
  </div>
<?= renderTippArchivLink($archivLigen, 'einsicht') ?>
    <?php
    return (string)ob_get_clean();
}

/**
 * Baut den Link "Archivierte Tippsaisons" unter Tippeinsicht/Gesamtübersicht
 * - erscheint nur, wenn es überhaupt archivierte Ligen mit Tipp-Historie
 * gibt (siehe getArchivierteLigenMitTipps()), sonst leerer String.
 */
function renderTippArchivLink(array $archivLigen, string $action) : string
{
    if (empty($archivLigen)) {
        return '';
    }
    return '<p style="margin-top:14px"><a href="' . h(tippUrl($action, ['archiv' => 1])) . '">'
         . h(tf('tf_tipp_archivierte_saisons')) . ' &rsaquo;</a></p>';
}

/**
 * Liste der archivierten Ligen mit Tipp-Historie zur Auswahl (Ziel des
 * "Archivierte Tippsaisons"-Links) - zeigt bewusst NUR Ligen mit
 * tatsächlicher Tipp-Historie (siehe getArchivierteLigenMitTipps()), keine
 * leeren archivierten Ligen.
 */
function renderTippArchivLigenListe(array $archivLigen, string $action) : string
{
    ob_start(); ?>
  <div class="card">
  <h2><?= h(tf('tf_tipp_archivierte_saisons')) ?></h2>
<?php if (empty($archivLigen)) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_keine_archivierten_ligen')) ?></p>
<?php } else { ?>
  <ul style="list-style:none;padding:0;margin:0">
<?php foreach ($archivLigen as $l) { ?>
    <li style="padding:6px 0;border-top:1px solid var(--border)">
      <a href="<?= h(tippUrl($action, ['liga' => (int)$l['id']])) ?>"><?= h($l['name']) ?></a>
    </li>
<?php } ?>
  </ul>
<?php } ?>
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
    <thead>
    <tr>
      <th><?= h(tf('tf_tipp_col_platz')) ?></th>
      <th><?= h(tf('tf_tipp_col_nickname')) ?></th>
      <th><?= h(tf('tf_tipp_col_punkte')) ?></th>
      <th><?= h(tf('tf_tipp_col_spiele_getippt')) ?></th>
      <th title="<?= h(tf('tf_tipp_col_re_titel')) ?>"><?= h(tf('tf_tipp_col_re')) ?></th>
      <th title="<?= h(tf('tf_tipp_col_rtd_titel')) ?>"><?= h(tf('tf_tipp_col_rtd')) ?></th>
      <th title="<?= h(tf('tf_tipp_col_rt_titel')) ?>"><?= h(tf('tf_tipp_col_rt')) ?></th>
      <th title="<?= h(tf('tf_tipp_col_jp_titel')) ?>"><?= h(tf('tf_tipp_col_jp')) ?></th>
      <th><?= h(tf('tf_tipp_col_quote')) ?></th>
      <th title="<?= h(tf('tf_tipp_col_spieltagssiege_titel')) ?>"><?= h(tf('tf_tipp_col_spieltagssiege')) ?></th>
    </tr>
    </thead>
    <tbody>
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
      <td><?= $eintrag['richtige_ergebnisse'] ?></td>
      <td><?= $eintrag['richtige_tendenz_tordiff'] ?></td>
      <td><?= $eintrag['richtige_tendenz'] ?></td>
      <td><?= $eintrag['joker_bonus_punkte'] ?></td>
      <td><?= $eintrag['ausgewertete_spiele'] > 0 ? round($eintrag['quote'] * 100) . '%' : '—' ?></td>
      <td><?= $eintrag['spieltagswertungen'] ?></td>
    </tr>
<?php } ?>
    </tbody>
  </table>
<?php } ?>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── GESAMTÜBERSICHT (SPIELTAGSPUNKTE) ───────────────────────────────────────

/**
 * "Gesamtübersicht" / "Spieltagspunkte" (Vorbild kicktipp.de) - eine Zeile
 * je Tipper, eine Spalte je Spieltag mit den dort
 * erzielten Punkten, Spieltagssieger (>0 Punkte, Höchstwert) rot/fett
 * hervorgehoben, plus Gesamtsumme (G) ganz rechts. Respektiert dieselbe
 * "Tippeinsicht öffentlich"-Einstellung wie renderTippEinsichtView() (siehe
 * tippspielHandleRequest()) - $tipper kann daher null sein.
 */
function renderTippGesamtuebersichtView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIdsAlle = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $ligaIds = $tipper !== null ? tippFilterLigenByAbo($ligaIdsAlle, (int)$tipper['id']) : $ligaIdsAlle;

    $archivLigen = getArchivierteLigenMitTipps();
    $archivLigenIds = array_map(static fn($l) => (int)$l['id'], $archivLigen);
    $requestedLigaId = isset($_GET['liga']) ? (int)$_GET['liga'] : null;
    $istArchivLiga = $requestedLigaId !== null && in_array($requestedLigaId, $archivLigenIds, true);
    $zeigeArchivListe = isset($_GET['archiv']) && $requestedLigaId === null;

    $ligaIdFuerTabs = $requestedLigaId ?? ($ligaIds[0] ?? ($archivLigenIds[0] ?? 0));

    ob_start();
    if ($tipper !== null) {
        echo renderTippspielUserBar($tipper);
    } else {
        echo '<p style="font-size:.85rem;color:var(--muted);margin-bottom:12px">'
           . h(tf('tf_tipp_einsicht_oeffentlich_hinweis'))
           . ' <a href="' . h(tippUrl('login')) . '">' . h(tf('tf_tipp_zum_login')) . '</a></p>';
    }
    echo renderTippspielTabsBar('gesamt', $ligaIdFuerTabs > 0 ? $ligaIdFuerTabs : null);

    if ($zeigeArchivListe) {
        echo renderTippArchivLigenListe($archivLigen, 'gesamt');
        return (string)ob_get_clean();
    }

    if (empty($ligaIds) && !$istArchivLiga) {
        if (empty($ligaIdsAlle)) {
            echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        } else {
            echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_kein_abo'))
               . ' <a href="' . h(tippUrl('konto')) . '">' . h(tf('tf_tipp_konto_link')) . '</a></p></div>';
        }
        echo renderTippArchivLink($archivLigen, 'gesamt');
        return (string)ob_get_clean();
    }

    if ($istArchivLiga) {
        $ligaId = $requestedLigaId;
    } else {
        $ligaId = $requestedLigaId ?? $ligaIds[0];
        if (!in_array($ligaId, $ligaIds, true)) {
            $ligaId = $ligaIds[0];
        }
    }
    $liga      = getLigaById($ligaId);
    $spieltage = getAllSpieltage($ligaId);
    $uebersicht = tippGetSpieltagspunkteUebersicht($ligaId, $spieltage);
    ?>
  <div class="card">
  <h2><?= h($liga['name'] ?? '') ?><?php if ($istArchivLiga) { ?> <span class="tipp-archiv-badge"><?= h(tf('tf_tipp_archiviert_badge')) ?></span><?php } ?></h2>
<?php if (!$istArchivLiga && count($ligaIds) > 1) { ?>
  <div style="margin-bottom:10px">
<?php foreach ($ligaIds as $lid) {
    $l = getLigaById($lid); ?>
    <a href="<?= h(tippUrl('gesamt', ['liga' => $lid])) ?>" style="margin-right:10px;<?= $lid === $ligaId ? 'font-weight:700' : '' ?>"><?= h($l['name'] ?? '') ?></a>
<?php } ?>
  </div>
<?php } ?>
<?php if (empty($uebersicht['rows'])) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_einsicht_noch_nicht_sichtbar')) ?></p>
<?php } else { ?>
  <div style="overflow-x:auto">
  <table class="tipp-matrix">
    <thead>
      <tr>
        <th><?= h(tf('tf_tipp_col_pos')) ?></th>
        <th><?= h(tf('tf_tipp_col_nickname')) ?></th>
<?php for ($n = 1; $n <= $uebersicht['maxNr']; $n++) { ?>
        <th><a href="<?= h(tippUrl('einsicht', ['liga' => $ligaId, 'spieltag' => $n])) ?>"><?= $n ?></a></th>
<?php } ?>
        <th><?= h(tf('tf_tipp_col_g')) ?></th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($uebersicht['rows'] as $i => $row) {
    $istIch = $tipper !== null && (int)$row['tipper_id'] === (int)$tipper['id']; ?>
    <tr<?= $istIch ? ' class="tipp-row-me"' : '' ?>>
      <td><?= $i + 1 ?>.</td>
      <td><a href="<?= h(tippUrl('gesamt_tipper', ['liga' => $ligaId, 'tipper' => $row['tipper_id']])) ?>"><?= h($row['nickname']) ?></a></td>
<?php for ($n = 1; $n <= $uebersicht['maxNr']; $n++) {
    $p = $row['punkte'][$n] ?? null;
    $siegerClass = !empty($row['sieger'][$n]) ? ' tipp-spieltag-sieger' : ''; ?>
        <td class="<?= $siegerClass ?>"><?= $p === null ? '' : $p ?></td>
<?php } ?>
      <td><strong><?= (int)$row['gesamt'] ?></strong></td>
    </tr>
<?php } ?>
    </tbody>
  </table>
  </div>
<?php } ?>
<?= renderTippArchivLink($archivLigen, 'gesamt') ?>
  </div>
    <?php
    return (string)ob_get_clean();
}

/**
 * Detailansicht für EINEN Tipper innerhalb der Gesamtübersicht (Vorbild
 * kicktipp.de, Klick auf den Namen in renderTippGesamtuebersichtView()) -
 * eine einfache Liste "N. Spieltag: Punkte" plus Gesamtsumme, mit Pfeil-
 * Navigation zum nächst- bzw. vorherplatzierten Tipper (nicht alphabetisch,
 * sondern nach der aktuellen Rangliste dieser Liga - siehe
 * tippGetSpieltagspunkteUebersicht()).
 */
function renderTippGesamtuebersichtTipperView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIdsAlle = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $ligaIds = $tipper !== null ? tippFilterLigenByAbo($ligaIdsAlle, (int)$tipper['id']) : $ligaIdsAlle;

    $archivLigenIds = array_map(static fn($l) => (int)$l['id'], getArchivierteLigenMitTipps());
    $requestedLigaId = isset($_GET['liga']) ? (int)$_GET['liga'] : null;
    $istArchivLiga = $requestedLigaId !== null && in_array($requestedLigaId, $archivLigenIds, true);
    $ligaIdFuerTabs = $requestedLigaId ?? ($ligaIds[0] ?? ($archivLigenIds[0] ?? 0));

    ob_start();
    if ($tipper !== null) {
        echo renderTippspielUserBar($tipper);
    } else {
        echo '<p style="font-size:.85rem;color:var(--muted);margin-bottom:12px">'
           . h(tf('tf_tipp_einsicht_oeffentlich_hinweis'))
           . ' <a href="' . h(tippUrl('login')) . '">' . h(tf('tf_tipp_zum_login')) . '</a></p>';
    }
    echo renderTippspielTabsBar('gesamt', $ligaIdFuerTabs > 0 ? $ligaIdFuerTabs : null);

    if (empty($ligaIds) && !$istArchivLiga) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        return (string)ob_get_clean();
    }
    if ($istArchivLiga) {
        $ligaId = $requestedLigaId;
    } else {
        $ligaId = $requestedLigaId ?? $ligaIds[0];
        if (!in_array($ligaId, $ligaIds, true)) {
            $ligaId = $ligaIds[0];
        }
    }
    $spieltage  = getAllSpieltage($ligaId);
    $uebersicht = tippGetSpieltagspunkteUebersicht($ligaId, $spieltage);
    $rows       = $uebersicht['rows'];

    $tipperId = (int)($_GET['tipper'] ?? 0);
    $index    = null;
    foreach ($rows as $i => $r) {
        if ((int)$r['tipper_id'] === $tipperId) {
            $index = $i;
            break;
        }
    }
    if ($index === null || empty($rows)) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_einsicht_noch_nicht_sichtbar')) . '</p></div>';
        return (string)ob_get_clean();
    }
    $aktuell = $rows[$index];
    $vorherige = $index > 0 ? $rows[$index - 1] : null;
    $naechste  = $index < count($rows) - 1 ? $rows[$index + 1] : null;
    ?>
  <div class="card">
  <div class="tipp-spieltag-nav" style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
<?php if ($vorherige !== null) { ?>
    <a href="<?= h(tippUrl('gesamt_tipper', ['liga' => $ligaId, 'tipper' => $vorherige['tipper_id']])) ?>" aria-label="<?= h(tf('tf_tipp_voriger_tipper')) ?>">&#9664;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9664;</span>
<?php } ?>
    <strong><?= $index + 1 ?>. <?= h($aktuell['nickname']) ?></strong>
<?php if ($naechste !== null) { ?>
    <a href="<?= h(tippUrl('gesamt_tipper', ['liga' => $ligaId, 'tipper' => $naechste['tipper_id']])) ?>" aria-label="<?= h(tf('tf_tipp_naechster_tipper')) ?>">&#9654;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9654;</span>
<?php } ?>
  </div>
  <table class="tipp-table">
<?php for ($n = 1; $n <= $uebersicht['maxNr']; $n++) {
    $p = $aktuell['punkte'][$n] ?? null;
    $siegerClass = !empty($aktuell['sieger'][$n]) ? ' class="tipp-spieltag-sieger"' : ''; ?>
    <tr>
      <td><a href="<?= h(tippUrl('einsicht', ['liga' => $ligaId, 'spieltag' => $n])) ?>"><?= h(tf('tf_tipp_spieltag_n', ['n' => $n])) ?></a></td>
      <td<?= $siegerClass ?> style="text-align:right"><?= $p === null ? '' : $p ?></td>
    </tr>
<?php } ?>
    <tr>
      <td><strong><?= h(tf('tf_tipp_col_gesamtpunkte')) ?></strong></td>
      <td style="text-align:right"><strong><?= (int)$aktuell['gesamt'] ?></strong></td>
    </tr>
  </table>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── SPIELREGELN ──────────────────────────────────────────────────────────────

/**
 * "Spielregeln"-Erklärseite (historisches Feature aus dem alten LMO, siehe
 * Projekt-Historie) - Anleitung + Punkteverteilung + Haftungsausschluss.
 * Die Punktewerte werden live aus den Admin-Einstellungen gelesen (nicht
 * hartkodiert), damit die Seite immer zur tatsächlich aktiven Wertung passt -
 * siehe calculateTippPunkte() in frontend_tipp.php für dieselben Schlüssel.
 * Enthält keine persönlichen Daten und ist daher immer ohne Login erreichbar
 * (siehe Aufrufer-Prüfung weiter oben in dieser Datei).
 */
function renderTippSpielregelnView(array $state) : string
{
    $tipper = $state['tipper'];
    $pktErgebnis      = (int)getTippSetting('pkt_ergebnis', '4');
    $pktTendenzTordiff = (int)getTippSetting('pkt_tendenz_tordiff', '3');
    $pktTendenz       = (int)getTippSetting('pkt_tendenz', '2');
    $pktToranzahl     = (int)getTippSetting('pkt_toranzahl', '1');
    $pktUnentschiedenBonus = (int)getTippSetting('pkt_unentschieden_bonus', '1');
    $jokerZulassen    = getTippSetting('joker_zulassen', '1') === '1';
    $jokerMulti       = getTippSetting('joker_multiplikator', '2');
    $abgabeMinuten    = (int)getTippSetting('abgabe_minuten', '15');
    $einheit = static fn(int $n) : string => $n === 1 ? tf('tf_tipp_regeln_punkt') : tf('tf_tipp_regeln_punkte');

    ob_start();
    if ($tipper !== null) {
        echo renderTippspielUserBar($tipper);
    }
    echo renderTippspielTabsBar('regeln');
    ?>
  <div class="card">
  <h1 style="font-size:1.3rem;text-align:center;margin-bottom:6px"><?= h(tf('tf_tipp_regeln_titel')) ?></h1>
  <p style="text-align:center;color:var(--muted);margin-bottom:20px"><?= h(tf('tf_tipp_regeln_intro')) ?></p>

  <details open class="tipp-regeln-block">
    <summary><?= h(tf('tf_tipp_regeln_abschnitt1')) ?></summary>
    <div style="padding:12px 4px 4px">
      <p><?= h(tf('tf_tipp_regeln_anmeldung_text1')) ?></p>
      <p><?= h(tf('tf_tipp_regeln_anmeldung_text2')) ?></p>
      <p><strong><?= h(tf('tf_tipp_regeln_userbereich_titel')) ?></strong></p>
      <ul style="margin:6px 0 14px 20px">
        <li><?= h(tf('tf_tipp_regeln_userbereich_1')) ?></li>
        <li><?= h(tf('tf_tipp_regeln_userbereich_2')) ?></li>
        <li><?= h(tf('tf_tipp_regeln_userbereich_3')) ?></li>
        <li><?= h(tf('tf_tipp_regeln_userbereich_4')) ?></li>
        <li><?= h(tf('tf_tipp_regeln_userbereich_5')) ?></li>
      </ul>
      <p><strong><?= h(tf('tf_tipp_regeln_abgabe_titel')) ?></strong></p>
      <p><?= h(tf('tf_tipp_regeln_abgabe_text1')) ?></p>
<?php if ($jokerZulassen) { ?>
      <p><?= h(tf('tf_tipp_regeln_abgabe_joker')) ?></p>
<?php } ?>
      <p><?= h(tf('tf_tipp_regeln_abgabe_frist', ['n' => $abgabeMinuten])) ?></p>
    </div>
  </details>

  <details class="tipp-regeln-block">
    <summary><?= h(tf('tf_tipp_regeln_abschnitt2')) ?></summary>
    <div style="padding:12px 4px 4px">
      <p><?= h(tf('tf_tipp_regeln_punkteverteilung_titel')) ?></p>
      <div class="tipp-regeln-punkte-grid">
        <div class="tipp-regeln-punkt-karte">
          <span class="wert"><?= $pktErgebnis ?></span>
          <span class="einheit"><?= h($einheit($pktErgebnis)) ?></span>
          <span class="label"><?= h(tf('tf_tipp_regeln_pkt_ergebnis')) ?></span>
        </div>
        <div class="tipp-regeln-punkt-karte">
          <span class="wert"><?= $pktTendenzTordiff ?></span>
          <span class="einheit"><?= h($einheit($pktTendenzTordiff)) ?></span>
          <span class="label"><?= h(tf('tf_tipp_regeln_pkt_tendenz_tordiff')) ?></span>
        </div>
        <div class="tipp-regeln-punkt-karte">
          <span class="wert"><?= $pktTendenz ?></span>
          <span class="einheit"><?= h($einheit($pktTendenz)) ?></span>
          <span class="label"><?= h(tf('tf_tipp_regeln_pkt_tendenz')) ?></span>
        </div>
        <div class="tipp-regeln-punkt-karte">
          <span class="wert"><?= $pktToranzahl ?></span>
          <span class="einheit"><?= h($einheit($pktToranzahl)) ?></span>
          <span class="label"><?= h(tf('tf_tipp_regeln_pkt_toranzahl')) ?></span>
        </div>
<?php if ($pktUnentschiedenBonus > 0) { ?>
        <div class="tipp-regeln-punkt-karte">
          <span class="wert">+<?= $pktUnentschiedenBonus ?></span>
          <span class="einheit"><?= h($einheit($pktUnentschiedenBonus)) ?></span>
          <span class="label"><?= h(tf('tf_tipp_regeln_pkt_unentschieden_bonus')) ?></span>
        </div>
<?php } if ($jokerZulassen) { ?>
        <div class="tipp-regeln-punkt-karte">
          <span class="wert">x&nbsp;<?= h($jokerMulti) ?></span>
          <span class="einheit"><?= h(tf('tf_tipp_regeln_faktor')) ?></span>
          <span class="label"><?= h(tf('tf_tipp_regeln_pkt_joker')) ?></span>
        </div>
<?php } ?>
      </div>
      <p><strong><?= h(tf('tf_tipp_regeln_liga_titel')) ?></strong></p>
      <p><?= h(tf('tf_tipp_regeln_liga_text1')) ?></p>
      <p><?= h(tf('tf_tipp_regeln_liga_text2')) ?></p>
      <p><?= h(tf('tf_tipp_regeln_liga_text3')) ?></p>
    </div>
  </details>

  <details class="tipp-regeln-block">
    <summary><?= h(tf('tf_tipp_regeln_abschnitt3')) ?></summary>
    <div style="padding:12px 4px 4px">
      <p><?= h(tf('tf_tipp_regeln_haftung_text1')) ?></p>
      <p><?= h(tf('tf_tipp_regeln_haftung_text2')) ?></p>
      <p style="font-style:italic;color:var(--muted)"><strong><?= h(tf('tf_tipp_regeln_haftung_text3')) ?></strong></p>
    </div>
  </details>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── STATISTIK ────────────────────────────────────────────────────────────────

/**
 * "Statistik"-Seite für einen Tipper (Vorbild kicktipp.de "Statistik") -
 * Tipp-/Punkte-Verteilung nach Tendenz, Rang-Verlauf über die Saison und
 * Top-3/Flop-3-Mannschaften nach Punkten. Nutzt Chart.js (bereits im Projekt
 * für die Fieberkurven im Einsatz, siehe RenderViewsTrait::renderFieberkurve()),
 * kein zusätzliches JS-Paket nötig. Dieselbe Rang-basierte Vor/Zurück-
 * Navigation wie bei der Gesamtübersicht-Tipper-Detailansicht.
 */
function renderTippStatistikView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIdsAlle = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $ligaIds = $tipper !== null ? tippFilterLigenByAbo($ligaIdsAlle, (int)$tipper['id']) : $ligaIdsAlle;

    // Archivierte Ligen mit Tipp-Historie bleiben einsehbar (siehe
    // renderTippEinsichtView() für die ausführliche Begründung) - eine über
    // ?liga= angeforderte archivierte Liga darf hier NICHT auf die erste
    // aktive Liga zurückfallen, sonst geht der Link von der Tipper-
    // Spieltagsansicht (renderTippEinsichtTipperView()) in einer
    // archivierten Liga ins Leere.
    $archivLigenIds = array_map(static fn($l) => (int)$l['id'], getArchivierteLigenMitTipps());
    $requestedLigaId = isset($_GET['liga']) ? (int)$_GET['liga'] : null;
    $istArchivLiga = $requestedLigaId !== null && in_array($requestedLigaId, $archivLigenIds, true);

    ob_start();
    if ($tipper !== null) {
        echo renderTippspielUserBar($tipper);
    } else {
        echo '<p style="font-size:.85rem;color:var(--muted);margin-bottom:12px">'
           . h(tf('tf_tipp_einsicht_oeffentlich_hinweis'))
           . ' <a href="' . h(tippUrl('login')) . '">' . h(tf('tf_tipp_zum_login')) . '</a></p>';
    }
    echo renderTippspielTabsBar('statistik', $requestedLigaId ?? ($ligaIds[0] ?? null));

    if (empty($ligaIds) && !$istArchivLiga) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        return (string)ob_get_clean();
    }
    if ($istArchivLiga) {
        $ligaId = $requestedLigaId;
    } else {
        $ligaId = $requestedLigaId ?? $ligaIds[0];
        if (!in_array($ligaId, $ligaIds, true)) {
            $ligaId = $ligaIds[0];
        }
    }
    $spieltage  = getAllSpieltage($ligaId);
    $maxNr      = getMaxSpieltagNummer($spieltage);
    $uebersicht = tippGetSpieltagspunkteUebersicht($ligaId, $spieltage);
    $rows       = $uebersicht['rows'];

    if (empty($rows)) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_einsicht_noch_nicht_sichtbar')) . '</p></div>';
        return (string)ob_get_clean();
    }

    $tipperId = isset($_GET['tipper']) ? (int)$_GET['tipper']
              : ($tipper !== null ? (int)$tipper['id'] : (int)$rows[0]['tipper_id']);
    $index = null;
    foreach ($rows as $i => $r) {
        if ((int)$r['tipper_id'] === $tipperId) { $index = $i; break; }
    }
    if ($index === null) {
        $index = 0;
        $tipperId = (int)$rows[0]['tipper_id'];
    }
    $aktuellerName = $rows[$index]['nickname'];
    $vorherige = $index > 0 ? $rows[$index - 1] : null;
    $naechste  = $index < count($rows) - 1 ? $rows[$index + 1] : null;

    // Statistik-Seiten (Vorbild kicktipp.de: mehrere Diagramm-Seiten statt
    // alles auf einmal) - aktuell 2 Seiten, mit ANZAHL_STAT_SEITEN einfach
    // erweiterbar, wenn weitere Diagramme dazukommen.
    $anzahlSeiten = 4;
    $seite = (int)($_GET['seite'] ?? 1);
    if ($seite < 1) { $seite = 1; }
    if ($seite > $anzahlSeiten) { $seite = $anzahlSeiten; }

    $stat = tippGetTipperStatistik($ligaId, $spieltage, $tipperId);
    ?>
  <div class="card">
  <div class="tipp-spieltag-nav" style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
<?php if ($vorherige !== null) { ?>
    <a href="<?= h(tippUrl('statistik', ['liga' => $ligaId, 'tipper' => $vorherige['tipper_id'], 'seite' => $seite])) ?>" aria-label="<?= h(tf('tf_tipp_voriger_tipper')) ?>">&#9664;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9664;</span>
<?php } ?>
    <strong><?= $index + 1 ?>. <?= h($aktuellerName) ?></strong>
<?php if ($naechste !== null) { ?>
    <a href="<?= h(tippUrl('statistik', ['liga' => $ligaId, 'tipper' => $naechste['tipper_id'], 'seite' => $seite])) ?>" aria-label="<?= h(tf('tf_tipp_naechster_tipper')) ?>">&#9654;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9654;</span>
<?php } ?>
  </div>
  <div class="tipp-spieltag-nav" style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
<?php if ($seite > 1) { ?>
    <a href="<?= h(tippUrl('statistik', ['liga' => $ligaId, 'tipper' => $tipperId, 'seite' => $seite - 1])) ?>" aria-label="<?= h(tf('tf_tipp_stat_vorige_seite')) ?>">&#9664;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9664;</span>
<?php } ?>
    <?= h(tf('tf_tipp_stat_seite_n', ['n' => $seite, 'gesamt' => $anzahlSeiten])) ?>
<?php if ($seite < $anzahlSeiten) { ?>
    <a href="<?= h(tippUrl('statistik', ['liga' => $ligaId, 'tipper' => $tipperId, 'seite' => $seite + 1])) ?>" aria-label="<?= h(tf('tf_tipp_stat_naechste_seite')) ?>">&#9654;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9654;</span>
<?php } ?>
  </div>

<?php if ($seite === 1) { ?>
  <div class="tipp-stat-grid">
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_tipps')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_tipps_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statTippsPie"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_punkte')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_punkte_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statPunktePie"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_platzierung')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_platzierung_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statPlatzierung"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_teams')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_teams_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statTeams"></canvas></div>
    </div>
  </div>
<?php } elseif ($seite === 2) { ?>
  <div class="tipp-stat-grid">
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_ergebnisse')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_ergebnisse_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statErgebnissePie"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_tipps_spieltag')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_tipps_spieltag_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statTippsSpieltag"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_punkte_spieltag')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_punkte_spieltag_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statPunkteSpieltag"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_punkte_spitze')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_punkte_spitze_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statPunkteSpitze"></canvas></div>
    </div>
  </div>
<?php } elseif ($seite === 3) { ?>
  <div class="tipp-stat-grid">
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_tipps_top3')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_tipps_top3_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statTippsTop3"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_ergebnisse_top3')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_ergebnisse_top3_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statErgebnisseTop3"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_spieltagsplatzierung')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_spieltagsplatzierung_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statSpieltagsplatzierung"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_vergleich_sieger')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_vergleich_sieger_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statVergleichSieger"></canvas></div>
    </div>
  </div>
<?php } elseif ($seite === 4) { ?>
  <div class="tipp-stat-grid">
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_ergebnisse_spieltag')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_ergebnisse_spieltag_erklaerung')) ?></p>
      <div class="tipp-stat-chart"><canvas id="statErgebnisseSpieltag"></canvas></div>
    </div>
    <div class="tipp-stat-box">
      <h3><?= h(tf('tf_tipp_stat_punkte_alle_teams')) ?></h3>
      <p class="tipp-stat-erklaerung"><?= h(tf('tf_tipp_stat_punkte_alle_teams_erklaerung')) ?></p>
      <div class="tipp-stat-chart tipp-stat-chart-hoch"><canvas id="statPunkteAlleTeams"></canvas></div>
    </div>
  </div>
<?php } ?>
  <div class="tipp-spieltag-nav" style="display:flex;align-items:center;gap:10px;margin-top:20px">
<?php if ($seite > 1) { ?>
    <a href="<?= h(tippUrl('statistik', ['liga' => $ligaId, 'tipper' => $tipperId, 'seite' => $seite - 1])) ?>" aria-label="<?= h(tf('tf_tipp_stat_vorige_seite')) ?>">&#9664;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9664;</span>
<?php } ?>
    <?= h(tf('tf_tipp_stat_seite_n', ['n' => $seite, 'gesamt' => $anzahlSeiten])) ?>
<?php if ($seite < $anzahlSeiten) { ?>
    <a href="<?= h(tippUrl('statistik', ['liga' => $ligaId, 'tipper' => $tipperId, 'seite' => $seite + 1])) ?>" aria-label="<?= h(tf('tf_tipp_stat_naechste_seite')) ?>">&#9654;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9654;</span>
<?php } ?>
  </div>
  </div>
  <script src="assets/vendor/chart.umd.min.js"></script>
  <script>
(function(){
  var farbHeim = '#699c46', farbRemis = '#f0ad4e', farbGast = '#d9534f';

<?php if ($seite === 1) { ?>
  new Chart(document.getElementById('statTippsPie'), {
    type: 'pie',
    data: {
      labels: [<?= json_encode(tf('tf_tipp_col_heim')) ?>, <?= json_encode(tf('tf_tipp_stat_remis')) ?>, <?= json_encode(tf('tf_tipp_col_gast')) ?>],
      datasets: [{ data: [<?= (int)$stat['tipps_tendenz']['Heim'] ?>, <?= (int)$stat['tipps_tendenz']['Remis'] ?>, <?= (int)$stat['tipps_tendenz']['Gast'] ?>], backgroundColor: [farbHeim, farbRemis, farbGast] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
  });

  new Chart(document.getElementById('statPunktePie'), {
    type: 'pie',
    data: {
      labels: [<?= json_encode(tf('tf_tipp_col_heim')) ?>, <?= json_encode(tf('tf_tipp_stat_remis')) ?>, <?= json_encode(tf('tf_tipp_col_gast')) ?>],
      datasets: [{ data: [<?= (int)$stat['punkte_tendenz']['Heim'] ?>, <?= (int)$stat['punkte_tendenz']['Remis'] ?>, <?= (int)$stat['punkte_tendenz']['Gast'] ?>], backgroundColor: [farbHeim, farbRemis, farbGast] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
  });

<?php
    $rvLabels = array_keys($stat['rang_verlauf']);
    $rvWerte  = array_values($stat['rang_verlauf']);
    ?>
  new Chart(document.getElementById('statPlatzierung'), {
    type: 'line',
    data: {
      labels: <?= json_encode($rvLabels) ?>,
      datasets: [{ data: <?= json_encode($rvWerte) ?>, borderColor: farbHeim, backgroundColor: farbHeim, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.35 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { reverse: true, min: 1, max: <?= max(1, (int)$stat['anzahl_tipper']) ?>, ticks: { stepSize: 1 } },
                x: { title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_spieltag_achse')) ?> } } }
    }
  });

<?php
    arsort($stat['punkte_team']);
    $teamNamen = array_keys($stat['punkte_team']);
    $teamWerte = array_values($stat['punkte_team']);
    $n = count($teamNamen);
    $top = min(3, (int)ceil($n / 2));
    $flopStart = max($top, $n - 3);
    $auswahlNamen = [];
    $auswahlWerte = [];
    $auswahlFarben = [];
    foreach ($teamNamen as $i => $name) {
        if ($i < $top || $i >= $flopStart) {
            $auswahlNamen[] = $name;
            $auswahlWerte[] = $teamWerte[$i];
            $auswahlFarben[] = $i < $top ? '#699c46' : '#d9534f';
        }
    }
    ?>
  new Chart(document.getElementById('statTeams'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($auswahlNamen, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{ data: <?= json_encode($auswahlWerte) ?>, backgroundColor: <?= json_encode($auswahlFarben) ?> }]
    },
    options: {
      indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true } }
    }
  });
<?php } elseif ($seite === 2) { ?>
  new Chart(document.getElementById('statErgebnissePie'), {
    type: 'pie',
    data: {
      labels: [<?= json_encode(tf('tf_tipp_col_heim')) ?>, <?= json_encode(tf('tf_tipp_stat_remis')) ?>, <?= json_encode(tf('tf_tipp_col_gast')) ?>],
      datasets: [{ data: [<?= (int)$stat['ergebnisse_tendenz']['Heim'] ?>, <?= (int)$stat['ergebnisse_tendenz']['Remis'] ?>, <?= (int)$stat['ergebnisse_tendenz']['Gast'] ?>], backgroundColor: [farbHeim, farbRemis, farbGast] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
  });

<?php
    $tpsLabels = [];
    $tpsHeim = []; $tpsRemis = []; $tpsGast = [];
    for ($n = 1; $n <= $maxNr; $n++) {
        $tpsLabels[] = $n;
        $tpsHeim[]  = $stat['tipps_pro_spieltag'][$n]['Heim'] ?? 0;
        $tpsRemis[] = $stat['tipps_pro_spieltag'][$n]['Remis'] ?? 0;
        $tpsGast[]  = $stat['tipps_pro_spieltag'][$n]['Gast'] ?? 0;
    }
    ?>
  new Chart(document.getElementById('statTippsSpieltag'), {
    type: 'line',
    data: {
      labels: <?= json_encode($tpsLabels) ?>,
      datasets: [
        { label: <?= json_encode(tf('tf_tipp_col_heim')) ?>, data: <?= json_encode($tpsHeim) ?>, borderColor: farbHeim, backgroundColor: farbHeim, borderWidth: 2, pointRadius: 0, tension: 0.35 },
        { label: <?= json_encode(tf('tf_tipp_stat_remis')) ?>, data: <?= json_encode($tpsRemis) ?>, borderColor: farbRemis, backgroundColor: farbRemis, borderWidth: 2, pointRadius: 0, tension: 0.35 },
        { label: <?= json_encode(tf('tf_tipp_col_gast')) ?>, data: <?= json_encode($tpsGast) ?>, borderColor: farbGast, backgroundColor: farbGast, borderWidth: 2, pointRadius: 0, tension: 0.35 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_spieltag_achse')) ?> } } }
    }
  });

<?php
    $ppsLabels = [];
    $ppsWerte  = [];
    for ($n = 1; $n <= $maxNr; $n++) {
        $ppsLabels[] = $n;
        $ppsWerte[]  = $stat['punkte_pro_spieltag'][$n] ?? null;
    }
    ?>
  new Chart(document.getElementById('statPunkteSpieltag'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($ppsLabels) ?>,
      datasets: [{ data: <?= json_encode($ppsWerte) ?>, backgroundColor: farbHeim }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true },
                x: { title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_spieltag_achse')) ?> } } }
    }
  });

<?php
    $pzsLabels = array_keys($stat['punkte_zur_spitze']);
    $pzsWerte  = array_values($stat['punkte_zur_spitze']);
    ?>
  new Chart(document.getElementById('statPunkteSpitze'), {
    type: 'line',
    data: {
      labels: <?= json_encode($pzsLabels) ?>,
      datasets: [{ data: <?= json_encode($pzsWerte) ?>, borderColor: farbGast, backgroundColor: farbGast, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.35, fill: true }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { reverse: true, beginAtZero: true, title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_punkte_spitze')) ?> } },
                x: { title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_spieltag_achse')) ?> } } }
    }
  });
<?php } elseif ($seite === 3) { ?>
<?php
    $top3Labels = []; $top3Werte = []; $top3Farben = [];
    foreach (['Heim', 'Remis', 'Gast'] as $tendenz) {
        $farbe = $tendenz === 'Heim' ? "farbHeim" : ($tendenz === 'Remis' ? "farbRemis" : "farbGast");
        foreach (($stat['tipps_top3_ergebnis'][$tendenz] ?? []) as $ergebnis => $anzahl) {
            $top3Labels[] = $ergebnis;
            $top3Werte[]  = $anzahl;
            $top3Farben[] = $farbe;
        }
    }
    ?>
  new Chart(document.getElementById('statTippsTop3'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($top3Labels) ?>,
      datasets: [{ data: <?= json_encode($top3Werte) ?>, backgroundColor: [<?= implode(',', $top3Farben) ?>] }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });

<?php
    $ergTop3Labels = []; $ergTop3Werte = []; $ergTop3Farben = [];
    foreach (['Heim', 'Remis', 'Gast'] as $tendenz) {
        $farbe = $tendenz === 'Heim' ? "farbHeim" : ($tendenz === 'Remis' ? "farbRemis" : "farbGast");
        foreach (($stat['ergebnisse_top3_ergebnis'][$tendenz] ?? []) as $ergebnis => $anzahl) {
            $ergTop3Labels[] = $ergebnis;
            $ergTop3Werte[]  = $anzahl;
            $ergTop3Farben[] = $farbe;
        }
    }
    ?>
  new Chart(document.getElementById('statErgebnisseTop3'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($ergTop3Labels) ?>,
      datasets: [{ data: <?= json_encode($ergTop3Werte) ?>, backgroundColor: [<?= implode(',', $ergTop3Farben) ?>] }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });

<?php
    $spLabels = array_keys($stat['spieltagsplatzierung_verlauf']);
    $spWerte  = array_values($stat['spieltagsplatzierung_verlauf']);
    ?>
  new Chart(document.getElementById('statSpieltagsplatzierung'), {
    type: 'line',
    data: {
      labels: <?= json_encode($spLabels) ?>,
      datasets: [{ data: <?= json_encode($spWerte) ?>, borderColor: farbHeim, backgroundColor: farbHeim, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.35 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { reverse: true, min: 1, max: <?= max(1, (int)$stat['anzahl_tipper']) ?>, ticks: { stepSize: 1 } },
                x: { title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_spieltag_achse')) ?> } } }
    }
  });

<?php
    $vsLabels = array_keys($stat['vergleich_spieltagssieger']);
    $vsMich   = array_values(array_map(static fn($v) => $v['mich'], $stat['vergleich_spieltagssieger']));
    $vsSieger = array_values(array_map(static fn($v) => $v['sieger'], $stat['vergleich_spieltagssieger']));
    ?>
  new Chart(document.getElementById('statVergleichSieger'), {
    type: 'line',
    data: {
      labels: <?= json_encode($vsLabels) ?>,
      datasets: [
        { label: <?= json_encode($aktuellerName) ?>, data: <?= json_encode($vsMich) ?>, borderColor: '#2563eb', backgroundColor: '#2563eb', borderWidth: 2.5, pointRadius: 0, tension: 0.35, fill: false },
        { label: <?= json_encode(tf('tf_tipp_stat_spieltagssieger')) ?>, data: <?= json_encode($vsSieger) ?>, borderColor: farbGast, backgroundColor: farbGast, borderWidth: 2, borderDash: [5, 3], pointRadius: 0, tension: 0.35, fill: false }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: { y: { beginAtZero: true },
                x: { title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_spieltag_achse')) ?> } } }
    }
  });
<?php } elseif ($seite === 4) { ?>
<?php
    $epsLabels = []; $epsHeim = []; $epsRemis = []; $epsGast = [];
    for ($n = 1; $n <= $maxNr; $n++) {
        $epsLabels[] = $n;
        $epsHeim[]  = $stat['ergebnisse_pro_spieltag'][$n]['Heim'] ?? 0;
        $epsRemis[] = $stat['ergebnisse_pro_spieltag'][$n]['Remis'] ?? 0;
        $epsGast[]  = $stat['ergebnisse_pro_spieltag'][$n]['Gast'] ?? 0;
    }
    ?>
  new Chart(document.getElementById('statErgebnisseSpieltag'), {
    type: 'line',
    data: {
      labels: <?= json_encode($epsLabels) ?>,
      datasets: [
        { label: <?= json_encode(tf('tf_tipp_col_heim')) ?>, data: <?= json_encode($epsHeim) ?>, borderColor: farbHeim, backgroundColor: farbHeim, borderWidth: 2, pointRadius: 0, tension: 0.35 },
        { label: <?= json_encode(tf('tf_tipp_stat_remis')) ?>, data: <?= json_encode($epsRemis) ?>, borderColor: farbRemis, backgroundColor: farbRemis, borderWidth: 2, pointRadius: 0, tension: 0.35 },
        { label: <?= json_encode(tf('tf_tipp_col_gast')) ?>, data: <?= json_encode($epsGast) ?>, borderColor: farbGast, backgroundColor: farbGast, borderWidth: 2, pointRadius: 0, tension: 0.35 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { title: { display: true, text: <?= json_encode(tf('tf_tipp_stat_spieltag_achse')) ?> } } }
    }
  });

<?php
    // Alle Mannschaften nach Punkten, in 3 Drittel eingefärbt (oberes
    // Drittel grün, mittleres grau, unteres Drittel rot) - analog zum
    // Vorbild, aber ohne auf Top-3/Flop-3 zu kürzen (siehe statTeams auf
    // Seite 1 für die kompakte Variante).
    $allTeamsSorted = $stat['punkte_team'];
    arsort($allTeamsSorted);
    $allTeamNamen = array_keys($allTeamsSorted);
    $allTeamWerte = array_values($allTeamsSorted);
    $gesamtTeams = count($allTeamNamen);
    $drittel = max(1, (int)ceil($gesamtTeams / 3));
    $allTeamFarben = [];
    foreach ($allTeamNamen as $i => $name) {
        $allTeamFarben[] = $i < $drittel ? '#699c46' : ($i >= $gesamtTeams - $drittel ? '#d9534f' : '#9098a8');
    }
    ?>
  new Chart(document.getElementById('statPunkteAlleTeams'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($allTeamNamen, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{ data: <?= json_encode($allTeamWerte) ?>, backgroundColor: <?= json_encode($allTeamFarben) ?> }]
    },
    options: {
      indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true } }
    }
  });
<?php } ?>
})();
  </script>
    <?php
    return (string)ob_get_clean();
}

// ── TIPPEINSICHT: EIN TIPPER, EIN SPIELTAG ─────────────────────────────────

/**
 * Detailansicht EINES Tippers für EINEN Spieltag (Vorbild kicktipp.de, Klick
 * auf den Namen in der Tippeinsicht-Matrix) - Heim/Gast/Erg/Tipp/Pkt je
 * Partie, Spieltagspunkte + Gesamtpunkte am Ende, plus Links zu Statistik
 * und Tipptabelle. Springt ohne expliziten ?spieltag= Parameter auf den
 * zuletzt ausgewerteten Spieltag (tippGetLetzterAusgewerteterSpieltag()).
 */
function renderTippEinsichtTipperView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIdsAlle = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $ligaIds = $tipper !== null ? tippFilterLigenByAbo($ligaIdsAlle, (int)$tipper['id']) : $ligaIdsAlle;

    $archivLigenIds = array_map(static fn($l) => (int)$l['id'], getArchivierteLigenMitTipps());
    $requestedLigaId = isset($_GET['liga']) ? (int)$_GET['liga'] : null;
    $istArchivLiga = $requestedLigaId !== null && in_array($requestedLigaId, $archivLigenIds, true);

    ob_start();
    if ($tipper !== null) {
        echo renderTippspielUserBar($tipper);
    } else {
        echo '<p style="font-size:.85rem;color:var(--muted);margin-bottom:12px">'
           . h(tf('tf_tipp_einsicht_oeffentlich_hinweis'))
           . ' <a href="' . h(tippUrl('login')) . '">' . h(tf('tf_tipp_zum_login')) . '</a></p>';
    }
    echo renderTippspielTabsBar('einsicht', $requestedLigaId ?? ($ligaIds[0] ?? null));

    if (empty($ligaIds) && !$istArchivLiga) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        return (string)ob_get_clean();
    }
    if ($istArchivLiga) {
        $ligaId = $requestedLigaId;
    } else {
        $ligaId = $requestedLigaId ?? $ligaIds[0];
        if (!in_array($ligaId, $ligaIds, true)) {
            $ligaId = $ligaIds[0];
        }
    }
    $spieltage = getAllSpieltage($ligaId);
    $maxNr     = getMaxSpieltagNummer($spieltage);
    $spieltagNr = isset($_GET['spieltag']) ? (int)$_GET['spieltag'] : tippGetLetzterAusgewerteterSpieltag($ligaId);
    if ($spieltagNr < 1) { $spieltagNr = 1; }
    if ($maxNr > 0 && $spieltagNr > $maxNr) { $spieltagNr = $maxNr; }

    $rangliste = tippGetSpieltagspunkteUebersicht($ligaId, $spieltage);
    $rlIndex = null;
    foreach ($rangliste['rows'] as $i => $r) {
        if ((int)$r['tipper_id'] === (int)($_GET['tipper'] ?? 0)) { $rlIndex = $i; break; }
    }
    $tipperId = $rlIndex !== null ? (int)($_GET['tipper']) : (int)($rangliste['rows'][0]['tipper_id'] ?? 0);
    if ($rlIndex === null && !empty($rangliste['rows'])) { $rlIndex = 0; }
    $aktuellerName = $rangliste['rows'][$rlIndex]['nickname'] ?? '';
    $vorherigerTipper = $rlIndex !== null && $rlIndex > 0 ? $rangliste['rows'][$rlIndex - 1] : null;
    $naechsterTipper  = $rlIndex !== null && $rlIndex < count($rangliste['rows']) - 1 ? $rangliste['rows'][$rlIndex + 1] : null;

    $matrix = tippGetSpieltagMatrix($ligaId, $spieltage, $spieltagNr);
    $meineZeile = null;
    foreach ($matrix['rows'] as $r) {
        if ((int)$r['tipper_id'] === $tipperId) { $meineZeile = $r; break; }
    }
    ?>
  <div class="card">
  <div class="tipp-spieltag-nav" style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
<?php if ($vorherigerTipper !== null) { ?>
    <a href="<?= h(tippUrl('einsicht_tipper', ['liga' => $ligaId, 'spieltag' => $spieltagNr, 'tipper' => $vorherigerTipper['tipper_id']])) ?>" aria-label="<?= h(tf('tf_tipp_voriger_tipper')) ?>">&#9664;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9664;</span>
<?php } ?>
    <strong><?= h($aktuellerName) ?></strong>
<?php if ($naechsterTipper !== null) { ?>
    <a href="<?= h(tippUrl('einsicht_tipper', ['liga' => $ligaId, 'spieltag' => $spieltagNr, 'tipper' => $naechsterTipper['tipper_id']])) ?>" aria-label="<?= h(tf('tf_tipp_naechster_tipper')) ?>">&#9654;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9654;</span>
<?php } ?>
  </div>
  <div class="tipp-spieltag-nav" style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
<?php if ($spieltagNr > 1) { ?>
    <a href="<?= h(tippUrl('einsicht_tipper', ['liga' => $ligaId, 'spieltag' => $spieltagNr - 1, 'tipper' => $tipperId])) ?>" aria-label="<?= h(tf('tf_tipp_voriger_spieltag')) ?>">&#9664;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9664;</span>
<?php } ?>
    <?= h(tf('tf_tipp_spieltag_n', ['n' => $spieltagNr])) ?>
<?php if ($spieltagNr < $maxNr) { ?>
    <a href="<?= h(tippUrl('einsicht_tipper', ['liga' => $ligaId, 'spieltag' => $spieltagNr + 1, 'tipper' => $tipperId])) ?>" aria-label="<?= h(tf('tf_tipp_naechster_spieltag')) ?>">&#9654;</a>
<?php } else { ?>
    <span style="opacity:.3">&#9654;</span>
<?php } ?>
  </div>

<?php if ($meineZeile === null || empty($matrix['partien'])) { ?>
  <p class="empty-msg"><?= h(tf('tf_tipp_einsicht_noch_nicht_sichtbar')) ?></p>
<?php } else { ?>
  <table class="tipp-table">
    <thead>
    <tr><th><?= h(tf('tf_tipp_col_heim')) ?></th><th><?= h(tf('tf_tipp_col_gast')) ?></th><th style="text-align:center"><?= h(tf('tf_tipp_col_ergebnis')) ?></th><th style="text-align:center"><?= h(tf('tf_tipp_col_tipp')) ?></th><th style="text-align:right"><?= h(tf('tf_tipp_col_punkte')) ?></th></tr>
    </thead>
    <tbody>
<?php foreach ($matrix['partien'] as $p) {
    $pid = (int)$p['id'];
    $heimName = $p['heim_name'] ?? $p['heim_label'] ?? '';
    $gastName = $p['gast_name'] ?? $p['gast_label'] ?? '';
    $erg = ($p['h_tore'] !== null && $p['g_tore'] !== null) ? ((int)$p['h_tore'] . ':' . (int)$p['g_tore']) : '-:-';
    $zelle = $meineZeile['zellen'][$pid] ?? null;
    ?>
    <tr>
      <td><?= h($heimName) ?></td>
      <td><?= h($gastName) ?></td>
      <td style="text-align:center"><?= h($erg) ?></td>
<?php if ($zelle === null) { ?>
      <td class="tipp-matrix-leer" style="text-align:center">-:-</td>
      <td style="text-align:right">–</td>
<?php } else { ?>
      <td class="<?= $zelle['punkte'] > 0 ? 'tipp-matrix-treffer' : 'tipp-matrix-leer' ?>" style="text-align:center"><?= (int)$zelle['heim'] ?>:<?= (int)$zelle['gast'] ?><?= $zelle['joker'] ? ' 🃏' : '' ?></td>
      <td style="text-align:right"><?= (int)$zelle['punkte'] ?></td>
<?php } ?>
    </tr>
<?php } ?>
    </tbody>
    <tfoot>
      <tr><td colspan="4" style="text-align:right"><strong><?= h(tf('tf_tipp_stat_spieltagspunkte')) ?></strong></td><td style="text-align:right"><strong><?= (int)$meineZeile['punkte_spieltag'] ?></strong></td></tr>
      <tr><td colspan="4" style="text-align:right"><strong><?= h(tf('tf_tipp_col_gesamtpunkte')) ?></strong></td><td style="text-align:right"><strong><?= (int)$meineZeile['punkte_aktuell'] ?></strong></td></tr>
    </tfoot>
  </table>

  <p style="margin-top:16px">
    <a href="<?= h(tippUrl('statistik', ['liga' => $ligaId, 'tipper' => $tipperId])) ?>"><?= h(tf('tf_tipp_tab_statistik')) ?> &rsaquo;</a>
  </p>
  <p>
    <a href="<?= h(tippUrl('tipptabelle', ['liga' => $ligaId, 'tipper' => $tipperId])) ?>"><?= h(tf('tf_tipp_tipptabelle_link')) ?> &rsaquo;</a>
  </p>
<?php } ?>
  </div>
    <?php
    return (string)ob_get_clean();
}

// ── TIPPTABELLE ──────────────────────────────────────────────────────────────

/**
 * "Tipptabelle" (Vorbild kicktipp.de) - die Liga-Tabelle, wie sie aussehen
 * würde, wenn statt der echten Ergebnisse die Tipps EINES Tippers eingetroffen
 * wären. Nur Partien, deren Tipp bereits sichtbar ist (siehe
 * tippGetEinsichtDaten()-Sichtbarkeitsregel), fließen ein - alle anderen
 * gelten als "noch nicht gespielt" (h_tore/g_tore = null), genau wie
 * computeStandings() das für echte, noch ausstehende Partien auch handhabt.
 * Nutzt LigaService::computeStandings() 1:1 wieder, nur mit den Tipp-Werten
 * statt der echten Ergebnisse gefüttert.
 */
function renderTippTipptabelleView(array $state) : string
{
    $tipper = $state['tipper'];
    $ligaIdsAlle = getTippSetting('tippbare_immer_alle', '1') === '1'
        ? array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten())
        : getTippLigaFreigabeIds();
    $ligaIds = $tipper !== null ? tippFilterLigenByAbo($ligaIdsAlle, (int)$tipper['id']) : $ligaIdsAlle;

    // Archivierte Ligen mit Tipp-Historie bleiben einsehbar (siehe
    // renderTippEinsichtView()) - derselbe Bypass wie dort, sonst geht der
    // Link von der Tipper-Spieltagsansicht in einer archivierten Liga ins
    // Leere (fällt sonst stillschweigend auf die erste aktive Liga zurück).
    $archivLigenIds = array_map(static fn($l) => (int)$l['id'], getArchivierteLigenMitTipps());
    $requestedLigaId = isset($_GET['liga']) ? (int)$_GET['liga'] : null;
    $istArchivLiga = $requestedLigaId !== null && in_array($requestedLigaId, $archivLigenIds, true);

    ob_start();
    if ($tipper !== null) {
        echo renderTippspielUserBar($tipper);
    } else {
        echo '<p style="font-size:.85rem;color:var(--muted);margin-bottom:12px">'
           . h(tf('tf_tipp_einsicht_oeffentlich_hinweis'))
           . ' <a href="' . h(tippUrl('login')) . '">' . h(tf('tf_tipp_zum_login')) . '</a></p>';
    }
    echo renderTippspielTabsBar('einsicht', $requestedLigaId ?? ($ligaIds[0] ?? null));

    if (empty($ligaIds) && !$istArchivLiga) {
        echo '<div class="card"><p class="empty-msg">' . h(tf('tf_tipp_keine_ligen')) . '</p></div>';
        return (string)ob_get_clean();
    }
    if ($istArchivLiga) {
        $ligaId = $requestedLigaId;
    } else {
        $ligaId = $requestedLigaId ?? $ligaIds[0];
        if (!in_array($ligaId, $ligaIds, true)) {
            $ligaId = $ligaIds[0];
        }
    }
    $tipperId = (int)($_GET['tipper'] ?? 0);
    $spieltage = getAllSpieltage($ligaId);
    $allePartien = getAllLigaPartien($spieltage);

    // Sichtbarkeits-Regel wie in renderTippEinsichtView()/tippGetSpieltagMatrix()
    $zeitpunkt = getTippSetting('tippeinsicht_zeitpunkt', 'abgabeschluss');
    $partieIds = array_map(static fn($p) => (int)$p['id'], $allePartien);
    $tipps = [];
    if (!empty($partieIds) && $tipperId > 0) {
        $ph = implode(',', array_fill(0, count($partieIds), '?'));
        $stmt = getDB()->prepare(
            'SELECT partie_id, tipp_heim, tipp_gast FROM ' . tbl('tipp_tipp') . '
              WHERE tipper_id = ? AND partie_id IN (' . $ph . ')
                AND tipp_heim IS NOT NULL AND tipp_gast IS NOT NULL'
        );
        $stmt->execute(array_merge([$tipperId], $partieIds));
        foreach ($stmt->fetchAll() as $t) {
            $tipps[(int)$t['partie_id']] = $t;
        }
    }

    $fakePartien = [];
    foreach ($allePartien as $p) {
        $pid = (int)$p['id'];
        $sichtbar = match ($zeitpunkt) {
            'sofort'   => true,
            'ergebnis' => $p['h_tore'] !== null && $p['g_tore'] !== null,
            default    => !tippIstAenderbar($p['zeit'] ?? null, $p['_spieltag_start'] ?? null),
        };
        $tipp = ($sichtbar && isset($tipps[$pid])) ? $tipps[$pid] : null;
        $fakePartien[] = [
            'heim_id' => $p['heim_id'] ?? 0,
            'gast_id' => $p['gast_id'] ?? 0,
            'heim_name' => $p['heim_name'] ?? '',
            'gast_name' => $p['gast_name'] ?? '',
            'h_tore' => $tipp !== null ? (int)$tipp['tipp_heim'] : null,
            'g_tore' => $tipp !== null ? (int)$tipp['tipp_gast'] : null,
        ];
    }

    $teams   = getLigaTeamsList($ligaId);
    $options = getLigaOptions($ligaId);
    $tabelle = \LMOnext\Liga\LigaService::computeStandings($teams, $fakePartien, $options, null);
    $liga    = getLigaById($ligaId);
    $tipperName = null;
    foreach (tippGetSpieltagspunkteUebersicht($ligaId, $spieltage)['rows'] as $r) {
        if ((int)$r['tipper_id'] === $tipperId) { $tipperName = $r['nickname']; break; }
    }
    ?>
  <div class="card">
  <h2><?= h($liga['name'] ?? '') ?><?= $tipperName !== null ? ' – ' . h($tipperName) : '' ?></h2>
  <p class="tipp-tipptabelle-hinweis"><?= h(tf('tf_tipp_tipptabelle_hinweis')) ?></p>
  <table class="tipp-table">
    <thead>
    <tr>
      <th><?= h(tf('tf_tipp_col_pos')) ?></th>
      <th><?= h(tf('tf_tipp_col_nickname')) ?></th>
      <th style="text-align:right"><?= h(tf('tf_tipp_tt_sp')) ?></th>
      <th style="text-align:right"><?= h(tf('tf_tipp_tt_pkt')) ?></th>
      <th style="text-align:right"><?= h(tf('tf_tipp_tt_tore')) ?></th>
      <th style="text-align:right"><?= h(tf('tf_tipp_tt_diff')) ?></th>
    </tr>
    </thead>
    <tbody>
<?php foreach ($tabelle as $i => $row) { ?>
    <tr>
      <td><?= $i + 1 ?>.</td>
      <td><?= h($row['name']) ?></td>
      <td style="text-align:right"><?= (int)$row['sp'] ?></td>
      <td style="text-align:right"><strong><?= (int)$row['pkt'] ?></strong></td>
      <td style="text-align:right"><?= (int)$row['tore_h'] ?>:<?= (int)$row['tore_g'] ?></td>
      <td style="text-align:right"><?= ((int)$row['tore_h'] - (int)$row['tore_g']) ?></td>
    </tr>
<?php } ?>
    </tbody>
  </table>
  </div>
    <?php
    return (string)ob_get_clean();
}
