<?php
/**
 * Project: LMOnext
 * Filename: view_users.php
 * Fileversion: 1.3.0
 * Changelog: 1.3.0 - E-Mail-Adresse (für "Passwort vergessen") jetzt auch nachträglich editierbar:
 *                     neues Feld im "Benutzer anlegen"-Formular, neue Spalte in der Tabelle,
 *                     neues Feld im Inline-Bearbeiten-Formular
 * Changelog: 1.2.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.2.0 - Alle Texte (PHP + JS-Bestätigung) über t() übersetzt
 * Changelog: 1.1.1 - Spalte "Letzter Login" in Benutzerliste
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── View: Users ──────────────────────────────────────────────────────────
?>
      <div class="card">
        <h2><?= h(t('usr_heading_create')) ?></h2>
        <form method="post" action="?action=create_user">
          <div class="form-row">
            <div class="form-group"><label><?= h(t('login_username')) ?></label><input type="text" name="new_username" autocomplete="off"></div>
            <div class="form-group"><label><?= h(t('usr_label_password_min8')) ?></label><input type="password" name="new_user_password" autocomplete="new-password"></div>
            <div class="form-group"><label><?= h(t('install_label_email')) ?></label><input type="email" name="new_user_email" autocomplete="email"></div>
          </div>
          <button type="submit" class="btn btn-primary"><?= h(t('usr_btn_create')) ?></button>
        </form>
      </div>
      <div class="card">
        <h2><?= h(t('usr_heading_existing')) ?></h2>
<?php
        if (empty($users)) { ?><p class="text-muted" style="font-size:.9rem"><?= h(t('usr_empty')) ?></p><?php } else { ?>
          <table class="tbl">
            <thead><tr><th><?= h(t('dash_col_id')) ?></th><th><?= h(t('login_username')) ?></th><th><?= h(t('install_label_email')) ?></th><th><?= h(t('usr_col_last_login')) ?></th><th><?= h(t('dash_col_actions')) ?></th></tr></thead>
            <tbody>
<?php
            foreach ($users as $u) {
                $isMe = $u['username'] === ($_SESSION['admin_user'] ?? ''); ?>
              <tr>
                <td class="text-muted" style="width:40px"><?= (int)$u['id'] ?></td>
                <td>
                  <?= h($u['username']) ?>
                  <?php if ($isMe) { ?><span class="chip chip-green" style="margin-left:6px"><?= h(t('usr_chip_me')) ?></span><?php } ?>
                </td>
                <td class="text-muted" style="font-size:.85rem"><?= !empty($u['email']) ? h($u['email']) : '—' ?></td>
                <td class="text-muted" style="font-size:.82rem;white-space:nowrap">
                  <?php if (!empty($u['last_login'])) {
                      $dt = new DateTime($u['last_login']);
                      echo $dt->format('d.m.Y H:i');
                  } else { echo '—'; } ?>
                </td>
                <td style="width:180px">
                  <button type="button" class="btn btn-muted btn-sm"
                          onclick="toggleEdit(<?= (int)$u['id'] ?>)"><?= h(t('usr_btn_edit')) ?></button>
<?php
                if (!$isMe) { ?>
                  <form method="post" action="?action=delete_user" style="display:inline;margin-left:4px"
                        onsubmit="return confirm('<?= h(addslashes(t('usr_confirm_delete', ['name' => $u['username']]))) ?>')">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><?= h(t('dash_btn_delete')) ?></button>
                  </form>
<?php
                } ?>
                </td>
              </tr>
              <!-- Inline-Bearbeiten-Formular -->
              <tr id="edit_row_<?= (int)$u['id'] ?>" style="display:none">
                <td></td>
                <td colspan="4">
                  <form method="post" action="?action=edit_user"
                        style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('login_username')) ?></label>
                      <input type="text" name="edit_username" value="<?= h($u['username']) ?>"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem" required>
                    </div>
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('install_label_email')) ?></label>
                      <input type="email" name="edit_email" value="<?= h($u['email'] ?? '') ?>" autocomplete="email"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem">
                    </div>
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('usr_label_new_password')) ?> <span style="color:var(--muted)"><?= h(t('usr_hint_empty_unchanged')) ?></span></label>
                      <input type="password" name="edit_password" autocomplete="new-password"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem">
                    </div>
                    <div>
                      <label style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('install_label_password2')) ?></label>
                      <input type="password" name="edit_password2" autocomplete="new-password"
                             style="width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:var(--radius);padding:7px 10px;font-size:.88rem">
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;grid-column:1/-1">
                      <button type="submit" class="btn btn-primary btn-sm"><?= h(t('usr_btn_save')) ?></button>
                      <button type="button" class="btn btn-muted btn-sm" onclick="toggleEdit(<?= (int)$u['id'] ?>)"><?= h(t('common_cancel')) ?></button>
                    </div>
                  </form>
                </td>
              </tr>
<?php
            } ?>
            </tbody>
          </table>
          <script>
          function toggleEdit(id) {
            const row = document.getElementById('edit_row_' + id);
            row.style.display = row.style.display === 'none' ? '' : 'none';
          }
          </script>
<?php } ?>
      </div>
