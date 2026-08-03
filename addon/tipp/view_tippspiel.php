<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/view_tippspiel.php
 * Fileversion: 0.5.0
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
} elseif ($tippTab === 'newsletter') { ?>
  <h2 style="margin-bottom:8px"><?= h(t('tipp_tab_newsletter')) ?></h2>
  <p style="color:var(--muted);font-size:.9rem"><?= h(t('tipp_placeholder_text_newsletter')) ?></p>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: USERVERWALTUNG
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tippTab === 'userverwaltung') { ?>
  <h2 style="margin-bottom:8px"><?= h(t('tipp_tab_userverwaltung')) ?></h2>
  <p style="color:var(--muted);font-size:.9rem"><?= h(t('tipp_placeholder_text_userverwaltung')) ?></p>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: OPTIONEN
// ═══════════════════════════════════════════════════════════════════════
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
    $selSt = 'background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.87rem';
    $inpSt = $selSt . ';width:100px';
    $tdR   = 'style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);white-space:nowrap"';
    $tdL   = 'style="padding:5px 10px"';
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
            <option value="standard_erstes_datum"<?= $ts('abgabeschluss_ohne_termin', 'standard_erstes_datum') === 'standard_erstes_datum' ? ' selected' : '' ?>><?= h(t('tipp_abgabeschluss_standard')) ?></option>
            <option value="kein_abgabeschluss"<?= $ts('abgabeschluss_ohne_termin') === 'kein_abgabeschluss' ? ' selected' : '' ?>><?= h(t('tipp_abgabeschluss_kein')) ?></option>
          </select>
          <div style="font-size:.72rem;color:var(--muted);margin-top:3px">⚠️ <?= h(t('tipp_annahme_hinweis')) ?></div>
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
<?php foreach ([1, 2, 3, 5, 10] as $stVal) { ?>
            <option value="<?= $stVal ?>"<?= $ts('max_spieltage_voraus') === (string)$stVal ? ' selected' : '' ?>><?= $stVal ?></option>
<?php } ?>
            <option value="unbegrenzt"<?= $ts('max_spieltage_voraus', 'unbegrenzt') === 'unbegrenzt' ? ' selected' : '' ?>><?= h(t('tipp_unbegrenzt')) ?></option>
          </select>
          <div style="font-size:.72rem;color:var(--muted);margin-top:3px">⚠️ <?= h(t('tipp_annahme_hinweis')) ?></div>
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
  <p style="color:var(--muted);font-size:.9rem"><?= h(t('tipp_placeholder_text_anmeldung')) ?></p>
<?php } elseif ($tippSubtab === 'punktgleichheit') { ?>
  <p style="color:var(--muted);font-size:.9rem"><?= h(t('tipp_placeholder_text_punktgleichheit')) ?></p>
<?php } elseif ($tippSubtab === 'tippbare_ligen') { ?>
  <p style="color:var(--muted);font-size:.9rem"><?= h(t('tipp_placeholder_text_tippbare_ligen')) ?></p>

<?php }
} ?>
</div>
