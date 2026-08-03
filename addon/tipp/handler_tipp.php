<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/handler_tipp.php
 * Fileversion: 0.1.0
 * Changelog: 0.1.0 - Initiale Version: bindet tipp_lib.php ein, erste Speicher-Aktion für den
 *                     Tab "Punkteverteilung" (save_tipp_punkte)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

require_once __DIR__ . '/tipp_lib.php';

if ($action === 'save_tipp_punkte' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $tippmodus = ($_POST['tippmodus'] ?? 'ergebnis') === 'tendenz' ? 'tendenz' : 'ergebnis';

    $intField = static function (string $name, int $default = 0) : string {
        $val = (int)($_POST[$name] ?? $default);
        return (string)$val;
    };

    setTippSettings([
        'tippmodus'                    => $tippmodus,
        'pkt_ergebnis'                 => $intField('pkt_ergebnis', 4),
        'pkt_tendenz_tordiff'          => $intField('pkt_tendenz_tordiff', 3),
        'pkt_tendenz'                  => $intField('pkt_tendenz', 2),
        'pkt_toranzahl'                => $intField('pkt_toranzahl', 1),
        'pkt_tendenz_toranzahl_regel'  => in_array($_POST['pkt_tendenz_toranzahl_regel'] ?? '', ['addieren', 'nur_tendenz'], true)
                                            ? $_POST['pkt_tendenz_toranzahl_regel'] : 'addieren',
        'pkt_unentschieden_regel'      => in_array($_POST['pkt_unentschieden_regel'] ?? '', ['tendenz_plus_toranzahl', 'nur_tendenz'], true)
                                            ? $_POST['pkt_unentschieden_regel'] : 'nur_tendenz',
        'pkt_unentschieden_bonus'      => $intField('pkt_unentschieden_bonus', 1),
        'pkt_nv_unentschieden_zaehlt'  => isset($_POST['pkt_nv_unentschieden_zaehlt']) ? '1' : '0',
        'pkt_ie_unentschieden_zaehlt'  => isset($_POST['pkt_ie_unentschieden_zaehlt']) ? '1' : '0',
        'pkt_gruener_tisch_regel'      => in_array($_POST['pkt_gruener_tisch_regel'] ?? '', ['tendenz_tordiff', 'keine_punkte'], true)
                                            ? $_POST['pkt_gruener_tisch_regel'] : 'tendenz_tordiff',
        'pkt_tendenz_treffer'          => $intField('pkt_tendenz_treffer', 1),
    ]);

    flash(t('tipp_flash_settings_saved'));
    redirect('?action=tippspiel&tab=optionen&subtab=punkteverteilung');
}
