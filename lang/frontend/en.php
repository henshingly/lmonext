<?php
/**
 * Project: LMOnext
 * Filename: lang/frontend/en.php
 * Fileversion: 1.23.0
 * Changelog: 1.23.0 - Added translations for the Tippspiel template integration (page title,
 *                     tab labels) - see addon/tipp/view_tippspiel_frontend.php 1.0.0. Several
 *                     old per-view titles (tf_tipp_*_titel, tf_tipp_zur_abgabe) are now unused
 *                     since the new tab bar replaces them - intentionally left in place
 *                     (harmless if still referenced anywhere)
 * Changelog: 1.22.0 - Added translations for the new Tippspiel link in the header/footer and
 *                     the homepage promo card (see addon/tipp/tipp_lib.php 0.5.0)
 * Changelog: 1.21.0 - Added translations for the new Tippspiel leaderboard (see
 *                     addon/tipp/tipp.php 0.3.0, tippGetRangliste() in frontend_tipp.php 0.3.0)
 * Changelog: 1.20.2 - Added missing key "tf_tipp_col_nickname" for the column header in the
 *                     tip overview (see addon/tipp/tipp.php)
 * Changelog: 1.20.1 - Added translations for the tip overview (Tippeinsicht)
 * Changelog: 1.20.0 - Added translations for the new (preliminary) prediction game tipster view
 *                     (login/registration/tip submission, see addon/tipp/tipp.php)
 * Changelog: 1.19.3
 * Changelog: 1.19.3 - Added key "home_overview_disabled" for the homepage message shown when
 *                     the league overview is disabled (see home.php 2.1.0)
 * Changelog: 1.19.2
 * Changelog: 1.19.2 - Added key "h2h_pdf_renamed_note" for the summarized rename note in the
 *                     head-to-head PDF export (see pdf_export.php 1.6.9)
 * Changelog: 1.19.1
 * Changelog: 1.19.1 - Added key "h2h_today_prefix" for the "(today TEAM_TODAY)" annotation on
 *                     linked teams in the head-to-head comparison (see
 *                     resolveLinkedTeamIds()/getHeadToHeadMatches() in data_liga.php 2.18.0)
 * Changelog: 1.19.0
 * Changelog: 1.19.0 - Added translation for the new "Spielfrei" (bye) note
 * Changelog: 1.18.0
 * Changelog: 1.18.0 - Added translations for the new "Player stats" visitor tab
 * Changelog: 1.17.0 - Added translations for the new "Mininext" addon (ported from old LMO, see
 *                     addon/mini/lmo-mininext.php)
 * Changelog: 1.16.9
 * Changelog: 1.16.9 - Added "As of: {datum}" translation for the new Minitabellen addon
 *                     (addon/mini/lmo-minitab.php)
 * Changelog: 1.16.8 - Added 'liga_col_spieltag_long' ('Matchday') for the responsive long/short
 *                     form in the head-to-head comparison modal (web/mobile); short key ('MD')
 *                     unchanged
 * Changelog: 1.16.7 - Added "Template:" (without name) translation, for the footer when the
 *                     selector dropdown appears there instead of the plain name
 * Changelog: 1.16.6 - Added "Theme" translation for the new template-switcher dropdown in the header
 * Changelog: 1.16.5 - Added "No." translation for the matchday-number column in the schedule PDF export
 * Changelog: 1.16.4 - Added PDF footer translation ("© {year} www.liga-manager-online.org.
 *                     All rights reserved. Version {version}")
 * Changelog: 1.16.3 - Added "Results Matchday {n}" PDF title translation
 * Changelog: 1.16.2 - Added translation for the "Export as PDF" button on the results page
 * Changelog: 1.16.1 - Added "Wins {team}" translation for the win chips in the comparison modal
 * Changelog: 1.16.0 - Translations for the head-to-head comparison modal (compare icon,
 *                     modal title, "Draw", "no previous matches") added
 * Changelog: 1.15.0 - Extensive translations for league statistics added (team stat box,
 *                     overall statistics block, streak categories, chances/remaining schedule)
 * Changelog: 1.14.0 - Translations for position-chart tab + placeholder text added
 * Changelog: 1.13.0 - Translation for cross table tab added
 * Changelog: 1.12.2 - Translation for team schedule placeholder added
 * Changelog: 1.12.1 - Removed scoring-line translation (no longer displayed)
 * Changelog: 1.12.0 - Translations for standings view (column headers, scoring line) added
 * Changelog: 1.11.3 - Footer line "Template: {name}" added
 * Changelog: 1.11.2 - Removed the test-site-specific note ("currently MySQL 8.0") again –
 *                     the Info page also runs for other users on their own servers with
 *                     possibly a different database; text stays generic ("MySQL/MariaDB")
 * Changelog: 1.11.1 - Info text now mentions MySQL/MariaDB (instead of just MariaDB) and
 *                     notes that this test site currently runs on MySQL 8.0
 * Changelog: 1.11.0 - Links to homepage + forum added on the Info page
 * Changelog: 1.10.0 - Translations for result suffix "AET"/"pens." added
 * Changelog: 1.9.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.9.0 - Info view rebuilt: now shows "About LMOnext" (version, copyright,
 *                     short description, license) instead of league metadata – matching the
 *                     old LMO's Info page, which is likewise a plain software-info page
 * Changelog: 1.8.0 - Translations for tab navigation (Calendar/Results/Bracket/Info),
 *                     info view and calendar view (month names, weekdays) added
 * Changelog: 1.7.0 - Translation for footer line "Calculation & page build time" added
 * Changelog: 1.6.0 - Removed named KO stages (Round of 32/64/128), replaced with generic
 *                     "Round {n}" (named stages now only apply from 16 teams downward)
 * Changelog: 1.5.0 - Small heading "Stats – {label}:" above the stats line added
 * Changelog: 1.4.0 - Translations for KO round names by team count + third-place match added
 * Changelog: 1.3.0 - Translations for table view (matchday dropdown, date range, stats) added
 * Changelog: 1.2.0 - Translations for matchday navigation (Previous/Next) added
 * Changelog: 1.1.0 - Translations for league detail page (latest results) added
 * Changelog: 1.0.0 - Initial version: visitor home page
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Any key missing here automatically falls back to lang/frontend/de.php.
 */
declare(strict_types = 1);

return [

    'lang_switch_label' => 'Language',
    'template_switch_label' => 'Theme',
    'site_title'         => 'LMOnext – Overview',

    // ── Home page (home.php, template/*/home.php) ────────────────────────────
    'home_heading_active_ligen' => 'Active leagues',
    'home_no_active_ligen'      => 'No active leagues at the moment.',
    'home_overview_disabled'    => 'The league overview is disabled.',
    'home_heading_archiv'       => 'Archive',
    'home_type_ko'               => 'KO tournament',
    'home_type_liga'             => 'League',
    'home_folder_empty'          => 'This folder is empty.',

    // ── League detail page (liga.php, template/*/liga.php) ───────────────────
    'liga_not_found'            => 'League not found.',
    'liga_back_link'            => '← Back to overview',
    'liga_label_matchday'       => 'Matchday {n}',
    'liga_no_results_yet'       => 'No results have been entered for this league yet.',
    'liga_spielfrei_label'      => 'Bye:',
    'liga_subtitle_matchday'     => 'Results – Matchday {n}',
    'liga_subtitle_round'        => 'Results – {name}',
    'liga_label_pick_matchday'   => 'Select matchday:',
    'liga_label_pick_round'      => 'Select round:',
    'liga_heading_matchday_range'=> 'Matchday {n} {range}',
    'liga_col_nr'                => 'No.',
    'liga_col_datum'             => 'Date',
    'liga_stand_datum'           => 'As of: {datum}',
    'mini_next_upcoming'         => 'Next match',
    'mini_next_season_over'      => 'Last match of the season',
    'mini_next_previous'         => 'Previous match',
    'mini_next_countdown'        => '{d} days, {h} hrs, {m} min',
    'mini_next_matches_heading'  => 'Previous meetings',
    'mini_next_win_short'        => 'W',
    'mini_next_draw_short'       => 'D',
    'mini_next_lost_short'       => 'L',
    'mini_next_home'             => 'Home',
    'mini_next_away'             => 'Away',
    'liga_col_heim'              => 'Home',
    'liga_col_gast'              => 'Away',
    'liga_col_ergebnis'          => 'Result',
    'liga_pdf_export_button'     => 'Export as PDF',
    'liga_pdf_title_matchday'    => 'Results Matchday {n}',
    'liga_pdf_footer'            => '© {year} www.liga-manager-online.org. All rights reserved. Version {version}',

    // ── Standings ─────────────────────────────────────────────────────────────
    'liga_tab_tabelle'            => 'Standings',
    'liga_tab_kreuztabelle'       => 'Cross table',
    'liga_tab_fieberkurve'        => 'Position chart',
    'liga_tab_ligastatistik'      => 'League stats',
    'liga_tab_spielerstatistik'   => 'Player stats',
    'liga_fieberkurve_no_data'    => 'Results are needed before the position chart can be shown.',
    'liga_col_spieltag_short'     => 'MD',
    'liga_col_spieltag_long'      => 'Matchday',
    'liga_stat_home'              => 'Home',
    'liga_stat_away'              => 'Away',
    'liga_stat_home_short'        => 'H',
    'liga_stat_away_short'        => 'A',
    'liga_stat_position'          => 'Table position',
    'liga_stat_points'            => 'Pts.',
    'liga_stat_played'            => 'Played',
    'liga_stat_ppg'               => 'Pts./game',
    'liga_stat_goals'             => 'Goals',
    'liga_stat_goals_per_game'    => 'Goals/game',
    'liga_stat_wins'              => 'Wins',
    'liga_stat_best_win'          => 'Biggest win',
    'liga_stat_losses'            => 'Losses',
    'liga_stat_worst_loss'        => 'Biggest loss',
    'liga_stat_current_streak'    => 'Current streak',
    'liga_stat_remaining'         => 'Remaining fixtures',
    'liga_stat_overall_title'     => 'League statistics',
    'liga_stat_games'             => 'Total games',
    'liga_stat_home_wins'         => 'Home wins',
    'liga_stat_draws'             => 'Draws',
    'liga_stat_away_wins'         => 'Away wins',
    'liga_stat_goals_total'       => 'Total goals',
    'liga_stat_home_goals'        => 'Home goals',
    'liga_stat_away_goals'        => 'Away goals',
    'liga_stat_highest_home_win'  => 'Biggest home win(s)',
    'liga_stat_highest_away_win'  => 'Biggest away win(s)',
    'liga_stat_most_goals'        => 'Most goals in a match',
    'liga_stat_streaks_title'     => 'Streaks',
    'liga_stat_streaks_current'   => 'Current',
    'liga_stat_streaks_season'    => 'Season',
    'liga_stat_streak_cat_won'      => 'Won',
    'liga_stat_streak_cat_unbeaten' => 'Unbeaten',
    'liga_stat_streak_cat_draw'     => 'Drawn',
    'liga_stat_streak_cat_winless'  => 'Winless',
    'liga_stat_streak_cat_lost'     => 'Lost',
    'liga_stat_pick_team'         => 'Select team',
    'liga_stat_pick_team_msg'     => 'Please select one or two teams now.',
    'liga_stat_chances'           => 'Chances against each other',
    'liga_stat_tendenz_equal'     => 'about equally hard',
    'liga_stat_tendenz_harder'    => 'harder for {team}',
    'liga_stat_remaining_eval_title' => 'Remaining schedule evaluation',
    'liga_stat_remaining_ppg'     => 'Avg. points/game of remaining opponents',
    'liga_stat_streak_wins'       => '{n} wins in a row',
    'liga_stat_streak_losses'     => '{n} losses in a row',
    'liga_stat_streak_draws'      => '{n} draws in a row',
    'liga_stat_streak_unbeaten'   => '{n} games unbeaten',
    'liga_stat_streak_winless'    => '{n} games without a win',
    'liga_standings_col_platz'    => '#',
    'liga_standings_col_team'     => 'Team',
    'liga_standings_col_sp'       => 'MP',
    'liga_standings_col_s'        => 'W',
    'liga_standings_col_u'        => 'D',
    'liga_standings_col_n'        => 'L',
    'liga_standings_col_tore'     => 'Goals',
    'liga_standings_col_diff'     => 'GD',
    'liga_standings_col_pkt'      => 'Pts',
    'liga_schedule_pick_team'     => 'Please select a team now.',

    // ── Head-to-head comparison modal ─────────────────────────────────────────
    'liga_h2h_icon_title'         => 'Head-to-head comparison',
    'liga_h2h_modal_title'        => '{heim} vs {gast}',
    'liga_h2h_wins'               => 'Wins {team}',
    'liga_h2h_draw'               => 'Draw',
    'liga_h2h_no_matches'         => 'No previous matches between these two teams yet.',
    'liga_h2h_close'              => 'Close',
    'h2h_today_prefix'            => 'today',
    'h2h_pdf_renamed_note'        => 'Note: {list} (same team, former name)',
    'liga_status_ie'             => 'pens.',
    'liga_status_nv'             => 'AET',
    'liga_stats_line'            => 'Average home: {heim}   Average away: {gast}   Goals: {tore}   Goals/match: {proSpiel}',
    'liga_stats_heading'         => 'Stats – {label}:',

    // ── KO round names by team count (data_liga.php: koRoundName()) ─────────
    'liga_round_finale'          => 'Final',
    'liga_round_halbfinale'      => 'Semi-final',
    'liga_round_viertelfinale'   => 'Quarter-final',
    'liga_round_achtelfinale'    => 'Round of 16',
    'liga_round_generic'         => 'Round {n}',
    'liga_heading_platz3'        => 'Third-place match',
    'footer_render_time'         => 'Calculation & page build time: {sekunden} sec.',
    'footer_template'            => 'Template: {name}',
    'footer_template_prefix'     => 'Template:',

    // ── Tab navigation (Calendar/Results/Bracket/Info) ───────────────────────
    'liga_tab_kalender'      => 'Calendar',
    'liga_tab_ergebnisse'    => 'Results',
    'liga_tab_spielplaene'   => 'Bracket',
    'liga_tab_info'          => 'Info',

    // ── Info view ─────────────────────────────────────────────────────────────
    'liga_info_title'      => 'LMOnext – Version {version}',
    'liga_info_copyright'  => '© 2026 Dietmar Kersting',
    'liga_info_text_1'     => 'LMOnext is software for managing sports leagues and tournaments – for regular leagues as well as knockout tournaments with automatic round structure.',
    'liga_info_text_2'     => 'It is a complete rewrite for PHP 8 and MySQL/MariaDB, inspired by Liga Manager Online (LMO).',
    'liga_info_license'    => 'This project is licensed under the GNU General Public License v3.0 (GPLv3).',
    'liga_info_link_homepage' => '<a href="https://www.liga-manager-online.org" target="_blank" rel="noopener">Homepage</a>',
    'liga_info_link_forum'    => '<a href="https://www.liga-manager-online.org/forum/" target="_blank" rel="noopener">Forum</a>',

    // ── Calendar view ─────────────────────────────────────────────────────────
    'liga_kalender_today' => 'Today',
    'liga_month_1'  => 'January',
    'liga_month_2'  => 'February',
    'liga_month_3'  => 'March',
    'liga_month_4'  => 'April',
    'liga_month_5'  => 'May',
    'liga_month_6'  => 'June',
    'liga_month_7'  => 'July',
    'liga_month_8'  => 'August',
    'liga_month_9'  => 'September',
    'liga_month_10' => 'October',
    'liga_month_11' => 'November',
    'liga_month_12' => 'December',
    'liga_weekday_mo' => 'Mon',
    'liga_weekday_di' => 'Tue',
    'liga_weekday_mi' => 'Wed',
    'liga_weekday_do' => 'Thu',
    'liga_weekday_fr' => 'Fri',
    'liga_weekday_sa' => 'Sat',
    'liga_weekday_so' => 'Sun',

    // ── Player stats (visitor view) ──────────────────────────────────────────
    'spst_view_empty'         => 'No player stats are available for this league yet.',
    'spst_view_all_clubs'     => 'All clubs',
    'spst_sort_desc'          => 'sort descending',
    'spst_sort_asc'           => 'sort ascending',
    'spst_player_link_title'  => 'Open external player profile',
    'spst_pager_prev'         => 'Previous',
    'spst_pager_next'         => 'Next',
    'spst_pager_range'        => '{from}–{to} of {total}',

    // ── Prediction game (tipster view, preliminary) ──────────────────────────
    'tf_tipp_login_titel'      => 'Prediction Game — Login',
    'tf_tipp_nickname'         => 'Nickname',
    'tf_tipp_passwort'         => 'Password',
    'tf_tipp_einloggen'        => 'Log in',
    'tf_tipp_noch_kein_konto'  => "Don't have an account yet?",
    'tf_tipp_registrieren'     => 'Register',
    'tf_tipp_registrieren_titel' => 'Prediction Game — Register',
    'tf_tipp_registrierung_erfolgreich_titel' => 'Registration successful',
    'tf_tipp_registrierung_sofort' => 'Your account is active immediately, you can log in now.',
    'tf_tipp_registrierung_email'  => 'We have sent you an email with a confirmation link. Please click it to activate your account.',
    'tf_tipp_registrierung_admin'  => 'Your registration has been submitted. An administrator still needs to activate your account.',
    'tf_tipp_zum_login'        => 'Go to login',
    'tf_tipp_email'            => 'Email address',
    'tf_tipp_passwort_wdh'     => 'Repeat password',
    'tf_tipp_vorname'          => 'First name',
    'tf_tipp_nachname'         => 'Last name',
    'tf_tipp_strasse'          => 'Street',
    'tf_tipp_plz'              => 'Zip code',
    'tf_tipp_ort'              => 'City',
    'tf_tipp_bestaetigung_titel' => 'Email confirmation',
    'tf_tipp_bestaetigung_erfolg' => 'Your account has been activated successfully.',
    'tf_tipp_bestaetigung_fehler' => 'This confirmation link is invalid or has already been used.',
    'tf_tipp_abgabe_titel'     => 'Tip Submission',
    'tf_tipp_keine_ligen'      => 'No league is currently open for tipping.',
    'tf_tipp_eingeloggt_als'   => 'Logged in as',
    'tf_tipp_logout'           => 'Logout',
    'tf_tipp_kein_spieltag'    => 'There are no games for this matchday.',
    'tf_tipp_col_termin'       => 'Kickoff',
    'tf_tipp_col_spiel'        => 'Match',
    'tf_tipp_col_nickname'     => 'Nickname',
    'tf_tipp_col_tipp'         => 'Tip',
    'tf_tipp_col_joker'        => 'Joker',
    'tf_tipp_col_ergebnis'     => 'Result',
    'tf_tipp_col_punkte'       => 'Pts.',
    'tf_tipp_speichern'        => 'Save tips',
    'tf_tipp_err_nickname'     => 'Please enter a valid nickname (max. 50 characters).',
    'tf_tipp_err_email'        => 'Please enter a valid email address.',
    'tf_tipp_err_passwort_kurz' => 'The password must be at least 6 characters long.',
    'tf_tipp_err_passwort_mismatch' => 'The two passwords do not match.',
    'tf_tipp_err_nickname_vergeben' => 'This nickname is already taken.',
    'tf_tipp_err_speichern'    => 'An error occurred while saving.',
    'tf_tipp_err_login'        => 'Nickname or password is incorrect.',
    'tf_tipp_err_nicht_freigeschaltet' => 'Your account has not been activated yet.',
    'tf_tipp_mail_confirm_betreff' => 'Please confirm your registration',
    'tf_tipp_mail_confirm_text'    => "Hello {nick},\n\nplease confirm your prediction game registration via this link:\n{link}",
    'tf_tipp_mail_welcome_betreff' => 'Welcome to the prediction game',
    'tf_tipp_mail_welcome_text'    => "Hello {nick},\n\nyour prediction game registration was successful.",
    'tf_tipp_mail_admin_betreff'   => 'New prediction game registration',
    'tf_tipp_mail_admin_text'      => "A new tipster has registered: {nick}",
    'tf_tipp_einsicht_titel'       => 'Tip Overview',
    'tf_tipp_zur_abgabe'           => 'To tip submission',
    'tf_tipp_rangliste_titel'      => 'Leaderboard',
    'tf_tipp_seiten_titel'         => 'Prediction Game',
    'tf_tipp_tab_abgabe'           => 'Submit Tips',
    'tf_tipp_tab_einsicht'         => 'View Tips',
    'tf_tipp_tab_rangliste'        => 'Leaderboard',
    'tf_tipp_header_link'          => '🎯 Prediction game',
    'tf_tipp_home_card_titel'      => 'Prediction game',
    'tf_tipp_home_card_text'       => 'Predict the results of your favourite league and compete with other players on the leaderboard!',
    'tf_tipp_home_card_button'     => 'Join now',
    'tf_tipp_rangliste_leer'       => 'No tips for the leaderboard yet.',
    'tf_tipp_col_platz'            => 'Rank',
    'tf_tipp_col_spiele_getippt'   => 'Games tipped',
    'tf_tipp_col_quote'            => 'Hit rate',
    'tf_tipp_col_spieltagssiege'   => 'Matchday wins',
    'tf_tipp_col_spieltagssiege_titel' => 'Number of matchdays won (highest score that matchday, shared wins possible)',
    'tf_tipp_col_re'               => 'ER',
    'tf_tipp_col_re_titel'         => 'Exact results (final score predicted exactly)',
    'tf_tipp_col_rtd'              => 'RGD',
    'tf_tipp_col_rtd_titel'        => 'Correct outcome with correct goal difference',
    'tf_tipp_col_rt'               => 'RO',
    'tf_tipp_col_rt_titel'         => 'Correct outcome (win/draw/loss)',
    'tf_tipp_col_jp'               => 'JB',
    'tf_tipp_col_jp_titel'         => 'Extra points gained through the joker',
    'tf_tipp_konto_link'           => 'Account',
    'tf_tipp_konto_gespeichert'    => 'Your details have been saved.',
    'tf_tipp_passwort_vergessen_link' => 'Forgot password?',
    'tf_tipp_reset_anfordern'      => 'Request link',
    'tf_tipp_reset_mail_verschickt' => 'If this email address is registered with us, a password reset link has been sent.',
    'tf_tipp_reset_ungueltig'      => 'This link is invalid or has expired. Please request a new one.',
    'tf_tipp_neues_passwort'       => 'New password',
    'tf_tipp_passwort_setzen'      => 'Set password',
    'tf_tipp_leer_lassen_hinweis'  => 'leave blank for no change',
    'tf_tipp_team'                 => 'Team',
    'tf_tipp_kein_team'            => 'no team',
    'tf_tipp_neues_team_gruenden'  => 'Create new team',
    'tf_tipp_neues_team_platzhalter' => 'Team name (leave blank to not create a new team)',
    'tf_tipp_newsletter_erhalten'  => 'Receive newsletter',
    'tf_tipp_reminder_erhalten'    => 'Receive tip reminders',
    'tf_tipp_abonnierte_ligen'     => 'Leagues I tip on (none checked = show all)',
    'tf_tipp_mail_reset_betreff'   => 'Reset your password',
    'tf_tipp_mail_reset_text'      => "Hello [nick],\n\nyou (or someone on your behalf) requested to reset your prediction game password. Click the following link to set a new password (valid for 1 hour):",
    'tf_tipp_einsicht_noch_nicht_sichtbar' => 'Tips for this game are not visible yet.',

];
