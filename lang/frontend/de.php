<?php
/**
 * Project: LMOnext
 * Filename: lang/frontend/de.php
 * Fileversion: 1.25.0
 * Changelog: 1.25.0 - Strafpunkte-Tooltip-Keys aktualisiert für die erweiterten
 *                     Bonus/Strafe-Felder (erzielte Tore ergänzt)
 * Changelog: 1.24.0 - Übersetzungen für den neuen Strafpunkte/Straftore-Tooltip in der
 *                     Liga-Tabelle ergänzt (siehe renderStrafHinweis() in
 *                     src/Liga/StandingsTrait.php 1.1.0)
 * Changelog: 1.23.0 - Übersetzungen für die Template-Integration des Tippspiels ergänzt
 *                     (Seitentitel, Tab-Beschriftungen) - siehe
 *                     addon/tipp/view_tippspiel_frontend.php 1.0.0. Mehrere alte Einzel-Titel
 *                     (tf_tipp_*_titel, tf_tipp_zur_abgabe) sind jetzt ungenutzt, da die neue
 *                     Tab-Leiste sie ersetzt - bewusst nicht gelöscht (harmlos, falls doch
 *                     irgendwo referenziert)
 * Changelog: 1.22.0 - Übersetzungen für den neuen Tippspiel-Link in Header/Footer und die
 *                     Startseiten-Werbekarte ergänzt (siehe addon/tipp/tipp_lib.php 0.5.0)
 * Changelog: 1.21.0 - Übersetzungen für die neue Tippspiel-Rangliste ergänzt (siehe
 *                     addon/tipp/tipp.php 0.3.0, tippGetRangliste() in frontend_tipp.php 0.3.0)
 * Changelog: 1.20.2 - Fehlenden Schlüssel "tf_tipp_col_nickname" für die Spaltenüberschrift
 *                     in der Tippeinsicht ergänzt (siehe addon/tipp/tipp.php)
 * Changelog: 1.20.1 - Übersetzungen für die Tippeinsicht ergänzt
 * Changelog: 1.20.0 - Übersetzungen für die neue (vorläufige) Tippspiel-Tipperansicht ergänzt
 *                     (Login/Registrierung/Tippabgabe, siehe addon/tipp/tipp.php)
 * Changelog: 1.19.3
 * Changelog: 1.19.3 - Neuer Schlüssel "home_overview_disabled" für den Hinweis auf der
 *                     Startseite, wenn die Liga-Übersicht deaktiviert ist (siehe home.php 2.1.0)
 * Changelog: 1.19.2
 * Changelog: 1.19.2 - Neuer Schlüssel "h2h_pdf_renamed_note" für den zusammenfassenden
 *                     Umbenennungs-Hinweis im Teamvergleich-PDF (siehe pdf_export.php 1.6.9)
 * Changelog: 1.19.1
 * Changelog: 1.19.1 - Neuer Schlüssel "h2h_today_prefix" für die "(heute TEAM_HEUTE)"-
 *                     Kennzeichnung bei verknüpften Teams im Teamvergleich (siehe
 *                     resolveLinkedTeamIds()/getHeadToHeadMatches() in data_liga.php 2.18.0)
 * Changelog: 1.19.0
 * Changelog: 1.19.0 - Übersetzung für den neuen "Spielfrei"-Hinweis ergänzt
 * Changelog: 1.18.0
 * Changelog: 1.18.0 - Übersetzungen für den neuen Besucher-Reiter "Spielerstatistik" ergänzt
 * Changelog: 1.17.0 - Übersetzungen für das neue Addon "Mininext" (Portierung aus dem alten LMO,
 *                     siehe addon/mini/lmo-mininext.php) ergänzt
 * Changelog: 1.16.9
 * Changelog: 1.16.9 - Übersetzung "Stand: {datum}" ergänzt, für das neue Minitabellen-Addon
 *                     (addon/mini/lmo-minitab.php)
 * Changelog: 1.16.8 - 'liga_col_spieltag_short' auf 'ST' geändert (vorher 'Sp.tag'), neuer
 *                     Schlüssel 'liga_col_spieltag_long' ('Spieltag') ergänzt – für die
 *                     responsive Lang-/Kurzform im Teamvergleich-Modal (Web/Mobil)
 * Changelog: 1.16.7 - Übersetzung "Template:" (ohne Namen) ergänzt, für den Footer, wenn dort
 *                     das Auswahl-Dropdown statt des reinen Namens steht
 * Changelog: 1.16.6 - Übersetzung "Design" für das neue Template-Auswahl-Dropdown im Header ergänzt
 * Changelog: 1.16.5 - Übersetzung "Nr." für die Spieltag-Nummer-Spalte im Spielplan-PDF-Export ergänzt
 * Changelog: 1.16.4 - Übersetzung für die PDF-Fußzeile ("© {year} www.liga-manager-online.org.
 *                     Alle Rechte vorbehalten. Version {version}") ergänzt
 * Changelog: 1.16.3 - Übersetzung "Ergebnisse Spieltag {n}" als PDF-Titel ergänzt
 * Changelog: 1.16.2 - Übersetzung für den "Als PDF exportieren"-Button auf der Ergebnisseite ergänzt
 * Changelog: 1.16.1 - Übersetzung "Siege {team}" für die Sieg-Chips im Vergleichs-Modal ergänzt
 * Changelog: 1.16.0 - Übersetzungen für das Direkter-Vergleich-Modal (Vergleichs-Icon,
 *                     Modaltitel, "Unentschieden", "keine bisherigen Begegnungen") ergänzt
 * Changelog: 1.15.0 - Umfangreiche Übersetzungen für Ligastatistik ergänzt (Team-Stat-Box,
 *                     ligaweiter Statistik-Block, Serien-Kategorien, Chancen/Restprogramm)
 * Changelog: 1.14.0 - Übersetzungen für Fieberkurven-Reiter + Platzhaltertext ergänzt
 * Changelog: 1.13.0 - Übersetzung für Kreuztabelle-Reiter ergänzt
 * Changelog: 1.12.2 - Übersetzung für Team-Spielplan-Platzhalter ergänzt
 * Changelog: 1.12.1 - Wertungshinweis-Übersetzung entfernt (nicht mehr angezeigt)
 * Changelog: 1.12.0 - Übersetzungen für Tabellen-Ansicht (Spaltenköpfe, Wertungshinweis) ergänzt
 * Changelog: 1.11.3 - Footer-Zeile "Template: {name}" ergänzt
 * Changelog: 1.11.2 - Testseiten-spezifischen Hinweis ("aktuell MySQL 8.0") wieder entfernt –
 *                     die Info-Seite läuft ja auch bei anderen Nutzern auf eigenen Servern mit
 *                     ggf. anderer Datenbank; Text bleibt generisch bei "MySQL/MariaDB"
 * Changelog: 1.11.1 - Info-Text erwähnt jetzt MySQL/MariaDB (statt nur MariaDB) und weist
 *                     darauf hin, dass diese Testseite aktuell mit MySQL 8.0 läuft
 * Changelog: 1.11.0 - Links zu Homepage + Forum auf der Info-Seite ergänzt
 * Changelog: 1.10.0 - Übersetzungen für Ergebnis-Zusatz "n.V."/"i.E." ergänzt
 * Changelog: 1.9.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.9.0 - Info-Ansicht umgebaut: zeigt jetzt "Über LMOnext" (Version, Copyright,
 *                     Kurzbeschreibung, Lizenz) statt Liga-Metadaten – analog zur Info-Seite
 *                     des alten LMO, die ebenfalls eine reine Software-Info-Seite ist
 * Changelog: 1.8.0 - Übersetzungen für Reiter-Navigation (Kalender/Ergebnisse/Spielpläne/Info),
 *                     Info-Ansicht und Kalender-Ansicht (Monatsnamen, Wochentage) ergänzt
 * Changelog: 1.7.0 - Übersetzung für Footer-Zeile "Dauer Berechnungen u. Seitenaufbau" ergänzt
 * Changelog: 1.6.0 - Benannte KO-Stufen (Sechzehntelfinale/32tel/64tel) entfernt, durch
 *                     generisches "Runde {n}" ersetzt (Namen gelten jetzt erst ab 16 Teams)
 * Changelog: 1.5.0 - Kleine Überschrift "Statistik {label}:" über der Statistikzeile ergänzt
 * Changelog: 1.4.0 - Übersetzungen für KO-Rundennamen nach Teamanzahl + Spiel um Platz 3 ergänzt
 * Changelog: 1.3.0 - Übersetzungen für Tabellen-Ansicht (Spieltag-Dropdown, Datumsspanne, Statistik) ergänzt
 * Changelog: 1.2.0 - Übersetzungen für Spieltag-Navigation (Vorherige/Nächste) ergänzt
 * Changelog: 1.1.0 - Übersetzungen für Liga-Detailseite (letzte Ergebnisse) ergänzt
 * Changelog: 1.0.0 - Initiale Version: Besucher-Startseite
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Deutsch ist die Referenzsprache des Besucherbereichs (separat vom
 * Adminbereich, siehe lang/admin/de.php). Fehlende Schlüssel in anderen
 * Sprachdateien fallen automatisch auf den hier hinterlegten Text zurück.
 */
declare(strict_types = 1);

return [

    'lang_switch_label' => 'Sprache',
    'template_switch_label' => 'Design',
    'site_title'         => 'LMOnext – Übersicht',

    // ── Startseite (home.php, template/*/home.php) ──────────────────────────
    'home_heading_active_ligen' => 'Aktive Ligen',
    'home_no_active_ligen'      => 'Aktuell sind keine aktiven Ligen vorhanden.',
    'home_overview_disabled'    => 'Die Liga-Übersicht ist deaktiviert.',
    'home_heading_archiv'       => 'Archiv',
    'home_type_ko'               => 'KO-Turnier',
    'home_type_liga'             => 'Liga',
    'home_folder_empty'          => 'Dieser Ordner ist leer.',

    // ── Liga-Detailseite (liga.php, template/*/liga.php) ────────────────────
    'liga_not_found'            => 'Liga nicht gefunden.',
    'liga_back_link'            => '← Zur Übersicht',
    'liga_label_matchday'       => 'Spieltag {n}',
    'liga_no_results_yet'       => 'Für diese Liga wurden noch keine Ergebnisse eingetragen.',
    'liga_spielfrei_label'      => 'Spielfrei:',
    'liga_subtitle_matchday'     => 'Ergebnisse Spieltag {n}',
    'liga_subtitle_round'        => 'Ergebnisse {name}',
    'liga_label_pick_matchday'   => 'Spieltag wählen:',
    'liga_label_pick_round'      => 'Runde wählen:',
    'liga_heading_matchday_range'=> '{n}. Spieltag {range}',
    'liga_col_nr'                => 'Nr.',
    'liga_col_datum'             => 'Datum',
    'liga_stand_datum'           => 'Stand: {datum}',
    'mini_next_upcoming'         => 'Nächstes Spiel',
    'mini_next_season_over'      => 'Letztes Spiel der Saison',
    'mini_next_previous'         => 'Vorheriges Spiel',
    'mini_next_countdown'        => '{d} Tage, {h} Std, {m} Min',
    'mini_next_matches_heading'  => 'Bisherige Begegnungen',
    'mini_next_win_short'        => 'S',
    'mini_next_draw_short'       => 'U',
    'mini_next_lost_short'       => 'N',
    'mini_next_home'             => 'Heim',
    'mini_next_away'             => 'Auswärts',
    'liga_col_heim'              => 'Heim',
    'liga_col_gast'              => 'Gast',
    'liga_col_ergebnis'          => 'Ergebnis',
    'liga_pdf_export_button'     => 'Als PDF exportieren',
    'liga_pdf_title_matchday'    => 'Ergebnisse Spieltag {n}',
    'liga_pdf_footer'            => '© {year} www.liga-manager-online.org. Alle Rechte vorbehalten. Version {version}',

    // ── Tabelle (Standings) ───────────────────────────────────────────────────
    'liga_tab_tabelle'            => 'Tabelle',
    'liga_tab_kreuztabelle'       => 'Kreuztabelle',
    'liga_tab_fieberkurve'        => 'Fieberkurven',
    'liga_tab_ligastatistik'      => 'Ligastatistik',
    'liga_tab_spielerstatistik'   => 'Spielerstatistik',
    'liga_fieberkurve_no_data'    => 'Für die Fieberkurve werden erst Ergebnisse benötigt.',
    'liga_col_spieltag_short'     => 'ST',
    'liga_col_spieltag_long'      => 'Spieltag',
    'liga_stat_home'              => 'Heim',
    'liga_stat_away'              => 'Auswärts',
    'liga_stat_home_short'        => 'H',
    'liga_stat_away_short'        => 'A',
    'liga_stat_position'          => 'Tabellenposition',
    'liga_stat_points'            => 'Pkt.',
    'liga_stat_played'            => 'Spiele',
    'liga_stat_ppg'               => 'Pkt./Spiel',
    'liga_stat_goals'             => 'Tore',
    'liga_stat_goals_per_game'    => 'Tore/Spiel',
    'liga_stat_wins'              => 'Siege',
    'liga_stat_best_win'          => 'Höchster Sieg',
    'liga_stat_losses'            => 'Niederlagen',
    'liga_stat_worst_loss'        => 'Höchste Niederlage',
    'liga_stat_current_streak'    => 'Aktuelle Serie',
    'liga_stat_remaining'         => 'Restprogramm',
    'liga_stat_overall_title'     => 'Statistische Daten zur Liga',
    'liga_stat_games'             => 'Spiele ges.',
    'liga_stat_home_wins'         => 'Heimsiege',
    'liga_stat_draws'             => 'Unentschieden',
    'liga_stat_away_wins'         => 'Auswärtssiege',
    'liga_stat_goals_total'       => 'Tore ges.',
    'liga_stat_home_goals'        => 'Heim-Tore',
    'liga_stat_away_goals'        => 'Auswärts-Tore',
    'liga_stat_highest_home_win'  => 'Höchste(r) Heimsieg(e)',
    'liga_stat_highest_away_win'  => 'Höchste(r) Auswärtssieg(e)',
    'liga_stat_most_goals'        => 'Die meisten Tore',
    'liga_stat_streaks_title'     => 'Serien',
    'liga_stat_streaks_current'   => 'Aktuell',
    'liga_stat_streaks_season'    => 'Saison',
    'liga_stat_streak_cat_won'      => 'Gewonnen',
    'liga_stat_streak_cat_unbeaten' => 'Ungeschlagen',
    'liga_stat_streak_cat_draw'     => 'Unentschieden',
    'liga_stat_streak_cat_winless'  => 'Sieglos',
    'liga_stat_streak_cat_lost'     => 'Verloren',
    'liga_stat_pick_team'         => 'Team wählen',
    'liga_stat_pick_team_msg'     => 'Bitte wählen Sie jetzt eine oder zwei Mannschaften aus.',
    'liga_stat_chances'           => 'Chancen gegeneinander',
    'liga_stat_tendenz_equal'     => 'etwa gleich schwer',
    'liga_stat_tendenz_harder'    => 'schwerer für {team}',
    'liga_stat_remaining_eval_title' => 'Bewertung der Restprogramme',
    'liga_stat_remaining_ppg'     => 'Ø Pkt.-Schnitt der verbleibenden Gegner',
    'liga_stat_streak_wins'       => '{n} Siege in Folge',
    'liga_stat_streak_losses'     => '{n} Niederlagen in Folge',
    'liga_stat_streak_draws'      => '{n} Unentschieden in Folge',
    'liga_stat_streak_unbeaten'   => '{n} Spiele ungeschlagen',
    'liga_stat_streak_winless'    => '{n} Spiele ohne Sieg',
    'liga_standings_col_platz'    => '#',
    'liga_standings_col_team'     => 'Team',
    'liga_standings_col_sp'       => 'Sp',
    'liga_standings_col_s'        => 'S',
    'liga_standings_col_u'        => 'U',
    'liga_standings_col_n'        => 'N',
    'liga_standings_col_tore'     => 'Tore',
    'liga_standings_col_diff'     => 'Diff',
    'liga_standings_col_pkt'      => 'Pkt',
    'liga_standings_straf_erzielt'   => 'Tore',
    'liga_standings_straf_gegentore' => 'Gegentore',
    'liga_schedule_pick_team'     => 'Bitte wählen Sie jetzt eine Mannschaft aus.',

    // ── Direkter Vergleich (Vergleichs-Modal) ─────────────────────────────────
    'liga_h2h_icon_title'         => 'Direkter Vergleich',
    'liga_h2h_modal_title'        => '{heim} vs {gast}',
    'liga_h2h_wins'               => 'Siege {team}',
    'liga_h2h_draw'               => 'Unent.',
    'liga_h2h_no_matches'         => 'Bisher noch keine Begegnungen zwischen diesen beiden Mannschaften.',
    'liga_h2h_close'              => 'Schließen',
    'h2h_today_prefix'            => 'heute',
    'h2h_pdf_renamed_note'        => 'Hinweis: {list} (jeweils selbes Team unter früherem Namen)',
    'liga_status_ie'             => 'i.E.',
    'liga_status_nv'             => 'n.V.',
    'liga_stats_line'            => 'Schnitt Heim: {heim}   Schnitt Gast: {gast}   Tore: {tore}   Tore/Spiel: {proSpiel}',
    'liga_stats_heading'         => 'Statistik {label}:',

    // ── KO-Rundennamen nach Teamanzahl (data_liga.php: koRoundName()) ────────
    'liga_round_finale'          => 'Finale',
    'liga_round_halbfinale'      => 'Halbfinale',
    'liga_round_viertelfinale'   => 'Viertelfinale',
    'liga_round_achtelfinale'    => 'Achtelfinale',
    'liga_round_generic'         => 'Runde {n}',
    'liga_heading_platz3'        => 'Kleines Finale – Spiel um Platz 3',
    'footer_render_time'         => 'Dauer Berechnungen u. Seitenaufbau: {sekunden} sek.',
    'footer_template'            => 'Template: {name}',
    'footer_template_prefix'     => 'Template:',

    // ── Reiter-Navigation (Kalender/Ergebnisse/Spielpläne/Info) ──────────────
    'liga_tab_kalender'      => 'Kalender',
    'liga_tab_ergebnisse'    => 'Ergebnisse',
    'liga_tab_spielplaene'   => 'Spielpläne',
    'liga_tab_info'          => 'Info',

    // ── Info-Ansicht ──────────────────────────────────────────────────────────
    'liga_info_title'      => 'LMOnext – Version {version}',
    'liga_info_copyright'  => '© 2026 Dietmar Kersting',
    'liga_info_text_1'     => 'LMOnext ist eine Software zur Verwaltung von Sportligen und Turnieren – für reguläre Ligen ebenso wie für KO-Turniere mit automatischer Rundenstruktur.',
    'liga_info_text_2'     => 'Es handelt sich um eine komplette Neuentwicklung für PHP 8 und MySQL/MariaDB, inspiriert vom Liga Manager Online (LMO).',
    'liga_info_license'    => 'Dieses Projekt steht unter der GNU General Public License v3.0 (GPLv3).',
    'liga_info_link_homepage' => '<a href="https://www.liga-manager-online.org" target="_blank" rel="noopener">Homepage</a>',
    'liga_info_link_forum'    => '<a href="https://www.liga-manager-online.org/forum/" target="_blank" rel="noopener">Forum</a>',

    // ── Kalender-Ansicht ──────────────────────────────────────────────────────
    'liga_kalender_today' => 'Heute',
    'liga_month_1'  => 'Januar',
    'liga_month_2'  => 'Februar',
    'liga_month_3'  => 'März',
    'liga_month_4'  => 'April',
    'liga_month_5'  => 'Mai',
    'liga_month_6'  => 'Juni',
    'liga_month_7'  => 'Juli',
    'liga_month_8'  => 'August',
    'liga_month_9'  => 'September',
    'liga_month_10' => 'Oktober',
    'liga_month_11' => 'November',
    'liga_month_12' => 'Dezember',
    'liga_weekday_mo' => 'Mo',
    'liga_weekday_di' => 'Di',
    'liga_weekday_mi' => 'Mi',
    'liga_weekday_do' => 'Do',
    'liga_weekday_fr' => 'Fr',
    'liga_weekday_sa' => 'Sa',
    'liga_weekday_so' => 'So',

    // ── Spielerstatistik (Besucheransicht) ──────────────────────────────────
    'spst_view_empty'         => 'Für diese Liga liegt noch keine Spielerstatistik vor.',
    'spst_view_all_clubs'     => 'Alle Vereine',
    'spst_sort_desc'          => 'absteigend sortieren',
    'spst_sort_asc'           => 'aufsteigend sortieren',
    'spst_player_link_title'  => 'Externes Spielerprofil öffnen',
    'spst_pager_prev'         => 'Zurück',
    'spst_pager_next'         => 'Weiter',
    'spst_pager_range'        => '{from}–{to} von {total}',

    // ── Tippspiel (Tipper-Ansicht, vorläufig) ────────────────────────────────
    'tf_tipp_login_titel'      => 'Tippspiel — Login',
    'tf_tipp_nickname'         => 'Nickname',
    'tf_tipp_passwort'         => 'Passwort',
    'tf_tipp_einloggen'        => 'Einloggen',
    'tf_tipp_noch_kein_konto'  => 'Noch kein Konto?',
    'tf_tipp_registrieren'     => 'Registrieren',
    'tf_tipp_registrieren_titel' => 'Tippspiel — Registrieren',
    'tf_tipp_registrierung_erfolgreich_titel' => 'Registrierung erfolgreich',
    'tf_tipp_registrierung_sofort' => 'Dein Konto ist sofort freigeschaltet, du kannst dich jetzt einloggen.',
    'tf_tipp_registrierung_email'  => 'Wir haben dir eine E-Mail mit einem Bestätigungslink geschickt. Bitte klicke darauf, um dein Konto freizuschalten.',
    'tf_tipp_registrierung_admin'  => 'Deine Registrierung wurde übermittelt. Ein Administrator muss dein Konto noch freischalten.',
    'tf_tipp_zum_login'        => 'Zum Login',
    'tf_tipp_email'            => 'E-Mail-Adresse',
    'tf_tipp_passwort_wdh'     => 'Passwort wiederholen',
    'tf_tipp_vorname'          => 'Vorname',
    'tf_tipp_nachname'         => 'Nachname',
    'tf_tipp_strasse'          => 'Strasse',
    'tf_tipp_plz'              => 'PLZ',
    'tf_tipp_ort'              => 'Wohnort',
    'tf_tipp_bestaetigung_titel' => 'E-Mail-Bestätigung',
    'tf_tipp_bestaetigung_erfolg' => 'Dein Konto wurde erfolgreich freigeschaltet.',
    'tf_tipp_bestaetigung_fehler' => 'Dieser Bestätigungslink ist ungültig oder wurde bereits verwendet.',
    'tf_tipp_abgabe_titel'     => 'Tippabgabe',
    'tf_tipp_keine_ligen'      => 'Aktuell ist keine Liga fürs Tippen freigegeben.',
    'tf_tipp_kein_abo'         => 'Du hast noch keine Liga zum Tippen abonniert.',
    'tf_tipp_eingeloggt_als'   => 'Eingeloggt als',
    'tf_tipp_logout'           => 'Logout',
    'tf_tipp_kein_spieltag'    => 'Für diesen Spieltag liegen keine Spiele vor.',
    'tf_tipp_col_termin'       => 'Termin',
    'tf_tipp_col_spiel'        => 'Spiel',
    'tf_tipp_col_nickname'     => 'Nickname',
    'tf_tipp_col_tipp'         => 'Tipp',
    'tf_tipp_col_joker'        => 'Joker',
    'tf_tipp_col_ergebnis'     => 'Ergebnis',
    'tf_tipp_col_punkte'       => 'Pkt.',
    'tf_tipp_speichern'        => 'Tipps speichern',
    'tf_tipp_err_nickname'     => 'Bitte einen gültigen Nickname angeben (max. 50 Zeichen).',
    'tf_tipp_err_email'        => 'Bitte eine gültige E-Mail-Adresse angeben.',
    'tf_tipp_err_passwort_kurz' => 'Das Passwort muss mindestens 6 Zeichen lang sein.',
    'tf_tipp_err_passwort_mismatch' => 'Die beiden Passwörter stimmen nicht überein.',
    'tf_tipp_err_nickname_vergeben' => 'Dieser Nickname ist bereits vergeben.',
    'tf_tipp_err_speichern'    => 'Beim Speichern ist ein Fehler aufgetreten.',
    'tf_tipp_err_login'        => 'Nickname oder Passwort ist falsch.',
    'tf_tipp_err_nicht_freigeschaltet' => 'Dein Konto ist noch nicht freigeschaltet.',
    'tf_tipp_mail_confirm_betreff' => 'Bitte bestätige deine Anmeldung',
    'tf_tipp_mail_confirm_text'    => "Hallo {nick},\n\nbitte bestätige deine Anmeldung zum Tippspiel über diesen Link:\n{link}",
    'tf_tipp_mail_welcome_betreff' => 'Willkommen beim Tippspiel',
    'tf_tipp_mail_welcome_text'    => "Hallo {nick},\n\ndeine Anmeldung zum Tippspiel war erfolgreich.",
    'tf_tipp_mail_admin_betreff'   => 'Neue Tippspiel-Anmeldung',
    'tf_tipp_mail_admin_text'      => "Ein neuer Tipper hat sich angemeldet: {nick}",
    'tf_tipp_einsicht_titel'       => 'Tippeinsicht',
    'tf_tipp_zur_abgabe'           => 'Zur Tippabgabe',
    'tf_tipp_rangliste_titel'      => 'Rangliste',
    'tf_tipp_seiten_titel'         => 'Tippspiel',
    'tf_tipp_tab_abgabe'           => 'Tippabgabe',
    'tf_tipp_tab_einsicht'         => 'Tippeinsicht',
    'tf_tipp_tab_rangliste'        => 'Rangliste',
    'tf_tipp_header_link'          => '🎯 Tippspiel',
    'tf_tipp_home_card_titel'      => 'Tippspiel',
    'tf_tipp_home_card_text'       => 'Tippe die Ergebnisse deiner Lieblingsliga und miss dich mit anderen Tippern in der Rangliste!',
    'tf_tipp_home_card_button'     => 'Jetzt mitmachen',
    'tf_tipp_rangliste_leer'       => 'Noch keine Tipps für die Rangliste vorhanden.',
    'tf_tipp_col_platz'            => 'Platz',
    'tf_tipp_col_spiele_getippt'   => 'Sp. getippt',
    'tf_tipp_col_quote'            => 'Trefferquote',
    'tf_tipp_col_spieltagssiege'   => 'Spieltagssiege',
    'tf_tipp_col_spieltagssiege_titel' => 'Anzahl gewonnener Spieltage (höchste Punktzahl an diesem Spieltag, geteilte Siege möglich)',
    'tf_tipp_col_re'               => 'RE',
    'tf_tipp_col_re_titel'         => 'Richtige Ergebnisse (exakter Endstand getippt)',
    'tf_tipp_col_rtd'              => 'RTD',
    'tf_tipp_col_rtd_titel'        => 'Richtige Tendenz mit Tordifferenz',
    'tf_tipp_col_rt'               => 'RT',
    'tf_tipp_col_rt_titel'         => 'Richtige Tendenz (Sieger/Unentschieden)',
    'tf_tipp_col_jp'               => 'JP',
    'tf_tipp_col_jp_titel'         => 'Durch Joker zusätzlich gewonnene Punkte',
    'tf_tipp_konto_link'           => 'Konto',
    'tf_tipp_konto_gespeichert'    => 'Deine Angaben wurden gespeichert.',
    'tf_tipp_passwort_vergessen_link' => 'Passwort vergessen?',
    'tf_tipp_reset_anfordern'      => 'Link anfordern',
    'tf_tipp_reset_mail_verschickt' => 'Ein Link zum Zurücksetzen des Passworts wurde an die hinterlegte Email-Adresse verschickt.',
    'tf_tipp_reset_hinweis'        => 'Bitte fülle eines der beiden Felder aus.',
    'tf_tipp_reset_nickname_nicht_gefunden' => 'Tipper nicht gefunden.',
    'tf_tipp_reset_email_nicht_gefunden' => 'Email-Adresse nicht gefunden.',
    'tf_tipp_reset_beides_leer'    => 'Bitte Nickname oder Email-Adresse angeben.',
    'tf_tipp_reset_ungueltig'      => 'Dieser Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.',
    'tf_tipp_neues_passwort'       => 'Neues Passwort',
    'tf_tipp_passwort_setzen'      => 'Passwort setzen',
    'tf_tipp_leer_lassen_hinweis'  => 'leer lassen für keine Änderung',
    'tf_tipp_team'                 => 'Team',
    'tf_tipp_kein_team'            => 'keinem Team',
    'tf_tipp_neues_team_gruenden'  => 'Neues Team gründen',
    'tf_tipp_neues_team_platzhalter' => 'Teamname (freilassen, um kein neues Team zu gründen)',
    'tf_tipp_newsletter_erhalten'  => 'Newsletter erhalten',
    'tf_tipp_reminder_erhalten'    => 'Tipp-Erinnerungen erhalten',
    'tf_tipp_abonnierte_ligen'     => 'Ligen, für die ich tippe',
    'tf_tipp_mail_reset_betreff'   => 'Passwort zurücksetzen',
    'tf_tipp_mail_reset_text'      => "Hallo [nick],\n\ndu hast (oder jemand in deinem Namen) angefordert, dein Tippspiel-Passwort zurückzusetzen. Klicke auf den folgenden Link, um ein neues Passwort zu vergeben (1 Stunde gültig):",
    'tf_tipp_einsicht_noch_nicht_sichtbar' => 'Für dieses Spiel sind die Tipps noch nicht einsehbar.',

];
