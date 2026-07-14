<?php
/**
 * Project: LMOnext
 * Filename: view_import_review.php
 * Fileversion: 1.0.0
 * Changelog: 1.0.0 - Initiale Version: Abgleichsseite zwischen Upload und tatsächlichem Import.
 *                     Wird nur angezeigt, wenn detectFuzzyTeamMatchesForImport() ungefähre
 *                     (nicht exakte) Namenstreffer mit bereits vorhandenen Teams gefunden hat –
 *                     der Admin entscheidet hier pro Team, ob der Name aus der DB übernommen
 *                     werden soll, bevor der eigentliche Import (?action=import_confirm) läuft.
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── View: Import-Abgleich (ungefähre Teamnamen-Treffer bestätigen) ──────────
$ambiguous = $_SESSION['import_pending_ambiguous'] ?? [];
if (empty($ambiguous) || empty($_SESSION['import_pending'])) {
    // Direkter Aufruf ohne vorherigen Upload, oder Session abgelaufen
    redirect('?action=import');
}

// Nach Datei gruppieren, für eine übersichtlichere Anzeige
$byFile = [];
foreach ($ambiguous as $amb) {
    $byFile[$amb['fileIdx']]['fileName'] = $amb['fileName'];
    $byFile[$amb['fileIdx']]['items'][]  = $amb;
}
?>
      <div class="card" style="max-width:820px">
        <h2><?= h(t('imp_review_heading', ['n' => count($ambiguous)])) ?></h2>
        <p style="color:var(--muted);font-size:.86rem;margin-bottom:18px"><?= h(t('imp_review_intro')) ?></p>

        <form method="post" action="?action=import_confirm">
<?php foreach ($byFile as $fileIdx => $group) { ?>
          <div style="margin-bottom:22px">
            <div style="font-weight:600;font-size:.88rem;margin-bottom:8px;color:var(--text)">
              📄 <?= h($group['fileName']) ?>
            </div>
<?php foreach ($group['items'] as $amb) {
    $cbId = 'adopt-' . $amb['fileIdx'] . '-' . $amb['nr']; ?>
            <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);
                        padding:10px 14px;margin-bottom:8px">
              <label for="<?= h($cbId) ?>" style="display:flex;align-items:flex-start;gap:10px;cursor:pointer">
                <input type="checkbox" id="<?= h($cbId) ?>"
                       name="adopt[<?= (int)$amb['fileIdx'] ?>][<?= (int)$amb['nr'] ?>]" value="1" checked
                       style="accent-color:var(--accent);width:15px;height:15px;flex:none;margin-top:3px">
                <span style="font-size:.85rem;line-height:1.5">
                  <?= t('imp_review_item', [
                        'import' => '<strong>' . h($amb['importName']) . '</strong>',
                        'db'     => '<strong>' . h($amb['dbName']) . '</strong>',
                        'id'     => (int)$amb['dbId'],
                      ]) ?><br>
                  <span style="color:var(--muted);font-size:.78rem">
                    <?= h(t('imp_review_db_details', ['kurz' => $amb['dbKurz'] ?: '–', 'mittel' => $amb['dbMittel'] ?: '–'])) ?>
                  </span>
                </span>
              </label>
            </div>
<?php } ?>
          </div>
<?php } ?>
          <div style="display:flex;gap:8px;margin-top:10px;padding-top:14px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-success btn-sm"><?= h(t('imp_review_btn_confirm')) ?></button>
            <a href="?action=import_cancel" class="btn btn-muted btn-sm" style="text-decoration:none"><?= h(t('imp_review_btn_cancel')) ?></a>
          </div>
        </form>
      </div>
