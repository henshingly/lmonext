<?php
/**
 * Project: LMOnext
 * Filename: addon/player/view_spst_import_review.php
 * Fileversion: 1.0.1
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */

$ambiguous = $_SESSION['spst_import_pending_ambiguous'] ?? [];
$lid       = (int)($_SESSION['spst_import_pending_liga'] ?? 0);
if (empty($ambiguous) || empty($_SESSION['spst_import_pending'])) {
    redirect('?action=spielerstatistik&liga_id=' . $lid);
}
?>
      <div class="card" style="max-width:820px">
        <h2><?= h(t('imp_review_heading', ['n' => count($ambiguous)])) ?></h2>
        <p style="color:var(--muted);font-size:.86rem;margin-bottom:18px"><?= h(t('spst_review_intro')) ?></p>

        <form method="post" action="?action=spst_import_confirm">
<?php foreach ($ambiguous as $rowIdx => $amb) { ?>
          <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);
                      padding:10px 14px;margin-bottom:8px">
            <div style="font-size:.85rem;line-height:1.5;margin-bottom:8px">
<?php       if (count($amb['candidates']) > 1) { ?>
              <?= t('imp_review_item_multi', ['import' => '<strong>' . h($amb['importName']) . '</strong>', 'n' => count($amb['candidates'])]) ?>
<?php       } else {
                $cand = $amb['candidates'][0]; ?>
              <?= t('imp_review_item', ['import' => '<strong>' . h($amb['importName']) . '</strong>', 'db' => '<strong>' . h($cand['name']) . '</strong>', 'id' => (int)$cand['id']]) ?>
<?php       } ?>
            </div>
            <label for="adopt-<?= (int)$rowIdx ?>" style="display:block;font-size:.78rem;color:var(--muted);margin-bottom:4px"><?= h(t('imp_review_select_label')) ?></label>
            <select id="adopt-<?= (int)$rowIdx ?>" name="adopt[<?= (int)$rowIdx ?>]"
                    style="width:100%;max-width:420px;background:var(--surface);border:1px solid var(--border);
                           color:var(--text);border-radius:var(--radius);padding:6px 10px;font-size:.85rem">
<?php       foreach ($amb['candidates'] as $i => $cand) { ?>
              <option value="<?= (int)$cand['id'] ?>"<?= $i === 0 ? ' selected' : '' ?>>
                <?= h($cand['name']) ?> (ID <?= (int)$cand['id'] ?>)<?= $cand['kurz'] !== '' ? ' · ' . h($cand['kurz']) : '' ?>
              </option>
<?php       } ?>
              <option value="0"><?= h(t('imp_review_option_new')) ?></option>
            </select>
          </div>
<?php } ?>
          <div style="display:flex;gap:8px;margin-top:10px;padding-top:14px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-success btn-sm"><?= h(t('imp_review_btn_confirm')) ?></button>
            <a href="?action=spst_import_cancel" class="btn btn-muted btn-sm" style="text-decoration:none"><?= h(t('imp_review_btn_cancel')) ?></a>
          </div>
        </form>
      </div>
