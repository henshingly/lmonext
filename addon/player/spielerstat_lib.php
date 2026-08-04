<?php
/**
 * Project: LMOnext
 * Filename: addon/player/spielerstat_lib.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */

// ── Schema ────────────────────────────────────────────────────────────────────
function ensureSpielerstatSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('spielerstat_spalten').' (
            `id`       INT AUTO_INCREMENT PRIMARY KEY,
            `liga_id`  INT           NOT NULL,
            `name`     VARCHAR(120)  NOT NULL,
            `typ`      ENUM(\'zahl\',\'text\',\'formel\') NOT NULL DEFAULT \'zahl\',
            `formel`   VARCHAR(255)  NULL DEFAULT NULL,
            `rolle`    ENUM(\'normal\',\'verein\',\'spielerlink\') NOT NULL DEFAULT \'normal\',
            `position` SMALLINT      NOT NULL DEFAULT 0,
            KEY `liga_id` (`liga_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('spielerstat_spieler').' (
            `id`       INT AUTO_INCREMENT PRIMARY KEY,
            `liga_id`  INT      NOT NULL,
            `team_id`  INT      NULL DEFAULT NULL,
            `position` SMALLINT NOT NULL DEFAULT 0,
            KEY `liga_id` (`liga_id`),
            KEY `team_id` (`team_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('spielerstat_werte').' (
            `spieler_id` INT           NOT NULL,
            `spalten_id` INT           NOT NULL,
            `wert`       VARCHAR(255)  NOT NULL DEFAULT \'\',
            PRIMARY KEY (`spieler_id`, `spalten_id`),
            KEY `spalten_id` (`spalten_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('spielerstat_config').' (
            `liga_id`          INT          PRIMARY KEY,
            `sort_column`      INT          NOT NULL DEFAULT 0,
            `sort_direction`   TINYINT      NOT NULL DEFAULT 0,
            `admin_sort_column` INT         NOT NULL DEFAULT 0,
            `per_page`         SMALLINT     NOT NULL DEFAULT 17,
            `show_zero`        TINYINT      NOT NULL DEFAULT 1,
            `show_extra_sort_column` TINYINT NOT NULL DEFAULT 0,
            `show_per_club`    TINYINT      NOT NULL DEFAULT 0,
            `link_label`       VARCHAR(100) NOT NULL DEFAULT \'Spielerstatistik\'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (Throwable) {}
}

/**
 * Persistente Spieler-Entität, unabhängig von Liga/Saison (analog zu
 * teams_global für Vereine). Ein Spieler wird einmal angelegt und bekommt
 * eine dauerhafte ID – er kann in mehreren Ligen/Saisons auftauchen, auch
 * für unterschiedliche Vereine (z.B. nach einem Wechsel). Die
 * saisonspezifischen Werte (Tore, Verein usw.) bleiben weiterhin in
 * spielerstat_spieler/-werte je Liga; global_player_id verknüpft die
 * jeweilige Zeile nur mit der dauerhaften Identität.
 */
function ensureSpielerGlobalSchema() : void
{
    static $done = false; if ($done) return; $done = true;
    try {
        $db = getDB();
        $db->exec('CREATE TABLE IF NOT EXISTS '.tbl('spieler_global').' (
            `id`   INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(120) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $cols = $db->query('SHOW COLUMNS FROM '.tbl('spielerstat_spieler'))->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('global_player_id', $cols, true)) {
            $db->exec('ALTER TABLE '.tbl('spielerstat_spieler').' ADD COLUMN `global_player_id` INT NULL DEFAULT NULL');
        }
    } catch (Throwable) {}
}

/**
 * Findet einen vorhandenen globalen Spieler per exaktem Namen, oder legt bei
 * Bedarf einen neuen an. Rückgabe ist immer eine gültige ID.
 */
function findOrCreateGlobalPlayer(string $name) : int
{
    ensureSpielerGlobalSchema();
    $db = getDB();
    $s = $db->prepare('SELECT id FROM '.tbl('spieler_global').' WHERE name=? LIMIT 1');
    $s->execute([$name]);
    $id = $s->fetchColumn();
    if ($id !== false) { return (int)$id; }
    $db->prepare('INSERT INTO '.tbl('spieler_global').' (name) VALUES (?)')->execute([$name]);
    return (int)$db->lastInsertId();
}

// ── Spielerfotos (analog zu Team-Logos, siehe findTeamLogoPath() in admin/bootstrap.php) ──
const SPIELERSTAT_PHOTO_ALLOWED_EXT = ['jpg', 'png', 'gif', 'svg'];

/** Absoluter Dateisystempfad zum Spielerfoto-Verzeichnis (wird bei Bedarf angelegt). */
function spielerPhotoDir() : string
{
    $dir = dirname(__DIR__, 2) . '/assets/img/player';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * Sucht ein hochgeladenes Foto für die angegebene globale Spieler-ID
 * (Dateiname "{id}.{ext}"). Gibt den Web-Pfad relativ zum Projekt-Root
 * zurück, oder null wenn kein Foto hinterlegt ist.
 */
function findPlayerPhotoPath(int $globalPlayerId) : ?string
{
    $dir = spielerPhotoDir();
    foreach (SPIELERSTAT_PHOTO_ALLOWED_EXT as $ext) {
        if (is_file($dir . '/' . $globalPlayerId . '.' . $ext)) {
            return 'assets/img/player/' . $globalPlayerId . '.' . $ext;
        }
    }
    return null;
}

/** Entfernt ein evtl. vorhandenes Foto (alle möglichen Endungen) für die globale Spieler-ID. */
function deletePlayerPhoto(int $globalPlayerId) : void
{
    $dir = spielerPhotoDir();
    foreach (SPIELERSTAT_PHOTO_ALLOWED_EXT as $ext) {
        $path = $dir . '/' . $globalPlayerId . '.' . $ext;
        if (is_file($path)) { @unlink($path); }
    }
}

/**
 * Prüft und speichert ein hochgeladenes Spielerfoto ($_FILES-Eintrag).
 * Erlaubt sind JPG/PNG/GIF/SVG. Ein evtl. vorhandenes altes Foto (auch mit
 * anderer Endung) wird vorher entfernt, damit nicht mehrere Foto-Dateien für
 * dieselbe Spieler-ID gleichzeitig existieren.
 *
 * @return array{ok:bool, error:?string}
 */
function savePlayerPhotoUpload(int $globalPlayerId, array $file) : array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => t('spst_photo_err_upload')];
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') { $ext = 'jpg'; }
    if (!in_array($ext, SPIELERSTAT_PHOTO_ALLOWED_EXT, true)) {
        return ['ok' => false, 'error' => t('spst_photo_err_format')];
    }
    $tmpPath = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => t('spst_photo_err_upload')];
    }
    if ($ext === 'svg') {
        $content = (string)file_get_contents($tmpPath);
        if (stripos($content, '<svg') === false) {
            return ['ok' => false, 'error' => t('spst_photo_err_invalid')];
        }
    } else {
        $info = @getimagesize($tmpPath);
        if ($info === false) {
            return ['ok' => false, 'error' => t('spst_photo_err_invalid')];
        }
        $detectedExt = image_type_to_extension((int)$info[2], false);
        $detectedExt = $detectedExt === 'jpeg' ? 'jpg' : $detectedExt;
        if ($detectedExt !== $ext) {
            return ['ok' => false, 'error' => t('spst_photo_err_invalid')];
        }
    }
    deletePlayerPhoto($globalPlayerId);
    $destPath = spielerPhotoDir() . '/' . $globalPlayerId . '.' . $ext;
    if (!move_uploaded_file($tmpPath, $destPath)) {
        return ['ok' => false, 'error' => t('spst_photo_err_upload')];
    }
    @chmod($destPath, 0644);
    return ['ok' => true, 'error' => null];
}

// ── Spaltenüberschriften-Bilder (analog zum alten LMO: liegt im Bild-Ordner ──
// eine Grafik mit exakt dem Spaltennamen, wird sie statt des Textes gezeigt) ─
const SPIELERSTAT_COLUMN_IMAGE_EXT = ['png', 'gif', 'jpg', 'jpeg', 'svg'];

/**
 * Sucht eine Grafik für eine Spaltenüberschrift im Ordner
 * assets/addon/player/ (Dateiname exakt der Spaltenname + eine der bekannten
 * Endungen, z.B. "Tore.png"). Gibt den Web-Pfad relativ zum Projekt-Root
 * zurück, oder null wenn keine passende Grafik hinterlegt ist.
 */
function findSpielerstatColumnImage(string $columnName) : ?string
{
    $dir = dirname(__DIR__, 2) . '/assets/addon/player';
    foreach (SPIELERSTAT_COLUMN_IMAGE_EXT as $ext) {
        $path = $dir . '/' . $columnName . '.' . $ext;
        if (is_file($path)) {
            return 'assets/addon/player/' . rawurlencode($columnName . '.' . $ext);
        }
    }
    return null;
}

// ── Konfiguration ─────────────────────────────────────────────────────────────
function getSpielerstatConfig(int $ligaId) : array
{
    ensureSpielerstatSchema();
    $default = [
        'liga_id' => $ligaId, 'sort_column' => 0, 'sort_direction' => 0,
        'admin_sort_column' => 0, 'per_page' => 17, 'show_zero' => 1,
        'show_extra_sort_column' => 0, 'show_per_club' => 0,
        'link_label' => 'Spielerstatistik',
    ];
    try {
        $s = getDB()->prepare('SELECT * FROM '.tbl('spielerstat_config').' WHERE liga_id=?');
        $s->execute([$ligaId]);
        $row = $s->fetch();
        return $row !== false ? $row : $default;
    } catch (Throwable) {
        return $default;
    }
}

function saveSpielerstatConfig(int $ligaId, array $cfg) : void
{
    ensureSpielerstatSchema();
    $db = getDB();
    $db->prepare('REPLACE INTO '.tbl('spielerstat_config').'
        (liga_id, sort_column, sort_direction, admin_sort_column, per_page, show_zero, show_extra_sort_column, show_per_club, link_label)
        VALUES (?,?,?,?,?,?,?,?,?)')
       ->execute([
            $ligaId,
            (int)($cfg['sort_column'] ?? 0),
            (int)($cfg['sort_direction'] ?? 0),
            (int)($cfg['admin_sort_column'] ?? 0),
            (int)($cfg['per_page'] ?? 17),
            (int)($cfg['show_zero'] ?? 0),
            (int)($cfg['show_extra_sort_column'] ?? 0),
            (int)($cfg['show_per_club'] ?? 0),
            (string)($cfg['link_label'] ?? 'Spielerstatistik'),
        ]);
}

// ── Spalten ───────────────────────────────────────────────────────────────────
/** @return array<int,array{id:int,liga_id:int,name:string,typ:string,formel:?string,rolle:string,position:int}> */
function getSpielerstatSpalten(int $ligaId) : array
{
    ensureSpielerstatSchema();
    try {
        $s = getDB()->prepare('SELECT * FROM '.tbl('spielerstat_spalten').' WHERE liga_id=? ORDER BY position, id');
        $s->execute([$ligaId]);
        return $s->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function addSpielerstatSpalte(int $ligaId, string $name, string $typ, string $formel = '', string $rolle = 'normal') : int
{
    ensureSpielerstatSchema();
    $db = getDB();
    $s = $db->prepare('SELECT COALESCE(MAX(position),-1)+1 FROM '.tbl('spielerstat_spalten').' WHERE liga_id=?');
    $s->execute([$ligaId]);
    $pos = (int)$s->fetchColumn();

    if (!in_array($typ, ['zahl', 'text', 'formel'], true)) { $typ = 'zahl'; }
    if (!in_array($rolle, ['normal', 'verein', 'spielerlink'], true)) { $rolle = 'normal'; }

    $db->prepare('INSERT INTO '.tbl('spielerstat_spalten').' (liga_id,name,typ,formel,rolle,position) VALUES (?,?,?,?,?,?)')
       ->execute([$ligaId, $name, $typ, $typ === 'formel' ? $formel : null, $rolle, $pos]);
    $newId = (int)$db->lastInsertId();

    // neue Spalte bei allen bestehenden Spielern mit "0" (Zahl) bzw. "" (Text/Formel) vorbelegen
    $players = $db->prepare('SELECT id FROM '.tbl('spielerstat_spieler').' WHERE liga_id=?');
    $players->execute([$ligaId]);
    $ins = $db->prepare('INSERT INTO '.tbl('spielerstat_werte').' (spieler_id,spalten_id,wert) VALUES (?,?,?)');
    $initial = $typ === 'zahl' ? '0' : '';
    foreach ($players->fetchAll(PDO::FETCH_COLUMN) as $pid) {
        $ins->execute([$pid, $newId, $initial]);
    }
    recalcSpielerstatFormulas($ligaId);
    return $newId;
}

function deleteSpielerstatSpalte(int $spaltenId) : void
{
    $db = getDB();
    $db->prepare('DELETE FROM '.tbl('spielerstat_werte').' WHERE spalten_id=?')->execute([$spaltenId]);
    $db->prepare('DELETE FROM '.tbl('spielerstat_spalten').' WHERE id=?')->execute([$spaltenId]);
}

function updateSpielerstatSpalte(int $spaltenId, string $name, ?string $formel = null) : void
{
    $db = getDB();
    if ($formel !== null) {
        $db->prepare('UPDATE '.tbl('spielerstat_spalten').' SET name=?, formel=? WHERE id=?')
           ->execute([$name, $formel, $spaltenId]);
    } else {
        $db->prepare('UPDATE '.tbl('spielerstat_spalten').' SET name=? WHERE id=?')
           ->execute([$name, $spaltenId]);
    }
}

// ── Spieler ───────────────────────────────────────────────────────────────────
function addSpielerstatSpieler(int $ligaId, string $nameValue, ?int $teamId = null) : int
{
    ensureSpielerstatSchema();
    ensureSpielerGlobalSchema();
    $db = getDB();
    $s = $db->prepare('SELECT COALESCE(MAX(position),-1)+1 FROM '.tbl('spielerstat_spieler').' WHERE liga_id=?');
    $s->execute([$ligaId]);
    $pos = (int)$s->fetchColumn();

    $globalPlayerId = findOrCreateGlobalPlayer(trim($nameValue));

    $db->prepare('INSERT INTO '.tbl('spielerstat_spieler').' (liga_id,team_id,global_player_id,position) VALUES (?,?,?,?)')
       ->execute([$ligaId, $teamId, $globalPlayerId, $pos]);
    $playerId = (int)$db->lastInsertId();

    $spalten = getSpielerstatSpalten($ligaId);
    $ins = $db->prepare('INSERT INTO '.tbl('spielerstat_werte').' (spieler_id,spalten_id,wert) VALUES (?,?,?)');
    foreach ($spalten as $i => $sp) {
        $val = $i === 0 ? $nameValue : ($sp['typ'] === 'zahl' ? '0' : '');
        $ins->execute([$playerId, $sp['id'], $val]);
    }
    recalcSpielerstatFormulas($ligaId);
    return $playerId;
}

function deleteSpielerstatSpieler(int $spielerId) : void
{
    $db = getDB();
    $db->prepare('DELETE FROM '.tbl('spielerstat_werte').' WHERE spieler_id=?')->execute([$spielerId]);
    $db->prepare('DELETE FROM '.tbl('spielerstat_spieler').' WHERE id=?')->execute([$spielerId]);
}

/**
 * Liest alle Spieler einer Liga mit ihren Werten als assoziatives Array
 * spalten_id => wert (Formel-Spalten bereits aktuell, siehe
 * recalcSpielerstatFormulas()).
 *
 * @return array<int,array{id:int,team_id:?int,werte:array<int,string>}>
 */
function getSpielerstatSpieler(int $ligaId) : array
{
    ensureSpielerstatSchema();
    try {
        $db = getDB();
        $s = $db->prepare('SELECT id, team_id, global_player_id, position FROM '.tbl('spielerstat_spieler').' WHERE liga_id=? ORDER BY position, id');
        $s->execute([$ligaId]);
        $players = $s->fetchAll();
        if (empty($players)) { return []; }

        $ids = array_column($players, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $sw = $db->prepare('SELECT spieler_id, spalten_id, wert FROM '.tbl('spielerstat_werte').' WHERE spieler_id IN ('.$in.')');
        $sw->execute($ids);
        $werte = [];
        foreach ($sw->fetchAll() as $row) {
            $werte[(int)$row['spieler_id']][(int)$row['spalten_id']] = $row['wert'];
        }
        foreach ($players as &$p) {
            $p['werte'] = $werte[(int)$p['id']] ?? [];
        }
        return $players;
    } catch (Throwable) {
        return [];
    }
}

/** Aktualisiert einen einzelnen Zellwert (Bulk-Edit in der Adminansicht). */
function setSpielerstatWert(int $spielerId, int $spaltenId, string $wert) : void
{
    getDB()->prepare('REPLACE INTO '.tbl('spielerstat_werte').' (spieler_id,spalten_id,wert) VALUES (?,?,?)')
           ->execute([$spielerId, $spaltenId, $wert]);
}

// ── Formel-Engine (kein eval()!) ──────────────────────────────────────────────
/**
 * Berechnet alle Formel-Spalten einer Liga neu und schreibt die Ergebnisse in
 * spielerstat_werte. Wird nach jeder Änderung an Spalten/Werten aufgerufen.
 * Sicherer Ersatz für das eval()-basierte Original (formel_berechnen() in
 * lmo-statadmin.php): Spaltennamen werden nicht mehr per Textersetzung in
 * PHP-Code verwandelt und ausgeführt, sondern über einen eigenen
 * Tokenizer/Parser/Evaluator behandelt, der nur Zahlen, +,-,*,/,(),Komma und
 * die Funktionen MIN/MAX/ROUND kennt – beliebiger Code ist darin nicht
 * ausdrückbar.
 */
function recalcSpielerstatFormulas(int $ligaId) : void
{
    $spalten = getSpielerstatSpalten($ligaId);
    $formelSpalten = array_filter($spalten, static fn($s) => $s['typ'] === 'formel' && (string)$s['formel'] !== '');
    if (empty($formelSpalten)) { return; }

    $players = getSpielerstatSpieler($ligaId);
    if (empty($players)) { return; }

    // Für die Spaltennamen-Erkennung im Formeltext: längste Namen zuerst,
    // damit z.B. "Tore pro Spiel" nicht schon durch "Tore" verstümmelt wird.
    $byName = [];
    foreach ($spalten as $sp) { $byName[$sp['name']] = $sp['id']; }
    uksort($byName, static fn($a, $b) => strlen($b) <=> strlen($a));

    $db = getDB();
    $upd = $db->prepare('REPLACE INTO '.tbl('spielerstat_werte').' (spieler_id,spalten_id,wert) VALUES (?,?,?)');

    foreach ($formelSpalten as $fs) {
        foreach ($players as $p) {
            $result = 0.0;
            try {
                $result = evaluateSpielerstatFormula((string)$fs['formel'], $byName, $p['werte']);
            } catch (Throwable) {
                $result = 0.0;
            }
            $upd->execute([$p['id'], $fs['id'], (string)$result]);
        }
    }
}

/**
 * Wertet eine Formel für EINE Spielerzeile aus. $columnIdsByName ist
 * Spaltenname => Spalten-ID (längste Namen zuerst, siehe Aufrufer), $rowValues
 * ist Spalten-ID => Wert (String, wird bei Bedarf in float umgewandelt).
 * Unterstützt +,-,*,/,(), Zahlenliterale, Spaltennamen sowie MIN(...),
 * MAX(...), ROUND(x[,n]). Division durch 0 ergibt 0.0 (wie im Original).
 */
function evaluateSpielerstatFormula(string $formula, array $columnIdsByName, array $rowValues) : float
{
    // 1) Spaltennamen durch neutrale Platzhalter ersetzen, die der Tokenizer
    //    eindeutig als Spaltenreferenz erkennt (Format: @<id>@).
    $work = $formula;
    foreach ($columnIdsByName as $name => $id) {
        if ($name === '') { continue; }
        $work = str_ireplace($name, '@' . $id . '@', $work);
    }

    $tokens = spielerstatTokenize($work);
    $pos = 0;
    $value = spielerstatParseExpr($tokens, $pos, $rowValues);
    if ($pos !== count($tokens)) {
        throw new \RuntimeException('Unerwartete Zeichen in Formel.');
    }
    return round($value, 2);
}

/** @return array<int,array{0:string,1:string}> Liste von [Typ, Wert]-Tokens */
function spielerstatTokenize(string $s) : array
{
    $tokens = [];
    $len = strlen($s);
    $i = 0;
    while ($i < $len) {
        $c = $s[$i];
        if (ctype_space($c)) { $i++; continue; }
        if ($c === '@') { // Spaltenreferenz @<id>@
            $j = $i + 1;
            $num = '';
            while ($j < $len && $s[$j] !== '@') { $num .= $s[$j]; $j++; }
            if ($j >= $len || !ctype_digit($num)) {
                throw new \RuntimeException('Ungültige Spaltenreferenz in Formel.');
            }
            $tokens[] = ['COL', $num];
            $i = $j + 1;
            continue;
        }
        if (ctype_digit($c) || ($c === '.' && $i + 1 < $len && ctype_digit($s[$i + 1]))) {
            $num = '';
            while ($i < $len && (ctype_digit($s[$i]) || $s[$i] === '.')) { $num .= $s[$i]; $i++; }
            $tokens[] = ['NUM', $num];
            continue;
        }
        if (ctype_alpha($c)) {
            $word = '';
            while ($i < $len && ctype_alpha($s[$i])) { $word .= $s[$i]; $i++; }
            $upper = strtoupper($word);
            if (!in_array($upper, ['MIN', 'MAX', 'ROUND'], true)) {
                throw new \RuntimeException('Unbekannte Funktion "' . $word . '" in Formel.');
            }
            $tokens[] = ['FUNC', $upper];
            continue;
        }
        if (in_array($c, ['+', '-', '*', '/', '(', ')', ','], true)) {
            $tokens[] = ['OP', $c];
            $i++;
            continue;
        }
        throw new \RuntimeException('Ungültiges Zeichen "' . $c . '" in Formel.');
    }
    return $tokens;
}

function spielerstatParseExpr(array $tokens, int &$pos, array $rowValues) : float
{
    $value = spielerstatParseTerm($tokens, $pos, $rowValues);
    while ($pos < count($tokens) && $tokens[$pos][0] === 'OP' && in_array($tokens[$pos][1], ['+', '-'], true)) {
        $op = $tokens[$pos][1]; $pos++;
        $rhs = spielerstatParseTerm($tokens, $pos, $rowValues);
        $value = $op === '+' ? $value + $rhs : $value - $rhs;
    }
    return $value;
}

function spielerstatParseTerm(array $tokens, int &$pos, array $rowValues) : float
{
    $value = spielerstatParseFactor($tokens, $pos, $rowValues);
    while ($pos < count($tokens) && $tokens[$pos][0] === 'OP' && in_array($tokens[$pos][1], ['*', '/'], true)) {
        $op = $tokens[$pos][1]; $pos++;
        $rhs = spielerstatParseFactor($tokens, $pos, $rowValues);
        if ($op === '*') { $value *= $rhs; }
        else { $value = $rhs != 0.0 ? $value / $rhs : 0.0; }
    }
    return $value;
}

function spielerstatParseFactor(array $tokens, int &$pos, array $rowValues) : float
{
    if ($pos >= count($tokens)) { throw new \RuntimeException('Formel endet unerwartet.'); }
    [$type, $val] = $tokens[$pos];

    if ($type === 'OP' && $val === '-') { $pos++; return -spielerstatParseFactor($tokens, $pos, $rowValues); }
    if ($type === 'OP' && $val === '(') {
        $pos++;
        $v = spielerstatParseExpr($tokens, $pos, $rowValues);
        if (!($pos < count($tokens) && $tokens[$pos][0] === 'OP' && $tokens[$pos][1] === ')')) {
            throw new \RuntimeException('Schließende Klammer fehlt.');
        }
        $pos++;
        return $v;
    }
    if ($type === 'NUM') { $pos++; return (float)$val; }
    if ($type === 'COL') {
        $pos++;
        $raw = $rowValues[(int)$val] ?? '0';
        return is_numeric($raw) ? (float)$raw : 0.0;
    }
    if ($type === 'FUNC') {
        $pos++;
        if (!($pos < count($tokens) && $tokens[$pos][0] === 'OP' && $tokens[$pos][1] === '(')) {
            throw new \RuntimeException('Klammer nach Funktion erwartet.');
        }
        $pos++;
        $args = [spielerstatParseExpr($tokens, $pos, $rowValues)];
        while ($pos < count($tokens) && $tokens[$pos][0] === 'OP' && $tokens[$pos][1] === ',') {
            $pos++;
            $args[] = spielerstatParseExpr($tokens, $pos, $rowValues);
        }
        if (!($pos < count($tokens) && $tokens[$pos][0] === 'OP' && $tokens[$pos][1] === ')')) {
            throw new \RuntimeException('Schließende Klammer nach Funktionsargumenten fehlt.');
        }
        $pos++;
        return match ($val) {
            'MIN'   => min($args),
            'MAX'   => max($args),
            'ROUND' => count($args) > 1 ? round($args[0], (int)$args[1]) : round($args[0], 2),
            default => throw new \RuntimeException('Unbekannte Funktion.'),
        };
    }
    throw new \RuntimeException('Unerwartetes Token in Formel.');
}
