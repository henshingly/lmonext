<?php
/**
 * Project: LMOnext
 * Filename: lang/admin/en.php
 * Fileversion: 1.10.15
 * Changelog: 1.10.15 - Added translation for the new "ZIP extension" installer check
 * Changelog: 1.10.14
 * Changelog: 1.10.14 - Added translations for the new team-logo backup/restore inclusion
 * Changelog: 1.10.13
 * Changelog: 1.10.13 - Renamed the fixture-generation-mode lang key (user request: internal
 *                      naming now consistently uses the English term "League Key" instead of
 *                      the previous German term)
 * Changelog: 1.10.12 - Added translations for the new "Show language selector?" setting
 * Changelog: 1.10.11
 * Changelog: 1.10.11 - Added translations for the new "Show PDF export to visitors?" setting
 * Changelog: 1.10.10 - Added translations for the extended installer system checks (GD, SVG
 *                      rasterization, team logo directory, "optional" label)
 * Changelog: 1.10.9 - Added translation for the new "Show logo" setting
 * Changelog: 1.10.8 - Added translations for logo & club link on "Teams (global)"
 * Changelog: 1.10.7 - Renamed "Fixture key ring" to "Fixture plan" label (wizard, schedule
 *                     creation mode), matching the German rename to "Schlüsselplan"
 * Changelog: 1.10.6 - Added translations for the new schedule-creation-mode selector in the
 *                     league wizard (fixture key ring/random/no schedule)
 * Changelog: 1.10.5 - Added/updated translations for multi-candidate team matching during
 *                     import: dropdown instead of a yes/no checkbox when several similar
 *                     teams were found
 * Changelog: 1.10.4 - Added translations for the new color pickers on the table markers
 * Changelog: 1.10.3 - Added translation for the "Winners & losers from round..." hint text
 *                     (final round with final + 3rd-place match)
 * Changelog: 1.10.2 - Added missing translations for the "forgot password" feature (login-page
 *                     modal, reset landing page, flash messages, email text) – the backend/
 *                     reset landing page already referenced these keys, but they were entirely
 *                     missing from the language files
 * Changelog: 1.10.1 - Added translation for the new "Visitor view" link in the admin topbar
 * Changelog: 1.10.0 - Added translations for the new team name matching step during .l98 import
 *                     (review screen between upload and the actual import)
 * Changelog: 1.9.9 - Added translation for the table-prefix remap notice during restore
 *                     (backups are now portable between installs with a different prefix)
 * Changelog: 1.9.8 - Translations for the new "Maintenance" page added (database backup/
 *                     restore, backup options, table selection, error messages)
 * Changelog: 1.9.7 - Translations for direct team-ID entry in the league detail team editor
 *                     added (alternative to name search)
 * Changelog: 1.9.6 - Link "Go to visitor area" added on the login page
 * Changelog: 1.9.5 - Example text in prefix hint changed from "olv_" to "lmonext_"
 * Changelog: 1.9.4 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.9.3 - "Tabelle" label added (bugfix: missing checkbox in view_liga_settings.php)
 * Changelog: 1.9.2 - Translations for visitor area settings (active template, allow template switch) added
 * Changelog: 1.9.1 - tpl_bundesliga_detail: reworded, dropped awkward "single" from "single round-robin"
 * Changelog: 1.9.0 - Translations for settings (view_settings.php) added (previously missed)
 * Changelog: 1.8.0 - Translations for user management (view_users.php) added
 * Changelog: 1.7.0 - Translations for table (view_tabelle.php) added
 * Changelog: 1.6.1 - Flash message for handler_settings.php added (invalid league ID)
 * Changelog: 1.6.0 - Translations for league settings (view_liga_settings.php) added
 * Changelog: 1.5.0 - Translations for league details (view_liga_detail.php) added
 * Changelog: 1.4.1 - Flash message for handler_export.php added
 * Changelog: 1.4.0 - Translations for import (view_import.php + handler_import_export.php) added
 * Changelog: 1.3.1 - Flash messages for handler_liga.php added
 * Changelog: 1.3.0 - Translations for archive (view_archiv.php) added
 * Changelog: 1.2.1 - Flash messages for handler_wizard.php + createLigaInDB() success message added
 * Changelog: 1.2.0 - Translations for wizard (view_wizard.php) + league templates (templates.php) added
 * Changelog: 1.1.0 - Moved from lang/en.php to lang/admin/en.php (separation of admin/visitor areas)
 * Changelog: 1.0.4 - Flash messages for handler_ko.php (save round, create rounds) + sp_btn_table added
 * Changelog: 1.0.3 - Translations for matchday/KO editor + koModusLabel() added
 * Changelog: 1.0.2 - Translations for teams (global) + common terms (common_*) added
 * Changelog: 1.0.1 - Translations for dashboard/league list added
 * Changelog: 1.0.0 - Initial version
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Any key missing here automatically falls back to lang/admin/de.php.
 */
declare(strict_types = 1);

return [

    // ── General ──────────────────────────────────────────────────────────────
    'lang_switch_label' => 'Language',

    // ── Navigation (sidebar) ─────────────────────────────────────────────────
    'nav_dashboard'   => 'Leagues',
    'nav_create_liga' => 'Create league',
    'nav_import'      => 'Import (.l98)',
    'nav_archiv'      => 'Archive',
    'nav_teams'       => 'Teams (global)',
    'nav_users'       => 'Users',
    'nav_wartung'     => 'Maintenance',
    'nav_settings'    => 'Settings',

    // ── Page titles (topbar) ─────────────────────────────────────────────────
    'title_dashboard'     => 'Leagues overview',
    'title_create_liga'   => 'Create league – step {step}',
    'title_liga_detail'   => 'League details',
    'title_spieltag'      => 'Enter results',
    'title_tabelle'       => 'Table',
    'title_import'        => '.l98 import',
    'title_import_review' => 'Import – team matching',
    'title_archiv'        => 'Archive',
    'title_users'         => 'User management',
    'title_liga_settings' => 'League settings',
    'title_settings'      => 'Settings',
    'title_admin_default' => 'Admin',

    // ── Topbar / Logout ──────────────────────────────────────────────────────
    'topbar_logout' => 'Logout',
    'topbar_visitor_link' => 'Visitor view',

    // ── Login ────────────────────────────────────────────────────────────────
    'login_subtitle' => 'Sign in with your credentials.',
    'login_username' => 'Username',
    'login_password' => 'Password',
    'login_submit'   => 'Sign in',
    'login_visitor_link' => '← Go to visitor area',
    'login_forgot_link'          => 'Forgot password?',
    'reset_modal_title'          => 'Reset password',
    'reset_modal_intro'          => 'Enter the email address on your account. We\'ll send you a reset link, valid for 4 hours.',
    'reset_modal_label_email'    => 'Email address',
    'reset_modal_submit'         => 'Request link',
    'reset_modal_cancel'         => 'Cancel',
    'reset_pw_subtitle'          => 'Please enter your new password.',
    'reset_pw_label_new'         => 'New password',
    'reset_pw_label_new2'        => 'Repeat new password',
    'reset_pw_submit'            => 'Save password',
    'reset_pw_invalid'           => 'This link is invalid or has expired. Please request a new one via "Forgot password".',
    'reset_pw_back_to_login'     => '← Back to login',
    'flash_reset_email_not_found'=> 'No administrator account is registered with this email address.',
    'flash_reset_email_sent'     => 'An email with a reset link has been sent.',
    'flash_reset_token_invalid'  => 'This link is invalid or has expired. Please request a new one.',
    'flash_reset_password_success' => 'Password changed successfully. You can now log in.',
    'mail_reset_subject'         => '{site}: Reset your password',
    'mail_reset_body'            => "Hello,\n\nA new password was requested for your administrator account.\n\nUse this link to set a new password (valid for {hours} hours):\n{link}\n\nIf this wasn't you, you can safely ignore this email.",

    // ── Flash messages (user management / login) ────────────────────────────
    'flash_invalid_credentials'      => 'Invalid credentials.',
    'flash_db_error'                 => 'Database error: {msg}',
    'flash_password_mismatch'        => 'Passwords do not match.',
    'flash_password_min_length'      => 'At least 8 characters.',
    'flash_password_changed'         => 'Password changed.',
    'flash_current_password_wrong'   => 'Current password is incorrect.',
    'flash_error_prefix'             => 'Error: {msg}',
    'flash_user_password_required'   => 'Username and a password of at least 8 characters are required.',
    'flash_user_created'             => 'User "{user}" created.',
    'flash_invalid_input'            => 'Invalid input.',
    'flash_username_taken'           => 'Username already taken.',
    'flash_user_updated'             => 'User "{user}" updated.',
    'flash_own_account_undeletable'  => 'You cannot delete your own account.',
    'flash_user_deleted'             => 'User "{user}" deleted.',
    'flash_settings_saved'           => 'Settings saved.',

    // ── install.php ──────────────────────────────────────────────────────────
    'install_title'            => 'LMOnext – Installation',
    'install_header_subtitle'  => 'Installation wizard v{ver}',
    'install_step1'            => 'System check',
    'install_step2'            => 'Configuration & installation',
    'install_errors_heading'   => 'Errors:',

    'install_check_php_version'      => 'PHP version',
    'install_check_php_version_fail' => ' (at least {min} required)',
    'install_check_pdo'              => 'PDO extension',
    'install_check_pdo_mysql'        => 'PDO MySQL driver',
    'install_check_mbstring'         => 'mbstring',
    'install_check_gd'                => 'GD extension',
    'install_check_svg_raster'        => 'SVG rasterization (Imagick/rsvg-convert)',
    'install_check_zip'               => 'ZIP extension (team logo backup)',
    'install_check_teams_dir'         => 'Write permission (assets/img/teams/)',
    'install_recommended_missing'     => 'not found (optional)',
    'install_optional'                => 'optional',
    'install_available'              => 'available',
    'install_missing_ini'            => 'MISSING – enable in php.ini',
    'install_missing_pdo_mysql'      => 'MISSING – pdo_mysql extension missing',
    'install_missing'                => 'MISSING',
    'install_check_writable'         => 'Write permission ({dir}/)',
    'install_writable_ok'            => 'OK',
    'install_writable_fail'          => 'No write permission – set chmod 755',
    'install_check_adminphp'         => 'admin.php present',
    'install_adminphp_found'         => 'found',
    'install_adminphp_missing'       => 'NOT found – place admin.php in the same directory',

    'install_requirements_heading' => 'System requirements',
    'install_fix_issues'           => 'Please fix the marked issues before installing.',
    'install_selfdestruct_notice'  => '<code>install.php</code> will be <strong>deleted automatically</strong> after completion.',
    'install_continue'             => 'Continue to configuration →',
    'install_check_failed'         => 'System check failed',

    'install_db_heading'      => 'Database connection',
    'install_label_host'      => 'Host',
    'install_label_port'      => 'Port',
    'install_label_dbname'    => 'Database name',
    'install_hint_dbname'     => 'Created automatically if it does not exist. Letters, digits, underscores only.',
    'install_label_prefix'    => 'Table prefix',
    'install_hint_prefix'     => 'e.g. <code id="pfx_prev">lmonext_</code>liga, <code id="pfx_prev2">lmonext_</code>teams_global',
    'install_label_dbuser'    => 'Database user',
    'install_label_dbpass'    => 'Database password',

    'install_admin_heading'      => 'Administrator account',
    'install_label_username'     => 'Username',
    'install_label_password'     => 'Password',
    'install_placeholder_pass'   => 'at least 8 characters',
    'install_label_password2'    => 'Repeat password',
    'install_label_email'        => 'Email address',
    'install_hint_email'         => 'Optional. Only needed for "Forgot password".',

    'install_app_heading'      => 'Application',
    'install_label_sitetitle'  => 'Site title',
    'install_hint_sitetitle'   => 'Shown in the browser tab and in the sidebar.',

    'install_final_warning' => 'Clicking "Install now" will write <code>config.php</code> and <strong>permanently delete install.php</strong>.',
    'install_back'          => '← Back',
    'install_submit'        => '🚀 Install now',
    'install_footer'        => 'LMOnext Installer v{ver} · PHP {phpver}',

    'err_dbname_required'       => 'Database name is required.',
    'err_dbuser_required'       => 'Database user is required.',
    'err_adminuser_required'    => 'Admin username is required.',
    'err_adminpass_minlen'      => 'Admin password must be at least 8 characters.',
    'err_adminemail_invalid'    => 'The email address is invalid.',
    'err_adminpass_mismatch'    => 'Admin passwords do not match.',
    'err_requirements_not_met'  => 'System requirements not met.',
    'err_config_write_failed'   => 'config.php could not be written – check write permissions.',

    // ── Dashboard / league list (view_liga_list.php) ────────────────────────
    'dash_tooltip_all_ligen'       => 'All leagues incl. archive',
    'dash_stat_ligen_total'        => 'Leagues total →',
    'dash_stat_active_archived'    => '{active} active · {archived} archived',
    'dash_tooltip_teams'           => 'Manage teams',
    'dash_stat_teams'              => 'Teams (global) →',
    'dash_stat_partien'            => 'Matches total',
    'dash_heading_all_ligen'       => 'All leagues',
    'dash_btn_missing_results'     => '📋 Missing results',
    'dash_select_folder_placeholder' => '— Select folder —',
    'dash_btn_archive'             => '🗄️ Archive',
    'dash_btn_clear_selection'     => '✕ Clear selection',
    'dash_empty_pre'               => 'No leagues yet.',
    'dash_empty_link'              => 'Create league',
    'dash_empty_post'              => ' or import a .l98 file.',
    'dash_col_id'                  => 'ID',
    'dash_col_name'                => 'Name',
    'dash_col_typ'                 => 'Type',
    'dash_col_created'             => 'Created',
    'dash_col_actions'             => 'Actions',
    'dash_tooltip_select_all'      => 'Select all',
    'dash_type_ko'                 => 'KO tournament',
    'dash_type_liga'               => 'League',
    'dash_tooltip_missing_results' => '{n} missing results',
    'dash_open_badge'              => '{n} open',
    'dash_confirm_delete'          => 'Really delete league »{name}«?',
    'dash_btn_delete'              => 'Delete',
    'dash_js_selected_count'       => '{n} selected',
    'dash_js_choose_folder_alert'  => 'Please select an archive folder.',
    'dash_confirm_archive_one'     => 'Move 1 league to archive?',
    'dash_confirm_archive_many'    => 'Move {n} leagues to archive?',

    // ── Common/reusable terms ────────────────────────────────────────────────
    'common_cancel'      => 'Cancel',
    'common_save'        => '💾 Save',
    'common_loading'     => 'Loading…',
    'common_load_error'  => 'Error loading data.',

    // ── Teams (global) (view_teams.php) ─────────────────────────────────────
    'teams_filter_placeholder'   => '🔍  Filter by name, mid-name or abbreviation…',
    'teams_btn_show_dups'        => '⚠️ Show possible duplicates ({n})',
    'teams_count_total'          => '{n} teams total',
    'teams_dup_found_one'        => '<strong>1 possible duplicate found</strong>',
    'teams_dup_found_many'       => '<strong>{n} possible duplicates found</strong>',
    'teams_dup_detail'           => '– Teams with the same or similar names (incl. umlaut variants like ü/ue, ß/ss) are marked yellow. Use "Merge" to combine two teams into one.',
    'teams_col_id'               => 'ID',
    'teams_col_name'             => 'Name',
    'teams_col_mittel'           => 'Mid-name',
    'teams_col_kurz'             => 'Abbr.',
    'teams_col_ligen'            => 'Leagues',
    'teams_dup_hint_name'        => 'Similar name',
    'teams_dup_hint_mittel'      => 'Similar mid-name',
    'teams_merge_title'          => '⇌ Merge teams',
    'teams_merge_desc'           => 'All links (leagues, matches) will be transferred to the team you keep.',
    'teams_merge_keep_label'     => '✓ Keep',
    'teams_search_placeholder'   => 'Search team…',
    'teams_merge_delete_label'   => '🗑 Delete (will be merged)',
    'teams_merge_submit'         => '⇌ Merge',
    'teams_btn_merge_short'      => '⇌ Merge',
    'teams_confirm_delete_team'  => 'Delete team »{name}«?',
    'teams_field_name_required'  => 'Name *',
    'teams_field_mittel'         => 'Mid-name',
    'teams_field_kurz'           => 'Abbr.',
    'teams_field_url'            => 'Club/team website',
    'teams_field_logo'           => 'Logo',
    'teams_logo_remove'          => 'Remove logo',
    'teams_logo_hint'            => 'SVG, JPG, PNG or GIF, at least 50px tall.',
    'teams_col_logo'             => 'Logo',
    'teams_logo_err_upload'      => 'Upload failed. Please try again.',
    'teams_logo_err_format'      => 'Invalid file format. Allowed: SVG, JPG, PNG, GIF.',
    'teams_logo_err_invalid'     => 'The file could not be read as a valid image.',
    'teams_logo_err_too_small'   => 'The image is too small. Minimum height: {min}px.',
    'teams_ligen_modal_default'  => 'Leagues',
    'teams_js_ligen_title'       => 'Leagues: {name}',
    'teams_js_no_ligen'          => 'No leagues found.',
    'teams_js_count'             => '{n} teams',
    'teams_js_alert_pick_keep'      => 'Please select a team to keep.',
    'teams_js_alert_pick_delete'    => 'Please select a team to delete.',
    'teams_js_alert_pick_different' => 'Please select two different teams.',
    'teams_js_confirm_merge'        => 'Really merge these teams?',

    // ── Common terms (continued) ─────────────────────────────────────────────
    'common_yes' => 'Yes',
    'common_no'  => 'No',

    // ── Matchday / enter results (view_spieltag.php, handler_ko.php) ────────
    'sp_not_found'                   => 'Matchday not found.',
    'sp_label_round'                 => 'Round',
    'sp_label_matchday'              => 'Matchday',
    'sp_final_and_third'             => '🏆 Final + 🥉 Third-place match',
    'sp_mode_label'                  => 'Match mode for this round:',
    'sp_no_pairs_yet'                => 'No matches in this round yet. Click "＋ Add pairing".',
    'sp_final_label'                 => '🏆 Final',
    'sp_third_place_label'           => '🥉 Third-place match',
    'sp_pairing_label'               => 'Pairing {n}',
    'sp_tooltip_remove_pair'         => 'Remove pairing',
    'sp_only_winners_from'           => '🏆 Only winners from round {round} ({count} teams)',
    'sp_winners_and_losers_from'     => '🏆 Winners & losers from round {round} ({count} teams) – final and 3rd-place match',
    'sp_leg_home'                    => 'First leg',
    'sp_leg_away'                    => 'Second leg',
    'sp_game_n'                      => 'Game {n}',
    'sp_status_normal'               => '– normal –',
    'sp_status_ie'                   => 'On penalties',
    'sp_status_nv'                   => 'After extra time',
    'sp_placeholder_venue'           => '📍 Venue',
    'sp_placeholder_report_link'     => '🔗 Match report link (optional)',
    'sp_btn_add_pair'                => '＋ Add pairing',
    'sp_btn_save_round'              => '💾 Save round',
    'sp_next_round'                  => 'Next: round {n} →',
    'sp_independent_settings_heading'=> 'MATCHDAY-INDEPENDENT SETTINGS',
    'sp_ticker_show_label'           => 'Show ticker?',
    'sp_ticker_text_label'           => 'Ticker text:',
    'sp_ticker_placeholder'          => 'Enter ticker text…',
    'sp_btn_save_ticker'             => '💾 Save ticker',
    'sp_col_heim'                    => 'Home',
    'sp_col_tore'                    => 'Goals',
    'sp_col_gast'                    => 'Away',
    'sp_col_status'                  => 'Status',
    'sp_col_anstoss'                 => 'Kick-off',
    'sp_status_dash'                 => '–',
    'sp_status_ie_short'             => 'pens.',
    'sp_status_nv_short'             => 'a.e.t.',
    'sp_btn_save_all'                => '💾 Save all',
    'sp_next_matchday'               => 'Next: matchday {n} →',
    'sp_heading_all_rounds'          => 'All rounds',
    'sp_heading_all_matchdays'       => 'All matchdays',
    'sp_btn_placeholder_short'       => '✏️ Placeholder',
    'sp_btn_team_short'              => '👤 Team',
    'sp_placeholder_example_winner'  => 'e.g. Winner R1P1',
    'sp_option_placeholder'          => '– Placeholder –',
    'sp_placeholder_word'            => 'Placeholder',
    'sp_tooltip_toggle_team_placeholder' => 'Toggle: real team ↔ placeholder',
    'sp_btn_table'                   => '📊 Table',

    // ── KO round handler flash messages (handler_ko.php) ────────────────────
    'ko_flash_invalid_round_id'   => 'Invalid round ID.',
    'ko_flash_round_saved'        => 'Round {nr} saved ({pairs} pairing(s), {matches} match(es)).',
    'ko_flash_rounds_added'       => '{n} missing round(s) created.',
    'ko_flash_rounds_all_exist'   => 'All rounds already exist.',

    // ── KO match modes (bootstrap.php: koModusLabel()) ──────────────────────
    'ko_mode_1' => 'Single match',
    'ko_mode_2' => 'Home & away',
    'ko_mode_3' => 'Best of 3',
    'ko_mode_5' => 'Best of 5',
    'ko_mode_7' => 'Best of 7',

    // ── Common terms (continued 2) ───────────────────────────────────────────
    'common_back'      => '← Back',
    'common_next'      => 'Next →',
    'common_save_liga' => '✓ Save league',

    // ── League templates (templates.php) ─────────────────────────────────────
    'tpl_cl_ligaphase_label'  => 'UEFA Champions League – League Phase',
    'tpl_cl_ligaphase_desc'   => '36 teams · 8 matchdays · 18 matches/round · league format',
    'tpl_cl_ligaphase_detail' => 'Each team plays 8 matches against 8 different opponents (from 4 pools of 9 teams, 1 home + 1 away per pool). Places 1–8 → straight to round of 16; places 9–24 → play-offs; places 25–36 → eliminated.',
    'tpl_cl_ko_label'         => 'UEFA Champions League – Knockout Phase',
    'tpl_cl_ko_desc'          => '24 teams · 5 rounds · Play-offs to final',
    'tpl_cl_ko_detail'        => 'Play-offs (places 9–24): 8 home & away pairings. Round of 16: 16 teams home & away. Quarter-finals + semi-finals: home & away. Final: single match.',
    'tpl_bundesliga_label'    => 'Bundesliga (18 teams)',
    'tpl_bundesliga_desc'     => '18 teams · 34 matchdays · home & away',
    'tpl_bundesliga_detail'   => 'Classic round-robin league with home and away matches. 3 points for a win, 1 for a draw.',

    // ── Wizard: create league (view_wizard.php) ──────────────────────────────
    'wiz_step_label_1' => '1 Basics',
    'wiz_step_label_2' => '2 Teams',
    'wiz_step_label_3' => '3 Schedule',
    'wiz_step_label_4' => '4 Save',

    'wiz_quickstart_heading'  => 'Quick start with a template',
    'wiz_quickstart_desc'     => 'Choose a preconfigured template – teams and settings are already filled in and can be adjusted.',
    'wiz_step1_heading'       => 'Step 1 – League basics (manual)',
    'wiz_label_liga_name'     => 'League name *',
    'wiz_placeholder_liga_name_example'  => 'e.g. Premier League 2025/26',
    'wiz_placeholder_liga_name_example2' => 'e.g. Champions League 2025/26',
    'wiz_label_liga_type'     => 'League type *',
    'wiz_option_ko_tournament'=> 'Knockout tournament / Cup',
    'wiz_label_team_count'    => 'Number of teams *',
    'wiz_option_n_teams'      => '{n} teams',
    'wiz_option_uefa_teams'   => '24 teams (UEFA mode)',
    'wiz_auto_rounds_label'   => 'Automatically calculated round structure:',

    'wiz_round_finale'          => 'Final',
    'wiz_round_halbfinale'      => 'Semi-final',
    'wiz_round_viertelfinale'   => 'Quarter-final',
    'wiz_round_achtelfinale'    => 'Round of 16',
    'wiz_round_sechzehntelfinale' => 'Round of 32',
    'wiz_round_32tel'           => 'Round of 64',
    'wiz_round_64tel'           => 'Round of 128',
    'wiz_round_zwischenrunde'   => 'Play-off round',
    'wiz_round_n'               => 'Round {n}',
    'wiz_pairing_word_one'      => 'pairing',
    'wiz_pairing_word_many'     => 'pairings',
    'wiz_dummy_teams_label'     => '· dummy teams',

    'wiz_step2_heading' => 'Step 2 – Enter teams ({n} teams)',
    'wiz_step2_desc'     => 'Name is required. Mid-name and abbreviation are optional.',
    'wiz_col_teamname'   => 'Team name *',
    'wiz_placeholder_kurzname' => 'Short name',
    'wiz_placeholder_kurzel'   => 'Abbr.',
    'wiz_next_edit_teams'      => 'Next: edit teams →',
    'wiz_back_other_template'  => '← Other template',

    'wiz_template_heading_prefix' => 'Template: ',
    'wiz_template_fallback'       => 'Template',

    'wiz_step3_liga_heading' => 'Step 3 – Schedule (auto-generated)',
    'wiz_schedule_mode_heading'        => 'Schedule creation',
    'wiz_schedule_mode_leaguekey' => 'Fixture plan (DFB pattern)',
    'wiz_schedule_mode_unavailable'    => 'not available for this number of teams',
    'wiz_schedule_mode_random'         => 'Generate randomly',
    'wiz_schedule_mode_none'          => 'No schedule',
    'wiz_schedule_mode_apply'         => 'Apply',
    'wiz_step3_liga_desc'    => 'Round-robin: {days} matchdays, {matches} matches per matchday.',
    'wiz_col_hash'           => '#',
    'wiz_matchday_first_half'  => ' (first half of season)',
    'wiz_matchday_second_half' => ' (second half of season)',
    'wiz_back_edit_teams'      => '← Edit teams',

    'wiz_step3_ko_heading' => 'Step 3 – KO round structure &amp; match modes',
    'wiz_uefa24_note'      => '🏆 <strong>UEFA 24-team mode:</strong> Play-off round (8 teams from places 9–24) + 4 KO rounds with home & away legs, final as a single match.',
    'wiz_step3_ko_desc'    => 'Choose the match mode for each round. Pairings are created automatically with dummy teams and can be edited afterwards in the matchday view.',
    'wiz_label_spielmodus' => 'Match mode:',
    'wiz_dummy_info'       => 'ℹ️ Round 1 is filled pairwise with the entered teams (team 1 vs 2, 3 vs 4, …). All further rounds get dummy pairings (<code>___</code>) which are replaced once the winners are determined.',

    'wiz_session_expired' => 'Session expired.',
    'wiz_restart'         => 'Start over',

    // ── Wizard handler flash messages (handler_wizard.php) ──────────────────
    'wiz_flash_unknown_template'   => 'Unknown template.',
    'wiz_flash_name_required'      => 'League name required.',
    'wiz_flash_team_name_missing'  => 'Team {n}: name missing.',
    'liga_flash_created'           => 'League "{name}" created (ID {id}, {teams} teams, {matchdays} matchdays).',

    // ── Archive (view_archiv.php) ────────────────────────────────────────────
    'arch_count_ligen_one'      => '{n} league',
    'arch_count_ligen_many'     => '{n} leagues',
    'arch_count_subfolders'     => ', {n} subfolders',
    'arch_confirm_delete_folder'=> 'Delete folder?',
    'arch_btn_reactivate'       => '↩ Reactivate',
    'arch_btn_new_folder'       => '📁 New folder',
    'arch_btn_missing_results_one'  => '⚠️ 1 league with missing results',
    'arch_btn_missing_results_many' => '⚠️ {n} leagues with missing results',
    'arch_summary_line'         => '{folders} folders · {ligen} archived leagues',
    'arch_empty_line1'          => 'No archive folders yet.',
    'arch_empty_line2'          => 'Click <strong>📁 New folder</strong> to create one.',
    'arch_empty_line3'          => 'Leagues can then be moved into a folder via <strong>⚙️ Settings → Archive</strong>.',
    'arch_no_folder_label'      => '📋 No folder',
    'arch_label_description'   => 'Description',
    'arch_label_parent_folder' => 'Parent folder',
    'arch_option_top_level'    => '— Top level —',
    'arch_label_sort'          => 'Sort order',
    'arch_modal_title_default' => '📁 Folder',
    'arch_modal_title_edit'    => '✏️ Edit folder',
    'arch_btn_clear_filter'    => '✕ Clear filter',

    // ── League handler flash messages (handler_liga.php) ────────────────────
    'hl_flash_spieltag_saved'          => 'Matchday {n} saved.',
    'hl_flash_team_id_or_name_missing' => 'Team ID or name missing.',
    'hl_flash_team_taken_from_db'      => 'Team adopted from database.',
    'hl_flash_team_updated'            => 'Team updated.',
    'hl_flash_spieltag_datum_saved'    => 'Matchday date saved.',
    'hl_flash_liga_deleted'            => 'League deleted.',
    'hl_flash_team_deleted'            => 'Team deleted.',
    'hl_flash_team_delete_blocked'     => 'Team is still assigned to a league and cannot be deleted.',
    'hl_flash_teams_merged'            => 'Teams merged.',
    'hl_flash_name_required'           => 'Name required.',
    'hl_flash_folder_updated'          => 'Folder updated.',
    'hl_flash_folder_created'          => 'Folder created.',
    'hl_flash_folder_deleted'          => 'Folder deleted.',
    'hl_flash_liga_archived'           => 'League moved to archive.',
    'hl_flash_liga_unarchived'         => 'League restored from archive.',
    'hl_flash_no_ligen_selected'       => 'No leagues selected.',
    'hl_flash_bulk_archived_one'       => '1 league moved to archive.',
    'hl_flash_bulk_archived_many'      => '{n} leagues moved to archive.',

    // ── Import (view_import.php) ─────────────────────────────────────────────
    'imp_tab_multi'          => '📂 Multiple .l98',
    'imp_tab_zip'            => '🗜️ ZIP archive',
    'imp_multi_heading'      => 'Import .l98 files',
    'imp_multi_desc'         => 'Upload one or more LMO league files (.l98) and import them into the database.',
    'imp_multi_limit_warning'=> '⚠️ Your server allows a maximum of <strong>{n} files</strong> per upload. For more files at once, please use the <strong>ZIP import</strong>.',
    'imp_label_ligadateien'  => 'League files (.l98)',
    'imp_btn_import'         => '📥 Import',
    'imp_js_file_one'        => '1 file selected',
    'imp_js_file_many'       => '{n} files selected',
    'imp_js_limit_warning'   => '⚠️ Limit: {n}',
    'imp_zip_heading'        => 'Import ZIP archive',
    'imp_zip_desc'           => 'Pack any number of .l98 files into a ZIP file and upload it.<br>All .l98 files in the ZIP (including subfolders) will be imported.',
    'imp_label_zipfile'      => 'ZIP file',
    'imp_btn_import_zip'     => '📥 Import ZIP',
    'imp_details_heading'    => 'Import details ({n} files)',
    'imp_active_ligen_heading' => 'Active leagues ({n})',

    // ── Import handler flash messages (handler_import_export.php) ───────────
    'imp_liga_imported'        => 'League "{name}" imported (ID {id}, {teams} teams, {rounds} rounds/matchdays).',
    'imp_error_prefix'         => 'Import error: {msg}',
    'imp_zip_extension_missing'=> 'ZIP extension not available on this server.',
    'imp_no_zip_uploaded'      => 'No ZIP uploaded or an error occurred.',
    'imp_zip_open_failed'      => 'ZIP file could not be opened.',
    'imp_zip_read_error'       => 'Read error in ZIP.',
    'imp_no_l98_in_zip'        => 'No .l98 files found in the ZIP.',
    'imp_summary'              => '{ok} of {total} files imported successfully.',
    'imp_upload_error'         => 'Upload error.',
    'imp_only_l98_supported'   => 'Only .l98 files are supported.',
    'imp_file_unreadable'      => 'File not readable.',
    'imp_no_files_uploaded'    => 'No files uploaded.',
    'exp_flash_liga_not_found' => 'League not found.',

    // ── League details (view_liga_detail.php) ────────────────────────────────
    'ld_back_link'          => '← Back to overview',
    'ld_id_created'         => 'ID {id} · Created: {datum}',
    'ld_btn_settings'       => '⚙️ Settings',
    'ld_btn_enter_results'  => '📝 Enter results',
    'ld_btn_export'         => '💾 Export as .l98',
    'ld_btn_archive_dd'     => '🗄️ Archive ▾',
    'ld_no_folders'         => 'No folders —',
    'ld_create_in_archive'  => 'create one in the archive',
    'ld_btn_fix_rounds'     => '⚠️ Create {n} missing round(s)',
    'ld_heading_teams'      => 'Teams ({n})',
    'ld_label_db_search'    => '🔍 Adopt from teams_global',
    'ld_placeholder_name_search' => 'Search name…',
    'ld_label_id_lookup'    => '🔢 …or enter team ID directly',
    'ld_placeholder_id_lookup' => 'Team ID',
    'ld_btn_id_apply'       => 'Apply',
    'ld_js_id_not_found'    => 'No team found with this ID.',
    'ld_label_mittel_short' => 'Mid-name',
    'ld_tooltip_edit_date'  => 'Edit date',
    'ld_btn_paarungen'      => '✏️ Pairings',
    'ld_label_startdatum'   => 'Start date',
    'ld_heading_spieltage'  => 'Matchdays ({n})',
    'ld_col_start'          => 'Start',
    'ld_col_partien'        => 'Matches',
    'ld_col_gespielt'       => 'Played',
    'ld_js_no_match'        => 'No match – a new team will be created.',
    'ls_flash_invalid_id'   => 'Invalid league ID.',

    // ── League settings (view_liga_settings.php) ─────────────────────────────
    'ls_tab_grundwerte'    => 'Basics',
    'ls_tab_anzeige'       => 'Display',
    'ls_tab_spielsystem'   => 'Game system',
    'ls_tab_tabelle'       => 'Table',
    'ls_tab_spieltage'     => 'Matchday & match count',

    'ls_label_liga_name'   => 'League name',
    'ls_label_alt_pkt'     => 'Alternative for points',
    'ls_label_alt_tore'    => 'Alternative for goals',
    'ls_label_dec_pkt'     => 'Decimal places (points)',
    'ls_label_dec_tore'    => 'Decimal places (goals)',
    'ls_opt_none'          => 'None',
    'ls_opt_one'           => 'One',
    'ls_opt_two'           => 'Two',
    'ls_label_fav_team'    => 'Favorite team',
    'ls_opt_none_dash'     => '— none —',
    'ls_opt_none_dash_m'   => '— none —',
    'ls_label_spielplan'   => 'Schedule',

    'ls_label_date_sort'      => 'Date sorting',
    'ls_label_third_place'    => 'Third-place match',
    'ls_label_playdown'       => 'Enable playdown',
    'ls_label_anstoss_termin' => 'Kick-off time',
    'ls_label_anstoss_format' => 'Kick-off time format',
    'ls_label_php_dateformat' => 'PHP date format',
    'ls_label_spieltagsdatum' => 'Matchday date',
    'ls_label_ergebnisse'     => 'Results',
    'ls_label_tabelle'        => 'Table',
    'ls_label_show_logos'     => 'Show logo',
    'ls_label_kalender'       => 'Calendar',
    'ls_cb_spielplaene'       => 'Schedules',
    'ls_label_kreuztabelle'   => 'Cross table',
    'ls_cb_fieberkurven'      => 'Form curves',
    'ls_label_spielerstatistik' => 'Player statistics',
    'ls_cb_ligastatistik'     => 'League statistics',
    'ls_heading_ticker'       => 'Ticker',
    'ls_label_ticker_show'    => 'Show ticker?',
    'ls_placeholder_tickertext' => 'Free text shown above the league…',
    'ls_heading_verlinkungen' => 'Links',
    'ls_cb_team_homepage'     => 'Link team homepages',
    'ls_cb_spielberichte'     => 'Link match reports',
    'ls_heading_playoff_mode' => 'Playoff mode – home game setting',
    'ls_label_modusauswahl'   => 'Mode selection',
    'ls_opt_mod_111'          => 'Mode: 1-1-1-...',
    'ls_opt_mod_221'          => 'Mode: 2-2-1',
    'ls_opt_mod_22111'        => 'Mode: 2-2-1-1-1',
    'ls_opt_mod_232'          => 'Mode: 2-3-2',

    'ls_cb_minuspunkte'    => 'Penalty points',
    'ls_cb_spielende_offen'=> 'Open-ended match',
    'ls_cb_hide_draw'      => "Hide 'draws' table column",
    'ls_cb_direct_compare' => 'Head-to-head comparison',
    'ls_cb_spez'           => 'Goals scored count before goal difference',
    'ls_cb_hand_sort'      => 'Enable manual table sorting',
    'ls_heading_punktesystem' => 'Points system',
    'ls_col_win'           => 'W',
    'ls_col_draw'          => 'D',
    'ls_col_loss'          => 'L',
    'ls_row_normal_end'    => 'after regular time',
    'ls_row_extra_time'    => 'after extra time',
    'ls_row_penalty'       => 'after penalty shootout',
    'ls_na'                => 'n/a',

    'ls_cb_hin_rueck_tables' => 'First/second half tables',
    'ls_cb_heim_ausw_tables' => 'Home/away tables',
    'ls_heading_table_markers' => 'Table markers',
    'ls_marker_champ' => 'Champion is decided on the pitch',
    'ls_marker_cl'    => 'Champions League participants / promotion',
    'ls_marker_ck'    => 'Champions League qualifiers',
    'ls_marker_uc'    => 'Europa League participants',
    'ls_marker_ar'    => 'Relegation play-off',
    'ls_marker_ab'    => 'Confirmed relegation',
    'ls_marker_color_title' => 'Marker border color',
    'ls_marker_color_hint'  => 'This color appears as a thin colored border on the left edge of the table in the visitor view.',

    'ls_warning_heading' => 'Warning!',
    'ls_warning_text'    => 'These settings can make an existing league unusable. Only change them if you are sure.',
    'ls_label_anzahl_spieltage' => 'Number of matchdays',
    'ls_label_anzahl_spiele_pro_spieltag' => 'Number of matches per matchday',

    'ls_btn_save' => '💾 Save changes',

    // ── Table (view_tabelle.php) ──────────────────────────────────────────────
    'tab_btn_results'    => '📝 Results',
    'tab_btn_export'     => '💾 Export .l98',
    'tab_heading'        => '{name} – Table',
    'tab_scoring_line'   => 'Scoring: win {win} pts · draw {draw} pts · Sort order: points → goal difference → goals',
    'tab_col_sp'         => 'MP',
    'tab_tooltip_sp'     => 'Matches played',
    'tab_col_s'          => 'W',
    'tab_tooltip_s'      => 'Wins',
    'tab_col_u'          => 'D',
    'tab_tooltip_u'      => 'Draws',
    'tab_col_n'          => 'L',
    'tab_tooltip_n'      => 'Losses',
    'tab_col_diff'       => 'GD',
    'tab_tooltip_diff'   => 'Goal difference',
    'tab_col_pkt'        => 'Pts',
    'tab_empty'          => 'No results entered yet.',

    // ── User management (view_users.php) ─────────────────────────────────────
    'usr_heading_create'        => 'Create new user',
    'usr_label_password_min8'   => 'Password (min. 8 characters)',
    'usr_btn_create'            => 'Create user',
    'usr_heading_existing'      => 'Existing users',
    'usr_empty'                 => 'No users found.',
    'usr_col_last_login'        => 'Last login',
    'usr_chip_me'               => 'Me',
    'usr_btn_edit'              => '✏️ Edit',
    'usr_confirm_delete'        => 'Delete user »{name}«?',
    'usr_label_new_password'    => 'New password',
    'usr_hint_empty_unchanged'  => '(empty = unchanged)',
    'usr_btn_save'              => 'Save',

    // ── Settings (view_settings.php) ──────────────────────────────────────────
    'settings_heading_system'      => 'System settings',
    'settings_label_language'      => 'Default language',
    'settings_hint_language'       => 'Applies as the default language for new visitors/sessions of this admin area.',
    'settings_label_timezone'      => 'Timezone',
    'settings_current_time_line'   => 'Current time in this zone: {time}',
    'settings_hint_timezone'       => 'Applies to the entire application: date displays, .l98 import and export.',
    'settings_heading_password'    => 'Change password',
    'settings_label_current_password' => 'Current password',
    'settings_label_new_password2' => 'Repeat new password',
    'settings_heading_db'          => 'Database connection',
    'settings_db_connected'        => 'Connected · {version}',
    'settings_hint_db_config'      => 'Adjust connection parameters in <code>config.php</code>.',
    'settings_heading_frontend'         => 'Visitor area',
    'settings_label_active_template'    => 'Active template',
    'settings_hint_active_template'     => 'Design visitors see on the home page and other pages.',
    'settings_label_allow_template_switch' => 'Allow visitors to switch templates?',
    'settings_hint_allow_template_switch'  => 'If yes, visitors can pick a different template than the one active here via a dropdown (only for their own session).',
    'settings_label_show_pdf_buttons'      => 'Show PDF export to visitors?',
    'settings_hint_show_pdf_buttons'       => 'If no, the PDF button is hidden entirely from visitors in results, standings, schedules, and the head-to-head comparison – for both KO and regular leagues, on every page.',
    'settings_label_show_language_switcher' => 'Show language selector?',
    'settings_hint_show_language_switcher'  => 'If no, the language selector is hidden from visitors on every page of the frontend.',

    // ── Maintenance (database backup/restore) ─────────────────────────────────
    'wartung_tab_backup'             => 'Backup',
    'wartung_tab_restore'            => 'Restore',
    'wartung_backup_intro'           => 'Here you can back up all database tables. The archive is saved in the store/ folder.',
    'wartung_hint_logos_included'    => 'Uploaded team logos (Teams (global) → logo upload) are automatically backed up as a separate ZIP in the same location.',
    'wartung_hint_logos_unavailable' => 'The ZIP extension is not available on this server – team logos will not be included in this backup (the database backup itself is unaffected).',
    'wartung_hint_includes_logos'    => 'This backup also includes team logos',
    'wartung_heading_backup_options' => 'Backup options',
    'wartung_label_backup_type'      => 'Backup type:',
    'wartung_backup_type_complete'   => 'Complete',
    'wartung_backup_type_data'       => 'Data only',
    'wartung_label_file_type'        => 'File type:',
    'wartung_format_unavailable'     => 'unavailable',
    'wartung_label_table_selection'  => 'Table selection:',
    'wartung_select_all'             => 'Select all',
    'wartung_select_none'            => 'Deselect all',
    'wartung_btn_submit_backup'      => 'Create backup',
    'wartung_heading_backup_settings'=> 'Backup management',
    'wartung_label_max_count'        => 'Maximum number of backups:',
    'wartung_btn_save_settings'      => 'Save',
    'wartung_hint_max_count'         => 'If this number is exceeded, the oldest backup is deleted automatically. 0 = unlimited.',
    'wartung_restore_intro'          => 'This performs a full restore from a saved backup. WARNING: this overwrites existing data.',
    'wartung_restore_empty'          => 'No backups exist yet.',
    'wartung_label_choose_backup'    => 'Choose a backup:',
    'wartung_btn_restore'            => 'Start restore',
    'wartung_btn_delete'             => 'Delete backup',
    'wartung_confirm_restore'        => 'Really restore? Existing data will be overwritten.',
    'wartung_confirm_delete'         => 'Really delete this backup?',
    'wartung_flash_backup_created'   => 'Backup created: {file}',
    'wartung_flash_logos_included'   => 'Team logos were backed up too.',
    'wartung_flash_logos_restored'   => '{n} team logo(s) restored.',
    'wartung_flash_logos_restore_failed' => 'Team logos could not be restored: {msg}',
    'wartung_error_zip_missing'      => 'The ZIP extension is not available on this server, so team logos cannot be restored.',
    'wartung_flash_restored'         => 'Restore complete ({n} statements executed).',
    'wartung_flash_prefix_remapped'  => 'Note: this backup was created with table prefix "{from}" and was automatically remapped to the currently configured prefix "{to}".',
    'wartung_flash_deleted'          => 'Backup deleted.',
    'wartung_flash_settings_saved'   => 'Settings saved.',
    'wartung_error_no_tables'        => 'No tables found.',
    'wartung_error_compress'         => 'Error compressing the backup.',
    'wartung_error_write'            => 'Backup could not be written ({path}). Please check write permissions.',
    'wartung_error_invalid_file'     => 'Invalid file name.',
    'wartung_error_file_missing'     => 'Backup file not found.',
    'wartung_error_decompress'       => 'Error decompressing the backup.',
    'wartung_error_generic'          => 'An error occurred.',

    // ── Import: team name matching (approximate matches before the actual import) ─
    'imp_review_heading'      => 'Team matching ({n})',
    'imp_review_intro'        => 'The following teams from the uploaded .l98 files resemble teams already in the database, but do not match exactly. Choose which ones should adopt the database name – this avoids duplicates. Unchecked teams will be created as new teams using their name from the .l98 file.',
    'imp_review_item'         => '{import} → resembles existing team {db} (ID {id}).',
    'imp_review_item_multi'   => '{import} → resembles {n} existing teams.',
    'imp_review_select_label' => 'Adopt team from the DB:',
    'imp_review_option_new'   => '– No matching team – create as new team –',
    'imp_review_db_details'   => 'Short: {kurz} · Medium: {mittel}',
    'imp_review_btn_confirm'  => 'Continue import',
    'imp_review_btn_cancel'   => 'Cancel',
    'imp_review_expired'      => 'The matching data is no longer available. Please upload the file(s) again.',

];
