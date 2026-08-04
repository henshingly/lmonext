<?php
/**
 * Project: LMOnext
 * Filename: addon/tipp/handler_tipp.php
 * Fileversion: 0.8.0
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

if ($action === 'save_tipp_ligen' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $immerAlle = isset($_POST['tippbare_immer_alle']) ? '1' : '0';
    setTippSetting('tippbare_immer_alle', $immerAlle);

    if ($immerAlle !== '1') {
        $ligaIds = array_map('intval', $_POST['tippbare_ligen'] ?? []);
        setTippLigaFreigabe($ligaIds);
    }
    // Bei "immer alle" wird tipp_liga_freigabe bewusst NICHT geleert -
    // falls der Admin später wieder auf gezielte Auswahl umschaltet, bleibt
    // die zuletzt getroffene Auswahl als Ausgangspunkt erhalten.

    flash(t('tipp_flash_settings_saved'));
    redirect('?action=tippspiel&tab=optionen&subtab=tippbare_ligen');
}

if ($action === 'save_tipp_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $originalNickname = trim($_POST['original_nickname'] ?? '') !== '' ? $_POST['original_nickname'] : null;
    $isNew = $originalNickname === null;
    $nickname = $isNew ? trim($_POST['nickname'] ?? '') : $originalNickname;
    $password = $_POST['password'] ?? '';

    if ($isNew && $nickname === '') {
        flash(t('tipp_flash_nickname_pflicht'), 'error');
        redirect('?action=tippspiel&tab=userverwaltung&new=1');
    }
    if ($isNew && $password === '') {
        flash(t('tipp_flash_passwort_pflicht'), 'error');
        redirect('?action=tippspiel&tab=userverwaltung&new=1');
    }
    if ($isNew && getTipperByNickname($nickname) !== null) {
        flash(t('tipp_flash_nickname_vergeben'), 'error');
        redirect('?action=tippspiel&tab=userverwaltung&new=1');
    }

    // Team-Zuordnung anhand des gewählten Radio-Buttons auflösen
    $teamRadio = $_POST['team_radio'] ?? 'keinem';
    $teamId = null;
    if ($teamRadio === 'bestehend' && !empty($_POST['team_bestehend'])) {
        $teamId = (int)$_POST['team_bestehend'];
    } elseif ($teamRadio === 'neu' && trim($_POST['team_neu'] ?? '') !== '') {
        // Team kann erst nach dem Speichern des Tippers verknüpft werden (braucht dessen ID),
        // wird daher weiter unten nach dem eigentlichen Speichern nachgetragen.
        $teamId = 'NEU:' . trim($_POST['team_neu']);
    }

    $data = [
        'nickname'       => $nickname,
        'email'          => trim($_POST['email'] ?? ''),
        'vorname'        => trim($_POST['vorname'] ?? '') ?: null,
        'nachname'       => trim($_POST['nachname'] ?? '') ?: null,
        'strasse'        => trim($_POST['strasse'] ?? '') ?: null,
        'plz'            => trim($_POST['plz'] ?? '') ?: null,
        'ort'            => trim($_POST['ort'] ?? '') ?: null,
        'team_id'        => is_int($teamId) ? $teamId : null,
        'freigeschaltet' => isset($_POST['freigeschaltet']) ? '1' : '0',
        'newsletter'     => isset($_POST['newsletter']) ? '1' : '0',
        'reminder'       => isset($_POST['reminder']) ? '1' : '0',
    ];

    $ok = saveTipper($originalNickname, $data, $password);

    if ($ok && is_string($teamId) && str_starts_with($teamId, 'NEU:')) {
        $tipper = getTipperByNickname($nickname);
        if ($tipper !== null) {
            $newTeamId = createTippTeam(substr($teamId, 4), (int)$tipper['id']);
            if ($newTeamId !== null) {
                saveTipper($nickname, array_merge($data, ['team_id' => $newTeamId]), null);
            }
        }
    }

    if ($ok) {
        $tipper = getTipperByNickname($nickname);
        if ($tipper !== null) {
            setTipperAbos((int)$tipper['id'], array_map('intval', $_POST['tipper_ligen'] ?? []));
        }
        flash(t('tipp_flash_settings_saved'));
    } else {
        flash(t('tipp_flash_speichern_fehlgeschlagen'), 'error');
    }
    redirect('?action=tippspiel&tab=userverwaltung');
}

if ($action === 'delete_tipp_user') {
    requireLogin();
    $nick = $_GET['nick'] ?? '';
    if ($nick !== '') {
        deleteTipper($nick);
        flash(t('tipp_flash_tipper_geloescht'));
    }
    redirect('?action=tippspiel&tab=userverwaltung');
}

if ($action === 'send_tipp_mail' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();

    $mailart = $_POST['mailart'] ?? 'alle';
    $betreff = trim($_POST['betreff'] ?? '');
    $message = $_POST['message'] ?? '';
    $alleTipper = getAllTipper();
    $versendet = 0;

    if ($mailart === 'persoenlich') {
        $adressatId = (int)($_POST['adressat'] ?? 0);
        $empfaenger = array_values(array_filter($alleTipper, fn($t) => (int)$t['id'] === $adressatId));
        foreach ($empfaenger as $t) {
            if (sendTippMail($t['email'], replaceTippPlaceholders($betreff, $t), replaceTippPlaceholders($message, $t))) {
                $versendet++;
            }
        }
    } elseif ($mailart === 'reminder') {
        $ligaId = (int)($_POST['reminder_liga'] ?? 0);
        $ligaIds = $ligaId > 0 ? [$ligaId] : array_map(fn($l) => (int)$l['id'], getTippbareLigenKandidaten());
        $spieltagNr = $ligaId > 0 ? (int)($_POST['spieltag_' . $ligaId] ?? 0) : 0;
        $spieltagNr = $spieltagNr > 0 ? $spieltagNr : null;
        $tage = max(1, (int)($_POST['tage'] ?? 4));

        $empfaenger = $alleTipper;
        if (($_POST['tipper_bereich'] ?? 'alle') === 'bereich') {
            $von = max(1, (int)($_POST['tipper_von'] ?? 1));
            $bis = max($von, (int)($_POST['tipper_bis'] ?? count($alleTipper)));
            $empfaenger = array_slice($alleTipper, $von - 1, $bis - $von + 1);
        }

        foreach ($empfaenger as $t) {
            $spiele = getTippReminderSpiele($ligaIds, $spieltagNr, $tage, (int)$t['id']);
            if (empty($spiele)) {
                continue; // Keine offenen Tipps für diesen Tipper in diesem Zeitraum -> keine Mail nötig
            }
            $spieleText = formatSpieleListe($spiele);
            if (sendTippMail($t['email'], replaceTippPlaceholders($betreff, $t, $spieleText), replaceTippPlaceholders($message, $t, $spieleText))) {
                $versendet++;
            }
        }
    } else {
        // Newsletter an Alle
        $empfaenger = $alleTipper;
        if (($_POST['tipper_bereich'] ?? 'alle') === 'bereich') {
            $von = max(1, (int)($_POST['tipper_von'] ?? 1));
            $bis = max($von, (int)($_POST['tipper_bis'] ?? count($alleTipper)));
            $empfaenger = array_slice($alleTipper, $von - 1, $bis - $von + 1);
        }
        foreach ($empfaenger as $t) {
            if (sendTippMail($t['email'], replaceTippPlaceholders($betreff, $t), replaceTippPlaceholders($message, $t))) {
                $versendet++;
            }
        }
    }

    flash(t('tipp_flash_mail_versendet', ['n' => $versendet]));
    redirect('?action=tippspiel&tab=newsletter');
}
