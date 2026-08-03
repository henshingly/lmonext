<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/handler_tipp.php
 * Fileversion: 0.5.0
 * Changelog: 0.5.0 - Neue Speicher-Aktion save_tipp_punktgleichheit für die drei Kriterien
 * Changelog: 0.4.1
 * Changelog: 0.4.1 - Bugfix: Validierungs-Wertelisten für abgabeschluss_ohne_termin und
 *                     max_spieltage_voraus korrigiert (siehe view_tippspiel.php 0.6.1)
 * Changelog: 0.4.0
 * Changelog: 0.4.0 - Neue Speicher-Aktion save_tipp_anmeldung für den Tab "Anmeldung"
 * Changelog: 0.3.0
 * Changelog: 0.3.0 - Neue Speicher-Aktion save_tipp_abgabe für den Tab "Tippabgabe", inkl.
 *                     serverseitiger Prüfung, dass mindestens eine Abgabe-Variante aktiv bleibt
 * Changelog: 0.2.0
 * Changelog: 0.2.0 - Neue Speicher-Aktion save_tipp_regeln für den Tab "Regeltechnisches"
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

if ($action === 'save_tipp_regeln' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $intField = static function (string $name, int $default = 0) : string {
        $val = (int)($_POST[$name] ?? $default);
        return (string)$val;
    };

    setTippSettings([
        'abgabe_minuten'            => $intField('abgabe_minuten', 15),
        'abgabeschluss_ohne_termin' => in_array($_POST['abgabeschluss_ohne_termin'] ?? '', ['standard_anstosszeit', 'erstes_datum_mitternacht'], true)
                                        ? $_POST['abgabeschluss_ohne_termin'] : 'standard_anstosszeit',
        'max_team_groesse'          => in_array($_POST['max_team_groesse'] ?? '', ['0', '2', '3', '5', '10', '20', '30', '50', '100', 'unbegrenzt'], true)
                                        ? $_POST['max_team_groesse'] : '0',
        'max_spieltage_voraus'      => in_array($_POST['max_spieltage_voraus'] ?? '', ['0', '1', '2', '3', '4', '5', 'unbegrenzt'], true)
                                        ? $_POST['max_spieltage_voraus'] : 'unbegrenzt',
        'joker_zulassen'            => isset($_POST['joker_zulassen']) ? '1' : '0',
        'joker_multiplikator'       => in_array($_POST['joker_multiplikator'] ?? '', ['1.5', '2', '2.5', '3'], true)
                                        ? $_POST['joker_multiplikator'] : '2',
        'warnung_stunden'           => $intField('warnung_stunden', 4),
    ]);

    flash(t('tipp_flash_settings_saved'));
    redirect('?action=tippspiel&tab=optionen&subtab=regeltechnisches');
}

if ($action === 'save_tipp_abgabe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $ligenweise   = isset($_POST['abgabe_ligenweise']) ? '1' : '0';
    $datumsweise  = isset($_POST['abgabe_datumsweise']) ? '1' : '0';

    // Serverseitige Absicherung (nicht nur JS): ohne mindestens eine der
    // beiden Varianten wäre gar keine Tippabgabe mehr möglich, siehe
    // Original-Hilfetext ("Falls Sie beide Varianten deaktivieren, ist keine
    // Tippabgabe möglich").
    if ($ligenweise !== '1' && $datumsweise !== '1') {
        flash(t('tipp_flash_mind_eine_variante'), 'error');
        redirect('?action=tippspiel&tab=optionen&subtab=tippabgabe');
    }

    setTippSettings([
        'abgabe_ligenweise'            => $ligenweise,
        'abgabe_datumsweise'           => $datumsweise,
        'abgabe_pfeile'                => isset($_POST['abgabe_pfeile']) ? '1' : '0',
        'abgabe_tendenzen_anzeigen'    => isset($_POST['abgabe_tendenzen_anzeigen']) ? '1' : '0',
        'abgabe_durchschnitt_anzeigen' => isset($_POST['abgabe_durchschnitt_anzeigen']) ? '1' : '0',
        'abgabe_auto_tippeinsicht'     => isset($_POST['abgabe_auto_tippeinsicht']) ? '1' : '0',
    ]);

    flash(t('tipp_flash_settings_saved'));
    redirect('?action=tippspiel&tab=optionen&subtab=tippabgabe');
}

if ($action === 'save_tipp_anmeldung' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    setTippSettings([
        'anmeldung_adresse_abfragen'      => isset($_POST['anmeldung_adresse_abfragen']) ? '1' : '0',
        'anmeldung_realname_abfragen'     => isset($_POST['anmeldung_realname_abfragen']) ? '1' : '0',
        'anmeldung_freischaltung'         => in_array($_POST['anmeldung_freischaltung'] ?? '', ['sofort', 'email', 'admin'], true)
                                              ? $_POST['anmeldung_freischaltung'] : 'sofort',
        'anmeldung_admin_benachrichtigen' => isset($_POST['anmeldung_admin_benachrichtigen']) ? '1' : '0',
        'anmeldung_bestaetigungsmail'     => isset($_POST['anmeldung_bestaetigungsmail']) ? '1' : '0',
    ]);

    flash(t('tipp_flash_settings_saved'));
    redirect('?action=tippspiel&tab=optionen&subtab=anmeldung');
}

if ($action === 'save_tipp_punktgleichheit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $validKriterien = [
        'kein_kriterium', 'hoehere_quote', 'anzahl_spiele_getippt',
        'anzahl_richtige_ergebnisse', 'anzahl_richtige_tendenz_tordiff',
        'anzahl_richtige_tendenz', 'joker_punkte', 'spieltagswertungen',
    ];
    $defaults = ['kriterium_1' => 'hoehere_quote', 'kriterium_2' => 'anzahl_spiele_getippt', 'kriterium_3' => 'anzahl_richtige_ergebnisse'];

    $toSave = [];
    foreach ($defaults as $krKey => $krDefault) {
        $toSave[$krKey] = in_array($_POST[$krKey] ?? '', $validKriterien, true) ? $_POST[$krKey] : $krDefault;
    }
    setTippSettings($toSave);

    flash(t('tipp_flash_settings_saved'));
    redirect('?action=tippspiel&tab=optionen&subtab=punktgleichheit');
}
