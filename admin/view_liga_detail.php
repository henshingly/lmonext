<?php
/**
 * Project: LMOnext
 * Filename: view_liga_detail.php
 * Fileversion: 1.7.0
 * Changelog: 1.7.0 - Team-Editor: neue direkte Team-ID-Eingabe (Alternative zur Namenssuche) –
 *                     Team-ID eintippen + "Übernehmen", schlägt per neuem team_by_id-AJAX-
 *                     Endpunkt nach und übernimmt den Treffer wie ein Suchergebnis
 *                     (selectDbTeam()); Fehlermeldung, falls die ID nicht existiert
 * Changelog: 1.6.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.6.0 - Alle Texte (PHP + JS) über t() übersetzt
 * Changelog: 1.5.0 - Archiv-Dropdown: hierarchische Ordner mit Einrückung
 * Changelog: 1.4.0 - Archiv-Dropdown: dunkles Styling, Hover via CSS-Klasse, Exception-Handling
 * Changelog: 1.3.0 - Archivieren-Dropdown in Aktions-Buttons; toggleArchivMenu JS
 * Changelog: 1.2.0 - toggleStDatum in script-Block verschoben; DB-Suche Team-Editor
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── View: Liga Detail ─────────────────────────────────────────────────────────
$ltype = (int)($ligaDetail['options']['Type']['option_value'] ?? 0);
$lid   = (int)$ligaDetail['liga']['id'];
?>
      <a href="?action=dashboard" class="back-link"><?= h(t('ld_back_link')) ?></a>
      <div class="card">
        <h2><?= h($ligaDetail['liga']['name']) ?>
          &nbsp;<?= $ltype === 1 ? '<span class="chip chip-yellow">'.h(t('dash_type_ko')).'</span>' : '<span class="chip chip-blue">'.h(t('dash_type_liga')).'</span>' ?>
        </h2>
        <p class="text-muted" style="font-size:.85rem"><?= h(t('ld_id_created', ['id' => $lid, 'datum' => $ligaDetail['liga']['datum']])) ?></p>
        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap">
          <a href="?action=liga_settings&id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_settings')) ?></a>
<?php if ($ltype !== 1) { ?>
          <a href="?action=tabelle&liga_id=<?= $lid ?>" class="btn btn-primary"><?= h(t('sp_btn_table')) ?></a>
<?php } ?>
          <a href="?action=spieltag&liga_id=<?= $lid ?>&nr=1" class="btn btn-muted"><?= h(t('ld_btn_enter_results')) ?></a>
          <a href="?action=export&liga_id=<?= $lid ?>" class="btn btn-muted"><?= h(t('ld_btn_export')) ?></a>
          <!-- Ins Archiv verschieben -->
          <div style="position:relative;display:inline-block" id="archiv-dd">
            <button type="button" class="btn btn-muted" onclick="toggleArchivMenu()"
                    style="border-color:var(--muted)"><?= h(t('ld_btn_archive_dd')) ?></button>
            <div id="archiv-menu" style="display:none;position:absolute;top:100%;left:0;z-index:100;
                 background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
                 min-width:200px;padding:6px 0;margin-top:4px;box-shadow:0 4px 12px #0004">
<?php
        try {
            $archFolders = getDB()->query('SELECT id,parent_id,name,sort FROM '.tbl('liga_archiv_folders').' ORDER BY sort,name')->fetchAll();
            if (empty($archFolders)) { ?>
              <div style="padding:8px 14px;font-size:.82rem;color:var(--muted)">
                <?= h(t('ld_no_folders')) ?>
                <a href="?action=archiv" style="color:var(--accent)"><?= h(t('ld_create_in_archive')) ?></a>
              </div>
<?php       } else {
                // Hierarchisch rendern: Eltern zuerst, dann Kinder eingerückt
                $byParent = [];
                foreach ($archFolders as $af) {
                    $byParent[(int)($af['parent_id'] ?? 0)][] = $af;
                }
                function renderArchivDdFolders(array $byParent, int $lid, int $parentId = 0, int $depth = 0): void {
                    if (empty($byParent[$parentId])) return;
                    if ($depth > 0 && $parentId !== 0) { ?>
              <div style="height:1px;background:var(--border);margin:2px 10px"></div>
<?php           }
                    foreach ($byParent[$parentId] as $af) {
                        $pad   = 10 + $depth * 16;
                        $icon  = $depth === 0 ? '📁' : '↳';
                        $style = $depth > 0 ? 'color:var(--muted);font-size:.8rem' : ''; ?>
              <form method="post" action="?action=move_liga_archiv" style="display:block">
                <input type="hidden" name="liga_id"   value="<?= $lid ?>">
                <input type="hidden" name="folder_id" value="<?= (int)$af['id'] ?>">
                <input type="hidden" name="redirect"  value="?action=archiv">
                <button type="submit" class="archiv-dd-item"
                        style="padding-left:<?= $pad ?>px;<?= $style ?>">
                  <?= $icon ?> <?= h($af['name']) ?>
                </button>
              </form>
<?php           renderArchivDdFolders($byParent, $lid, (int)$af['id'], $depth + 1);
                    }
                }
                renderArchivDdFolders($byParent, $lid);
            }
        } catch (Throwable) {}
?>
            </div>
          </div>
<?php
        if ($ltype === 1) {
            $expectedRounds = (int)($ligaDetail['options']['Rounds']['option_value'] ?? 0);
            $actualRounds   = count($ligaDetail['spieltage']);
            if ($expectedRounds > $actualRounds) { ?>
          <form method="post" action="?action=fix_ko_rounds" style="display:inline">
            <input type="hidden" name="liga_id" value="<?= $lid ?>">
            <button type="submit" class="btn btn-muted" style="border-color:var(--yellow);color:var(--yellow)">
              <?= h(t('ld_btn_fix_rounds', ['n' => $expectedRounds - $actualRounds])) ?>
            </button>
          </form>
<?php
            }
        } ?>
        </div>
      </div>
      <div class="form-row">
        <div class="card">
          <h2><?= h(t('ld_heading_teams', ['n' => count($ligaDetail['teams'])])) ?></h2>
          <table class="tbl" id="teams-tbl">
            <thead><tr><th><?= h(t('dash_col_name')) ?></th><th><?= h(t('ld_label_mittel_short')) ?></th><th><?= h(t('teams_col_kurz')) ?></th><th style="width:80px"></th></tr></thead>
            <tbody>
<?php
        foreach ($ligaDetail['teams'] as $t) { ?>
              <tr id="team-row-<?= (int)$t['id'] ?>">
                <td style="font-weight:500"><?= h($t['name']) ?></td>
                <td class="text-muted"><?= h($t['mittel']) ?></td>
                <td><?php if ($t['kurz']) { ?><span class="chip chip-blue"><?= h($t['kurz']) ?></span><?php } else { echo '–'; } ?></td>
                <td>
                  <button type="button" class="btn btn-muted btn-sm"
                          onclick="openTeamEditor(<?= (int)$t['id'] ?>, <?= h(json_encode($t['name'])) ?>, <?= h(json_encode($t['mittel'])) ?>, <?= h(json_encode($t['kurz'])) ?>)">✏️</button>
                </td>
              </tr>
              <tr id="team-edit-<?= (int)$t['id'] ?>" style="display:none">
                <td colspan="4" style="padding:0">
                  <!-- ── DB-Suche ──────────────────────────────────────── -->
                  <div style="background:var(--surface2);border-radius:var(--radius);padding:10px 12px;margin:4px 0 2px">
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:4px"><?= h(t('ld_label_db_search')) ?></label>
                    <div style="display:flex;gap:6px;align-items:center">
                      <input type="text" id="dbsearch-<?= (int)$t['id'] ?>"
                             placeholder="<?= h(t('ld_placeholder_name_search')) ?>" autocomplete="off"
                             oninput="teamDbSearch(<?= (int)$t['id'] ?>, this.value)"
                             style="flex:1;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.85rem">
                    </div>
                    <div id="dbresults-<?= (int)$t['id'] ?>"
                         style="margin-top:4px;max-height:160px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius);display:none;background:var(--bg)">
                    </div>
                    <label style="font-size:.73rem;color:var(--muted);display:block;margin:10px 0 4px"><?= h(t('ld_label_id_lookup')) ?></label>
                    <div style="display:flex;gap:6px;align-items:center">
                      <input type="number" min="1" id="dbid-<?= (int)$t['id'] ?>"
                             placeholder="<?= h(t('ld_placeholder_id_lookup')) ?>" autocomplete="off"
                             onkeydown="if(event.key==='Enter'){event.preventDefault();teamIdLookup(<?= (int)$t['id'] ?>);}"
                             style="width:120px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:5px 10px;font-size:.85rem">
                      <button type="button" class="btn btn-muted btn-sm" onclick="teamIdLookup(<?= (int)$t['id'] ?>)"><?= h(t('ld_btn_id_apply')) ?></button>
                      <span id="dbid-msg-<?= (int)$t['id'] ?>" style="font-size:.78rem;color:var(--muted)"></span>
                    </div>
                  </div>
                  <!-- ── Felder (manuell oder aus DB) ─────────────────── -->
                  <form method="post" action="?action=save_team"
                        style="display:grid;grid-template-columns:2fr 1.5fr 1fr auto auto;gap:8px;align-items:end;padding:8px 12px 10px">
                    <input type="hidden" name="team_id"  value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="liga_id"  value="<?= $lid ?>">
                    <!-- global_id: wenn gesetzt, wird das bestehende Global-Team mit der Liga verknüpft statt eines neuen angelegt -->
                    <input type="hidden" name="global_id" id="te-gid-<?= (int)$t['id'] ?>" value="">
                    <div>
                      <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('teams_field_name_required')) ?></label>
                      <input type="text" name="team_name" id="te-name-<?= (int)$t['id'] ?>"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem" required>
                    </div>
                    <div>
                      <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('ld_label_mittel_short')) ?></label>
                      <input type="text" name="team_mittel" id="te-mittel-<?= (int)$t['id'] ?>"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem">
                    </div>
                    <div>
                      <label style="font-size:.73rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('teams_col_kurz')) ?></label>
                      <input type="text" name="team_kurz" id="te-kurz-<?= (int)$t['id'] ?>" maxlength="10"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm" style="align-self:end">💾</button>
                    <button type="button" class="btn btn-muted btn-sm" style="align-self:end"
                            onclick="closeTeamEditor(<?= (int)$t['id'] ?>)">✕</button>
                  </form>
                </td>
              </tr>
<?php
        } ?>
            </tbody>
          </table>
        </div>
        <div class="card">
          <h2><?= h(t('ld_heading_spieltage', ['n' => count($ligaDetail['spieltage'])])) ?></h2>
          <table class="tbl">
            <thead><tr><th><?= h(t('wiz_col_hash')) ?></th><th><?= h(t('ld_col_start')) ?></th><th><?= h(t('ld_col_partien')) ?></th><th><?= h(t('ld_col_gespielt')) ?></th><th></th></tr></thead>
            <tbody>
<?php
        foreach ($ligaDetail['spieltage'] as $st) {
            $g = (int)$st['gespielt']; $tc = (int)$st['partie_count'];
            $cls = $g === $tc && $tc > 0 ? 'chip-green' : ($g > 0 ? 'chip-yellow' : 'chip-blue');
            $startVal = $st['start'] ? substr($st['start'], 0, 10) : ''; ?>
              <tr id="st-row-<?= (int)$st['id'] ?>">
                <td><?= (int)$st['nummer'] ?></td>
                <td class="text-muted" style="font-size:.8rem"><?= $startVal ?: '—' ?></td>
                <td><?= $tc ?></td>
                <td><span class="chip <?= $cls ?>"><?= $g ?>/<?= $tc ?></span></td>
                <td style="white-space:nowrap">
                  <button type="button" class="btn btn-muted btn-sm"
                          onclick="toggleStDatum(<?= (int)$st['id'] ?>, '<?= h($startVal) ?>')"
                          title="<?= h(t('ld_tooltip_edit_date')) ?>">📅</button>
                  <a href="?action=spieltag&liga_id=<?= $lid ?>&nr=<?= (int)$st['nummer'] ?>" class="btn btn-muted btn-sm"><?= h(t('ld_btn_paarungen')) ?></a>
                </td>
              </tr>
              <tr id="st-edit-<?= (int)$st['id'] ?>" style="display:none">
                <td colspan="5">
                  <form method="post" action="?action=save_spieltag_datum"
                        style="display:flex;gap:8px;align-items:end;padding:6px 0">
                    <input type="hidden" name="spieltag_id" value="<?= (int)$st['id'] ?>">
                    <input type="hidden" name="liga_id" value="<?= $lid ?>">
                    <div>
                      <label style="font-size:.75rem;color:var(--muted);display:block;margin-bottom:3px"><?= h(t('ld_label_startdatum')) ?></label>
                      <input type="date" name="start_datum" id="st-datum-<?= (int)$st['id'] ?>"
                             style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.88rem">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">💾</button>
                    <button type="button" class="btn btn-muted btn-sm"
                            onclick="document.getElementById('st-edit-<?= (int)$st['id'] ?>').style.display='none'">✕</button>
                  </form>
                </td>
              </tr>
<?php
        } ?>
            </tbody>
          </table>
        </div>
      </div>
      <script>
      const i18nLdNoMatch = <?= json_encode(t('ld_js_no_match')) ?>;
      const i18nLdIdNotFound = <?= json_encode(t('ld_js_id_not_found')) ?>;
      const i18nLdLoading = <?= json_encode(t('common_loading')) ?>;
      // ── Team-Editor: öffnen/schließen ─────────────────────────────────────
      function openTeamEditor(id, name, mittel, kurz) {
        document.querySelectorAll('[id^="team-edit-"]').forEach(r => r.style.display = 'none');
        document.getElementById('te-name-'   + id).value = name;
        document.getElementById('te-mittel-' + id).value = mittel;
        document.getElementById('te-kurz-'   + id).value = kurz;
        document.getElementById('te-gid-'    + id).value = '';
        document.getElementById('dbsearch-'  + id).value = '';
        hideResults(id);
        document.getElementById('team-edit-' + id).style.display = '';
        document.getElementById('dbsearch-'  + id).focus();
      }

      function closeTeamEditor(id) {
        document.getElementById('team-edit-' + id).style.display = 'none';
      }

      function hideResults(id) {
        const r = document.getElementById('dbresults-' + id);
        if (r) { r.style.display = 'none'; r.innerHTML = ''; }
      }

      // ── DB-Suche ──────────────────────────────────────────────────────────
      let searchTimer = null;

      function teamDbSearch(tid, q) {
        clearTimeout(searchTimer);
        const results = document.getElementById('dbresults-' + tid);
        if (q.trim().length < 2) { hideResults(tid); return; }

        searchTimer = setTimeout(() => {
          fetch('?action=team_search&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
              if (!data.length) {
                results.innerHTML = '<div style="padding:8px 12px;font-size:.82rem;color:var(--muted)">' + i18nLdNoMatch + '</div>';
              } else {
                results.innerHTML = '';
                data.forEach(t => {
                  const div = document.createElement('div');
                  div.style.cssText = 'padding:10px 12px;font-size:.85rem;cursor:pointer;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;-webkit-tap-highlight-color:rgba(59,130,246,.2)';
                  div.innerHTML = `<span style="font-weight:500">${esc(t.name)}</span>`
                                + `<span style="color:var(--muted);font-size:.78rem">${esc(t.mittel)}${t.kurz ? ' · '+esc(t.kurz) : ''}</span>`;

                  // Hover (Desktop)
                  div.addEventListener('mouseover', () => div.style.background = 'var(--surface2)');
                  div.addEventListener('mouseout',  () => div.style.background = '');

                  // Auswahl – sowohl Touch als auch Mouse
                  function doSelect(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectDbTeam(tid, t.id, t.name, t.mittel, t.kurz);
                  }
                  div.addEventListener('touchstart', doSelect, { passive: false });
                  div.addEventListener('mousedown',  doSelect);

                  results.appendChild(div);
                });
              }
              results.style.display = 'block';
            })
            .catch(() => hideResults(tid));
        }, 280);
      }

      function selectDbTeam(tid, globalId, name, mittel, kurz) {
        document.getElementById('te-gid-'    + tid).value = globalId;
        document.getElementById('te-name-'   + tid).value = name;
        document.getElementById('te-mittel-' + tid).value = mittel;
        document.getElementById('te-kurz-'   + tid).value = kurz;
        document.getElementById('dbsearch-'  + tid).value = name;
        hideResults(tid);
      }

      // Direkte Team-ID-Eingabe: Team per numerischer ID nachschlagen und bei
      // Erfolg wie einen DB-Suchtreffer übernehmen (selectDbTeam).
      function teamIdLookup(tid) {
        const input = document.getElementById('dbid-' + tid);
        const msg   = document.getElementById('dbid-msg-' + tid);
        const id    = parseInt(input.value, 10);
        msg.style.color = 'var(--muted)';
        if (!id || id < 1) { return; }
        msg.textContent = i18nLdLoading;
        fetch('?action=team_by_id&id=' + id)
          .then(r => r.json())
          .then(t => {
            if (!t) {
              msg.style.color = 'var(--red)';
              msg.textContent = i18nLdIdNotFound;
              return;
            }
            selectDbTeam(tid, t.id, t.name, t.mittel, t.kurz);
            input.value = '';
            msg.style.color = 'var(--green)';
            msg.textContent = '✓ ' + t.name;
          })
          .catch(() => { msg.style.color = 'var(--red)'; msg.textContent = i18nLdIdNotFound; });
      }

      function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      }

      // Klick/Touch außerhalb schließt Dropdown
      document.addEventListener('touchstart', e => {
        if (!e.target.closest('[id^="dbresults-"]') && !e.target.closest('[id^="dbsearch-"]')) {
          document.querySelectorAll('[id^="dbresults-"]').forEach(r => { r.style.display='none'; });
        }
      }, { passive: true });
      document.addEventListener('mousedown', e => {
        if (!e.target.closest('[id^="dbresults-"]') && !e.target.closest('[id^="dbsearch-"]')) {
          document.querySelectorAll('[id^="dbresults-"]').forEach(r => { r.style.display='none'; });
        }
      });

      function toggleStDatum(id, current) {
        const editRow = document.getElementById('st-edit-' + id);
        if (editRow.style.display === 'none') {
          document.getElementById('st-datum-' + id).value = current;
          editRow.style.display = '';
        } else {
          editRow.style.display = 'none';
        }
      }

      function toggleArchivMenu() {
        const m = document.getElementById('archiv-menu');
        m.style.display = m.style.display === 'none' ? 'block' : 'none';
      }
      document.addEventListener('click', e => {
        const dd = document.getElementById('archiv-dd');
        if (dd && !dd.contains(e.target)) {
          document.getElementById('archiv-menu').style.display = 'none';
        }
      });
      </script>
