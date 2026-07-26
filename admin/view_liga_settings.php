<?php
/**
 * Project: LMOnext
 * Filename: view_liga_settings.php
 * Fileversion: 1.4.4
 * Changelog: 1.4.4 - Neue Einstellung "Spielfrei anzeigen" (ShowSpielfrei) im Tab Anzeigen/
 *                     Darstellung, direkt unter "Ergebnisse" - steuert, ob der "Spielfrei:
 *                     TEAMNAME"-Hinweis in Ergebnisse-Ansicht und PDF-Export erscheint (siehe
 *                     liga.php 3.10.3). Default "an" (kein stiller Verhaltenswechsel für
 *                     bestehende Ligen, da die Anzeige bereits ohne diese Einstellung
 *                     ausgeliefert wurde)
 * Changelog: 1.4.3
 * Changelog: 1.4.3 - Bugfix: Punktesystem-Tabelle (Tab Spielsystem) hatte für "nach
 *                     Verlängerung"/"nach Elfmeterschießen" nur ein einzelnes Eingabefeld
 *                     (Sieg) statt der vollen S/U/N-Spalten wie im alten LMO – die beiden
 *                     Felder missbrauchten zudem versehentlich goalfaktor/pointsfaktor,
 *                     dieselben Schlüssel wie der Grundwerte-Tab (Dezimalstellen-Anzeige),
 *                     wodurch sich beide Tabs beim Speichern gegenseitig überschrieben haben.
 *                     Jetzt volles 3×3-Eingabegitter mit eigenen Schlüsseln
 *                     PointsForWin/Draw/LostET bzw. PS, siehe computeStandings() 2.15.5
 * Changelog: 1.4.2
 * Changelog: 1.4.2 - Kalender/Spielpläne, Kreuztabelle/Fieberkurven und Spielerstatistik/
 *                     Ligastatistik wieder in einzelne Tabellenzeilen aufgeteilt (statt jeweils
 *                     zwei Checkboxen in einer gemeinsamen Zeile), konsistent zum Rest der
 *                     Tabelle (ein Eintrag pro Zeile)
 * Changelog: 1.4.1 - Neue Einstellung "Logo anzeigen" (ShowLogos) im Tab Anzeigen/Darstellung,
 *                     gilt für KO- und reguläre Ligen gleichermaßen. Steuert, ob Team-Logos
 *                     (siehe Teams (global) → Logo-Upload) in der Besucheransicht dieser Liga
 *                     erscheinen (Tabelle, Ergebnisse, Kreuztabelle, Teamvergleich,
 *                     Ligastatistik, Spielpläne)
 * Changelog: 1.4.0
 * Changelog: 1.4.0 - Farbwähler (Color-Picker) neben jeder Tabellenmarkierung ergänzt
 *                     (Champions League/-Qualifikation/Euroleague/Relegation/Absteiger/
 *                     Meister) – Farben waren bisher hartkodiert und wirkten sich zudem gar
 *                     nicht auf die Besucheransicht aus. Neue Options-Schlüssel {Key}Color,
 *                     Vorschau-Chip in der Admin-Ansicht zeigt jetzt die gewählte Farbe live
 * Changelog: 1.3.3 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.3.2 - Bugfix: "Tabelle"-Checkbox fehlte in dieser Ansicht komplett, obwohl
 *                     handler_settings.php den Schlüssel schon speicherte – wurde dadurch bei
 *                     jedem Speichern stillschweigend auf "0" zurückgesetzt; Checkbox ergänzt
 * Changelog: 1.3.1 - Bugfix: "Kalender"-Checkbox nutzte versehentlich denselben Schlüssel
 *                     wie "Spieltagsdatum" (DatC) und wurde nie gespeichert; jetzt eigener
 *                     Schlüssel "Kalender"
 * Changelog: 1.3.0 - Alle Texte über t() übersetzt
 * Changelog: 1.2.1 - Ticker-Sektion: Ein/Aus-Checkbox + Tickertext-Feld
 * Changelog: 1.2.0 - KO/Liga-Verzweigung; Playoff-Modus-Einstellungen fuer KO
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── View: Liga Settings ──────────────────────────────────────────────────────────
        $lid       = $ligaSettingsData['lid'];
        $liga      = $ligaSettingsData['liga'];
        $opts      = $ligaSettingsData['opts'];
        $ligaTeams = $ligaSettingsData['teams'] ?? [];
        $tab       = $_GET['tab'] ?? 'grundwerte';
        $o         = fn(string $k, string $d = '') => $opts[$k] ?? $d;
        $oc        = fn(string $k) => ($opts[$k] ?? '0') === '1';
        $isKO      = ($o('Type', '0') === '1');

        // Tab-Definitionen je nach Liga-Typ
        $tabs = $isKO
            ? ['grundwerte' => t('ls_tab_grundwerte'), 'anzeige' => t('ls_tab_anzeige')]
            : ['grundwerte' => t('ls_tab_grundwerte'), 'anzeige' => t('ls_tab_anzeige'),
               'spielsystem' => t('ls_tab_spielsystem'), 'tabelle' => t('ls_tab_tabelle'),
               'spieltage' => t('ls_tab_spieltage')];

        $selSt = 'background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.87rem';
        $inpSt = $selSt . ';width:120px';
        $tdR   = 'style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);white-space:nowrap"';
        $tdL   = 'style="padding:5px 10px"';
?>

      <!-- Tab-Navigation -->
      <div style="display:flex;gap:0;margin-bottom:0;flex-wrap:wrap">
<?php foreach ($tabs as $key => $label) {
    $active = $key === $tab; ?>
        <a href="?action=liga_settings&id=<?= $lid ?>&tab=<?= $key ?>"
           style="padding:8px 16px;font-size:.83rem;text-decoration:none;
                  border-radius:var(--radius) var(--radius) 0 0;
                  background:<?= $active ? 'var(--surface)' : 'var(--surface2)' ?>;
                  border:1px solid var(--border);
                  border-bottom:<?= $active ? '1px solid var(--surface)' : '1px solid var(--border)' ?>;
                  color:<?= $active ? 'var(--accent)' : 'var(--muted)' ?>;
                  font-weight:<?= $active ? '600' : '400' ?>;margin-right:3px"><?= h($label) ?></a>
<?php } ?>
      </div>

      <div class="card" style="border-radius:0 var(--radius) var(--radius) var(--radius);margin-top:0">
        <div style="margin-bottom:12px">
          <a href="?action=liga_detail&id=<?= $lid ?>" class="back-link">← <?= h($liga['name']) ?></a>
        </div>
        <form method="post" action="?action=save_liga_settings">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <input type="hidden" name="tab"     value="<?= h($tab) ?>">

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: GRUNDWERTE
// ═══════════════════════════════════════════════════════════════════════
if ($tab === 'grundwerte') { ?>
          <table style="width:100%;border-collapse:collapse;max-width:600px">
            <tr>
              <td <?= $tdR ?> style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);width:220px"><?= h(t('ls_label_liga_name')) ?></td>
              <td <?= $tdL ?>>
                <input type="text" name="liga_name" value="<?= h($o('Name', $liga['name'])) ?>"
                       style="width:100%;max-width:340px;<?= $selSt ?>">
              </td>
            </tr>
<?php if (!$isKO) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_alt_pkt')) ?></td>
              <td <?= $tdL ?>><input type="text" name="namePkt" value="<?= h($o('namePkt','Punkte')) ?>" style="<?= $inpSt ?>"></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_alt_tore')) ?></td>
              <td <?= $tdL ?>><input type="text" name="nameTor" value="<?= h($o('nameTor','Tore')) ?>" style="<?= $inpSt ?>"></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_dec_pkt')) ?></td>
              <td <?= $tdL ?>>
                <select name="goalfaktor" style="<?= $selSt ?>">
                  <?php foreach (['0'=>t('ls_opt_none'),'1'=>t('ls_opt_one'),'2'=>t('ls_opt_two')] as $v=>$l) { ?>
                  <option value="<?= $v ?>"<?= $o('goalfaktor','1')===$v?' selected':'' ?>><?= h($l) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_dec_tore')) ?></td>
              <td <?= $tdL ?>>
                <select name="pointsfaktor" style="<?= $selSt ?>">
                  <?php foreach (['0'=>t('ls_opt_none'),'1'=>t('ls_opt_one'),'2'=>t('ls_opt_two')] as $v=>$l) { ?>
                  <option value="<?= $v ?>"<?= $o('pointsfaktor','1')===$v?' selected':'' ?>><?= h($l) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
<?php } ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_fav_team')) ?></td>
              <td <?= $tdL ?>>
                <select name="favTeam" style="<?= $selSt ?>;min-width:220px">
                  <option value="0"><?= h(t('ls_opt_none_dash')) ?></option>
                  <?php foreach ($ligaTeams as $i => $t) { $tNr = $i + 1; ?>
                  <option value="<?= $tNr ?>"<?= $o('favTeam','0')==(string)$tNr?' selected':'' ?>><?= h($t['name']) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spielplan')) ?></td>
              <td <?= $tdL ?>>
                <select name="selTeam" style="<?= $selSt ?>;min-width:220px">
                  <option value="0"><?= h(t('ls_opt_none_dash_m')) ?></option>
                  <?php foreach ($ligaTeams as $i => $t) { $tNr = $i + 1; ?>
                  <option value="<?= $tNr ?>"<?= $o('selTeam','0')==(string)$tNr?' selected':'' ?>><?= h($t['name']) ?></option>
                  <?php } ?>
                </select>
              </td>
            </tr>
          </table>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: ANZEIGEN/DARSTELLUNG
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'anzeige') {
    $dfPresets = [
        ''              => '__',
        'd.m. H:i'      => 'd.m. H:i  (24.06. 14:38)',
        'd.m.Y H:i'     => 'd.m.Y H:i  (24.06.2026 14:38)',
        'D., d.m. H:i'  => 'D., d.m. H:i  (Mi., 24.06. 14:38)',
        'l, d.m. H:i'   => 'l, d.m. H:i  (Mittwoch, 24.06. 14:38)',
        'D., d.m.Y H:i' => 'D., d.m.Y H:i  (Mi., 24.06.2026 14:38)',
        'l, d.m.Y H:i'  => 'l, d.m.Y H:i  (Mittwoch, 24.06.2026 14:38)',
    ];
    $curDatF   = $o('DatF', 'd.m.Y H:i');
    $isPreset  = array_key_exists($curDatF, $dfPresets);
    $cbSt = ''; // inline checkbox style
?>
          <table style="width:100%;border-collapse:collapse;max-width:600px">
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_date_sort')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="DatS" value="1"<?= $oc('DatS')?' checked':'' ?>></td>
            </tr>
<?php if ($isKO) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_third_place')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="KlFin" value="1"<?= $oc('KlFin')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_playdown')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="playdown" value="1"<?= $oc('playdown')?' checked':'' ?>></td>
            </tr>
<?php } ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anstoss_termin')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="DatM" value="1"<?= $oc('DatM')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anstoss_format')) ?></td>
              <td style="padding:5px 10px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
                  <input type="radio" name="DatF_mode" value="preset" id="drf_preset"<?= $isPreset?' checked':'' ?>>
                  <select name="DatF_preset" id="drf_sel" style="<?= $selSt ?>" onchange="syncDatF()">
                    <?php foreach ($dfPresets as $v=>$l) { ?>
                    <option value="<?= h($v) ?>"<?= $curDatF===$v?' selected':'' ?>><?= h($l) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                  <input type="radio" name="DatF_mode" value="custom" id="drf_custom"<?= !$isPreset?' checked':'' ?>>
                  <input type="text" name="DatF_custom" id="drf_txt"
                         value="<?= !$isPreset ? h($curDatF) : '' ?>" placeholder="d.m.Y H:i"
                         style="<?= $inpSt ?>" oninput="syncDatF()">
                  <span style="font-size:.73rem;color:var(--muted)"><?= h(t('ls_label_php_dateformat')) ?></span>
                </div>
                <input type="hidden" name="DatF" id="drf_hidden" value="<?= h($curDatF) ?>">
                <script>
                document.querySelectorAll('[name=DatF_mode]').forEach(r => r.addEventListener('change', syncDatF));
                function syncDatF() {
                  const mode = document.querySelector('[name=DatF_mode]:checked')?.value;
                  document.getElementById('drf_hidden').value = mode === 'preset'
                    ? document.getElementById('drf_sel').value
                    : document.getElementById('drf_txt').value;
                }
                </script>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spieltagsdatum')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="DatC" value="1"<?= $oc('DatC')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_ergebnisse')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Ergebnis" value="1"<?= $oc('Ergebnis')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_show_spielfrei')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="ShowSpielfrei" value="1"<?= ($opts['ShowSpielfrei'] ?? '1') === '1' ?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_kalender')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Kalender" value="1"<?= $oc('Kalender')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_cb_spielplaene')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Plan" value="1"<?= $oc('Plan')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_show_logos')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="ShowLogos" value="1"<?= $oc('ShowLogos')?' checked':'' ?>></td>
            </tr>
<?php if (!$isKO) { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_tabelle')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Tabelle" value="1"<?= $oc('Tabelle')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_kreuztabelle')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Kreuz" value="1"<?= $oc('Kreuz')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_cb_fieberkurven')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="kurve1" value="1"<?= $oc('kurve1')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spielerstatistik')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="stats" value="1"<?= $oc('stats')?' checked':'' ?>></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_cb_ligastatistik')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="Ligastats" value="1"<?= $oc('Ligastats')?' checked':'' ?>></td>
            </tr>
<?php } else { ?>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_spielerstatistik')) ?></td>
              <td style="padding:5px 10px"><input type="checkbox" name="stats" value="1"<?= $oc('stats')?' checked':'' ?>></td>
            </tr>
<?php } ?>
            <tr>
              <td colspan="2" style="padding:10px 12px 4px;font-size:.82rem;font-weight:700;color:var(--text);
                                     background:var(--surface2);border-radius:var(--radius)"><?= h(t('ls_heading_ticker')) ?></td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_ticker_show')) ?></td>
              <td style="padding:5px 10px">
                <label><input type="checkbox" name="ticker" value="1"<?= $oc('ticker')?' checked':'' ?>> <?= h(t('common_yes')) ?></label>
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:7px 12px;font-size:.85rem;color:var(--muted);vertical-align:top"><?= h(t('sp_ticker_text_label')) ?></td>
              <td style="padding:5px 10px">
                <textarea name="tickertext" rows="3"
                          style="width:100%;max-width:480px;background:var(--bg);border:1px solid var(--border);
                                 color:var(--text);border-radius:var(--radius);padding:8px 10px;font-size:.87rem;
                                 font-family:inherit;resize:vertical"
                          placeholder="<?= h(t('ls_placeholder_tickertext')) ?>"><?= h($opts['tickertext'] ?? '') ?></textarea>
              </td>
            </tr>
            <tr>
              <td colspan="2" style="padding:10px 12px 4px;font-size:.82rem;font-weight:700;color:var(--text);
                                     background:var(--surface2);border-radius:var(--radius)"><?= h(t('ls_heading_verlinkungen')) ?></td>
            </tr>
            <tr>
              <td></td>
              <td style="padding:5px 10px">
                <label style="margin-right:16px"><input type="checkbox" name="urlT" value="1"<?= $oc('urlT')?' checked':'' ?>> <?= h(t('ls_cb_team_homepage')) ?></label>
              </td>
            </tr>
            <tr>
              <td></td>
              <td style="padding:5px 10px">
                <label><input type="checkbox" name="urlB" value="1"<?= $oc('urlB')?' checked':'' ?>> <?= h(t('ls_cb_spielberichte')) ?></label>
              </td>
            </tr>
<?php if ($isKO) { ?>
            <tr>
              <td colspan="2" style="padding:10px 12px 4px;font-size:.82rem;font-weight:700;color:var(--text);
                                     background:var(--surface2);border-radius:var(--radius);margin-top:8px">
                <?= h(t('ls_heading_playoff_mode')) ?>
              </td>
            </tr>
            <tr>
              <td colspan="2" style="padding:10px 12px">
                <div style="display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start">
                  <div style="font-size:.84rem;color:var(--muted);line-height:1.9">
                    <strong style="color:var(--text)">Best Of 3</strong><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_111')) ?><br>
                    <hr style="border-color:var(--border);margin:6px 0">
                    <strong style="color:var(--text)">Best Of 5</strong><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_111')) ?><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_221')) ?><br>
                    <hr style="border-color:var(--border);margin:6px 0">
                    <strong style="color:var(--text)">Best Of 7</strong><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_111')) ?><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_22111')) ?><br>
                    &nbsp;&nbsp;&nbsp;<?= h(t('ls_opt_mod_232')) ?>
                  </div>
                  <div>
                    <div style="font-size:.82rem;font-weight:600;color:var(--text);margin-bottom:8px;text-align:center"><?= h(t('ls_label_modusauswahl')) ?></div>
                    <select name="playoffmode" style="<?= $selSt ?>;min-width:170px">
                      <option value="0"<?= $o('playoffmode','0')==='0'?' selected':'' ?>><?= h(t('ls_opt_mod_111')) ?></option>
                      <option value="1"<?= $o('playoffmode','0')==='1'?' selected':'' ?>><?= h(t('ls_opt_mod_221')) ?></option>
                      <option value="2"<?= $o('playoffmode','0')==='2'?' selected':'' ?>><?= h(t('ls_opt_mod_22111')) ?></option>
                      <option value="3"<?= $o('playoffmode','0')==='3'?' selected':'' ?>><?= h(t('ls_opt_mod_232')) ?></option>
                    </select>
                  </div>
                </div>
              </td>
            </tr>
<?php } ?>
          </table>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: SPIELSYSTEM (nur Liga)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'spielsystem' && !$isKO) { ?>
          <table style="width:100%;border-collapse:collapse;max-width:500px">
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="MinusPoints" value="1"<?= $oc('MinusPoints')?' checked':'' ?>> <?= h(t('ls_cb_minuspunkte')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="OnRun" value="1"<?= $oc('OnRun')?' checked':'' ?>> <?= h(t('ls_cb_spielende_offen')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="HideDraw" value="1"<?= $oc('HideDraw')?' checked':'' ?>> <?= h(t('ls_cb_hide_draw')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="Direct" value="1"<?= $oc('Direct')?' checked':'' ?>> <?= h(t('ls_cb_direct_compare')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="Spez" value="1"<?= $oc('Spez')?' checked':'' ?>> <?= h(t('ls_cb_spez')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="enableGameSort" value="1"<?= $oc('enableGameSort')?' checked':'' ?>> <?= h(t('ls_cb_hand_sort')) ?></label></td></tr>
          </table>

          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px;margin-top:14px;max-width:500px">
            <div style="font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:12px"><?= h(t('ls_heading_punktesystem')) ?></div>
            <table style="border-collapse:collapse;font-size:.85rem">
              <thead>
                <tr>
                  <td style="padding:4px 10px"></td>
                  <td style="padding:4px 14px;text-align:center;color:var(--muted)"><?= h(t('ls_col_win')) ?></td>
                  <td style="padding:4px 14px;text-align:center;color:var(--muted)"><?= h(t('ls_col_draw')) ?></td>
                  <td style="padding:4px 14px;text-align:center;color:var(--muted)"><?= h(t('ls_col_loss')) ?></td>
                </tr>
              </thead>
              <tbody>
<?php
    $numSt = 'width:54px;text-align:center;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:4px 6px;font-size:.88rem';
?>
                <tr>
                  <td style="padding:5px 10px;text-align:right;color:var(--muted)"><?= h(t('ls_row_normal_end')) ?></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForWin"  value="<?= h($o('PointsForWin','3'))  ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForDraw" value="<?= h($o('PointsForDraw','1')) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForLost" value="<?= h($o('PointsForLost','0')) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                </tr>
                <tr>
                  <td style="padding:5px 10px;text-align:right;color:var(--muted)"><?= h(t('ls_row_extra_time')) ?></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForWinET"  value="<?= h($o('PointsForWinET',  $o('PointsForWin','3')))  ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForDrawET" value="<?= h($o('PointsForDrawET', $o('PointsForDraw','1'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForLostET" value="<?= h($o('PointsForLostET', $o('PointsForLost','0'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                </tr>
                <tr>
                  <td style="padding:5px 10px;text-align:right;color:var(--muted)"><?= h(t('ls_row_penalty')) ?></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForWinPS"  value="<?= h($o('PointsForWinPS',  $o('PointsForWin','3')))  ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForDrawPS" value="<?= h($o('PointsForDrawPS', $o('PointsForDraw','1'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                  <td style="padding:4px 6px"><input type="number" name="PointsForLostPS" value="<?= h($o('PointsForLostPS', $o('PointsForLost','0'))) ?>" min="0" max="99" style="<?= $numSt ?>"></td>
                </tr>
              </tbody>
            </table>
          </div>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: TABELLE (nur Liga)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'tabelle' && !$isKO) {
    $colorMap = [
        'Champ' => ['label' => t('ls_marker_champ'), 'bg' => '#22c55e22', 'default' => '#22c55e'],
        'CL'    => ['label' => t('ls_marker_cl'),     'bg' => '#3b82f622', 'default' => '#3b82f6'],
        'CK'    => ['label' => t('ls_marker_ck'),     'bg' => '#0ea5e922', 'default' => '#0ea5e9'],
        'UC'    => ['label' => t('ls_marker_uc'),     'bg' => '#f59e0b22', 'default' => '#f59e0b'],
        'AR'    => ['label' => t('ls_marker_ar'),     'bg' => '#f9731622', 'default' => '#f97316'],
        'AB'    => ['label' => t('ls_marker_ab'),     'bg' => '#ef444422', 'default' => '#ef4444'],
    ]; ?>
          <table style="width:100%;border-collapse:collapse;max-width:500px;margin-bottom:16px">
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="tableHinRueck" value="1"<?= $oc('tableHinRueck')?' checked':'' ?>> <?= h(t('ls_cb_hin_rueck_tables')) ?></label></td></tr>
            <tr><td style="padding:7px 12px"><label><input type="checkbox" name="tableHeimAusw" value="1"<?= $oc('tableHeimAusw')?' checked':'' ?>> <?= h(t('ls_cb_heim_ausw_tables')) ?></label></td></tr>
          </table>

          <div style="font-size:.82rem;font-weight:700;color:var(--text);margin-bottom:10px;padding:8px 12px;background:var(--surface2);border-radius:var(--radius);max-width:500px"><?= h(t('ls_heading_table_markers')) ?></div>
          <table style="border-collapse:collapse;max-width:500px">
<?php   foreach ($colorMap as $key => $info) {
            $val = $o($key, '0');
            $colorVal = $o($key . 'Color', $info['default']); ?>
            <tr>
              <td style="padding:6px 12px;width:80px;text-align:right">
<?php       if ($key === 'Champ') { ?>
                <input type="checkbox" name="Champ_enabled" value="1"<?= $val!=='0'?' checked':'' ?>>
<?php       } else { ?>
                <select name="<?= $key ?>" style="width:64px;<?= $selSt ?>">
                  <?php for ($n = 0; $n <= 36; $n++) { ?><option value="<?= $n ?>"<?= $val==(string)$n?' selected':'' ?>><?= $n ?></option><?php } ?>
                </select>
<?php       } ?>
              </td>
              <td style="padding:6px 8px">
                <input type="color" name="<?= $key ?>Color" value="<?= h($colorVal) ?>"
                       title="<?= h(t('ls_marker_color_title')) ?>"
                       style="width:36px;height:28px;padding:0;border:1px solid var(--border);border-radius:6px;background:none;cursor:pointer;vertical-align:middle">
              </td>
              <td style="padding:6px 12px">
                <span style="background:<?= h($colorVal) ?>22;border-left:3px solid <?= h($colorVal) ?>;border-radius:4px;padding:3px 12px;font-size:.84rem;color:var(--text)"><?= h($info['label']) ?></span>
              </td>
            </tr>
<?php   } ?>
          </table>
          <p style="font-size:.78rem;color:var(--muted);max-width:500px;margin-top:8px"><?= h(t('ls_marker_color_hint')) ?></p>

<?php
// ═══════════════════════════════════════════════════════════════════════
// TAB: SPIELTAGE (nur Liga)
// ═══════════════════════════════════════════════════════════════════════
} elseif ($tab === 'spieltage' && !$isKO) { ?>
          <div style="background:var(--red,#ef4444);color:#fff;border-radius:var(--radius);padding:10px 14px;margin-bottom:16px;font-size:.84rem;max-width:500px">
            ⚠️ <strong><?= h(t('ls_warning_heading')) ?></strong> <?= h(t('ls_warning_text')) ?>
          </div>
          <table style="border-collapse:collapse">
            <tr>
              <td style="text-align:right;padding:8px 14px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anzahl_spieltage')) ?></td>
              <td style="padding:6px 10px">
                <input type="number" name="Rounds" value="<?= h($o('Rounds','0')) ?>" min="1" max="200"
                       style="width:80px;text-align:center;<?= $selSt ?>">
              </td>
            </tr>
            <tr>
              <td style="text-align:right;padding:8px 14px;font-size:.85rem;color:var(--muted)"><?= h(t('ls_label_anzahl_spiele_pro_spieltag')) ?></td>
              <td style="padding:6px 10px">
                <input type="number" name="Matches" value="<?= h($o('Matches','0')) ?>" min="1" max="100"
                       style="width:80px;text-align:center;<?= $selSt ?>">
              </td>
            </tr>
          </table>
<?php } ?>

          <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-success"><?= h(t('ls_btn_save')) ?></button>
            <a href="?action=liga_detail&id=<?= $lid ?>" class="btn btn-muted" style="margin-left:8px"><?= h(t('common_cancel')) ?></a>
          </div>
        </form>
      </div>
