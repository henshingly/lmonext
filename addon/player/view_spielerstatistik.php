<?php
/**
 * Project: LMOnext
 * Filename: addon/player/view_spielerstatistik.php
 * Fileversion: 1.3.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */

$lid     = (int)($spielerstatData['liga_id'] ?? 0);
$liga    = $spielerstatData['liga'] ?? null;
$spalten = $spielerstatData['spalten'] ?? [];
$spieler = $spielerstatData['spieler'] ?? [];
$cfg     = $spielerstatData['config'] ?? [];
$ligaTeams = $spielerstatData['teams'] ?? [];
// Spalten, deren Name auf "Team"/"Mannschaft"/"Verein" hindeutet, bekommen
// beim Werte-Eintragen ein Dropdown mit den aktuellen Teams der Liga statt
// eines Freitextfeldes – erleichtert die Zuordnung und vermeidet
// Tippfehler/uneinheitliche Schreibweisen. Der gespeicherte Wert bleibt
// weiterhin einfacher Text (kein Fremdschlüssel auf teams_global), damit
// bestehende Werte und das generische Spalten-Datenmodell unverändert
// bleiben; das Dropdown ist rein eine Eingabehilfe.
$isTeamColumn = static fn(string $name): bool => in_array(strtolower(trim($name)), ['team', 'mannschaft', 'verein'], true);
?>
      <a href="?action=liga_detail&id=<?= $lid ?>" class="back-link"><?= h(t('spst_back_link')) ?></a>
      <h1 style="margin:8px 0 18px"><?= h(t('spst_title', ['liga' => $liga['name'] ?? ''])) ?></h1>

      <!-- Spalte hinzufügen -->
      <div class="card" style="margin-bottom:16px">
        <h2><?= h(t('spst_heading_add_column')) ?></h2>
        <p style="color:var(--muted);font-size:.78rem;margin-bottom:10px"><?= h(t('spst_column_image_hint')) ?></p>
        <form method="post" action="?action=spst_addcolumn" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <div>
            <label style="display:block;font-size:.78rem;color:var(--muted)"><?= h(t('spst_col_name')) ?></label>
            <input type="text" name="name" required
                   style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                          border-radius:var(--radius);padding:7px 10px;font-size:.85rem">
          </div>
          <div>
            <label style="display:block;font-size:.78rem;color:var(--muted)"><?= h(t('spst_col_typ')) ?></label>
            <select name="typ" id="spst-new-typ" onchange="document.getElementById('spst-new-formel-wrap').style.display=(this.value==='formel')?'block':'none'"
                    style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                           border-radius:var(--radius);padding:7px 10px;font-size:.85rem">
              <option value="zahl"><?= h(t('spst_typ_zahl')) ?></option>
              <option value="text"><?= h(t('spst_typ_text')) ?></option>
              <option value="formel"><?= h(t('spst_typ_formel')) ?></option>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:.78rem;color:var(--muted)"><?= h(t('spst_col_rolle')) ?></label>
            <select name="rolle"
                    style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                           border-radius:var(--radius);padding:7px 10px;font-size:.85rem">
              <option value="normal"><?= h(t('spst_rolle_normal')) ?></option>
              <option value="verein"><?= h(t('spst_rolle_verein')) ?></option>
              <option value="spielerlink"><?= h(t('spst_rolle_spielerlink')) ?></option>
            </select>
          </div>
          <div id="spst-new-formel-wrap" style="display:none">
            <label style="display:block;font-size:.78rem;color:var(--muted)"><?= h(t('spst_col_formel')) ?></label>
            <input type="text" name="formel" placeholder="z.B. Tore/Spiele oder ROUND(Tore/Spiele,2)"
                   style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                          border-radius:var(--radius);padding:7px 10px;font-size:.85rem;width:220px">
          </div>
          <button type="submit" class="btn btn-primary btn-sm"><?= h(t('spst_btn_add_column')) ?></button>
        <?= csrfField() ?></form>
      </div>

      <!-- Spieler hinzufügen -->
      <div class="card" style="margin-bottom:16px">
        <h2><?= h(t('spst_heading_add_player')) ?></h2>
        <form method="post" action="?action=spst_addplayer" style="display:flex;gap:10px;align-items:end">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <input type="text" name="name" required placeholder="<?= h(t('spst_col_name')) ?>"
                 style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                        border-radius:var(--radius);padding:7px 10px;font-size:.85rem">
          <button type="submit" class="btn btn-primary btn-sm"><?= h(t('spst_btn_add_player')) ?></button>
        <?= csrfField() ?></form>
      </div>

<?php if (!empty($spalten) && !empty($spieler)) { ?>
      <!-- Werte-Tabelle (Bulk-Edit) -->
      <div class="card" style="padding:0;overflow-x:auto;margin-bottom:16px">
        <form method="post" action="?action=spst_update">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <table class="tbl" style="margin:0;min-width:600px">
            <thead>
              <tr>
                <th></th>
<?php foreach ($spalten as $sp) { ?>
                <th>
                  <input type="text" name="spalte_name_<?= (int)$sp['id'] ?>" value="<?= h($sp['name']) ?>"
                         style="width:100%;background:transparent;border:none;color:var(--text);font-weight:600;font-size:.8rem">
<?php if ($sp['typ'] === 'formel') { ?>
                  <input type="text" name="spalte_formel_<?= (int)$sp['id'] ?>" value="<?= h((string)$sp['formel']) ?>"
                         title="<?= h(t('spst_col_formel')) ?>"
                         style="width:100%;background:var(--bg);border:1px solid var(--border);border-radius:4px;
                                color:var(--muted);font-size:.72rem;margin-top:3px;padding:2px 4px">
<?php } ?>
                  <div style="font-size:.68rem;color:var(--muted);margin-top:2px">
                    <?= h(t('spst_typ_' . $sp['typ'])) ?><?= $sp['rolle'] !== 'normal' ? ' · ' . h(t('spst_rolle_' . $sp['rolle'])) : '' ?>
                  </div>
                </th>
<?php } ?>
                <th></th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($spieler as $p) { ?>
              <tr>
                <td class="text-muted" style="font-size:.78rem">
                  #<?= (int)$p['id'] ?>
<?php $photo = !empty($p['global_player_id']) ? findPlayerPhotoPath((int)$p['global_player_id']) : null; ?>
                  <div style="margin-top:4px">
<?php if ($photo !== null) { ?>
                    <img src="<?= h($photo) ?>" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:50%;display:block;margin-bottom:2px">
<?php } ?>
                    <button type="button" class="btn btn-muted btn-sm" style="font-size:.68rem;padding:2px 5px"
                            onclick="document.getElementById('photo-input-<?= (int)$p['id'] ?>').click()"><?= h(t('spst_btn_photo')) ?></button>
                    <form id="photo-form-<?= (int)$p['id'] ?>" method="post" action="?action=spst_upload_photo" enctype="multipart/form-data" style="display:none">
                      <input type="hidden" name="liga_id" value="<?= $lid ?>">
                      <input type="hidden" name="global_player_id" value="<?= (int)($p['global_player_id'] ?? 0) ?>">
                      <input id="photo-input-<?= (int)$p['id'] ?>" type="file" name="photo" accept=".jpg,.jpeg,.png,.gif,.svg"
                             onchange="document.getElementById('photo-form-<?= (int)$p['id'] ?>').submit()">
                    <?= csrfField() ?></form>
<?php if ($photo !== null) { ?>
                    <form method="post" action="?action=spst_upload_photo" style="display:inline">
                      <input type="hidden" name="liga_id" value="<?= $lid ?>">
                      <input type="hidden" name="global_player_id" value="<?= (int)($p['global_player_id'] ?? 0) ?>">
                      <input type="hidden" name="remove_photo" value="1">
                      <button type="submit" class="btn btn-danger btn-sm" style="font-size:.68rem;padding:2px 5px"><?= h(t('spst_btn_photo_remove')) ?></button>
                    <?= csrfField() ?></form>
<?php } ?>
                  </div>
                </td>
<?php foreach ($spalten as $sp) {
                    $val = $p['werte'][$sp['id']] ?? '';
                    if ($sp['typ'] === 'formel') { ?>
                <td style="text-align:center;color:var(--muted)"><?= h($val) ?></td>
<?php             } elseif ($isTeamColumn($sp['name'])) { ?>
                <td>
                  <select name="wert_<?= (int)$p['id'] ?>_<?= (int)$sp['id'] ?>"
                          style="width:100%;min-width:110px;background:var(--bg);border:1px solid var(--border);
                                color:var(--text);border-radius:4px;padding:4px 6px;font-size:.82rem">
                    <option value=""<?= $val === '' ? ' selected' : '' ?>>—</option>
<?php               $valueMatchesTeam = false;
                    foreach ($ligaTeams as $teamName) {
                        if ($teamName === $val) { $valueMatchesTeam = true; }
                        ?>
                    <option value="<?= h($teamName) ?>"<?= $teamName === $val ? ' selected' : '' ?>><?= h($teamName) ?></option>
<?php               }
                    // Bestehender Wert passt zu keinem aktuellen Team der Liga (z.B. Team
                    // inzwischen umbenannt/entfernt, oder aus altem Import übernommen) -
                    // als zusätzliche Option anzeigen, damit er beim Speichern nicht
                    // unbemerkt durch "—" ersetzt wird.
                    if ($val !== '' && !$valueMatchesTeam) { ?>
                    <option value="<?= h($val) ?>" selected><?= h($val) ?> (<?= h(t('spst_team_col_unknown')) ?>)</option>
<?php               } ?>
                  </select>
                </td>
<?php             } else { ?>
                <td>
                  <input type="text" name="wert_<?= (int)$p['id'] ?>_<?= (int)$sp['id'] ?>" value="<?= h($val) ?>"
                         style="width:100%;min-width:70px;background:var(--bg);border:1px solid var(--border);
                                color:var(--text);border-radius:4px;padding:4px 6px;font-size:.82rem">
                </td>
<?php             }
                } ?>
                <td>
                  <button type="button" class="btn btn-danger btn-sm"
                          onclick="if(confirm('<?= h(t('spst_confirm_delete_player')) ?>')){document.getElementById('delplayer-<?= (int)$p['id'] ?>').submit();}">✕</button>
                </td>
              </tr>
<?php } ?>
            </tbody>
          </table>
          <div style="padding:12px 16px">
            <button type="submit" class="btn btn-success btn-sm"><?= h(t('spst_btn_save_values')) ?></button>
          </div>
        <?= csrfField() ?></form>
      </div>
<?php
      foreach ($spieler as $p) { ?>
      <form id="delplayer-<?= (int)$p['id'] ?>" method="post" action="?action=spst_delplayer" style="display:none">
        <input type="hidden" name="liga_id" value="<?= $lid ?>">
        <input type="hidden" name="spieler_id" value="<?= (int)$p['id'] ?>">
      <?= csrfField() ?></form>
<?php }
      foreach ($spalten as $sp) { ?>
      <form id="delcolumn-<?= (int)$sp['id'] ?>" method="post" action="?action=spst_delcolumn" style="display:none">
        <input type="hidden" name="liga_id" value="<?= $lid ?>">
        <input type="hidden" name="spalten_id" value="<?= (int)$sp['id'] ?>">
      <?= csrfField() ?></form>
<?php }
} else { ?>
      <div class="card" style="margin-bottom:16px;color:var(--muted);font-size:.88rem">
        <?= h(t('spst_empty_hint')) ?>
      </div>
<?php } ?>

<?php if (!empty($spalten)) { ?>
      <!-- Spalten löschen (separat, damit die Werte-Tabelle nicht zu breit wird) -->
      <div class="card" style="margin-bottom:16px">
        <h2><?= h(t('spst_heading_columns')) ?></h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
<?php foreach ($spalten as $i => $sp) { if ($i === 0) { continue; } // erste Spalte (Name) nicht löschbar ?>
          <span style="display:inline-flex;align-items:center;gap:6px;background:var(--bg);
                       border:1px solid var(--border);border-radius:var(--radius);padding:5px 10px;font-size:.8rem">
            <?= h($sp['name']) ?>
            <button type="button" onclick="if(confirm('<?= h(t('spst_confirm_delete_column')) ?>')){document.getElementById('delcolumn-<?= (int)$sp['id'] ?>').submit();}"
                    style="border:none;background:none;color:var(--red);cursor:pointer;font-size:.85rem">✕</button>
          </span>
<?php } ?>
        </div>
      </div>
<?php } ?>

      <!-- Konfiguration -->
      <div class="card" style="margin-bottom:16px">
        <h2><?= h(t('spst_heading_config')) ?></h2>
        <form method="post" action="?action=spst_saveconfig" style="display:flex;gap:16px;flex-wrap:wrap;align-items:end">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <div>
            <label style="display:block;font-size:.78rem;color:var(--muted)"><?= h(t('spst_cfg_per_page')) ?></label>
            <input type="number" name="per_page" value="<?= (int)($cfg['per_page'] ?? 17) ?>" min="0"
                   style="width:80px;background:var(--bg);border:1px solid var(--border);color:var(--text);
                          border-radius:var(--radius);padding:6px 8px;font-size:.85rem">
          </div>
          <div>
            <label style="display:block;font-size:.78rem;color:var(--muted)"><?= h(t('spst_cfg_link_label')) ?></label>
            <input type="text" name="link_label" value="<?= h((string)($cfg['link_label'] ?? 'Spielerstatistik')) ?>"
                   style="background:var(--bg);border:1px solid var(--border);color:var(--text);
                          border-radius:var(--radius);padding:6px 8px;font-size:.85rem">
          </div>
          <label style="font-size:.83rem;display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="show_zero" value="1"<?= !empty($cfg['show_zero']) ? ' checked' : '' ?>>
            <?= h(t('spst_cfg_show_zero')) ?>
          </label>
          <label style="font-size:.83rem;display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="show_per_club" value="1"<?= !empty($cfg['show_per_club']) ? ' checked' : '' ?>>
            <?= h(t('spst_cfg_show_per_club')) ?>
          </label>
          <label style="font-size:.83rem;display:flex;align-items:center;gap:6px">
            <input type="checkbox" name="show_extra_sort_column" value="1"<?= !empty($cfg['show_extra_sort_column']) ? ' checked' : '' ?>>
            <?= h(t('spst_cfg_show_extra_sort')) ?>
          </label>
          <button type="submit" class="btn btn-success btn-sm"><?= h(t('spst_btn_save_config')) ?></button>
        <?= csrfField() ?></form>
      </div>

<?php if (empty($spalten)) { ?>
      <!-- Import alter Statistik-Dateien: nur solange noch keine einzige Spalte
           angelegt wurde. Sobald der Admin mit dem manuellen Aufbau begonnen
           hat (mindestens die erste Spalte existiert), würde ein Import einer
           alten .stat-Datei mit dem bereits angelegten Aufbau kollidieren -
           daher komplett ausgeblendet statt nur eine Warnung anzuzeigen. -->
      <div class="card">
        <h2><?= h(t('spst_heading_import')) ?></h2>
        <p style="color:var(--muted);font-size:.83rem;margin-bottom:12px"><?= h(t('spst_import_hint')) ?></p>
        <form method="post" action="?action=spst_import" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
          <input type="hidden" name="liga_id" value="<?= $lid ?>">
          <div>
            <label style="display:block;font-size:.78rem;color:var(--muted)">.stat-Datei</label>
            <input type="file" name="statfile" accept=".stat" required>
          </div>
          <div>
            <label style="display:block;font-size:.78rem;color:var(--muted)">.cfg-Datei (optional)</label>
            <input type="file" name="cfgfile" accept=".cfg">
          </div>
          <button type="submit" class="btn btn-muted btn-sm"><?= h(t('spst_btn_import')) ?></button>
        <?= csrfField() ?></form>
      </div>
<?php } ?>
