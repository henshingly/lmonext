<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/view_tippspiel.php
 * Fileversion: 1.0.1
 * Changelog: 1.0.1 - Bugfix: Die Checkboxen "freigeschaltet"/"Newsletter"/"Tipp-Reminder" im
 *                     Bearbeiten-Formular zeigten den gespeicherten Zustand nie an (immer
 *                     unchecked), obwohl der Wert in der DB korrekt 1 war - Ursache: getDB()
 *                     nutzt PDO::ATTR_EMULATE_PREPARES=false, wodurch TINYINT-Spalten als
 *                     natives PHP int statt als String zurückkommen. Der bisherige strikte
 *                     Vergleich mit '1' (String) via === war dadurch immer false. Jetzt (int)-Cast
 *                     vor dem Vergleich mit int 1
 * Changelog: 1.0.0 - Tab "Newsletter/Reminder" vollständig umgesetzt: drei Versandarten
 *                     (Newsletter an Alle/Persönliche Email/Tipp-Reminder mit Liga+Spieltag-
 *                     Auswahl je tippbarer Liga), Tipper-Bereich von-bis PLUS die neue "an alle
 *                     Tipper"-Kurzoption, Vorlagen-Umschaltung per JS beim Wechsel der
 *                     Versandart - exakt nach dem vom Nutzer bereitgestellten Original-HTML
 * Changelog: 0.9.1
 * Changelog: 0.9.1 - Bugfix: $tdR/$tdL/$selSt/$inpSt waren nur innerhalb des "Optionen"-Tabs
 *                     definiert und fehlten im "Userverwaltung"-Tab (führte zu PHP-Warnungen +
 *                     kaputtem HTML) - an eine gemeinsame Stelle vor der Tab-Weiche verschoben
 * Changelog: 0.9.0
 * Changelog: 0.9.0 - Tab "Userverwaltung" vollständig umgesetzt: sortierbare Liste (Nickname
 *                     als mailto-Link, Realname, Team, letzter Tipp, Editieren), sowie das
 *                     Bearbeiten-/Neuanlegen-Formular mit allen Original-Feldern inkl. der
 *                     drei Team-Radio-Optionen (keinem/bestehendes Team mit [Mitgliederzahl]/
 *                     neues Team gründen) und abonnierten Ligen - exakt nach dem vom Nutzer
 *                     bereitgestellten Original-HTML-Quellcode
 * Changelog: 0.8.0
 * Changelog: 0.8.0 - Letzter der sechs Optionen-Bereiche fertig: "Tippbare Ligen" mit "immer
 *                     alle"-Checkbox (deaktiviert bei Aktivierung die Einzelauswahl, genau wie
 *                     im Original) und gezielter Einzelauswahl aus dem obersten Ordner der
 *                     Ligenübersicht (keine archivierten Ligen)
 * Changelog: 0.7.0
 * Changelog: 0.7.0 - "Was zählt bei Punktgleichheit" vollständig umgesetzt: drei
 *                     Kriterien-Dropdowns mit den acht aus Screenshots UND Original-Quellcode
 *                     doppelt bestätigten Werten (kein Kriterium/höhere Quote/höhere Anzahl
 *                     Spiele getippt/Anzahl richtiger Ergebnistipps/Anzahl richtiger Tendenz-
 *                     u. Tordiff.tipps/Anzahl richtiger Tendenztipps/durch Joker
 *                     dazugewonnene Punkte/Gewonnene Spieltagswertungen)
 * Changelog: 0.6.1
 * Changelog: 0.6.1 - Bugfix: Zwei Dropdown-Wertelisten in "Regeltechnisches" waren nur
 *                     Annahmen ("Tippabgabeschluss ohne Anstoßtermin", "max. Spieltage im
 *                     Voraus") - anhand des vom Nutzer bereitgestellten Original-Quellcodes
 *                     (lmo-admintippoptions.php) auf die tatsächlich korrekten Werte
 *                     korrigiert: "Standard-Anstoßzeit"/"Erstes Spieltagsdatum 0 Uhr" (statt
 *                     der erfundenen Option "kein Abgabeschluss"), sowie 0/1/2/3/4/5/unbegrenzt
 *                     (statt 1/2/3/5/10/unbegrenzt). Annahme-Warnhinweise entfernt, da jetzt
 *                     bestätigt korrekt
 * Changelog: 0.6.0
 * Changelog: 0.6.0 - "Anmeldung" vollständig umgesetzt: Adresse/Realname optional abfragbar,
 *                     drei Freischaltungsarten (sofort/E-Mail/Admin), Admin-Benachrichtigung +
 *                     Bestätigungsmail an Tipper - alles exakt nach den Vorgesprächen
 * Changelog: 0.5.0
 * Changelog: 0.5.0 - Neuer sechster Optionen-Bereich "Tippabgabe": Ligenweise/Datumsweise
 *                     Tippabgabe (beide gemäß Original-Hilfedokumentation möglich, mind. eine
 *                     muss aktiv bleiben - client- UND serverseitig geprüft), plus die
 *                     Anzeige-Details (Pfeile, Tendenzen anderer, Durchschnittstipps,
 *                     automatische Tippeinsicht-Aktualisierung). Bewusst nur die Admin-Schalter
 *                     - die eigentliche Tipper-Ansicht folgt in einem eigenen Abschnitt
 * Changelog: 0.4.0
 * Changelog: 0.4.0 - "Regeltechnisches" vollständig umgesetzt: Tippabgabefrist, Team-
 *                     Höchstgröße, Joker an/aus + Multiplikator, max. Spieltage im Voraus, plus
 *                     die neue Warnung-Einstellung (Stunden vor Fristende) aus den
 *                     Vorgesprächen. Zwei Dropdown-Wertelisten (Abgabeschluss ohne Termin, max.
 *                     Spieltage im Voraus) waren nie im Detail besprochen worden - mit
 *                     sinnvollen Annahmen befüllt und im UI selbst als Annahme gekennzeichnet
 * Changelog: 0.3.0
 * Changelog: 0.3.0 - Tab "Optionen" bekommt eine Unter-Navigation für die fünf besprochenen
 *                     Bereiche (Punkteverteilung/Regeltechnisches/Anmeldung/Was zählt bei
 *                     Punktgleichheit/Tippbare Ligen). "Punkteverteilung" ist jetzt vollständig
 *                     funktionsfähig: alle vier Basiswerte + Sonderregeln aus den Vorgesprächen,
 *                     inkl. Tippmodus-Auswahl (Ergebnis/Tendenz) ganz oben - im Tendenz-Modus
 *                     werden die torzahl-abhängigen Felder per JS ausgegraut/deaktiviert (nicht
 *                     nur versteckt), analog zum Verhalten im Original-Screenshot. Speichert
 *                     über die neue Aktion save_tipp_punkte (siehe handler_tipp.php)
 * Changelog: 0.2.0
 * Changelog: 0.2.0 - Die vier Karteikarten des alten LMO-Tippspiels nachgebaut (Auswertung /
 *                     Newsletter-Reminder / Userverwaltung / Optionen), als Tab-Navigation nach
 *                     demselben Muster wie admin/view_liga_settings.php. Jeder Tab zeigt aktuell
 *                     noch einen eigenen Platzhalter - die tatsächlichen Inhalte (Rangliste,
 *                     Mailversand, Tipper-Verwaltung, die zahlreichen in den Vorgesprächen
 *                     festgelegten Einstellungen) folgen in kommenden Sitzungen, ein Tab nach
 *                     dem anderen.
 * Changelog: 0.1.0 - Initiale Version: reiner Platzhalter, damit der neue Navigationspunkt
 *                     "Tippspiel" nicht ins Leere führt.
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */

$tippTab = $_GET['tab'] ?? 'auswertung';

// Gemeinsame Formular-Stile, werden in mehreren Tabs (Optionen, Userverwaltung) genutzt
$selSt = 'background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.87rem';
$inpSt = $selSt . ';width:100px';
$tdR   = 'style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);white-space:nowrap"';
$tdL   = 'style="padding:5px 10px"';

$tippTabs = [
    'auswertung'     => t('tipp_tab_auswertung'),
    'newsletter'     => t('tipp_tab_newsletter'),
    'userverwaltung' => t('tipp_tab_userverwaltung'),
    'optionen'       => t('tipp_tab_optionen'),
];
?>

<!-- Tab-Navigation -->
<div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
<?php foreach ($tippTabs as $tippKey => $tippLabel) {
    $tippActive = $tippKey === $tippTab; ?>
  <a href="?action=tippspiel&tab=<?= $tippKey ?>"
     style="padding:8px 16px;font-size:.83rem;text-decoration:none;
            border-radius:var(--radius) var(--radius) 0 0;
            background:<?= $tippActive ? 'var(--surface)' : 'var(--surface2)' ?>;
            border:1px solid var(--border);
            border-bottom:<?= $tippActive ? '1px solid var(--surface)' : '1px solid var(--border)' ?>;
            color:<?= $tippActive ? 'var(--accent)' : 'var(--muted)' ?>;
            font-weight:<?= $tippActive ? '600' : '400' ?>;margin-right:3px"><?= h($tippLabel) ?></a>
<?php } ?>
</div>

<div class="card" style="border-radius:0 var(--radius) var(--radius) var(--radius);margin-top:0">
<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: AUSWERTUNG
// ═══════════════════════════════════════════════════════════════════════
if ($tippTab === 'auswertung') { ?>
  <h2 style="margin-bottom:8px"><?= h(t('tipp_tab_auswertung')) ?></h2>
  <p style="color:var(--muted);font-size:.9rem"><?= h(t('tipp_placeholder_text_auswertung')) ?></p>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: NEWSLETTER/REMINDER
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tippTab === 'newsletter') {
    $nlLigen = getTippbareLigenKandidaten();
    $nlTipper = getAllTipper();
    $nlSpieltage = [];
    try {
        foreach ($nlLigen as $nlLiga) {
            $stmt = getDB()->prepare('SELECT nummer FROM ' . tbl('liga_spieltage') . ' WHERE liga_id = ? ORDER BY nummer ASC');
            $stmt->execute([(int)$nlLiga['id']]);
            $nlSpieltage[(int)$nlLiga['id']] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
    } catch (Throwable) {}
    ?>
  <form method="post" action="?action=send_tipp_mail" id="tipp-mail-form">
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td style="padding:6px 10px" colspan="2"><label><input type="radio" name="mailart" value="alle" checked onchange="tippMailUpdateTemplate('alle')"> <?= h(t('tipp_mail_an_alle')) ?></label></td>
      </tr>
      <tr>
        <td style="padding:6px 10px"><label><input type="radio" name="mailart" value="persoenlich" onchange="tippMailUpdateTemplate('persoenlich')"> <?= h(t('tipp_mail_persoenlich')) ?></label></td>
        <td style="padding:6px 10px">
          <select name="adressat" style="<?= $selSt ?>;width:auto" onchange="document.querySelector('input[name=mailart][value=persoenlich]').checked=true;tippMailUpdateTemplate('persoenlich')">
            <option value=""><?= h(t('tipp_bitte_waehlen')) ?></option>
<?php foreach ($nlTipper as $nt) { ?>
            <option value="<?= (int)$nt['id'] ?>"><?= h($nt['nickname']) ?> (<?= h(trim(($nt['vorname'] ?? '') . ' ' . ($nt['nachname'] ?? ''))) ?> - <?= h($nt['email']) ?>)</option>
<?php } ?>
          </select>
        </td>
      </tr>
      <tr>
        <td style="padding:6px 10px;vertical-align:top"><label><input type="radio" name="mailart" value="reminder" onchange="tippMailUpdateTemplate('reminder')"> <?= h(t('tipp_mail_reminder')) ?></label></td>
        <td style="padding:6px 10px">
<?php foreach ($nlLigen as $nlLiga) {
    $nlLigaId = (int)$nlLiga['id']; ?>
          <div style="margin-bottom:4px">
            <label><input type="radio" name="reminder_liga" value="<?= $nlLigaId ?>" onchange="document.querySelector('input[name=mailart][value=reminder]').checked=true;tippMailUpdateTemplate('reminder')"> <?= h($nlLiga['name']) ?></label>
            <select name="spieltag_<?= $nlLigaId ?>" style="<?= $selSt ?>;width:auto" onchange="document.querySelector('input[name=reminder_liga][value=<?= $nlLigaId ?>]').checked=true;document.querySelector('input[name=mailart][value=reminder]').checked=true;tippMailUpdateTemplate('reminder')">
              <option value="0"><?= h(t('tipp_alle_spieltage')) ?></option>
<?php foreach ($nlSpieltage[$nlLigaId] ?? [] as $stNr) { ?>
              <option value="<?= $stNr ?>"><?= $stNr ?>. <?= h(t('tipp_spieltag_singular')) ?></option>
<?php } ?>
            </select>
          </div>
<?php } ?>
          <label><input type="radio" name="reminder_liga" value="0" onchange="document.querySelector('input[name=mailart][value=reminder]').checked=true;tippMailUpdateTemplate('reminder')"> <strong><?= h(t('tipp_alle_spieltage_aller_ligen')) ?></strong></label>
        </td>
      </tr>
      <tr>
        <td></td>
        <td style="padding:6px 10px">
          <label><input type="radio" name="tipper_bereich" value="alle" checked> <?= h(t('tipp_an_alle_tipper')) ?></label>
          &nbsp;&nbsp;
          <label><input type="radio" name="tipper_bereich" value="bereich"> <?= h(t('tipp_tipper')) ?></label>
          <input type="text" name="tipper_von" value="1" size="2" maxlength="4" onfocus="document.querySelector('input[name=tipper_bereich][value=bereich]').checked=true">
          <?= h(t('tipp_bis')) ?>
          <input type="text" name="tipper_bis" value="<?= max(1, count($nlTipper)) ?>" size="2" maxlength="4" onfocus="document.querySelector('input[name=tipper_bereich][value=bereich]').checked=true">
          &nbsp;&nbsp;<?= h(t('tipp_fuer_spiele_in_den_naechsten')) ?>
          <input type="text" name="tage" value="4" size="2" maxlength="2" onfocus="document.querySelector('input[name=mailart][value=reminder]').checked=true">
          <?= h(t('tipp_tagen')) ?>
        </td>
      </tr>
      <tr><td colspan="2" style="padding:10px 0"><hr style="border:none;border-top:1px solid var(--border)"></td></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_betreff')) ?></td>
        <td <?= $tdL ?>><input type="text" name="betreff" id="tipp-mail-betreff" value="<?= h(t('tipp_vorlage_newsletter_betreff')) ?>" maxlength="150" style="<?= $inpSt ?>;width:400px"></td>
      </tr>
      <tr>
        <td></td>
        <td style="padding:5px 10px"><textarea name="message" id="tipp-mail-message" rows="10" style="<?= $selSt ?>;width:100%;box-sizing:border-box"><?= h(t('tipp_vorlage_newsletter_text')) ?></textarea></td>
      </tr>
      <tr>
        <td></td>
        <td style="padding:14px 10px 0"><button type="submit" class="btn btn-primary"><?= h(t('tipp_abschicken')) ?></button></td>
      </tr>
      <tr>
        <td></td>
        <td style="padding:8px 10px;font-size:.78rem;color:var(--muted)">
          <?= h(t('tipp_platzhalter_label')) ?> [nick] <?= h(t('tipp_platzhalter_nick')) ?> · [name] <?= h(t('tipp_platzhalter_name')) ?> · [spiele] <?= h(t('tipp_platzhalter_spiele')) ?>
        </td>
      </tr>
    </table>
  </form>
  <script>
    var tippMailVorlagen = {
      alle: { betreff: <?= json_encode(t('tipp_vorlage_newsletter_betreff')) ?>, text: <?= json_encode(t('tipp_vorlage_newsletter_text')) ?> },
      persoenlich: { betreff: <?= json_encode(t('tipp_vorlage_persoenlich_betreff')) ?>, text: <?= json_encode(t('tipp_vorlage_persoenlich_text')) ?> },
      reminder: { betreff: <?= json_encode(t('tipp_vorlage_reminder_betreff')) ?>, text: <?= json_encode(t('tipp_vorlage_reminder_text')) ?> }
    };
    function tippMailUpdateTemplate(art) {
      var v = tippMailVorlagen[art];
      if (!v) return;
      document.getElementById('tipp-mail-betreff').value = v.betreff;
      document.getElementById('tipp-mail-message').value = v.text;
    }
  </script>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: USERVERWALTUNG
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tippTab === 'userverwaltung') {
    $tippEditNick = $_GET['edit'] ?? null;
    $tippIsNew    = isset($_GET['new']);

    if ($tippEditNick !== null || $tippIsNew) {
        // ── Bearbeiten / Neuanlegen ─────────────────────────────────────
        $tippUserRow = $tippIsNew ? null : getTipperByNickname($tippEditNick);
        if (!$tippIsNew && $tippUserRow === null) {
            echo '<p style="color:var(--red)">' . h(t('tipp_user_nicht_gefunden')) . '</p>';
        } else {
            $tuf = fn(string $k, string $d = '') => $tippUserRow[$k] ?? $d;
            $teams = getAllTeamsWithCount();
            $tuTeamId = (int)$tuf('team_id', '0');
            $tuAboIds = $tippUserRow ? getTipperAboLigaIds((int)$tippUserRow['id']) : [];
            $tuLigen = getTippbareLigenKandidaten();
            ?>
  <form method="post" action="?action=save_tipp_user">
    <input type="hidden" name="original_nickname" value="<?= h($tippUserRow['nickname'] ?? '') ?>">
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_nickname')) ?></td>
        <td <?= $tdL ?>>
<?php if ($tippIsNew) { ?>
          <input type="text" name="nickname" value="" maxlength="50" required style="<?= $inpSt ?>;width:200px">
<?php } else { ?>
          <strong><?= h($tuf('nickname')) ?></strong>
<?php } ?>
        </td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_passwort')) ?></td>
        <td <?= $tdL ?>><input type="password" name="password" value="" maxlength="100" style="<?= $inpSt ?>;width:200px" title="<?= h(t('tipp_passwort_hinweis')) ?>"></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_vorname')) ?></td>
        <td <?= $tdL ?>><input type="text" name="vorname" value="<?= h($tuf('vorname')) ?>" maxlength="50" style="<?= $inpSt ?>;width:200px"></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_nachname')) ?></td>
        <td <?= $tdL ?>><input type="text" name="nachname" value="<?= h($tuf('nachname')) ?>" maxlength="50" style="<?= $inpSt ?>;width:200px"></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_strasse')) ?></td>
        <td <?= $tdL ?>><input type="text" name="strasse" value="<?= h($tuf('strasse')) ?>" maxlength="100" style="<?= $inpSt ?>;width:200px"></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_plz')) ?></td>
        <td <?= $tdL ?>><input type="text" name="plz" value="<?= h($tuf('plz')) ?>" maxlength="10" style="<?= $inpSt ?>;width:100px"></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_ort')) ?></td>
        <td <?= $tdL ?>><input type="text" name="ort" value="<?= h($tuf('ort')) ?>" maxlength="80" style="<?= $inpSt ?>;width:200px"></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_email')) ?></td>
        <td <?= $tdL ?>><input type="email" name="email" value="<?= h($tuf('email')) ?>" maxlength="150" required style="<?= $inpSt ?>;width:250px"></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_freigeschaltet')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="freigeschaltet" value="1"<?= (int)$tuf('freigeschaltet', '0') === 1 ? ' checked' : '' ?>></td>
      </tr>
<?php if (!$tippIsNew) { ?>
      <tr><th colspan="2" style="text-align:left;padding:12px 10px 4px;font-size:.85rem"><?= h(t('tipp_tab_newsletter')) ?></th></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_col_newsletter')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="newsletter" value="1"<?= (int)$tuf('newsletter', '1') === 1 ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_col_reminder')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="reminder" value="1"<?= (int)$tuf('reminder', '1') === 1 ? ' checked' : '' ?>></td>
      </tr>
<?php } ?>
      <tr><th colspan="2" style="text-align:left;padding:12px 10px 4px;font-size:.85rem"><?= h(t('tipp_col_team')) ?></th></tr>
      <tr>
        <td></td>
        <td <?= $tdL ?>><label><input type="radio" name="team_radio" value="keinem"<?= $tuTeamId === 0 ? ' checked' : '' ?>> <?= h(t('tipp_team_keinem')) ?></label></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_team_bestehend')) ?></td>
        <td <?= $tdL ?>>
          <select name="team_bestehend" style="<?= $selSt ?>;width:auto" onchange="document.querySelector('input[name=team_radio][value=bestehend]').checked=true">
            <option value=""><?= h(t('tipp_bitte_waehlen')) ?></option>
<?php foreach ($teams as $team) { ?>
            <option value="<?= (int)$team['id'] ?>"<?= $tuTeamId === (int)$team['id'] ? ' selected' : '' ?>><?= h($team['name']) ?> [<?= (int)$team['mitglieder'] ?>]</option>
<?php } ?>
          </select>
          <input type="radio" name="team_radio" value="bestehend" style="display:none"<?= $tuTeamId > 0 ? ' checked' : '' ?>>
        </td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_team_neu')) ?></td>
        <td <?= $tdL ?>>
          <input type="text" name="team_neu" value="" maxlength="50" style="<?= $inpSt ?>;width:200px" onfocus="document.querySelector('input[name=team_radio][value=neu]').checked=true">
          <input type="radio" name="team_radio" value="neu" style="display:none">
        </td>
      </tr>
<?php if (!empty($tuLigen)) { ?>
      <tr><th colspan="2" style="text-align:left;padding:12px 10px 4px;font-size:.85rem"><?= h(t('tipp_abonnierte_ligen')) ?></th></tr>
      <tr>
        <td></td>
        <td <?= $tdL ?>>
<?php foreach ($tuLigen as $tuLiga) { ?>
          <label style="display:block;padding:2px 0"><input type="checkbox" name="tipper_ligen[]" value="<?= (int)$tuLiga['id'] ?>"<?= in_array((int)$tuLiga['id'], $tuAboIds, true) ? ' checked' : '' ?>> <?= h($tuLiga['name']) ?></label>
<?php } ?>
        </td>
      </tr>
<?php } ?>
      <tr>
        <td></td>
        <td style="padding:14px 10px 0">
<?php if (!$tippIsNew) { ?>
          <a href="?action=delete_tipp_user&nick=<?= urlencode($tuf('nickname')) ?>" class="btn btn-danger" style="margin-right:8px" onclick="return confirm(<?= json_encode(t('tipp_confirm_delete')) ?>)"><?= h(t('common_delete')) ?></a>
<?php } ?>
          <button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button>
          <a href="?action=tippspiel&tab=userverwaltung" class="btn" style="margin-left:8px"><?= h(t('common_cancel')) ?></a>
        </td>
      </tr>
    </table>
  </form>
<?php
        }
    } else {
        // ── Liste ────────────────────────────────────────────────────────
        $tippAlleUser = getAllTipper();
        ?>
  <table style="width:100%;border-collapse:collapse;font-size:.87rem">
    <thead>
      <tr style="border-bottom:1px solid var(--border)">
        <th style="text-align:left;padding:6px 10px">#</th>
        <th style="text-align:left;padding:6px 10px"><?= h(t('tipp_col_nickname')) ?></th>
        <th style="text-align:left;padding:6px 10px"><?= h(t('tipp_col_realname')) ?></th>
        <th style="text-align:left;padding:6px 10px"><?= h(t('tipp_col_team')) ?></th>
        <th style="text-align:left;padding:6px 10px"><?= h(t('tipp_col_letzter_tipp')) ?></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
<?php if (empty($tippAlleUser)) { ?>
      <tr><td colspan="6" style="padding:14px 10px;color:var(--muted)"><?= h(t('tipp_keine_tipper')) ?></td></tr>
<?php } else { foreach ($tippAlleUser as $i => $u) { ?>
      <tr style="border-bottom:1px solid var(--border)">
        <td style="padding:6px 10px"><?= $i + 1 ?></td>
        <td style="padding:6px 10px"><a href="mailto:<?= h($u['email']) ?>"><?= h($u['nickname']) ?></a></td>
        <td style="padding:6px 10px"><?= h(trim(($u['vorname'] ?? '') . ' ' . ($u['nachname'] ?? ''))) ?></td>
        <td style="padding:6px 10px"><?= h($u['team_name'] ?? '') ?></td>
        <td style="padding:6px 10px"><?= h($u['letzter_tipp'] ?? '–') ?></td>
        <td style="padding:6px 10px"><a href="?action=tippspiel&tab=userverwaltung&edit=<?= urlencode($u['nickname']) ?>"><?= h(t('common_edit')) ?></a></td>
      </tr>
<?php } } ?>
    </tbody>
  </table>
  <div style="padding-top:14px"><a href="?action=tippspiel&tab=userverwaltung&new=1" class="btn btn-primary"><?= h(t('tipp_neuer_tipper')) ?></a></div>
<?php
    }
} elseif ($tippTab === 'optionen') {
    $tippSubtab = $_GET['subtab'] ?? 'punkteverteilung';
    $tippSubtabs = [
        'punkteverteilung' => t('tipp_subtab_punkteverteilung'),
        'regeltechnisches' => t('tipp_subtab_regeltechnisches'),
        'tippabgabe'       => t('tipp_subtab_tippabgabe'),
        'anmeldung'        => t('tipp_subtab_anmeldung'),
        'punktgleichheit'  => t('tipp_subtab_punktgleichheit'),
        'tippbare_ligen'   => t('tipp_subtab_tippbare_ligen'),
    ];
    $ts  = fn(string $k, string $d = '') => getTippSetting($k, $d);
    $tsc = fn(string $k, string $d = '0') => getTippSetting($k, $d) === '1';
    $tippmodus = $ts('tippmodus', 'ergebnis');
    ?>
  <!-- Unter-Navigation der Optionen -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:12px">
<?php foreach ($tippSubtabs as $subKey => $subLabel) {
    $subActive = $subKey === $tippSubtab; ?>
    <a href="?action=tippspiel&tab=optionen&subtab=<?= $subKey ?>"
       style="padding:6px 12px;font-size:.82rem;text-decoration:none;border-radius:var(--radius);
              background:<?= $subActive ? 'var(--accent)' : 'var(--bg)' ?>;
              color:<?= $subActive ? '#fff' : 'var(--muted)' ?>;
              border:1px solid <?= $subActive ? 'var(--accent)' : 'var(--border)' ?>"><?= h($subLabel) ?></a>
<?php } ?>
  </div>

<?php if ($tippSubtab === 'punkteverteilung') { ?>
  <form method="post" action="?action=save_tipp_punkte" id="tipp-punkte-form">
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_tippmodus')) ?></td>
        <td <?= $tdL ?>>
          <select name="tippmodus" id="tipp-modus-select" style="<?= $selSt ?>" onchange="tippUpdateModusFields()">
            <option value="ergebnis"<?= $tippmodus === 'ergebnis' ? ' selected' : '' ?>><?= h(t('tipp_modus_ergebnis')) ?></option>
            <option value="tendenz"<?= $tippmodus === 'tendenz' ? ' selected' : '' ?>><?= h(t('tipp_modus_tendenz')) ?></option>
          </select>
        </td>
      </tr>
      <tr><td colspan="2" style="padding:10px 0"><hr style="border:none;border-top:1px solid var(--border)"></td></tr>

      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_pkt_ergebnis')) ?></td>
        <td <?= $tdL ?>><input type="number" name="pkt_ergebnis" value="<?= (int)$ts('pkt_ergebnis', '4') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_punkte_suffix')) ?></td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_pkt_tendenz_tordiff')) ?></td>
        <td <?= $tdL ?>><input type="number" name="pkt_tendenz_tordiff" value="<?= (int)$ts('pkt_tendenz_tordiff', '3') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_punkte_suffix')) ?></td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_pkt_tendenz')) ?></td>
        <td <?= $tdL ?>><input type="number" name="pkt_tendenz" value="<?= (int)$ts('pkt_tendenz', '2') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_punkte_suffix')) ?></td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_pkt_toranzahl')) ?></td>
        <td <?= $tdL ?>><input type="number" name="pkt_toranzahl" value="<?= (int)$ts('pkt_toranzahl', '1') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_punkte_suffix')) ?></td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_regel_tendenz_toranzahl')) ?></td>
        <td <?= $tdL ?>>
          <select name="pkt_tendenz_toranzahl_regel" style="<?= $selSt ?>">
            <option value="addieren"<?= $ts('pkt_tendenz_toranzahl_regel', 'addieren') === 'addieren' ? ' selected' : '' ?>><?= h(t('tipp_regel_addieren')) ?></option>
            <option value="nur_tendenz"<?= $ts('pkt_tendenz_toranzahl_regel') === 'nur_tendenz' ? ' selected' : '' ?>><?= h(t('tipp_regel_nur_tendenz')) ?></option>
          </select>
        </td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_regel_unentschieden')) ?></td>
        <td <?= $tdL ?>>
          <select name="pkt_unentschieden_regel" style="<?= $selSt ?>">
            <option value="tendenz_plus_toranzahl"<?= $ts('pkt_unentschieden_regel') === 'tendenz_plus_toranzahl' ? ' selected' : '' ?>><?= h(t('tipp_regel_tendenz_plus_toranzahl')) ?></option>
            <option value="nur_tendenz"<?= $ts('pkt_unentschieden_regel', 'nur_tendenz') === 'nur_tendenz' ? ' selected' : '' ?>><?= h(t('tipp_regel_nur_tendenz')) ?></option>
          </select>
        </td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_bonus_unentschieden')) ?></td>
        <td <?= $tdL ?>><input type="number" name="pkt_unentschieden_bonus" value="<?= (int)$ts('pkt_unentschieden_bonus', '1') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_punkte_suffix')) ?></td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_nv_unentschieden')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="pkt_nv_unentschieden_zaehlt" value="1"<?= $tsc('pkt_nv_unentschieden_zaehlt', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_ie_unentschieden')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="pkt_ie_unentschieden_zaehlt" value="1"<?= $tsc('pkt_ie_unentschieden_zaehlt', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr class="tipp-ergebnis-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_gruener_tisch')) ?></td>
        <td <?= $tdL ?>>
          <select name="pkt_gruener_tisch_regel" style="<?= $selSt ?>">
            <option value="tendenz_tordiff"<?= $ts('pkt_gruener_tisch_regel', 'tendenz_tordiff') === 'tendenz_tordiff' ? ' selected' : '' ?>><?= h(t('tipp_regel_gt_tendenz_tordiff')) ?></option>
            <option value="keine_punkte"<?= $ts('pkt_gruener_tisch_regel') === 'keine_punkte' ? ' selected' : '' ?>><?= h(t('tipp_regel_gt_keine_punkte')) ?></option>
          </select>
        </td>
      </tr>

      <tr class="tipp-tendenz-field">
        <td <?= $tdR ?>><?= h(t('tipp_label_pkt_tendenz_treffer')) ?></td>
        <td <?= $tdL ?>><input type="number" name="pkt_tendenz_treffer" value="<?= (int)$ts('pkt_tendenz_treffer', '1') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_punkte_suffix')) ?></td>
      </tr>

      <tr><td></td><td style="padding:14px 10px 0"><button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button></td></tr>
    </table>
  </form>
  <script>
    function tippUpdateModusFields() {
      var modus = document.getElementById('tipp-modus-select').value;
      var showErgebnis = modus === 'ergebnis';
      document.querySelectorAll('.tipp-ergebnis-field').forEach(function (row) {
        row.style.display = showErgebnis ? '' : 'none';
        row.querySelectorAll('input,select').forEach(function (el) { el.disabled = !showErgebnis; });
      });
      document.querySelectorAll('.tipp-tendenz-field').forEach(function (row) {
        row.style.display = showErgebnis ? 'none' : '';
        row.querySelectorAll('input,select').forEach(function (el) { el.disabled = showErgebnis; });
      });
    }
    tippUpdateModusFields();
  </script>
<?php } elseif ($tippSubtab === 'regeltechnisches') { ?>
  <form method="post" action="?action=save_tipp_regeln">
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_abgabe_minuten')) ?></td>
        <td <?= $tdL ?>><input type="number" name="abgabe_minuten" value="<?= (int)$ts('abgabe_minuten', '15') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_minuten_suffix')) ?></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_abgabeschluss_ohne_termin')) ?></td>
        <td <?= $tdL ?>>
          <select name="abgabeschluss_ohne_termin" style="<?= $selSt ?>;width:auto">
            <option value="standard_anstosszeit"<?= $ts('abgabeschluss_ohne_termin', 'standard_anstosszeit') === 'standard_anstosszeit' ? ' selected' : '' ?>><?= h(t('tipp_abgabeschluss_standard')) ?></option>
            <option value="erstes_datum_mitternacht"<?= $ts('abgabeschluss_ohne_termin') === 'erstes_datum_mitternacht' ? ' selected' : '' ?>><?= h(t('tipp_abgabeschluss_mitternacht')) ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_max_team_groesse')) ?></td>
        <td <?= $tdL ?>>
          <select name="max_team_groesse" style="<?= $selSt ?>">
            <option value="0"<?= $ts('max_team_groesse', '0') === '0' ? ' selected' : '' ?>><?= h(t('tipp_keine_teambildung')) ?></option>
<?php foreach ([2, 3, 5, 10, 20, 30, 50, 100] as $tgSize) { ?>
            <option value="<?= $tgSize ?>"<?= $ts('max_team_groesse') === (string)$tgSize ? ' selected' : '' ?>><?= $tgSize ?></option>
<?php } ?>
            <option value="unbegrenzt"<?= $ts('max_team_groesse') === 'unbegrenzt' ? ' selected' : '' ?>><?= h(t('tipp_unbegrenzt')) ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_max_spieltage_voraus')) ?></td>
        <td <?= $tdL ?>>
          <select name="max_spieltage_voraus" style="<?= $selSt ?>">
<?php foreach ([0, 1, 2, 3, 4, 5] as $stVal) { ?>
            <option value="<?= $stVal ?>"<?= $ts('max_spieltage_voraus') === (string)$stVal ? ' selected' : '' ?>><?= $stVal ?></option>
<?php } ?>
            <option value="unbegrenzt"<?= $ts('max_spieltage_voraus', 'unbegrenzt') === 'unbegrenzt' ? ' selected' : '' ?>><?= h(t('tipp_unbegrenzt')) ?></option>
          </select>

        </td>
      </tr>
      <tr><td colspan="2" style="padding:10px 0"><hr style="border:none;border-top:1px solid var(--border)"></td></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_joker_zulassen')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="joker_zulassen" value="1"<?= $tsc('joker_zulassen', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_joker_multiplikator')) ?></td>
        <td <?= $tdL ?>>
          <select name="joker_multiplikator" style="<?= $selSt ?>">
<?php foreach (['1.5', '2', '2.5', '3'] as $jm) { ?>
            <option value="<?= $jm ?>"<?= $ts('joker_multiplikator', '2') === $jm ? ' selected' : '' ?>><?= $jm ?></option>
<?php } ?>
          </select>
        </td>
      </tr>
      <tr><td colspan="2" style="padding:10px 0"><hr style="border:none;border-top:1px solid var(--border)"></td></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_warnung_stunden')) ?></td>
        <td <?= $tdL ?>><input type="number" name="warnung_stunden" value="<?= (int)$ts('warnung_stunden', '4') ?>" style="<?= $inpSt ?>"> <?= h(t('tipp_stunden_suffix')) ?></td>
      </tr>
      <tr><td></td><td style="padding:14px 10px 0"><button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button></td></tr>
    </table>
  </form>
<?php } elseif ($tippSubtab === 'tippabgabe') { ?>
  <form method="post" action="?action=save_tipp_abgabe" id="tipp-abgabe-form" onsubmit="return tippValidateAbgabeForm()">
    <table style="width:100%;border-collapse:collapse">
      <tr><td colspan="2" style="padding:0 0 6px;font-size:.82rem;color:var(--muted)"><?= h(t('tipp_abgabe_hinweis')) ?></td></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_ligenweise')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="abgabe_ligenweise" id="tipp-cb-ligenweise" value="1"<?= $tsc('abgabe_ligenweise', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_datumsweise')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="abgabe_datumsweise" id="tipp-cb-datumsweise" value="1"<?= $tsc('abgabe_datumsweise') ? ' checked' : '' ?>></td>
      </tr>
      <tr><td colspan="2" style="padding:10px 0"><hr style="border:none;border-top:1px solid var(--border)"></td></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_pfeile')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="abgabe_pfeile" value="1"<?= $tsc('abgabe_pfeile', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_tendenzen_anzeigen')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="abgabe_tendenzen_anzeigen" value="1"<?= $tsc('abgabe_tendenzen_anzeigen', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_durchschnitt_anzeigen')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="abgabe_durchschnitt_anzeigen" value="1"<?= $tsc('abgabe_durchschnitt_anzeigen', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_auto_tippeinsicht')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="abgabe_auto_tippeinsicht" value="1"<?= $tsc('abgabe_auto_tippeinsicht') ? ' checked' : '' ?>></td>
      </tr>
      <tr><td></td><td style="padding:14px 10px 0"><button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button></td></tr>
    </table>
  </form>
  <script>
    function tippValidateAbgabeForm() {
      var lw = document.getElementById('tipp-cb-ligenweise').checked;
      var dw = document.getElementById('tipp-cb-datumsweise').checked;
      if (!lw && !dw) {
        alert(<?= json_encode(t('tipp_alert_mind_eine_variante')) ?>);
        return false;
      }
      return true;
    }
  </script>
<?php } elseif ($tippSubtab === 'anmeldung') { ?>
  <form method="post" action="?action=save_tipp_anmeldung">
    <table style="width:100%;border-collapse:collapse">
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_adresse_abfragen')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="anmeldung_adresse_abfragen" value="1"<?= $tsc('anmeldung_adresse_abfragen') ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_realname_abfragen')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="anmeldung_realname_abfragen" value="1"<?= $tsc('anmeldung_realname_abfragen') ? ' checked' : '' ?>></td>
      </tr>
      <tr><td colspan="2" style="padding:10px 0"><hr style="border:none;border-top:1px solid var(--border)"></td></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_freischaltung')) ?></td>
        <td <?= $tdL ?>>
          <select name="anmeldung_freischaltung" style="<?= $selSt ?>;width:auto">
            <option value="sofort"<?= $ts('anmeldung_freischaltung', 'sofort') === 'sofort' ? ' selected' : '' ?>><?= h(t('tipp_freischaltung_sofort')) ?></option>
            <option value="email"<?= $ts('anmeldung_freischaltung') === 'email' ? ' selected' : '' ?>><?= h(t('tipp_freischaltung_email')) ?></option>
            <option value="admin"<?= $ts('anmeldung_freischaltung') === 'admin' ? ' selected' : '' ?>><?= h(t('tipp_freischaltung_admin')) ?></option>
          </select>
        </td>
      </tr>
      <tr><td colspan="2" style="padding:10px 0"><hr style="border:none;border-top:1px solid var(--border)"></td></tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_admin_benachrichtigen')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="anmeldung_admin_benachrichtigen" value="1"<?= $tsc('anmeldung_admin_benachrichtigen', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_bestaetigungsmail')) ?></td>
        <td <?= $tdL ?>><input type="checkbox" name="anmeldung_bestaetigungsmail" value="1"<?= $tsc('anmeldung_bestaetigungsmail', '1') ? ' checked' : '' ?>></td>
      </tr>
      <tr><td></td><td style="padding:14px 10px 0"><button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button></td></tr>
    </table>
  </form>
<?php } elseif ($tippSubtab === 'punktgleichheit') { ?>
<?php
  $tippKriterien = [
      'kein_kriterium'          => t('tipp_kriterium_kein'),
      'hoehere_quote'           => t('tipp_kriterium_quote'),
      'anzahl_spiele_getippt'   => t('tipp_kriterium_anzahl_getippt'),
      'anzahl_richtige_ergebnisse' => t('tipp_kriterium_richtige_ergebnisse'),
      'anzahl_richtige_tendenz_tordiff' => t('tipp_kriterium_richtige_tendenz_tordiff'),
      'anzahl_richtige_tendenz' => t('tipp_kriterium_richtige_tendenz'),
      'joker_punkte'            => t('tipp_kriterium_joker_punkte'),
      'spieltagswertungen'      => t('tipp_kriterium_spieltagswertungen'),
  ];
  ?>
  <form method="post" action="?action=save_tipp_punktgleichheit">
    <table style="width:100%;border-collapse:collapse">
<?php foreach ([1, 2, 3] as $krNr) {
    $krKey = 'kriterium_' . $krNr;
    $krDefault = $krNr === 1 ? 'hoehere_quote' : ($krNr === 2 ? 'anzahl_spiele_getippt' : 'anzahl_richtige_ergebnisse'); ?>
      <tr>
        <td <?= $tdR ?>><?= h(t('tipp_label_kriterium')) ?> <?= $krNr ?></td>
        <td <?= $tdL ?>>
          <select name="<?= $krKey ?>" style="<?= $selSt ?>;width:auto">
<?php foreach ($tippKriterien as $krVal => $krLabel) { ?>
            <option value="<?= $krVal ?>"<?= $ts($krKey, $krDefault) === $krVal ? ' selected' : '' ?>><?= h($krLabel) ?></option>
<?php } ?>
          </select>
        </td>
      </tr>
<?php } ?>
      <tr><td></td><td style="padding:14px 10px 0"><button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button></td></tr>
    </table>
  </form>

<?php } elseif ($tippSubtab === 'tippbare_ligen') {
    $tippImmerAlle = $tsc('tippbare_immer_alle', '1');
    $tippFreigegebenIds = getTippLigaFreigabeIds();
    $tippKandidaten = getTippbareLigenKandidaten();
    ?>
  <form method="post" action="?action=save_tipp_ligen" id="tipp-ligen-form">
    <div style="margin-bottom:12px">
      <label style="font-weight:600;font-size:.87rem">
        <input type="checkbox" name="tippbare_immer_alle" id="tipp-cb-immer-alle" value="1"<?= $tippImmerAlle ? ' checked' : '' ?> onchange="tippToggleLigenAuswahl()">
        <?= h(t('tipp_label_immer_alle')) ?>
      </label>
    </div>
    <div style="font-size:.82rem;color:var(--muted);margin-bottom:10px"><?= h(t('tipp_ligen_hinweis')) ?></div>
<?php if (empty($tippKandidaten)) { ?>
    <p style="color:var(--muted);font-size:.87rem"><?= h(t('tipp_ligen_keine')) ?></p>
<?php } else { ?>
    <div id="tipp-ligen-liste">
<?php foreach ($tippKandidaten as $tippLiga) {
    $tippChecked = $tippImmerAlle || in_array((int)$tippLiga['id'], $tippFreigegebenIds, true); ?>
      <label style="display:block;padding:4px 0;font-size:.87rem">
        <input type="checkbox" name="tippbare_ligen[]" value="<?= (int)$tippLiga['id'] ?>"<?= $tippChecked ? ' checked' : '' ?><?= $tippImmerAlle ? ' disabled' : '' ?> class="tipp-liga-cb">
        <?= h($tippLiga['name']) ?>
      </label>
<?php } ?>
    </div>
<?php } ?>
    <div style="padding-top:14px"><button type="submit" class="btn btn-primary"><?= h(t('common_save')) ?></button></div>
  </form>
  <script>
    function tippToggleLigenAuswahl() {
      var immerAlle = document.getElementById('tipp-cb-immer-alle').checked;
      document.querySelectorAll('.tipp-liga-cb').forEach(function (cb) {
        cb.disabled = immerAlle;
        if (immerAlle) { cb.checked = true; }
      });
    }
  </script>
<?php }
} ?>
</div>
