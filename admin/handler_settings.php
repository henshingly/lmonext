<?php
/**
 * Project: LMOnext
 * Filename: handler_settings.php
 * Fileversion: 1.3.6
 * Changelog: 1.3.6 - Speichert die neue Einstellung ShowSpielfrei (Tab Anzeigen/Darstellung)
 * Changelog: 1.3.5
 * Changelog: 1.3.5 - Bugfix: Tab "spielsystem" speicherte versehentlich goalfaktor/
 *                     pointsfaktor (kollidierte mit dem Grundwerte-Tab, siehe
 *                     view_liga_settings.php 1.4.3) statt der neuen ET/PS-Punktefelder;
 *                     jetzt PointsForWin/Draw/LostET und PointsForWin/Draw/LostPS
 * Changelog: 1.3.4
 * Changelog: 1.3.4 - Neue Einstellung "ShowLogos" (Tab Anzeigen/Darstellung) wird jetzt
 *                     mitgespeichert
 * Changelog: 1.3.3
 * Changelog: 1.3.3 - Bugfix: "Meister wird ausgespielt"-Checkbox wurde nie gespeichert
 *                     (Formularfeld heißt "Champ_enabled", Handler las aber "Champ" – dieses
 *                     POST-Feld existierte nie). Neu: Randfarben der Tabellenmarkierungen
 *                     ({Key}Color) werden jetzt mitgespeichert (nur gültige #rrggbb-Hexwerte)
 * Changelog: 1.3.2 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.3.1 - Bugfix: "Kalender"-Option wird jetzt unter eigenem Schlüssel gespeichert
 *                     (statt der Namenskollision mit DatC/Spieltagsdatum)
 * Changelog: 1.3.0 - Flash-Meldungen über t() übersetzt
 * Changelog: 1.2.2 - tab=ticker für schnelles Speichern von ticker+tickertext aus Spieltagansicht
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── Liga-Einstellungen speichern ─────────────────────────────────────────────
if ($action === 'save_liga_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $lid = (int)($_POST['liga_id'] ?? 0);
    $tab = $_POST['tab'] ?? 'grundwerte';
    if ($lid <= 0) { flash(t('ls_flash_invalid_id'), 'error'); redirect('?action=liga_settings&id='.$lid.'&tab='.$tab); }
    try {
        $db   = getDB();
        $stmt = $db->prepare('INSERT INTO '.tbl('liga_options').' (liga_id,option_key,option_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE option_value=VALUES(option_value)');
        $save = function(string $key, string $val) use ($stmt, $lid) { $stmt->execute([$lid, $key, $val]); };

        switch ($tab) {
            case 'grundwerte':
                $save('Name',         trim($_POST['liga_name']  ?? ''));
                $save('namePkt',      trim($_POST['namePkt']    ?? 'Punkte'));
                $save('nameTor',      trim($_POST['nameTor']    ?? 'Tore'));
                $save('goalfaktor',   trim($_POST['goalfaktor'] ?? '1'));
                $save('pointsfaktor', trim($_POST['pointsfaktor'] ?? '1'));
                $save('favTeam',      trim($_POST['favTeam']    ?? '0'));
                $save('selTeam',      trim($_POST['selTeam']    ?? '0'));
                // Liga-Namen auch in liga-Tabelle aktualisieren
                $name = trim($_POST['liga_name'] ?? '');
                if ($name !== '') {
                    $db->prepare('UPDATE '.tbl('liga').' SET name=? WHERE id=?')->execute([$name, $lid]);
                }
                break;

            case 'anzeige':
                $save('DatS',       isset($_POST['DatS'])       ? '1' : '0');
                $save('DatM',       isset($_POST['DatM'])       ? '1' : '0');
                $save('DatC',       isset($_POST['DatC'])       ? '1' : '0');
                $save('Kalender',   isset($_POST['Kalender'])   ? '1' : '0');
                $save('DatF',       trim($_POST['DatF']         ?? 'd.m.Y H:i'));
                $save('Actual',     trim($_POST['Actual']       ?? '1'));
                $save('Ergebnis',   isset($_POST['Ergebnis'])   ? '1' : '0');
                $save('ShowSpielfrei', isset($_POST['ShowSpielfrei']) ? '1' : '0');
                $save('Plan',       isset($_POST['Plan'])       ? '1' : '0');
                $save('Tabelle',    isset($_POST['Tabelle'])    ? '1' : '0');
                $save('ShowLogos',  isset($_POST['ShowLogos'])  ? '1' : '0');
                $save('Kreuz',      isset($_POST['Kreuz'])      ? '1' : '0');
                $save('stats',      isset($_POST['stats'])      ? '1' : '0');
                $save('Ligastats',  isset($_POST['Ligastats'])  ? '1' : '0');
                $save('kurve1',     isset($_POST['kurve1'])     ? '1' : '0');
                $save('kurve2',     isset($_POST['kurve2'])     ? '1' : '0');
                $save('ticker',     isset($_POST['ticker'])     ? '1' : '0');
                $save('tickertext', trim($_POST['tickertext']   ?? ''));
                $save('urlT',       isset($_POST['urlT'])       ? '1' : '0');
                $save('urlB',       isset($_POST['urlB'])       ? '1' : '0');
                // KO-spezifisch
                $save('KlFin',      isset($_POST['KlFin'])      ? '1' : '0');
                $save('playdown',   isset($_POST['playdown'])   ? '1' : '0');
                $save('playoffmode', trim($_POST['playoffmode'] ?? '0'));
                break;

            case 'spielsystem':
                $save('MinusPoints',  isset($_POST['MinusPoints'])  ? '1' : '0');
                $save('OnRun',        isset($_POST['OnRun'])        ? '1' : '0');
                $save('HideDraw',     isset($_POST['HideDraw'])     ? '1' : '0');
                $save('Direct',       isset($_POST['Direct'])       ? '1' : '0');
                $save('Spez',         isset($_POST['Spez'])         ? '1' : '0');
                $save('enableGameSort',isset($_POST['enableGameSort'])? '1' : '0');
                $save('PointsForWin',  trim($_POST['PointsForWin']  ?? '3'));
                $save('PointsForDraw', trim($_POST['PointsForDraw'] ?? '1'));
                $save('PointsForLost', trim($_POST['PointsForLost'] ?? '0'));
                // Eigene Punktwerte "nach Verlängerung" (ET) und "nach
                // Elfmeterschießen" (PS), analog zum alten LMO. Frühere
                // Versionen missbrauchten hierfür versehentlich goalfaktor/
                // pointsfaktor – dieselben Schlüssel, die der Grundwerte-Tab
                // für die Dezimalstellen-Anzeige nutzt, wodurch sich beide
                // Tabs beim Speichern gegenseitig überschrieben haben.
                $save('PointsForWinET',  trim($_POST['PointsForWinET']  ?? $_POST['PointsForWin']  ?? '3'));
                $save('PointsForDrawET', trim($_POST['PointsForDrawET'] ?? $_POST['PointsForDraw'] ?? '1'));
                $save('PointsForLostET', trim($_POST['PointsForLostET'] ?? $_POST['PointsForLost'] ?? '0'));
                $save('PointsForWinPS',  trim($_POST['PointsForWinPS']  ?? $_POST['PointsForWin']  ?? '3'));
                $save('PointsForDrawPS', trim($_POST['PointsForDrawPS'] ?? $_POST['PointsForDraw'] ?? '1'));
                $save('PointsForLostPS', trim($_POST['PointsForLostPS'] ?? $_POST['PointsForLost'] ?? '0'));
                $save('Kegel',         isset($_POST['Kegel'])       ? '1' : '0');
                $save('HandS',         isset($_POST['HandS'])       ? '1' : '0');
                break;

            case 'tabelle':
                $save('tableHinRueck',  isset($_POST['tableHinRueck'])  ? '1' : '0');
                $save('tableHeimAusw',  isset($_POST['tableHeimAusw'])  ? '1' : '0');
                // Bugfix: Checkbox im Formular heißt "Champ_enabled" (nicht "Champ") –
                // $_POST['Champ'] existierte nie, wodurch dieser Haken bislang immer
                // stillschweigend als "0" gespeichert wurde, unabhängig von der Auswahl.
                $save('Champ',    isset($_POST['Champ_enabled']) ? '1' : '0');
                $save('CL',       trim($_POST['CL']       ?? '0'));
                $save('CK',       trim($_POST['CK']       ?? '0'));
                $save('UC',       trim($_POST['UC']       ?? '0'));
                $save('AR',       trim($_POST['AR']       ?? '0'));
                $save('AB',       trim($_POST['AB']       ?? '0'));
                // Randfarben der Tabellenmarkierungen (siehe computeStandingsMarkerColor()
                // in frontend/data_liga.php). Nur speichern, wenn es ein gültiger
                // #rrggbb-Hexwert ist (so wie ihn <input type="color"> liefert).
                foreach (['Champ', 'CL', 'CK', 'UC', 'AR', 'AB'] as $mk) {
                    $colVal = trim($_POST[$mk . 'Color'] ?? '');
                    if (preg_match('/^#[0-9a-fA-F]{6}$/', $colVal)) {
                        $save($mk . 'Color', $colVal);
                    }
                }
                break;

            case 'ticker':
                $save('ticker',     $_POST['ticker'] === '1' ? '1' : '0');
                $save('tickertext', trim($_POST['tickertext'] ?? ''));
                break;

            case 'spieltage':
                $save('Rounds',  trim($_POST['Rounds']  ?? '0'));
                $save('Matches', trim($_POST['Matches'] ?? '0'));
                break;
        }
        flash(t('flash_settings_saved'));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    $redirect = $_POST['redirect'] ?? ('?action=liga_settings&id='.$lid.'&tab='.$tab);
    redirect($redirect);
}

