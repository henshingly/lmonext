<?php
/**
 * Project: LMOnext
 * Filename: handler_import_export.php
 * Fileversion: 1.5.4
 * Changelog: 1.5.4 - l98DecodeText() jetzt auch auf den Spielbericht-Link (BE-Feld) angewendet,
 *                     in beiden Zweigen (KO und regulär) – relevant für "&amp;" in
 *                     Query-Parametern von URLs, war bisher übersehen worden
 * Changelog: 1.5.3 - Zwei Bugfixes beim .l98-Import: (1) Runden im regulären Liga-Format
 *                     (Type=0) behandelten GA/GB=-1 (LMO-Legacy für "kein Ergebnis") nicht wie
 *                     im KO-Zweig als null, sondern übernahmen die -1 wörtlich bis in die DB/
 *                     Admin-Oberfläche. (2) Ältere .l98-Exporte liefern Freitext (Teamnamen/
 *                     -kürzel/-mittelnamen, Liganamen, Spielnotizen, Ticker) teils schon
 *                     HTML-entity-kodiert, dabei oft sogar ohne das abschließende Semikolon
 *                     (z.B. "M&oumlnchengladbach" statt korrekt "M&ouml;nchengladbach"); neue
 *                     l98DecodeText()-Hilfsfunktion ergänzt bei Bedarf erst das fehlende
 *                     Semikolon für die gängigen Buchstaben und dekodiert dann korrekt
 * Changelog: 1.5.2 - Bugfix (gefunden beim Testen der neuen Team-Abgleich-Funktion): teams_global.name
 *                     hat keinen UNIQUE-Key, wodurch "INSERT ... ON DUPLICATE KEY UPDATE" bei
 *                     exakter Namensgleichheit NIE griff und stattdessen stumpf ein doppeltes
 *                     Team anlegte (in importL98IntoDB() UND createLigaInDB(), betraf also auch
 *                     die "Liga erstellen"-Wizard-Seite, nicht nur den .l98-Import). Beide Stellen
 *                     prüfen jetzt explizit per SELECT vor dem Anlegen statt sich auf einen nicht
 *                     existierenden DB-Constraint zu verlassen
 * Changelog: 1.5.1 - Bugfix: Fuzzy-Matching nutzte mb_strtolower()/mb_strlen()/mb_substr(),
 *                     die mbstring-Extension ist nicht auf jedem Shared-Hosting garantiert
 *                     vorhanden (führte zu einem Fatalen Fehler). Komplett ohne mbstring
 *                     umgebaut: Umlaute/Akzente werden explizit (Groß+klein) per strtr()
 *                     ersetzt, danach reicht strtolower() für den ASCII-Rest; UTF-8-Zeichen
 *                     werden über preg_split('//u', ...) zerlegt statt mb_substr()
 * Changelog: 1.5.0 - Vorhandene Teams werden beim .l98-Import nicht mehr überschrieben: bei
 *                     exaktem Namenstreffer gelten Name/Kurz/Mittel aus der DB als maßgeblich
 *                     (vorher wurden nicht-leere Werte aus der .l98-Datei übernommen). Neu: bei
 *                     ungefährer (nicht exakter) Namensgleichheit wird vor dem eigentlichen
 *                     Import ein Abgleichsschritt eingeschoben (siehe view_import_review.php,
 *                     Action "import_review"/"import_confirm") – der Admin entscheidet dort pro
 *                     Team, ob der Name aus der DB übernommen werden soll. Fuzzy-Matching
 *                     (teamNormalizeName()/teamTrigramSimilarity()/findFuzzyTeamMatch()) ist ein
 *                     PHP-Port derselben Logik, die im Teams-Suchfeld schon länger läuft
 * Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.4.0 - Import-Handler + importL98IntoDB()-Meldungen über t() übersetzt
 * Changelog: 1.3.1 - createLigaInDB(): Erfolgs-/Fehlermeldung über t() übersetzt
 * Changelog: 1.3.0 - Kommentar Status-Mapping korrigiert (1=i.E., 2=n.V., war vertauscht dokumentiert)
 * Changelog: 1.2.9 - [News]-Sektion (Tickertext) geparst und in liga_options gespeichert
 * Changelog: 1.2.3 - KO-Import: Dummy-Team ___ fuer TA/TB=0 Paarungen: alle .l98 aus ZIP-Archiv importieren (umgeht max_file_uploads)
 * Changelog: 1.2.0 - kurz/mittel nur ueberschreiben wenn nicht leer (ON DUPLICATE KEY)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── Fuzzy-Namensvergleich für den Team-Abgleich beim .l98-Import ─────────────
// PHP-Portierung der gleichen Normalisierungs-/Trigram-Logik, die im
// Teams-Suchfeld (admin/view_teams.php, normalize()/fuzzyMatch()) bereits
// clientseitig für die Duplikat-Erkennung verwendet wird.

function teamNormalizeName(string $s) : string
{
    // Bewusst ohne mb_strtolower()/mb_*-Funktionen: die mbstring-Extension ist
    // nicht auf jedem Shared-Hosting garantiert vorhanden (siehe auch
    // frontend/pdf_export.php für dasselbe Muster). Die Ersetzungstabelle
    // deckt Groß- und Kleinschreibung der Umlaute/Akzente explizit ab, den
    // restlichen (reinen ASCII-)Teil erledigt strtolower() zuverlässig.
    $map = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'À' => 'a', 'Á' => 'a', 'Â' => 'a', 'Ã' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'È' => 'e', 'É' => 'e', 'Ê' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u',
        'ñ' => 'n', 'Ñ' => 'n', 'ç' => 'c', 'Ç' => 'c',
    ];
    return strtolower(strtr($s, $map));
}

/**
 * Zerlegt einen UTF-8-String in einzelne Zeichen, ohne auf mbstring
 * angewiesen zu sein (preg_split mit dem "u"-Modifier nutzt die
 * UTF-8-Fähigkeiten von PCRE, die praktisch immer verfügbar sind – anders als
 * die separate mbstring-Extension).
 *
 * @return array<int,string>
 */
function teamUtf8Chars(string $s) : array
{
    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    return $chars !== false ? $chars : str_split($s);
}

/**
 * Trigramm-basierte Ähnlichkeit zweier (bereits normalisierter) Strings,
 * 0.0 (keine Übereinstimmung) bis 1.0 (identisch) – toleriert Tippfehler.
 */
function teamTrigramSimilarity(string $a, string $b) : float
{
    $trigrams = static function (string $s) : array {
        $chars = teamUtf8Chars('  ' . $s . '  ');
        $len = count($chars);
        $set = [];
        for ($i = 0; $i < $len - 2; $i++) {
            $set[$chars[$i] . $chars[$i + 1] . $chars[$i + 2]] = true;
        }
        return $set;
    };
    $ta = $trigrams($a);
    $tb = $trigrams($b);
    $common = 0;
    foreach ($ta as $t => $_) {
        if (isset($tb[$t])) { $common++; }
    }
    $denom = count($ta) + count($tb);
    return $denom > 0 ? (2 * $common) / $denom : 0.0;
}

/**
 * Ob zwei (rohe, unnormalisierte) Teamnamen "ungefähr gleich" sind – gleicher
 * Schwellenwert wie im Teams-Suchfeld (normalize()/fuzzyMatch() in
 * view_teams.php). Exakte Übereinstimmung zählt hier NICHT als "fuzzy"
 * (dafür sorgt der Aufrufer, der exakte Treffer vorher separat behandelt).
 */
function teamNamesAreFuzzyMatch(string $a, string $b) : bool
{
    $an = teamNormalizeName(trim($a));
    $bn = teamNormalizeName(trim($b));
    if ($an === '' || $bn === '' || $an === $bn) {
        return false; // leer oder exakt gleich -> kein "fuzzy"-Fall
    }
    if (str_contains($bn, $an) || str_contains($an, $bn)) {
        return true;
    }
    $similarity = teamTrigramSimilarity($an, $bn);
    $len = count(teamUtf8Chars($an));
    $threshold = $len <= 4 ? 0.5 : ($len <= 7 ? 0.35 : 0.25);
    return $similarity >= $threshold;
}

/**
 * Sucht unter $existingTeams (Zeilen aus teams_global: id,name,mittel,kurz)
 * den besten ungefähren (nicht-exakten) Treffer für $name. Gibt null zurück,
 * wenn keiner über der Ähnlichkeitsschwelle liegt.
 *
 * @param array<int,array{id:int,name:string,mittel:string,kurz:string}> $existingTeams
 */
function findFuzzyTeamMatch(string $name, array $existingTeams) : ?array
{
    $best = null;
    $bestScore = -1.0;
    $nameNorm = teamNormalizeName(trim($name));
    foreach ($existingTeams as $t) {
        if (!teamNamesAreFuzzyMatch($name, $t['name'])) {
            continue;
        }
        $score = teamTrigramSimilarity($nameNorm, teamNormalizeName($t['name']));
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $t;
        }
    }
    return $best;
}

// ── Dummy-Team ___ anlegen oder vorhandene ID holen ───────────────────────────
function getOrCreateDummyTeam(): int {
    $db = getDB();
    // Erst schauen ob schon vorhanden
    $s = $db->prepare('SELECT id FROM '.tbl('teams_global').' WHERE name=?');
    $s->execute(['___']);
    $id = (int)$s->fetchColumn();
    if ($id) { return $id; }
    // Noch nicht vorhanden: anlegen
    $db->prepare('INSERT IGNORE INTO '.tbl('teams_global').' (name,kurz,mittel) VALUES (?,?,?)')
       ->execute(['___', '', '']);
    $id = (int)$db->lastInsertId();
    if ($id) { return $id; }
    // Race condition: nochmal abfragen
    $s->execute(['___']);
    return (int)$s->fetchColumn();
}

// ── Liga erstellen ────────────────────────────────────────────────────────────
function createLigaInDB(string $name, int $type, array $teamData, array $spieltage, array $options = []): array {
    $db = getDB();
    try {
        $db->beginTransaction();
        $db->prepare('INSERT INTO '.tbl('liga').' (name) VALUES (?)')->execute([$name]);
        $ligaId = (int)$db->lastInsertId();
        $stmtOpt = $db->prepare('INSERT INTO '.tbl('liga_options').' (liga_id, option_key, option_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value)');
        $stmtOpt->execute([$ligaId, 'Type',   (string)$type]);
        $stmtOpt->execute([$ligaId, 'Rounds', (string)count($spieltage)]);
        foreach ($options as $k => $v) { $stmtOpt->execute([$ligaId, $k, $v]); }
        // teams_global.name hat keinen UNIQUE-Key -> explizit per SELECT
        // prüfen statt uns auf ON DUPLICATE KEY zu verlassen (siehe
        // importL98IntoDB() für dieselbe Korrektur mit ausführlicherem
        // Kommentar). Vorhandene Teams bleiben dadurch unverändert.
        $stmtTSel = $db->prepare('SELECT id FROM '.tbl('teams_global').' WHERE name=? LIMIT 1');
        $stmtTIns = $db->prepare('INSERT INTO '.tbl('teams_global').' (name, kurz, mittel) VALUES (?,?,?)');
        $stmtLT   = $db->prepare('INSERT IGNORE INTO '.tbl('liga_teams').' (liga_id, team_id) VALUES (?,?)');
        $teamDbIds = [];
        foreach ($teamData as $idx => $t) {
            $stmtTSel->execute([$t['name']]);
            $existingId = $stmtTSel->fetchColumn();
            if ($existingId !== false) {
                $tid = (int)$existingId;
            } else {
                $stmtTIns->execute([$t['name'], $t['kurz'] ?? '', $t['mittel'] ?? '']);
                $tid = (int)$db->lastInsertId();
            }
            $teamDbIds[$idx] = $tid;
            $stmtLT->execute([$ligaId, $tid]);
        }
        $stmtST = $db->prepare('INSERT INTO '.tbl('liga_spieltage').' (liga_id, nummer, modus) VALUES (?,?,?)');
        $stmtP  = $db->prepare('INSERT INTO '.tbl('liga_partien').' (spieltag_id, heim_id, gast_id, spiel_nr) VALUES (?,?,?,?)');
        foreach ($spieltage as $nr => $entry) {
            // $entry kann einfaches Pairs-Array ODER ['pairs'=>[...],'modus'=>N] sein
            if (isset($entry['pairs'])) {
                $pairs = $entry['pairs'];
                $mod   = (int)($entry['modus'] ?? ($type === 1 ? KO_MODUS_DEFAULT : 0));
            } else {
                $pairs = $entry;
                $mod   = ($type === 1) ? KO_MODUS_DEFAULT : 0;
            }
            // Spieltag-Zeile immer anlegen (auch bei leeren KO-Runden)
            $stmtST->execute([$ligaId, $nr, $mod]);
            $stid = (int)$db->lastInsertId();
            // Dummy-Team für -1 Platzhalter (lazy: nur anlegen wenn nötig)
            $dummyId = null;
            foreach ($pairs as $pair) {
                if (($pair[0] ?? 0) < 0 || ($pair[1] ?? 0) < 0) {
                    $dummyId = getOrCreateDummyTeam();
                    break;
                }
            }

            // Paarungen einfügen (mit Dummy-Team bei -1)
            $stmtPL = $db->prepare(
                'INSERT INTO '.tbl('liga_partien').'
                 (spieltag_id,heim_id,gast_id,heim_label,gast_label,h_tore,g_tore,zeit,notiz,spiel_nr)
                 VALUES (?,?,?,?,?,NULL,NULL,NULL,NULL,?)'
            );
            foreach ($pairs as $pIdx => [$hIdx, $gIdx]) {
                $hId = $teamDbIds[$hIdx] ?? null;
                $gId = $teamDbIds[$gIdx] ?? null;
                // -1 oder nicht gefunden → Dummy
                if (!$hId) { $hId = $dummyId; $hLabel = '___'; } else { $hLabel = null; }
                if (!$gId) { $gId = $dummyId; $gLabel = '___'; } else { $gLabel = null; }
                if ($hId && $gId) {
                    $paarNr = $pIdx + 1;
                    // KO: spiel_nr im Format "Paarung_Spiel" z.B. "1_1"
                    // Bei Einzelspiel: nur ein Spiel pro Paarung → "1_1"
                    // Bei HR/Best-of: wird beim save_ko_runde korrekt gesetzt
                    $spielNr = ($type === 1) ? $paarNr.'_1' : (string)$paarNr;
                    $stmtPL->execute([$stid, $hId, $gId, $hLabel, $gLabel, $spielNr]);
                }
            }
        }
        $db->commit();
        return ['ok' => true, 'liga_id' => $ligaId, 'msg' =>
            t('liga_flash_created', ['name' => $name, 'id' => $ligaId, 'teams' => count($teamData), 'matchdays' => count($spieltage)])];
    } catch (Throwable $e) { $db->rollBack(); return ['ok' => false, 'liga_id' => 0, 'msg' => t('flash_error_prefix', ['msg' => $e->getMessage()])]; }
}

// ── .l98 Parser ──────────────────────────────────────────────────────────────
/**
 * Ältere .l98-Exporte (aus dem klassischen LMO/LMO4) speichern Freitext-Felder
 * teils schon HTML-entity-kodiert ab (z.B. "M&uumlnchen" statt "München") –
 * vermutlich weil das Original-LMO die Werte direkt unescaped in HTML
 * ausgegeben hat. Ohne Dekodierung landen die rohen Entities 1:1 in der DB
 * und werden überall (Dropdowns, Tabellen, PDF-Export, …) als Rohtext
 * angezeigt statt als das eigentlich gemeinte Zeichen. Wird auf Teamnamen/
 * -kürzel/-mittelnamen, Liganamen, Spielnotizen und Ticker-Text angewendet.
 */
function l98DecodeText(string $s) : string
{
    if ($s === '' || !str_contains($s, '&')) {
        return $s; // schneller Ausstieg für den Normalfall ohne Entities
    }
    // Manche (v.a. ältere) .l98-Exporte schreiben die Entity-Namen ohne das
    // abschließende Semikolon (z.B. "M&oumlnchengladbach" statt korrekt
    // "M&ouml;nchengladbach") – html_entity_decode() erkennt das ohne
    // Semikolon nicht zuverlässig. Für die im deutschsprachigen Liga-Kontext
    // relevanten Buchstaben wird das Semikolon deshalb vorher ergänzt, falls
    // es fehlt, bevor regulär dekodiert wird.
    $knownEntities = 'auml|ouml|uuml|Auml|Ouml|Uuml|szlig|eacute|egrave|ecirc|Eacute'
        . '|aacute|iacute|oacute|uacute|Aacute|Oacute|Uacute|ccedil|Ccedil|ntilde|Ntilde';
    $s = preg_replace('/&(' . $knownEntities . ')(?!;)/', '&$1;', $s) ?? $s;
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function parseL98(string $content): array {
    $sections = []; $current = null;
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line === '') { continue; }
        if (preg_match('/^\[(.+)\]$/', $line, $m)) { $current = $m[1]; $sections[$current] ??= []; continue; }
        if ($current !== null && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $sections[$current][trim($k)] = trim($v);
        }
    }
    $options  = $sections['Options'] ?? [];
    $ligaName = l98DecodeText($options['Name']    ?? 'Unbekannte Liga');
    $ligaType = (int)($options['Type']    ?? 0);
    $rounds   = (int)($options['Rounds']  ?? 0);
    $teams    = (int)($options['Teams']   ?? 0);

    $teamNames = []; $teamMittel = []; $teamKurz = [];
    for ($i = 1; $i <= $teams; $i++) {
        $si = (string)$i;
        if (isset($sections['Teams'][$si])) {
            $teamNames[$i]  = l98DecodeText($sections['Teams'][$si]);
            $teamMittel[$i] = l98DecodeText($sections['Teamm'][$si] ?? '');
            $teamKurz[$i]   = l98DecodeText($sections['Teamk'][$si] ?? '');
        }
    }

    $spieltage = [];

    if ($ligaType === 1) {
        // ── KO-Format: TAp/TBp = Paarung p, GApS/GBpS = Tore Paarung p Spiel S ──
        // Anzahl Runden: entweder aus Options.Rounds oder Anzahl [RoundN]-Sektionen
        $roundNrs = [];
        foreach (array_keys($sections) as $sec) {
            if (preg_match('/^Round(\d+)$/', $sec, $m)) { $roundNrs[] = (int)$m[1]; }
        }
        if ($rounds === 0) { $rounds = count($roundNrs); }
        if (empty($roundNrs)) { $roundNrs = range(1, $rounds); }

        for ($r = 1; $r <= $rounds; $r++) {
            $sec = $sections["Round{$r}"] ?? null;
            if ($sec === null) { continue; }

            $modus = (int)($sec['MO'] ?? KO_MODUS_DEFAULT);
            if (!isset(KO_MODUS[$modus])) { $modus = KO_MODUS_DEFAULT; }

            $paarungen = [];
            // Paarungen: TAp/TBp, p = 1, 2, 3 …
            // Abbruch erst wenn TAp-Key gar nicht existiert (nicht bei TA=0!)
            for ($p = 1; ; $p++) {
                if (!isset($sec["TA{$p}"])) { break; }  // Key existiert nicht → Ende
                $ta = (int)$sec["TA{$p}"];
                $tb = (int)($sec["TB{$p}"] ?? 0);

                $spiele = [];
                // Spiele: GApS/GBpS, S = 1, 2 … (max = $modus)
                for ($s = 1; $s <= $modus; $s++) {
                    $gaKey = "GA{$p}{$s}"; $gbKey = "GB{$p}{$s}";
                    $atKey = "AT{$p}{$s}"; $ntKey = "NT{$p}{$s}";

                    $ga  = isset($sec[$gaKey]) && $sec[$gaKey] !== '' ? (int)$sec[$gaKey] : null;
                    $gb  = isset($sec[$gbKey]) && $sec[$gbKey] !== '' ? (int)$sec[$gbKey] : null;
                    // Negativer Wert = kein Ergebnis (z.B. GA11=-1)
                    if ($ga !== null && $ga < 0) { $ga = null; }
                    if ($gb !== null && $gb < 0) { $gb = null; }

                    $at  = isset($sec[$atKey]) && $sec[$atKey] !== '' ? tsToDatetime($sec[$atKey]) : null;
                    $notiz = isset($sec[$ntKey]) ? l98DecodeText($sec[$ntKey]) : null;

                    // SP{p}{s} = Spielstatus: 0=normal, 1=i.E. (Elfmeterschießen), 2=n.V. (Verlängerung)
                    $spKey  = "SP{$p}{$s}";
                    $status = isset($sec[$spKey]) ? (int)$sec[$spKey] : 0;
                    if ($status < 0 || $status > 2) { $status = 0; }

                    // BE{p}{s} = Bemerkung/Link zum Spielbericht
                    $beKey   = "BE{$p}{$s}";
                    $bericht = isset($sec[$beKey]) && $sec[$beKey] !== '' ? l98DecodeText($sec[$beKey]) : null;

                    $spiele[$s] = [
                        'h_tore' => $ga, 'g_tore' => $gb,
                        'zeit'   => $at, 'notiz'  => $notiz,
                        'status' => $status, 'bericht' => $bericht,
                    ];
                }
                $paarungen[$p] = ['heim' => $ta, 'gast' => $tb, 'spiele' => $spiele];
            }

            $spieltage[$r] = [
                'datum'     => $sec['D1'] ?? null,
                'modus'     => $modus,
                'paarungen' => $paarungen,
                'partien'   => [],  // wird aus paarungen aufgebaut
            ];
        }
    } else {
        // ── Liga-Format: TAp/TBp = Partie p (klassisch) ──────────────────────
        $matches = (int)($options['Matches'] ?? 0);
        for ($r = 1; $r <= $rounds; $r++) {
            $sec = $sections["Round{$r}"] ?? null;
            if ($sec === null) { continue; }
            $partien = [];
            for ($p = 1; $p <= $matches; $p++) {
                $sp = (string)$p;
                $ta = (int)($sec["TA{$sp}"] ?? 0); $tb = (int)($sec["TB{$sp}"] ?? 0);
                if (!$ta || !$tb) { continue; }
                $at  = isset($sec["AT{$sp}"]) && $sec["AT{$sp}"] !== '' ? tsToDatetime($sec["AT{$sp}"]) : null;
                $ga  = isset($sec["GA{$sp}"]) && $sec["GA{$sp}"] !== '' ? (int)$sec["GA{$sp}"] : null;
                $gb  = isset($sec["GB{$sp}"]) && $sec["GB{$sp}"] !== '' ? (int)$sec["GB{$sp}"] : null;
                // Negativer Wert = kein Ergebnis (LMO-Legacy, z.B. GA1=-1) – dieselbe
                // Konvention wie im KO-Zweig oben, war hier bisher übersehen worden
                // und lief als literale -1 bis in die DB/Admin-Oberfläche durch.
                if ($ga !== null && $ga < 0) { $ga = null; }
                if ($gb !== null && $gb < 0) { $gb = null; }

                $status = isset($sec["SP{$sp}"]) ? (int)$sec["SP{$sp}"] : 0;
                if ($status < 0 || $status > 2) { $status = 0; }
                $bericht = isset($sec["BE{$sp}"]) && $sec["BE{$sp}"] !== '' ? l98DecodeText($sec["BE{$sp}"]) : null;

                $partien[] = [
                    'heim' => $ta, 'gast' => $tb, 'zeit' => $at,
                    'h_tore'  => $ga,
                    'g_tore'  => $gb,
                    'notiz'   => isset($sec["NT{$sp}"]) ? l98DecodeText($sec["NT{$sp}"]) : null,
                    'status'  => $status,
                    'bericht' => $bericht,
                    'spiel_nr'=> (string)$p,
                ];
            }
            $spieltage[$r] = ['datum' => $sec['D1'] ?? null, 'partien' => $partien];
        }
    }

    $teamValues = [];
    for ($i = 1; $i <= $teams; $i++) {
        $sec = $sections["Team{$i}"] ?? [];
        if (!empty($sec)) { $teamValues[$i] = $sec; }
    }
    $optionKeys = ['goalfaktor','pointsfaktor','enableGameSort','Kegel','HandS',
        'PointsForWin','PointsForDraw','PointsForLost','Spez','HideDraw','OnRun',
        'MinusPoints','Direct','Champ','CL','CK','UC','AR','AB','namePkt','nameTor',
        'tableHinRueck','tableHeimAusw','DatC','DatS','DatM','DatF','urlT','urlB',
        'stats','Plan','Ergebnis','mittore','favTeam','selTeam','ticker','icon',
        'Graph','Kreuz','Tabelle','Ligastats','kurve1','kurve2','Actual','Title',
        'Rounds','Matches','KlFin','playdown'];
    $ligaOptions = [];
    foreach ($optionKeys as $key) { if (isset($options[$key])) { $ligaOptions[$key] = $options[$key]; } }
    // Rounds aus tatsächlichen Daten
    if (!isset($ligaOptions['Rounds'])) { $ligaOptions['Rounds'] = (string)count($spieltage); }

    // [News]-Sektion: Tickertext (NC = Anzahl Zeilen, N0/N1/... = Texte, zeilenweise zusammenfügen)
    $newsSec = $sections['News'] ?? [];
    $newsCount = (int)($newsSec['NC'] ?? 0);
    if ($newsCount > 0) {
        $lines = [];
        for ($i = 0; $i < $newsCount; $i++) {
            if (isset($newsSec['N'.$i])) { $lines[] = l98DecodeText($newsSec['N'.$i]); }
        }
        if (!empty($lines)) { $ligaOptions['tickertext'] = implode("\n", $lines); }
    }

    return ['name'=>$ligaName,'type'=>$ligaType,'rounds'=>count($spieltage),'matches'=>0,
        'teams_count'=>$teams,'teams'=>$teamNames,'teamMittel'=>$teamMittel,
        'teamKurz'=>$teamKurz,'teamValues'=>$teamValues,'spieltage'=>$spieltage,'options'=>$ligaOptions];
}

function importL98IntoDB(array $data, array $teamNameOverrides = []): array {
    $db = getDB();
    try {
        $db->beginTransaction();
        $stmt = $db->prepare('INSERT INTO '.tbl('liga').' (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
        $stmt->execute([$data['name']]);
        $ligaId = (int)$db->lastInsertId();
        if (!$ligaId) {
            $s = $db->prepare('SELECT id FROM '.tbl('liga').' WHERE name=?');
            $s->execute([$data['name']]); $ligaId = (int)$s->fetchColumn();
        }
        $stmtOpt = $db->prepare('INSERT INTO '.tbl('liga_options').' (liga_id,option_key,option_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value)');
        foreach ($data['options'] as $k => $v) { $stmtOpt->execute([$ligaId, $k, $v]); }
        $stmtOpt->execute([$ligaId, 'Type', (string)$data['type']]);
        $stmtOpt->execute([$ligaId, 'Rounds', (string)$data['rounds']]);

        // Bei einem bereits vorhandenen Team (exakter Namenstreffer) NICHT
        // dessen Name/Kurz/Mittel mit den Werten aus der .l98-Datei
        // überschreiben – die Daten in der DB gelten als maßgeblich, die
        // .l98-Datei liefert in diesem Fall nur die Verknüpfung zur Liga.
        //
        // Wichtig: teams_global.name hat KEINEN UNIQUE-Key (nur PRIMARY KEY
        // auf id) – "INSERT ... ON DUPLICATE KEY UPDATE" würde bei
        // Namensgleichheit also nie greifen, sondern stumpf ein Duplikat
        // anlegen. Deshalb hier bewusst explizit per SELECT prüfen, statt
        // uns auf einen DB-Constraint zu verlassen, der nicht existiert.
        $stmtTSel = $db->prepare('SELECT id,kurz,mittel FROM '.tbl('teams_global').' WHERE name=? LIMIT 1');
        $stmtTIns = $db->prepare('INSERT INTO '.tbl('teams_global').' (name,kurz,mittel) VALUES (?,?,?)');
        $stmtLT = $db->prepare('INSERT IGNORE INTO '.tbl('liga_teams').' (liga_id,team_id) VALUES (?,?)');
        $stmtTV = $db->prepare('INSERT INTO '.tbl('liga_team_values').' (liga_id,team_id,key_name,key_value) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE key_value=VALUES(key_value)');
        $teamMap = [];
        foreach ($data['teams'] as $nr => $name) {
            // Wurde beim Import-Abgleich bestätigt, dass dieses Team einem
            // bereits vorhandenen (ungefähr namensgleichen) DB-Team
            // entspricht, den Namen aus der DB übernehmen statt dem aus der
            // .l98-Datei – dadurch greift unten der exakte Treffer und es
            // wird korrekt verknüpft statt ein Duplikat anzulegen.
            $ov = $teamNameOverrides[$nr] ?? null;
            $useName   = $ov['name']   ?? $name;
            $useKurz   = $ov['kurz']   ?? ($data['teamKurz'][$nr] ?? '');
            $useMittel = $ov['mittel'] ?? ($data['teamMittel'][$nr] ?? '');

            $stmtTSel->execute([$useName]);
            $existing = $stmtTSel->fetch();
            if ($existing !== false) {
                $tid = (int)$existing['id']; // vorhandenes Team unverändert übernehmen
            } else {
                $stmtTIns->execute([$useName, $useKurz, $useMittel]);
                $tid = (int)$db->lastInsertId();
            }
            $teamMap[$nr] = $tid;
            $stmtLT->execute([$ligaId, $tid]);
            foreach (($data['teamValues'][$nr] ?? []) as $k => $v) { $stmtTV->execute([$ligaId, $tid, $k, $v]); }
        }

        $stmtST = $db->prepare('INSERT INTO '.tbl('liga_spieltage').' (liga_id,nummer,start,modus) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),start=VALUES(start),modus=VALUES(modus)');
        $stmtP  = $db->prepare('INSERT INTO '.tbl('liga_partien').' (spieltag_id,heim_id,gast_id,h_tore,g_tore,zeit,notiz,status,bericht_url,spiel_nr) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE h_tore=VALUES(h_tore),g_tore=VALUES(g_tore),zeit=VALUES(zeit),notiz=VALUES(notiz),status=VALUES(status),bericht_url=VALUES(bericht_url)');

        foreach ($data['spieltage'] as $nr => $st) {
            // Startdatum parsen
            $dt = null;
            if (!empty($st['datum'])) {
                $d = DateTime::createFromFormat('d.m.Y', $st['datum'])
                  ?: DateTime::createFromFormat('d.m.y', $st['datum']);
                $dt = $d ? $d->format('Y-m-d 00:00:00') : null;
            }
            $modus = (int)($st['modus'] ?? 0);

            $stmtST->execute([$ligaId, $nr, $dt, $modus]);
            $stid = (int)$db->lastInsertId();
            if (!$stid) {
                $s = $db->prepare('SELECT id FROM '.tbl('liga_spieltage').' WHERE liga_id=? AND nummer=?');
                $s->execute([$ligaId, $nr]); $stid = (int)$s->fetchColumn();
            }

            if ($data['type'] === 1 && isset($st['paarungen'])) {
                // ── KO: Paarungen mit Einzel-Spielen importieren ──────────────
                // playoffmode aus liga_options lesen (wurde vorher gespeichert)
                $sPlm = $db->prepare('SELECT option_value FROM '.tbl('liga_options').' WHERE liga_id=? AND option_key="playoffmode"');
                $sPlm->execute([$ligaId]); $playoffMode = (int)($sPlm->fetchColumn() ?: 0);

                // Dummy-Team ___ für noch unbekannte KO-Teams anlegen/finden
                $dummyId = getOrCreateDummyTeam();
                // Dummy NICHT in liga_teams eintragen (kein echtes Team)

                $stmtPL = $db->prepare(
                    'INSERT INTO '.tbl('liga_partien').'
                     (spieltag_id,heim_id,gast_id,heim_label,gast_label,h_tore,g_tore,zeit,notiz,status,bericht_url,spiel_nr)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE h_tore=VALUES(h_tore),g_tore=VALUES(g_tore),zeit=VALUES(zeit),
                       notiz=VALUES(notiz),status=VALUES(status),bericht_url=VALUES(bericht_url)'
                );

                foreach ($st['paarungen'] as $pNr => $paarung) {
                    $hNr = $paarung['heim'];
                    $gNr = $paarung['gast'];
                    $hId = $teamMap[$hNr] ?? null;
                    $gId = $teamMap[$gNr] ?? null;

                    // Bei TA/TB=0: Dummy-Team + Label "___"
                    $hIdDb    = $hId    ?: $dummyId;
                    $gIdDb    = $gId    ?: $dummyId;
                    $hLabel   = $hId    ? null : '___';
                    $gLabel   = $gId    ? null : '___';

                    foreach ($paarung['spiele'] as $sNr => $spiel) {
                        $aHatHeim = koHeimatTeamA($sNr, $modus, $playoffMode);
                        $spielHId    = $aHatHeim ? $hIdDb  : $gIdDb;
                        $spielGId    = $aHatHeim ? $gIdDb  : $hIdDb;
                        $spielHLabel = $aHatHeim ? $hLabel : $gLabel;
                        $spielGLabel = $aHatHeim ? $gLabel : $hLabel;
                        $spielNr     = $pNr.'_'.$sNr;
                        $stmtPL->execute([
                            $stid, $spielHId, $spielGId,
                            $spielHLabel, $spielGLabel,
                            $spiel['h_tore'], $spiel['g_tore'],
                            $spiel['zeit'], $spiel['notiz'] ?: null,
                            $spiel['status'] ?? 0, $spiel['bericht'] ?? null,
                            $spielNr,
                        ]);
                    }
                }
            } else {
                // ── Liga: klassische Partien importieren ──────────────────────
                foreach (($st['partien'] ?? []) as $p) {
                    $hId = $teamMap[$p['heim']] ?? null; $gId = $teamMap[$p['gast']] ?? null;
                    if ($hId && $gId) {
                        $stmtP->execute([$stid, $hId, $gId, $p['h_tore'], $p['g_tore'], $p['zeit'], $p['notiz'] ?: null, $p['status'] ?? 0, $p['bericht'] ?? null, $p['spiel_nr']]);
                    }
                }
            }
        }

        $db->commit();
        return ['ok' => true, 'msg' => t('imp_liga_imported', ['name' => $data['name'], 'id' => $ligaId, 'teams' => count($data['teams']), 'rounds' => count($data['spieltage'])])];
    } catch (Throwable $e) { $db->rollBack(); return ['ok' => false, 'msg' => t('imp_error_prefix', ['msg' => $e->getMessage()])]; }
}

// ── KO-Team-Picker: echtes Team oder Platzhalter-Label ───────────────────────

// ── Import-Handler (mehrere Dateien gleichzeitig) ─────────────────────────────
/**
 * Führt den eigentlichen DB-Import für eine Liste bereits geparster .l98-
 * Dateien aus (gemeinsam von beiden Importpfaden unten sowie vom
 * "import_confirm"-Handler nach der Abgleich-Bestätigung genutzt).
 *
 * @param array<int,array{fileName:string,data:array}> $parsedList
 * @param array<int,array<int,array{name:string,kurz:string,mittel:string}>> $overridesByFile Je Datei-Index eine Team-Nr→Override-Map
 * @return array{ok:int,fail:int,msgs:array}
 */
function runL98Import(array $parsedList, array $overridesByFile = []) : array {
    $ok = 0; $fail = 0; $msgs = [];
    foreach ($parsedList as $idx => $entry) {
        $overrides = $overridesByFile[$idx] ?? [];
        $r = importL98IntoDB($entry['data'], $overrides);
        $msgs[] = ['text' => h(basename($entry['fileName'])).': '.$r['msg'], 'type' => $r['ok'] ? 'success' : 'error'];
        $r['ok'] ? $ok++ : $fail++;
    }
    return ['ok' => $ok, 'fail' => $fail, 'msgs' => $msgs];
}

/**
 * Prüft alle Teams aller geparsten Dateien gegen die bereits vorhandenen
 * Teams in teams_global. Exakte Namenstreffer werden ignoriert (die
 * verknüpft importL98IntoDB() ohnehin automatisch korrekt, siehe dort).
 * Zurückgegeben werden nur die AMBIGEN Fälle: Teams, deren Name keinem
 * vorhandenen Team exakt, aber ungefähr entspricht (siehe
 * teamNamesAreFuzzyMatch()) – dafür soll der Admin explizit entscheiden, ob
 * der DB-Name übernommen werden soll.
 *
 * @param array<int,array{fileName:string,data:array}> $parsedList
 * @return array<int,array{fileIdx:int,fileName:string,nr:int,importName:string,importKurz:string,importMittel:string,dbId:int,dbName:string,dbKurz:string,dbMittel:string}>
 */
function detectFuzzyTeamMatchesForImport(array $parsedList) : array {
    try {
        $existing = getDB()->query('SELECT id,name,mittel,kurz FROM '.tbl('teams_global'))->fetchAll();
    } catch (Throwable) {
        return [];
    }
    $existingByName = [];
    foreach ($existing as $t) { $existingByName[$t['name']] = true; }

    $ambiguous = [];
    foreach ($parsedList as $fileIdx => $entry) {
        foreach ($entry['data']['teams'] as $nr => $name) {
            if (isset($existingByName[$name])) {
                continue; // exakter Treffer -> kein Abgleich nötig
            }
            $match = findFuzzyTeamMatch($name, $existing);
            if ($match === null) {
                continue; // kein ähnliches Team gefunden -> wird als neues Team angelegt
            }
            $ambiguous[] = [
                'fileIdx'      => $fileIdx,
                'fileName'     => $entry['fileName'],
                'nr'           => $nr,
                'importName'   => $name,
                'importKurz'   => $entry['data']['teamKurz'][$nr] ?? '',
                'importMittel' => $entry['data']['teamMittel'][$nr] ?? '',
                'dbId'         => (int)$match['id'],
                'dbName'       => $match['name'],
                'dbKurz'       => $match['kurz'],
                'dbMittel'     => $match['mittel'],
            ];
        }
    }
    return $ambiguous;
}

if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    // $_FILES['l98file'] ist bei multiple ein Array von Arrays
    $files = $_FILES['l98file'] ?? [];
    $mode  = $_POST['import_mode'] ?? 'multi';

    // ── Alle hochgeladenen Dateien einlesen (ZIP oder Mehrfach-Upload) ────────
    // liefert eine einheitliche Liste ['name'=>Dateiname,'content'=>Inhalt];
    // Lesefehler landen direkt in $earlyMsgs und werden nicht weiterverarbeitet.
    $rawFiles  = [];
    $earlyMsgs = [];

    if ($mode === 'zip') {
        if (!class_exists('ZipArchive')) {
            flash(t('imp_zip_extension_missing'), 'error');
            redirect('?action=import');
        }
        $zipFile = $_FILES['zipfile'] ?? null;
        if (!$zipFile || $zipFile['error'] !== UPLOAD_ERR_OK) {
            flash(t('imp_no_zip_uploaded'), 'error');
            redirect('?action=import');
        }
        $zip = new ZipArchive();
        if ($zip->open($zipFile['tmp_name']) !== true) {
            flash(t('imp_zip_open_failed'), 'error');
            redirect('?action=import');
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'l98') continue;
            $content = $zip->getFromIndex($i);
            if ($content === false) {
                $earlyMsgs[] = ['text' => h(basename($name)).': '.t('imp_zip_read_error'), 'type' => 'error'];
                continue;
            }
            $rawFiles[] = ['name' => basename($name), 'content' => $content];
        }
        $zip->close();
        if (empty($rawFiles) && empty($earlyMsgs)) {
            flash(t('imp_no_l98_in_zip'), 'error');
            redirect('?action=import');
        }
    } else {
        if (isset($files['name']) && !is_array($files['name'])) {
            $files = [
                'name'     => [$files['name']],
                'tmp_name' => [$files['tmp_name']],
                'error'    => [$files['error']],
            ];
        }
        foreach (($files['name'] ?? []) as $i => $name) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $earlyMsgs[] = ['text' => h($name).': '.t('imp_upload_error'), 'type' => 'error'];
                continue;
            }
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'l98') {
                $earlyMsgs[] = ['text' => h($name).': '.t('imp_only_l98_supported'), 'type' => 'error'];
                continue;
            }
            $content = file_get_contents($files['tmp_name'][$i]);
            if ($content === false) {
                $earlyMsgs[] = ['text' => h($name).': '.t('imp_file_unreadable'), 'type' => 'error'];
                continue;
            }
            $rawFiles[] = ['name' => $name, 'content' => $content];
        }
        if (empty($rawFiles) && empty($earlyMsgs)) {
            flash(t('imp_no_files_uploaded'), 'error');
            redirect('?action=import');
        }
    }

    // ── Alle gültigen Dateien parsen ──────────────────────────────────────────
    $parsedList = [];
    foreach ($rawFiles as $rf) {
        $parsedList[] = ['fileName' => $rf['name'], 'data' => parseL98($rf['content'])];
    }

    // ── Team-Abgleich: gibt es ungefähre (nicht exakte) Namenstreffer mit
    // bereits vorhandenen Teams? Dann erst nachfragen statt sofort zu
    // importieren (siehe view_import_review.php) ───────────────────────────
    $ambiguous = detectFuzzyTeamMatchesForImport($parsedList);
    if (!empty($ambiguous)) {
        $_SESSION['import_pending']      = $parsedList;
        $_SESSION['import_pending_msgs'] = $earlyMsgs;
        $_SESSION['import_pending_ambiguous'] = $ambiguous;
        redirect('?action=import_review');
    }

    // ── Kein Abgleich nötig -> direkt importieren (bisheriges Verhalten) ─────
    $result = runL98Import($parsedList);
    $msgs = array_merge($earlyMsgs, $result['msgs']);
    $ok   = $result['ok'];
    $fail = $result['fail'] + count($earlyMsgs);

    if (empty($msgs)) {
        flash(t('imp_no_files_uploaded'), 'error');
    } elseif (count($msgs) === 1) {
        flash($msgs[0]['text'], $msgs[0]['type']);
    } else {
        $summary = t('imp_summary', ['ok' => $ok, 'total' => $ok+$fail]);
        flash($summary, $fail === 0 ? 'success' : 'error');
        $_SESSION['import_details'] = $msgs;
    }

    redirect('?action=import');
}

// ── Import-Abgleich abbrechen: Session aufräumen ──────────────────────────────
if ($action === 'import_cancel') {
    requireLogin();
    unset($_SESSION['import_pending'], $_SESSION['import_pending_msgs'], $_SESSION['import_pending_ambiguous']);
    redirect('?action=import');
}

// ── Bestätigung nach dem Team-Abgleich: tatsächlicher Import ─────────────────
if ($action === 'import_confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $parsedList = $_SESSION['import_pending'] ?? null;
    $earlyMsgs  = $_SESSION['import_pending_msgs'] ?? [];
    $ambiguous  = $_SESSION['import_pending_ambiguous'] ?? [];
    unset($_SESSION['import_pending'], $_SESSION['import_pending_msgs'], $_SESSION['import_pending_ambiguous']);

    if ($parsedList === null) {
        flash(t('imp_review_expired'), 'error');
        redirect('?action=import');
    }

    // adopt[<fileIdx>][<nr>] = "1" -> Admin hat bestätigt: DB-Namen übernehmen
    $adopt = $_POST['adopt'] ?? [];
    $overridesByFile = [];
    foreach ($ambiguous as $amb) {
        $checked = ($adopt[$amb['fileIdx']][$amb['nr']] ?? '') === '1';
        if ($checked) {
            $overridesByFile[$amb['fileIdx']][$amb['nr']] = [
                'name'   => $amb['dbName'],
                'kurz'   => $amb['dbKurz'],
                'mittel' => $amb['dbMittel'],
            ];
        }
    }

    $result = runL98Import($parsedList, $overridesByFile);
    $msgs = array_merge($earlyMsgs, $result['msgs']);
    $ok   = $result['ok'];
    $fail = $result['fail'] + count($earlyMsgs);

    if (count($msgs) === 1) {
        flash($msgs[0]['text'], $msgs[0]['type']);
    } else {
        $summary = t('imp_summary', ['ok' => $ok, 'total' => $ok+$fail]);
        flash($summary, $fail === 0 ? 'success' : 'error');
        $_SESSION['import_details'] = $msgs;
    }

    redirect('?action=import');
}

