<?php
/**
 * Project: LMOnext
 * Filename: lang/admin/de.php
 * Fileversion: 1.13.4
 * Changelog: 1.13.4 - Übersetzung für den neuen Team-Spalten-Dropdown-Hinweis im
 *                     Spielerstatistik-Addon ergänzt
 * Changelog: 1.13.3
 * Changelog: 1.13.3 - Übersetzung für die neue Einstellung "Spielfrei anzeigen" ergänzt
 * Changelog: 1.13.2
 * Changelog: 1.13.2 - Übersetzungen für die neuen Installer-Prüfungen (store/-Schreibrecht,
 *                     bzip2) und die verständlicheren DB-Verbindungsfehlermeldungen ergänzt
 * Changelog: 1.13.1
 * Changelog: 1.13.1 - Neue Meldung für den blockierten Spielerstatistik-Import ergänzt
 * Changelog: 1.13.0
 * Changelog: 1.13.0 - Übersetzungen für Foto-Upload und Spaltenüberschriften-Grafiken ergänzt
 * Changelog: 1.12.0 - Übersetzungen für das neue Spielerstatistik-Addon ergänzt (Verwaltung,
 *                     Import alter .stat/.cfg-Dateien, Team-Abgleich)
 * Changelog: 1.11.6 - Übersetzung für die neue Installer-Systemprüfung "ZIP-Erweiterung" ergänzt
 * Changelog: 1.11.5
 * Changelog: 1.11.5 - Übersetzungen für die neue Team-Logo-Mitsicherung bei Backup/
 *                     Wiederherstellung ergänzt
 * Changelog: 1.11.4
 * Changelog: 1.11.4 - Lang-Schlüssel für die Spielplan-Erstellungsart umbenannt (Nutzerwunsch:
 *                     interne Bezeichnungen jetzt durchgehend auf Englisch, "League Key" statt
 *                     der vorherigen deutschen Bezeichnung)
 * Changelog: 1.11.3 - Übersetzungen für die neue Einstellung "Sprachauswahl anzeigen?" ergänzt
 * Changelog: 1.11.2
 * Changelog: 1.11.2 - Übersetzungen für die neue Einstellung "PDF-Export für Besucher
 *                     anzeigen?" ergänzt
 * Changelog: 1.11.1 - Übersetzungen für die erweiterten Installer-Systemprüfungen ergänzt
 *                     (GD, SVG-Rasterisierung, Team-Logo-Ordner, "optional"-Kennzeichnung)
 * Changelog: 1.11.0 - Übersetzung für die neue Einstellung "Logo anzeigen" ergänzt
 * Changelog: 1.10.9 - Übersetzungen für Logo & Vereinslink bei "Teams (global)" ergänzt
 * Changelog: 1.10.8 - Zusatz "Berger-Tabelle: " aus der Spielplan-Beschreibung entfernt (das ist
 *                     ein Schach-Fachbegriff, gehört hier nicht rein)
 * Changelog: 1.10.7 - Bezeichnung "League Key" (intern) zu "Schlüsselplan" (UI-Text) geändert
 *                     (Wizard, Spielplan-Erstellungsart)
 * Changelog: 1.10.6 - Übersetzungen für die neue Spielplan-Erstellungsart-Auswahl im
 *                     Liga-Wizard ergänzt (League Key/Zufall/kein Spielplan)
 * Changelog: 1.10.5 - Übersetzungen für die Mehrfachauswahl beim Team-Abgleich (Import) ergänzt/
 *                     angepasst: Dropdown statt Ja/Nein-Checkbox, wenn mehrere ähnliche Teams
 *                     gefunden wurden
 * Changelog: 1.10.4 - Übersetzungen für die neuen Farbwähler bei den Tabellenmarkierungen ergänzt
 * Changelog: 1.10.3 - Übersetzung für den Hinweistext "Sieger & Verlierer aus Runde..." ergänzt
 *                     (letzte Runde bei Finale + Spiel um Platz 3)
 * Changelog: 1.10.2 - Fehlende Übersetzungen für die "Passwort vergessen"-Funktion ergänzt
 *                     (Modal auf der Login-Seite, Reset-Landingpage, Flash-Meldungen,
 *                     E-Mail-Text) – Backend/Reset-Landingpage referenzierten diese Schlüssel
 *                     bereits, sie fehlten aber komplett in den Sprachdateien
 * Changelog: 1.10.1 - Übersetzung für den neuen "Benutzeransicht"-Link in der Admin-Topbar ergänzt
 * Changelog: 1.10.0 - Übersetzungen für den neuen Team-Namensabgleich beim .l98-Import ergänzt
 *                     (Abgleichsseite zwischen Upload und tatsächlichem Import)
 * Changelog: 1.9.9 - Übersetzung für den Hinweis bei Tabellenprefix-Umschreibung während der
 *                     Wiederherstellung ergänzt (Backups jetzt zwischen Installationen mit
 *                     unterschiedlichem Prefix portabel)
 * Changelog: 1.9.8 - Übersetzungen für die neue "Wartung"-Seite ergänzt (Datenbank-Backup/
 *                     Wiederherstellung, Backup-Optionen, Tabellen-Auswahl, Fehlermeldungen)
 * Changelog: 1.9.7 - Übersetzungen für die direkte Team-ID-Eingabe im Liga-Detail-Team-Editor
 *                     ergänzt (Alternative zur Namenssuche)
 * Changelog: 1.9.6 - Link "Zum Besucherbereich" auf der Login-Seite ergänzt
 * Changelog: 1.9.5 - Beispieltext im Präfix-Hinweis von "olv_" auf "lmonext_" umgestellt
 * Changelog: 1.9.4 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.9.3 - "Tabelle"-Label ergänzt (Bugfix: fehlende Checkbox in view_liga_settings.php)
 * Changelog: 1.9.2 - Übersetzungen für Besucherbereich-Einstellungen (aktives Template, Template-Wechsel erlauben) ergänzt
 * Changelog: 1.9.1 - tpl_bundesliga_detail: "eingleisig" entfernt (unnötiger Fachbegriff)
 * Changelog: 1.9.0 - Übersetzungen für Einstellungen (view_settings.php) ergänzt (war bisher übersehen)
 * Changelog: 1.8.0 - Übersetzungen für Benutzerverwaltung (view_users.php) ergänzt
 * Changelog: 1.7.0 - Übersetzungen für Tabelle (view_tabelle.php) ergänzt
 * Changelog: 1.6.1 - Flash-Meldung für handler_settings.php ergänzt (ungültige Liga-ID)
 * Changelog: 1.6.0 - Übersetzungen für Liga-Einstellungen (view_liga_settings.php) ergänzt
 * Changelog: 1.5.0 - Übersetzungen für Liga-Details (view_liga_detail.php) ergänzt
 * Changelog: 1.4.1 - Flash-Meldung für handler_export.php ergänzt
 * Changelog: 1.4.0 - Übersetzungen für Import (view_import.php + handler_import_export.php) ergänzt
 * Changelog: 1.3.1 - Flash-Meldungen für handler_liga.php ergänzt
 * Changelog: 1.3.0 - Übersetzungen für Archiv (view_archiv.php) ergänzt
 * Changelog: 1.2.1 - Flash-Meldungen für handler_wizard.php + createLigaInDB()-Erfolgsmeldung ergänzt
 * Changelog: 1.2.0 - Übersetzungen für Wizard (view_wizard.php) + Liga-Vorlagen (templates.php) ergänzt
 * Changelog: 1.1.0 - Verschoben von lang/de.php nach lang/admin/de.php (Trennung Admin-/Besucherbereich)
 * Changelog: 1.0.4 - Flash-Meldungen für handler_ko.php (Runde speichern, Runden anlegen) + sp_btn_table ergänzt
 * Changelog: 1.0.3 - Übersetzungen für Spieltag/KO-Editor + koModusLabel() ergänzt
 * Changelog: 1.0.2 - Übersetzungen für Teams (global) + gemeinsame Begriffe (common_*) ergänzt
 * Changelog: 1.0.1 - Übersetzungen für Dashboard/Ligen-Liste ergänzt
 * Changelog: 1.0.0 - Initiale Version (Referenzsprache)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * Deutsch ist die Referenzsprache: jeder Schlüssel, der in einer anderen
 * Sprachdatei fehlt, fällt automatisch auf den hier hinterlegten Text zurück.
 */
declare(strict_types = 1);

return [

    // ── Allgemein ────────────────────────────────────────────────────────────
    'lang_switch_label' => 'Sprache',

    // ── Navigation (Sidebar) ─────────────────────────────────────────────────
    'nav_dashboard'   => 'Ligen',
    'nav_create_liga' => 'Liga erstellen',
    'nav_import'      => 'Import (.l98)',
    'nav_archiv'      => 'Archiv',
    'nav_teams'       => 'Teams (global)',
    'nav_users'       => 'Benutzer',
    'nav_wartung'     => 'Wartung',
    'nav_settings'    => 'Einstellungen',

    // ── Seitentitel (Topbar) ─────────────────────────────────────────────────
    'title_dashboard'     => 'Ligen-Übersicht',
    'title_create_liga'   => 'Liga erstellen – Schritt {step}',
    'title_liga_detail'   => 'Liga-Details',
    'title_spieltag'      => 'Ergebnisse eintragen',
    'title_tabelle'       => 'Tabelle',
    'title_import'        => '.l98 Import',
    'title_import_review' => 'Import – Team-Abgleich',
    'title_archiv'        => 'Archiv',
    'title_users'         => 'Benutzerverwaltung',
    'title_liga_settings' => 'Liga-Einstellungen',
    'title_settings'      => 'Einstellungen',
    'title_admin_default' => 'Admin',

    // ── Topbar / Logout ──────────────────────────────────────────────────────
    'topbar_logout' => 'Logout',
    'topbar_visitor_link' => 'Benutzeransicht',

    // ── Login ────────────────────────────────────────────────────────────────
    'login_subtitle' => 'Melde dich mit deinen Zugangsdaten an.',
    'login_username' => 'Benutzername',
    'login_password' => 'Passwort',
    'login_submit'   => 'Anmelden',
    'login_visitor_link' => '← Zum Besucherbereich',
    'login_forgot_link'          => 'Passwort vergessen?',
    'reset_modal_title'          => 'Passwort zurücksetzen',
    'reset_modal_intro'          => 'Gib deine hinterlegte E-Mail-Adresse ein. Wir schicken dir einen Link zum Zurücksetzen, gültig für 4 Stunden.',
    'reset_modal_label_email'    => 'E-Mail-Adresse',
    'reset_modal_submit'         => 'Link anfordern',
    'reset_modal_cancel'         => 'Abbrechen',
    'reset_pw_subtitle'          => 'Bitte gib dein neues Passwort ein.',
    'reset_pw_label_new'         => 'Neues Passwort',
    'reset_pw_label_new2'        => 'Neues Passwort wiederholen',
    'reset_pw_submit'            => 'Passwort speichern',
    'reset_pw_invalid'           => 'Dieser Link ist ungültig oder abgelaufen. Bitte fordere über „Passwort vergessen" einen neuen Link an.',
    'reset_pw_back_to_login'     => '← Zurück zum Login',
    'flash_reset_email_not_found'=> 'Diese E-Mail-Adresse ist keinem Administrator-Konto hinterlegt.',
    'flash_reset_email_sent'     => 'E-Mail mit Link zum Zurücksetzen wurde verschickt.',
    'flash_reset_token_invalid'  => 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.',
    'flash_reset_password_success' => 'Passwort erfolgreich geändert. Du kannst dich jetzt anmelden.',
    'mail_reset_subject'         => '{site}: Passwort zurücksetzen',
    'mail_reset_body'            => "Hallo,\n\nfür dein Administrator-Konto wurde ein neues Passwort angefordert.\n\nÜber diesen Link kannst du ein neues Passwort vergeben (gültig für {hours} Stunden):\n{link}\n\nWenn du das nicht selbst warst, kannst du diese E-Mail einfach ignorieren.",

    // ── Flash-Meldungen (Benutzerverwaltung / Login) ────────────────────────
    'flash_invalid_credentials'      => 'Ungültige Zugangsdaten.',
    'flash_db_error'                 => 'DB-Fehler: {msg}',
    'flash_password_mismatch'        => 'Passwörter stimmen nicht überein.',
    'flash_password_min_length'      => 'Mindestens 8 Zeichen.',
    'flash_password_changed'         => 'Passwort geändert.',
    'flash_current_password_wrong'   => 'Aktuelles Passwort falsch.',
    'flash_error_prefix'             => 'Fehler: {msg}',
    'flash_user_password_required'   => 'Benutzername + mind. 8 Zeichen Passwort erforderlich.',
    'flash_user_created'             => 'Benutzer "{user}" angelegt.',
    'flash_invalid_input'            => 'Ungültige Eingabe.',
    'flash_username_taken'           => 'Benutzername bereits vergeben.',
    'flash_user_updated'             => 'Benutzer "{user}" aktualisiert.',
    'flash_own_account_undeletable'  => 'Eigenen Account nicht löschbar.',
    'flash_user_deleted'             => 'Benutzer "{user}" gelöscht.',
    'flash_settings_saved'           => 'Einstellungen gespeichert.',

    // ── install.php ──────────────────────────────────────────────────────────
    'install_title'            => 'LMOnext – Installation',
    'install_header_subtitle'  => 'Installationsassistent v{ver}',
    'install_step1'            => 'Systemprüfung',
    'install_step2'            => 'Konfiguration & Installation',
    'install_errors_heading'   => 'Fehler:',

    'install_check_php_version'      => 'PHP-Version',
    'install_check_php_version_fail' => ' (mind. {min} erforderlich)',
    'install_check_pdo'              => 'PDO-Extension',
    'install_check_pdo_mysql'        => 'PDO MySQL-Treiber',
    'install_check_mbstring'         => 'mbstring',
    'install_check_gd'                => 'GD-Erweiterung',
    'install_check_svg_raster'        => 'SVG-Rasterisierung (Imagick/rsvg-convert)',
    'install_check_zip'               => 'ZIP-Erweiterung (Team-Logo-Backup)',
    'install_check_teams_dir'         => 'Schreibrecht (assets/img/teams/)',
    'install_check_store_dir'         => 'Schreibrecht (store/, für Backups)',
    'install_check_bzip2'             => 'bzip2-Erweiterung (Backup-Kompression)',
    'install_recommended_missing'     => 'nicht gefunden (optional)',
    'install_optional'                => 'optional',
    'install_available'              => 'verfügbar',
    'install_missing_ini'            => 'FEHLT – in php.ini aktivieren',
    'install_missing_pdo_mysql'      => 'FEHLT – pdo_mysql fehlt',
    'install_missing'                => 'FEHLT',
    'install_check_writable'         => 'Schreibrecht ({dir}/)',
    'install_writable_ok'            => 'OK',
    'install_writable_fail'          => 'Kein Schreibrecht – chmod 755 setzen',
    'install_check_adminphp'         => 'admin.php vorhanden',
    'install_adminphp_found'         => 'gefunden',
    'install_adminphp_missing'       => 'NICHT gefunden – admin.php ins gleiche Verzeichnis legen',
    'install_db_error_unreachable'    => 'Der Datenbankserver ist unter Host/Port nicht erreichbar. Prüfe, ob MariaDB/MySQL läuft und ob Host und Port korrekt sind (bei den meisten Webhostern ist der Host NICHT "localhost", sondern ein spezieller Servername – siehe Hosting-Zugangsdaten).',
    'install_db_error_access_denied'  => 'Zugriff verweigert – Benutzername oder Passwort ist falsch, oder der Datenbank-Benutzer hat keine Berechtigung für diese Datenbank.',
    'install_db_error_no_db_permission' => 'Die Datenbank existiert nicht und konnte auch nicht automatisch angelegt werden. Bei den meisten Webhostern muss die Datenbank vorher im Hosting-Kontrollpanel manuell angelegt werden – der Datenbank-Benutzer darf dort meist keine neuen Datenbanken erstellen.',
    'install_db_error_unknown_host'   => 'Der angegebene Datenbank-Host wurde nicht gefunden. Bitte den Hostnamen in den Hosting-Zugangsdaten prüfen.',
    'install_db_error_generic'        => 'Datenbankfehler: {msg}',

    'install_requirements_heading' => 'Systemvoraussetzungen',
    'install_fix_issues'           => 'Bitte behebe die markierten Probleme vor der Installation.',
    'install_selfdestruct_notice'  => '<code>install.php</code> wird nach Abschluss <strong>automatisch gelöscht</strong>.',
    'install_continue'             => 'Weiter zur Konfiguration →',
    'install_check_failed'         => 'Systemprüfung fehlgeschlagen',

    'install_db_heading'      => 'Datenbankverbindung',
    'install_label_host'      => 'Host',
    'install_label_port'      => 'Port',
    'install_label_dbname'    => 'Datenbankname',
    'install_hint_dbname'     => 'Wird angelegt wenn nicht vorhanden. Nur Buchstaben, Ziffern, Unterstriche.',
    'install_label_prefix'    => 'Tabellen-Präfix',
    'install_hint_prefix'     => 'z.B. <code id="pfx_prev">lmonext_</code>liga, <code id="pfx_prev2">lmonext_</code>teams_global',
    'install_label_dbuser'    => 'Datenbankbenutzer',
    'install_label_dbpass'    => 'Datenbankpasswort',

    'install_admin_heading'      => 'Administrator-Konto',
    'install_label_username'     => 'Benutzername',
    'install_label_password'     => 'Passwort',
    'install_placeholder_pass'   => 'mind. 8 Zeichen',
    'install_label_password2'    => 'Passwort wiederholen',
    'install_label_email'        => 'E-Mail-Adresse',
    'install_hint_email'         => 'Optional. Wird nur für "Passwort vergessen" benötigt.',

    'install_app_heading'      => 'Anwendung',
    'install_label_sitetitle'  => 'Seitentitel',
    'install_hint_sitetitle'   => 'Wird im Browser-Tab und in der Sidebar angezeigt.',

    'install_final_warning' => 'Nach Klick auf „Jetzt installieren" wird <code>config.php</code> geschrieben und <strong>install.php unwiderruflich gelöscht</strong>.',
    'install_back'          => '← Zurück',
    'install_submit'        => '🚀 Jetzt installieren',
    'install_footer'        => 'LMOnext Installer v{ver} · PHP {phpver}',

    'err_dbname_required'       => 'Datenbankname ist erforderlich.',
    'err_dbuser_required'       => 'Datenbankbenutzer ist erforderlich.',
    'err_adminuser_required'    => 'Admin-Benutzername ist erforderlich.',
    'err_adminpass_minlen'      => 'Admin-Passwort muss mindestens 8 Zeichen haben.',
    'err_adminemail_invalid'    => 'Die E-Mail-Adresse ist ungültig.',
    'err_adminpass_mismatch'    => 'Admin-Passwörter stimmen nicht überein.',
    'err_requirements_not_met'  => 'Systemvoraussetzungen nicht erfüllt.',
    'err_config_write_failed'   => 'config.php konnte nicht geschrieben werden – Schreibrechte prüfen.',

    // ── Dashboard / Ligen-Liste (view_liga_list.php) ────────────────────────
    'dash_tooltip_all_ligen'       => 'Alle Ligen inkl. Archiv',
    'dash_stat_ligen_total'        => 'Ligen gesamt →',
    'dash_stat_active_archived'    => '{active} aktiv · {archived} archiviert',
    'dash_tooltip_teams'           => 'Teams verwalten',
    'dash_stat_teams'              => 'Teams (global) →',
    'dash_stat_partien'            => 'Partien gesamt',
    'dash_heading_all_ligen'       => 'Alle Ligen',
    'dash_btn_missing_results'     => '📋 Fehlende Ergebnisse',
    'dash_select_folder_placeholder' => '— Ordner wählen —',
    'dash_btn_archive'             => '🗄️ Archivieren',
    'dash_btn_clear_selection'     => '✕ Auswahl aufheben',
    'dash_empty_pre'               => 'Noch keine Ligen.',
    'dash_empty_link'              => 'Liga erstellen',
    'dash_empty_post'              => ' oder .l98 importieren.',
    'dash_col_id'                  => 'ID',
    'dash_col_name'                => 'Name',
    'dash_col_typ'                 => 'Typ',
    'dash_col_created'             => 'Erstellt',
    'dash_col_actions'             => 'Aktionen',
    'dash_tooltip_select_all'      => 'Alle auswählen',
    'dash_type_ko'                 => 'KO-Turnier',
    'dash_type_liga'               => 'Liga',
    'dash_tooltip_missing_results' => '{n} fehlende Ergebnisse',
    'dash_open_badge'              => '{n} offen',
    'dash_confirm_delete'          => 'Liga »{name}« wirklich löschen?',
    'dash_btn_delete'              => 'Löschen',
    'dash_js_selected_count'       => '{n} ausgewählt',
    'dash_js_choose_folder_alert'  => 'Bitte einen Archivordner auswählen.',
    'dash_confirm_archive_one'     => '1 Liga ins Archiv verschieben?',
    'dash_confirm_archive_many'    => '{n} Ligen ins Archiv verschieben?',

    // ── Gemeinsame/wiederverwendbare Begriffe ───────────────────────────────
    'common_cancel'      => 'Abbrechen',
    'common_save'        => '💾 Speichern',
    'common_loading'     => 'Laden…',
    'common_load_error'  => 'Fehler beim Laden.',

    // ── Teams (global) (view_teams.php) ─────────────────────────────────────
    'teams_filter_placeholder'   => '🔍  Name, Mittel oder Kürzel filtern…',
    'teams_btn_show_dups'        => '⚠️ Mögliche Duplikate anzeigen ({n})',
    'teams_count_total'          => '{n} Teams gesamt',
    'teams_dup_found_one'        => '<strong>1 mögliches Duplikat gefunden</strong>',
    'teams_dup_found_many'       => '<strong>{n} mögliche Duplikate gefunden</strong>',
    'teams_dup_detail'           => '– Teams mit gleichem oder ähnlichem Namen (inkl. Umlaut-Varianten wie ü/ue, ß/ss) sind gelb markiert. Über „Zusammenführen" kannst du zwei Teams zu einem zusammenfassen.',
    'teams_col_id'               => 'ID',
    'teams_col_name'             => 'Name',
    'teams_col_mittel'           => 'Mittel',
    'teams_col_kurz'             => 'Kürzel',
    'teams_col_ligen'            => 'Ligen',
    'teams_dup_hint_name'        => 'Ähnlicher Name',
    'teams_dup_hint_mittel'      => 'Ähnlicher Mittelname',
    'teams_merge_title'          => '⇌ Teams zusammenführen',
    'teams_merge_desc'           => 'Alle Verknüpfungen (Ligen, Partien) werden auf das behaltene Team übertragen.',
    'teams_merge_keep_label'     => '✓ Behalten',
    'teams_search_placeholder'   => 'Team suchen…',
    'teams_merge_delete_label'   => '🗑 Löschen (wird zusammengeführt)',
    'teams_merge_submit'         => '⇌ Zusammenführen',
    'teams_btn_merge_short'      => '⇌ Merge',
    'teams_confirm_delete_team'  => 'Team »{name}« löschen?',
    'teams_field_name_required'  => 'Name *',
    'teams_field_mittel'         => 'Mittelname',
    'teams_field_kurz'           => 'Kürzel',
    'teams_field_url'            => 'Vereins-/Team-Website',
    'teams_field_logo'           => 'Logo',
    'teams_logo_remove'          => 'Logo entfernen',
    'teams_logo_hint'            => 'SVG, JPG, PNG oder GIF, mindestens 50px hoch.',
    'teams_col_logo'             => 'Logo',
    'teams_logo_err_upload'      => 'Der Upload ist fehlgeschlagen. Bitte erneut versuchen.',
    'teams_logo_err_format'      => 'Ungültiges Dateiformat. Erlaubt sind SVG, JPG, PNG und GIF.',
    'teams_logo_err_invalid'     => 'Die Datei konnte nicht als gültiges Bild gelesen werden.',
    'teams_logo_err_too_small'   => 'Das Bild ist zu klein. Mindesthöhe: {min}px.',
    'teams_ligen_modal_default'  => 'Ligen',
    'teams_js_ligen_title'       => 'Ligen: {name}',
    'teams_js_no_ligen'          => 'Keine Ligen gefunden.',
    'teams_js_count'             => '{n} Teams',
    'teams_js_alert_pick_keep'      => 'Bitte ein Team zum Behalten auswählen.',
    'teams_js_alert_pick_delete'    => 'Bitte ein Team zum Löschen auswählen.',
    'teams_js_alert_pick_different' => 'Bitte zwei verschiedene Teams wählen.',
    'teams_js_confirm_merge'        => 'Teams wirklich zusammenführen?',

    // ── Gemeinsame Begriffe (Fortsetzung) ────────────────────────────────────
    'common_yes' => 'Ja',
    'common_no'  => 'Nein',

    // ── Spieltag / Ergebnisse eintragen (view_spieltag.php, handler_ko.php) ─
    'sp_not_found'                   => 'Spieltag nicht gefunden.',
    'sp_label_round'                 => 'Runde',
    'sp_label_matchday'              => 'Spieltag',
    'sp_final_and_third'             => '🏆 Finale + 🥉 Spiel um Platz 3',
    'sp_mode_label'                  => 'Spielmodus dieser Runde:',
    'sp_no_pairs_yet'                => 'Noch keine Begegnungen in dieser Runde. Klick auf „＋ Paarung hinzufügen".',
    'sp_final_label'                 => '🏆 Finale',
    'sp_third_place_label'           => '🥉 Spiel um Platz 3',
    'sp_pairing_label'               => 'Paarung {n}',
    'sp_tooltip_remove_pair'         => 'Paarung entfernen',
    'sp_only_winners_from'           => '🏆 Nur Sieger aus Runde {round} ({count} Teams)',
    'sp_winners_and_losers_from'     => '🏆 Sieger & Verlierer aus Runde {round} ({count} Teams) – Finale und Spiel um Platz 3',
    'sp_leg_home'                    => 'Hinspiel',
    'sp_leg_away'                    => 'Rückspiel',
    'sp_game_n'                      => 'Spiel {n}',
    'sp_status_normal'               => '– normal –',
    'sp_status_ie'                   => 'i.E. (nach Elfmeterschießen)',
    'sp_status_nv'                   => 'n.V. (nach Verlängerung)',
    'sp_placeholder_venue'           => '📍 Spielort',
    'sp_placeholder_report_link'     => '🔗 Link zum Spielbericht (optional)',
    'sp_btn_add_pair'                => '＋ Paarung hinzufügen',
    'sp_btn_save_round'              => '💾 Runde speichern',
    'sp_next_round'                  => 'Weiter: Runde {n} →',
    'sp_independent_settings_heading'=> 'SPIELTAGS-UNABHÄNGIGE EINSTELLUNGEN',
    'sp_ticker_show_label'           => 'Ticker anzeigen?',
    'sp_ticker_text_label'           => 'Tickertext:',
    'sp_ticker_placeholder'          => 'Tickertext eingeben…',
    'sp_btn_save_ticker'             => '💾 Ticker speichern',
    'sp_col_heim'                    => 'Heim',
    'sp_col_tore'                    => 'Tore',
    'sp_col_gast'                    => 'Gast',
    'sp_col_status'                  => 'Status',
    'sp_col_anstoss'                 => 'Anstoß',
    'sp_status_dash'                 => '–',
    'sp_status_ie_short'             => 'i.E.',
    'sp_status_nv_short'             => 'n.V.',
    'sp_btn_save_all'                => '💾 Alles speichern',
    'sp_next_matchday'               => 'Weiter: Spieltag {n} →',
    'sp_heading_all_rounds'          => 'Alle Runden',
    'sp_heading_all_matchdays'       => 'Alle Spieltage',
    'sp_btn_placeholder_short'       => '✏️ Platzh.',
    'sp_btn_team_short'              => '👤 Team',
    'sp_placeholder_example_winner'  => 'z.B. Sieger R1P1',
    'sp_option_placeholder'          => '– Platzhalter –',
    'sp_placeholder_word'            => 'Platzhalter',
    'sp_tooltip_toggle_team_placeholder' => 'Wechsel: echtes Team ↔ Platzhalter',
    'sp_btn_table'                   => '📊 Tabelle',

    // ── KO-Runden-Handler Flash-Meldungen (handler_ko.php) ──────────────────
    'ko_flash_invalid_round_id'   => 'Ungültige Runden-ID.',
    'ko_flash_round_saved'        => 'Runde {nr} gespeichert ({pairs} Paarung(en), {matches} Partie(n)).',
    'ko_flash_rounds_added'       => '{n} fehlende Runde(n) angelegt.',
    'ko_flash_rounds_all_exist'   => 'Alle Runden bereits vorhanden.',

    // ── KO-Spielmodi (bootstrap.php: koModusLabel()) ────────────────────────
    'ko_mode_1' => 'Einzelspiel',
    'ko_mode_2' => 'Hin- und Rückspiel',
    'ko_mode_3' => 'Best of 3',
    'ko_mode_5' => 'Best of 5',
    'ko_mode_7' => 'Best of 7',

    // ── Gemeinsame Begriffe (Fortsetzung 2) ──────────────────────────────────
    'common_back'      => '← Zurück',
    'common_next'      => 'Weiter →',
    'common_save_liga' => '✓ Liga speichern',

    // ── Liga-Vorlagen (templates.php) ────────────────────────────────────────
    'tpl_cl_ligaphase_label'  => 'UEFA Champions League – Ligaphase (Vorrunde)',
    'tpl_cl_ligaphase_desc'   => '36 Teams · 8 Spieltage · 18 Partien/Runde · Ligaformat',
    'tpl_cl_ligaphase_detail' => 'Jedes Team spielt 8 Spiele gegen 8 verschiedene Gegner (aus 4 Pools à 9 Teams, je 1 Heim + 1 Auswärts pro Pool). Plätze 1–8 → direkt Achtelfinale; Plätze 9–24 → Zwischenrunde; Plätze 25–36 → ausgeschieden.',
    'tpl_cl_ko_label'         => 'UEFA Champions League – KO-Phase',
    'tpl_cl_ko_desc'          => '24 Teams · 5 Runden · Zwischenrunde bis Finale',
    'tpl_cl_ko_detail'        => 'Zwischenrunde (Pl. 9–24): 8 Paarungen Hin+Rück. Achtelfinale: 16 Teams Hin+Rück. Viertelfinale + Halbfinale: Hin+Rück. Finale: Einzelspiel.',
    'tpl_bundesliga_label'    => '1. Bundesliga (18 Teams)',
    'tpl_bundesliga_desc'     => '18 Teams · 34 Spieltage · Hin- und Rückrunde',
    'tpl_bundesliga_detail'   => 'Klassische Liga mit Hin- und Rückrunde. 3 Punkte für Sieg, 1 für Unentschieden.',

    // ── Wizard: Liga erstellen (view_wizard.php) ─────────────────────────────
    'wiz_step_label_1' => '1 Stammdaten',
    'wiz_step_label_2' => '2 Teams',
    'wiz_step_label_3' => '3 Spielplan',
    'wiz_step_label_4' => '4 Speichern',

    'wiz_quickstart_heading'  => 'Schnellstart mit Vorlage',
    'wiz_quickstart_desc'     => 'Wähle eine vorkonfigurierte Vorlage – Teams und Einstellungen sind bereits eingetragen und können angepasst werden.',
    'wiz_step1_heading'       => 'Schritt 1 – Ligagrundlagen (manuell)',
    'wiz_label_liga_name'     => 'Liga-Name *',
    'wiz_placeholder_liga_name_example'  => 'z.B. 1. Bundesliga 2025/26',
    'wiz_placeholder_liga_name_example2' => 'z.B. Champions League 2025/26',
    'wiz_label_liga_type'     => 'Liga-Typ *',
    'wiz_option_ko_tournament'=> 'KO-Turnier / Pokal',
    'wiz_label_team_count'    => 'Anzahl Teams *',
    'wiz_option_n_teams'      => '{n} Teams',
    'wiz_option_uefa_teams'   => '24 Teams (UEFA-Modus)',
    'wiz_auto_rounds_label'   => 'Automatisch berechnete Rundenstruktur:',

    'wiz_round_finale'          => 'Finale',
    'wiz_round_halbfinale'      => 'Halbfinale',
    'wiz_round_viertelfinale'   => 'Viertelfinale',
    'wiz_round_achtelfinale'    => 'Achtelfinale',
    'wiz_round_sechzehntelfinale' => 'Sechzehntelfinale',
    'wiz_round_32tel'           => '32tel-Finale',
    'wiz_round_64tel'           => '64tel-Finale',
    'wiz_round_zwischenrunde'   => 'Zwischenrunde',
    'wiz_round_n'               => 'Runde {n}',
    'wiz_pairing_word_one'      => 'Paarung',
    'wiz_pairing_word_many'     => 'Paarungen',
    'wiz_dummy_teams_label'     => '· Dummy-Teams',

    'wiz_step2_heading' => 'Schritt 2 – Teams eingeben ({n} Teams)',
    'wiz_step2_desc'     => 'Name ist Pflicht. Mittelname und Kürzel sind optional.',
    'wiz_col_teamname'   => 'Teamname *',
    'wiz_placeholder_kurzname' => 'Kurzname',
    'wiz_placeholder_kurzel'   => 'Kürzel',
    'wiz_next_edit_teams'      => 'Weiter: Teams bearbeiten →',
    'wiz_back_other_template'  => '← Andere Vorlage',

    'wiz_template_heading_prefix' => 'Vorlage: ',
    'wiz_template_fallback'       => 'Vorlage',

    'wiz_step3_liga_heading' => 'Schritt 3 – Spielplan (automatisch generiert)',
    'wiz_schedule_mode_heading'        => 'Spielplan-Erstellung',
    'wiz_schedule_mode_leaguekey' => 'Schlüsselplan (DFB-Muster)',
    'wiz_schedule_mode_unavailable'    => 'für diese Teamzahl nicht verfügbar',
    'wiz_schedule_mode_random'         => 'Zufällig erstellen',
    'wiz_schedule_mode_none'          => 'Kein Spielplan',
    'wiz_schedule_mode_apply'         => 'Anwenden',
    'wiz_step3_liga_desc'    => '{days} Spieltage, {matches} Partien pro Spieltag.',
    'wiz_col_hash'           => '#',
    'wiz_matchday_first_half'  => ' (Hinrunde)',
    'wiz_matchday_second_half' => ' (Rückrunde)',
    'wiz_back_edit_teams'      => '← Teams bearbeiten',

    'wiz_step3_ko_heading' => 'Schritt 3 – KO-Rundenstruktur &amp; Spielmodi',
    'wiz_uefa24_note'      => '🏆 <strong>UEFA-24-Teams-Modus:</strong> Zwischenrunde (8 Teams von Pl. 9–24) + 4 KO-Runden mit Hin- und Rückspiel, Finale als Einzelspiel.',
    'wiz_step3_ko_desc'    => 'Wähle den Spielmodus für jede Runde. Die Paarungen werden automatisch mit Dummy-Teams angelegt und können danach in der Spieltagansicht bearbeitet werden.',
    'wiz_label_spielmodus' => 'Spielmodus:',
    'wiz_dummy_info'       => 'ℹ️ Runde 1 wird mit den eingetragenen Teams paarweise besetzt (Team 1 vs 2, 3 vs 4, …). Alle weiteren Runden erhalten Dummy-Paarungen (<code>___</code>) die nach Feststehen der Sieger ersetzt werden.',

    'wiz_session_expired' => 'Sitzung abgelaufen.',
    'wiz_restart'         => 'Von vorne beginnen',

    // ── Wizard-Handler Flash-Meldungen (handler_wizard.php) ─────────────────
    'wiz_flash_unknown_template'   => 'Unbekannte Vorlage.',
    'wiz_flash_name_required'      => 'Liga-Name erforderlich.',
    'wiz_flash_team_name_missing'  => 'Team {n}: Name fehlt.',
    'liga_flash_created'           => 'Liga "{name}" angelegt (ID {id}, {teams} Teams, {matchdays} Spieltage).',

    // ── Archiv (view_archiv.php) ─────────────────────────────────────────────
    'arch_count_ligen_one'      => '{n} Liga',
    'arch_count_ligen_many'     => '{n} Ligen',
    'arch_count_subfolders'     => ', {n} Unterordner',
    'arch_confirm_delete_folder'=> 'Ordner löschen?',
    'arch_btn_reactivate'       => '↩ Reaktivieren',
    'arch_btn_new_folder'       => '📁 Neuer Ordner',
    'arch_btn_missing_results_one'  => '⚠️ 1 Liga mit fehlenden Ergebnissen',
    'arch_btn_missing_results_many' => '⚠️ {n} Ligen mit fehlenden Ergebnissen',
    'arch_summary_line'         => '{folders} Ordner · {ligen} archivierte Ligen',
    'arch_empty_line1'          => 'Noch keine Archivordner vorhanden.',
    'arch_empty_line2'          => 'Klicke auf <strong>📁 Neuer Ordner</strong> um einen Ordner anzulegen.',
    'arch_empty_line3'          => 'Ligen können danach über <strong>⚙️ Einstellungen → Archivieren</strong> in einen Ordner verschoben werden.',
    'arch_no_folder_label'      => '📋 Ohne Ordner',
    'arch_label_description'   => 'Beschreibung',
    'arch_label_parent_folder' => 'Übergeordneter Ordner',
    'arch_option_top_level'    => '— Hauptebene —',
    'arch_label_sort'          => 'Sortierung',
    'arch_modal_title_default' => '📁 Ordner',
    'arch_modal_title_edit'    => '✏️ Ordner bearbeiten',
    'arch_btn_clear_filter'    => '✕ Filter aufheben',

    // ── Liga-Handler Flash-Meldungen (handler_liga.php) ─────────────────────
    'hl_flash_spieltag_saved'          => 'Spieltag {n} gespeichert.',
    'hl_flash_team_id_or_name_missing' => 'Team-ID oder Name fehlt.',
    'hl_flash_team_taken_from_db'      => 'Team aus DB übernommen.',
    'hl_flash_team_updated'            => 'Team aktualisiert.',
    'hl_flash_spieltag_datum_saved'    => 'Spieltag-Datum gespeichert.',
    'hl_flash_liga_deleted'            => 'Liga gelöscht.',
    'hl_flash_team_deleted'            => 'Team gelöscht.',
    'hl_flash_team_delete_blocked'     => 'Team ist noch in einer Liga eingetragen und kann nicht gelöscht werden.',
    'hl_flash_teams_merged'            => 'Teams zusammengeführt.',
    'hl_flash_name_required'           => 'Name erforderlich.',
    'hl_flash_folder_updated'          => 'Ordner aktualisiert.',
    'hl_flash_folder_created'          => 'Ordner angelegt.',
    'hl_flash_folder_deleted'          => 'Ordner gelöscht.',
    'hl_flash_liga_archived'           => 'Liga ins Archiv verschoben.',
    'hl_flash_liga_unarchived'         => 'Liga aus Archiv zurückgeholt.',
    'hl_flash_no_ligen_selected'       => 'Keine Ligen ausgewählt.',
    'hl_flash_bulk_archived_one'       => '1 Liga ins Archiv verschoben.',
    'hl_flash_bulk_archived_many'      => '{n} Ligen ins Archiv verschoben.',

    // ── Import (view_import.php) ─────────────────────────────────────────────
    'imp_tab_multi'          => '📂 Mehrere .l98',
    'imp_tab_zip'            => '🗜️ ZIP-Archiv',
    'imp_multi_heading'      => '.l98-Dateien importieren',
    'imp_multi_desc'         => 'Lädt eine oder mehrere LMO-Ligadateien (.l98) hoch und importiert sie in die Datenbank.',
    'imp_multi_limit_warning'=> '⚠️ Dein Server erlaubt maximal <strong>{n} Dateien</strong> pro Upload. Für mehr Dateien gleichzeitig bitte den <strong>ZIP-Import</strong> verwenden.',
    'imp_label_ligadateien'  => 'Ligadateien (.l98)',
    'imp_btn_import'         => '📥 Importieren',
    'imp_js_file_one'        => '1 Datei ausgewählt',
    'imp_js_file_many'       => '{n} Dateien ausgewählt',
    'imp_js_limit_warning'   => '⚠️ Limit: {n}',
    'imp_zip_heading'        => 'ZIP-Archiv importieren',
    'imp_zip_desc'           => 'Packe beliebig viele .l98-Dateien in eine ZIP-Datei und lade sie hoch.<br>Alle .l98-Dateien im ZIP (auch in Unterordnern) werden importiert.',
    'imp_label_zipfile'      => 'ZIP-Datei',
    'imp_btn_import_zip'     => '📥 ZIP importieren',
    'imp_details_heading'    => 'Import-Details ({n} Dateien)',
    'imp_active_ligen_heading' => 'Aktive Ligen ({n})',

    // ── Import-Handler Flash-Meldungen (handler_import_export.php) ──────────
    'imp_liga_imported'        => 'Liga "{name}" importiert (ID {id}, {teams} Teams, {rounds} Runden/Spieltage).',
    'imp_error_prefix'         => 'Importfehler: {msg}',
    'imp_zip_extension_missing'=> 'ZIP-Erweiterung nicht verfügbar auf diesem Server.',
    'imp_no_zip_uploaded'      => 'Kein ZIP hochgeladen oder Fehler.',
    'imp_zip_open_failed'      => 'ZIP-Datei konnte nicht geöffnet werden.',
    'imp_zip_read_error'       => 'Lesefehler im ZIP.',
    'imp_no_l98_in_zip'        => 'Keine .l98-Dateien im ZIP gefunden.',
    'imp_summary'              => '{ok} von {total} Dateien erfolgreich importiert.',
    'imp_upload_error'         => 'Upload-Fehler.',
    'imp_only_l98_supported'   => 'Nur .l98-Dateien werden unterstützt.',
    'imp_file_unreadable'      => 'Datei nicht lesbar.',
    'imp_no_files_uploaded'    => 'Keine Dateien hochgeladen.',
    'exp_flash_liga_not_found' => 'Liga nicht gefunden.',

    // ── Liga-Details (view_liga_detail.php) ──────────────────────────────────
    'ld_back_link'          => '← Zurück zur Übersicht',
    'ld_id_created'         => 'ID {id} · Erstellt: {datum}',
    'ld_btn_settings'       => '⚙️ Einstellungen',
    'ld_btn_enter_results'  => '📝 Ergebnisse eintragen',
    'ld_btn_export'         => '💾 Als .l98 exportieren',
    'ld_btn_archive_dd'     => '🗄️ Archivieren ▾',
    'ld_no_folders'         => 'Keine Ordner —',
    'ld_create_in_archive'  => 'im Archiv anlegen',
    'ld_btn_fix_rounds'     => '⚠️ {n} fehlende Runde(n) anlegen',
    'ld_heading_teams'      => 'Teams ({n})',
    'ld_label_db_search'    => '🔍 Aus teams_global übernehmen',
    'ld_placeholder_name_search' => 'Name suchen…',
    'ld_label_id_lookup'    => '🔢 …oder Team-ID direkt eingeben',
    'ld_placeholder_id_lookup' => 'Team-ID',
    'ld_btn_id_apply'       => 'Übernehmen',
    'ld_js_id_not_found'    => 'Kein Team mit dieser ID gefunden.',
    'ld_label_mittel_short' => 'Mittel',
    'ld_tooltip_edit_date'  => 'Datum bearbeiten',
    'ld_btn_paarungen'      => '✏️ Paarungen',
    'ld_label_startdatum'   => 'Startdatum',
    'ld_heading_spieltage'  => 'Spieltage ({n})',
    'ld_col_start'          => 'Start',
    'ld_col_partien'        => 'Partien',
    'ld_col_gespielt'       => 'Gespielt',
    'ld_js_no_match'        => 'Kein Treffer – neues Team wird angelegt.',
    'ls_flash_invalid_id'   => 'Ungültige Liga-ID.',

    // ── Liga-Einstellungen (view_liga_settings.php) ──────────────────────────
    'ls_tab_grundwerte'    => 'Grundwerte',
    'ls_tab_anzeige'       => 'Anzeigen/Darstellung',
    'ls_tab_spielsystem'   => 'Spielsystem',
    'ls_tab_tabelle'       => 'Tabelle',
    'ls_tab_spieltage'     => 'Spieltags- und Spiel-Anzahl',

    'ls_label_liga_name'   => 'Name der Liga',
    'ls_label_alt_pkt'     => 'Alternative für Pkt.',
    'ls_label_alt_tore'    => 'Alternative für Tore',
    'ls_label_dec_pkt'     => 'Kommastellen Pkt.',
    'ls_label_dec_tore'    => 'Kommastellen Tore',
    'ls_opt_none'          => 'Keine',
    'ls_opt_one'           => 'Eine',
    'ls_opt_two'           => 'Zwei',
    'ls_label_fav_team'    => 'Lieblingsmannschaft',
    'ls_opt_none_dash'     => '— keine —',
    'ls_opt_none_dash_m'   => '— kein —',
    'ls_label_spielplan'   => 'Spielplan',

    'ls_label_date_sort'      => 'Datumssortierung',
    'ls_label_third_place'    => 'Spiel um Platz 3',
    'ls_label_playdown'       => 'Playdown ermöglichen',
    'ls_label_anstoss_termin' => 'Anstoßtermin',
    'ls_label_anstoss_format' => 'Format der Anstoßtermine',
    'ls_label_php_dateformat' => 'PHP-Datumsformat',
    'ls_label_spieltagsdatum' => 'Spieltagsdatum',
    'ls_label_ergebnisse'     => 'Ergebnisse',
    'ls_label_show_spielfrei' => 'Spielfrei anzeigen',
    'ls_label_tabelle'        => 'Tabelle',
    'ls_label_show_logos'     => 'Logo anzeigen',
    'ls_label_kalender'       => 'Kalender',
    'ls_cb_spielplaene'       => 'Spielpläne',
    'ls_label_kreuztabelle'   => 'Kreuztabelle',
    'ls_cb_fieberkurven'      => 'Fieberkurven',
    'ls_label_spielerstatistik' => 'Spielerstatistik',
    'ls_cb_ligastatistik'     => 'Ligastatistik',
    'ls_heading_ticker'       => 'Ticker',
    'ls_label_ticker_show'    => 'Ticker anzeigen?',
    'ls_placeholder_tickertext' => 'Freitext, der oberhalb der Liga angezeigt wird…',
    'ls_heading_verlinkungen' => 'Verlinkungen',
    'ls_cb_team_homepage'     => 'Mannschafts-Homepages verlinken',
    'ls_cb_spielberichte'     => 'Spielberichte verlinken',
    'ls_heading_playoff_mode' => 'Playoff Modus – Heimspieleinstellung',
    'ls_label_modusauswahl'   => 'Modusauswahl',
    'ls_opt_mod_111'          => 'Mod.: 1-1-1-...',
    'ls_opt_mod_221'          => 'Mod.: 2-2-1',
    'ls_opt_mod_22111'        => 'Mod.: 2-2-1-1-1',
    'ls_opt_mod_232'          => 'Mod.: 2-3-2',

    'ls_cb_minuspunkte'    => 'Minuspunkte',
    'ls_cb_spielende_offen'=> 'Spielende offen',
    'ls_cb_hide_draw'      => "Tabellenspalte 'Unentschieden' verbergen",
    'ls_cb_direct_compare' => 'Direkter Vergleich',
    'ls_cb_spez'           => 'erzielte Tore zählen vor Tordifferenz',
    'ls_cb_hand_sort'      => 'Handsortierung der Tabelle ermöglichen',
    'ls_heading_punktesystem' => 'Punktesystem',
    'ls_col_win'           => 'S',
    'ls_col_draw'          => 'U',
    'ls_col_loss'          => 'N',
    'ls_row_normal_end'    => 'nach regulärem Ende',
    'ls_row_extra_time'    => 'nach Verlängerung',
    'ls_row_penalty'       => 'nach Penalty- bzw. 11-Meter-Schießen',
    'ls_na'                => 'n/a',

    'ls_cb_hin_rueck_tables' => 'Hin-/Rückrundentabellen',
    'ls_cb_heim_ausw_tables' => 'Heim-/Auswärtstabellen',
    'ls_heading_table_markers' => 'Tabellenmarkierungen',
    'ls_marker_champ' => 'Meister wird ausgespielt',
    'ls_marker_cl'    => 'Champions-League-Teilnehmer bzw. Aufsteiger',
    'ls_marker_ck'    => 'Champions-League-Qualifikanten',
    'ls_marker_uc'    => 'Euroleague-Teilnehmer',
    'ls_marker_ar'    => 'Relegation zum Abstieg',
    'ls_marker_ab'    => 'feststehende Absteiger',
    'ls_marker_color_title' => 'Farbe der Randmarkierung',
    'ls_marker_color_hint'  => 'Die Farbe erscheint als schmaler farbiger Rand am linken Tabellenrand in der Besucheransicht.',

    'ls_warning_heading' => 'Achtung!',
    'ls_warning_text'    => 'Diese Einstellungen können eine bestehende Liga unbrauchbar machen. Bitte nur ändern wenn du dir sicher bist.',
    'ls_label_anzahl_spieltage' => 'Anzahl der Spieltage',
    'ls_label_anzahl_spiele_pro_spieltag' => 'Anzahl der Spiele pro Spieltag',

    'ls_btn_save' => '💾 Änderungen speichern',

    // ── Tabelle (view_tabelle.php) ────────────────────────────────────────────
    'tab_btn_results'    => '📝 Ergebnisse',
    'tab_btn_export'     => '💾 Export .l98',
    'tab_heading'        => '{name} – Tabelle',
    'tab_scoring_line'   => 'Wertung: Sieg {win} Pkt · Unentschieden {draw} Pkt · Sortierung: Punkte → Tordifferenz → Tore',
    'tab_col_sp'         => 'Sp',
    'tab_tooltip_sp'     => 'Spiele',
    'tab_col_s'          => 'S',
    'tab_tooltip_s'      => 'Siege',
    'tab_col_u'          => 'U',
    'tab_tooltip_u'      => 'Unentschieden',
    'tab_col_n'          => 'N',
    'tab_tooltip_n'      => 'Niederlagen',
    'tab_col_diff'       => 'Diff',
    'tab_tooltip_diff'   => 'Tordifferenz',
    'tab_col_pkt'        => 'Pkt',
    'tab_empty'          => 'Noch keine Ergebnisse eingetragen.',

    // ── Benutzerverwaltung (view_users.php) ──────────────────────────────────
    'usr_heading_create'        => 'Neuen Benutzer anlegen',
    'usr_label_password_min8'   => 'Passwort (min. 8 Zeichen)',
    'usr_btn_create'            => 'Benutzer anlegen',
    'usr_heading_existing'      => 'Vorhandene Benutzer',
    'usr_empty'                 => 'Keine Benutzer gefunden.',
    'usr_col_last_login'        => 'Letzter Login',
    'usr_chip_me'               => 'Ich',
    'usr_btn_edit'              => '✏️ Bearbeiten',
    'usr_confirm_delete'        => 'Benutzer »{name}« löschen?',
    'usr_label_new_password'    => 'Neues Passwort',
    'usr_hint_empty_unchanged'  => '(leer = unverändert)',
    'usr_btn_save'              => 'Speichern',

    // ── Einstellungen (view_settings.php) ────────────────────────────────────
    'settings_heading_system'      => 'Systemeinstellungen',
    'settings_label_language'      => 'Standardsprache',
    'settings_hint_language'       => 'Gilt als Standardsprache für neue Besucher/Sitzungen dieses Adminbereichs.',
    'settings_label_timezone'      => 'Zeitzone',
    'settings_current_time_line'   => 'Aktuelle Zeit in dieser Zone: {time}',
    'settings_hint_timezone'       => 'Gilt für die gesamte Anwendung: Datumsanzeigen, .l98-Import und -Export.',
    'settings_heading_password'    => 'Passwort ändern',
    'settings_label_current_password' => 'Aktuelles Passwort',
    'settings_label_new_password2' => 'Neues Passwort wiederholen',
    'settings_heading_db'          => 'Datenbankverbindung',
    'settings_db_connected'        => 'Verbunden · {version}',
    'settings_hint_db_config'      => 'Verbindungsparameter in <code>config.php</code> anpassen.',
    'settings_heading_frontend'         => 'Besucherbereich',
    'settings_label_active_template'    => 'Aktives Template',
    'settings_hint_active_template'     => 'Design, das Besucher auf der Startseite und den weiteren Seiten sehen.',
    'settings_label_allow_template_switch' => 'Besucher erlauben, Template zu wechseln?',
    'settings_hint_allow_template_switch'  => 'Wenn ja, können Besucher über ein Dropdown ein anderes als das hier aktive Template auswählen (nur für ihre eigene Sitzung).',
    'settings_label_show_pdf_buttons'      => 'PDF-Export für Besucher anzeigen?',
    'settings_hint_show_pdf_buttons'       => 'Wenn nein, wird der PDF-Button in Ergebnisse, Tabelle, Spielplänen und dem Teamvergleich für Besucher komplett ausgeblendet – bei KO- und regulären Ligen gleichermaßen, auf allen Seiten.',
    'settings_label_show_language_switcher' => 'Sprachauswahl anzeigen?',
    'settings_hint_show_language_switcher'  => 'Wenn nein, wird die Sprachauswahl für Besucher auf allen Seiten der Besucheransicht ausgeblendet.',

    // ── Wartung (Datenbank-Backup/Wiederherstellung) ──────────────────────────
    'wartung_tab_backup'             => 'Backup',
    'wartung_tab_restore'            => 'Wiederherstellung',
    'wartung_backup_intro'           => 'Hier kannst du alle Datenbank-Tabellen sichern. Das Archiv wird im Ordner store/ gespeichert.',
    'wartung_hint_logos_included'    => 'Hochgeladene Team-Logos (Teams (global) → Logo-Upload) werden automatisch als separates ZIP am gleichen Ort mitgesichert.',
    'wartung_hint_logos_unavailable' => 'Die ZIP-Erweiterung ist auf diesem Server nicht verfügbar – Team-Logos werden bei dieser Sicherung nicht mit gespeichert (die Datenbank-Sicherung selbst ist davon nicht betroffen).',
    'wartung_hint_includes_logos'    => 'Dieses Backup enthält auch die Team-Logos',
    'wartung_heading_backup_options' => 'Backup-Optionen',
    'wartung_label_backup_type'      => 'Backup-Art:',
    'wartung_backup_type_complete'   => 'Komplett',
    'wartung_backup_type_data'       => 'Nur Daten',
    'wartung_label_file_type'        => 'Dateityp:',
    'wartung_format_unavailable'     => 'nicht verfügbar',
    'wartung_label_table_selection'  => 'Tabellen-Auswahl:',
    'wartung_select_all'             => 'Alle markieren',
    'wartung_select_none'            => 'Alle Markierungen aufheben',
    'wartung_btn_submit_backup'      => 'Backup erstellen',
    'wartung_heading_backup_settings'=> 'Backup-Verwaltung',
    'wartung_label_max_count'        => 'Maximale Anzahl an Backups:',
    'wartung_btn_save_settings'      => 'Speichern',
    'wartung_hint_max_count'         => 'Wird diese Anzahl überschritten, wird automatisch das älteste Backup gelöscht. 0 = unbegrenzt.',
    'wartung_restore_intro'          => 'Hiermit wird eine vollständige Wiederherstellung aus einem gespeicherten Backup durchgeführt. ACHTUNG: Dieser Vorgang überschreibt vorhandene Daten.',
    'wartung_restore_empty'          => 'Es sind noch keine Backups vorhanden.',
    'wartung_label_choose_backup'    => 'Wähle eine Sicherung aus:',
    'wartung_btn_restore'            => 'Wiederherstellung starten',
    'wartung_btn_delete'             => 'Backup löschen',
    'wartung_confirm_restore'        => 'Wirklich wiederherstellen? Vorhandene Daten werden überschrieben.',
    'wartung_confirm_delete'         => 'Dieses Backup wirklich löschen?',
    'wartung_flash_backup_created'   => 'Backup erstellt: {file}',
    'wartung_flash_logos_included'   => 'Team-Logos wurden mitgesichert.',
    'wartung_flash_logos_restored'   => '{n} Team-Logo(s) wiederhergestellt.',
    'wartung_flash_logos_restore_failed' => 'Team-Logos konnten nicht wiederhergestellt werden: {msg}',
    'wartung_error_zip_missing'      => 'Die ZIP-Erweiterung ist auf diesem Server nicht verfügbar, Team-Logos können daher nicht wiederhergestellt werden.',
    'wartung_flash_restored'         => 'Wiederherstellung abgeschlossen ({n} Anweisungen ausgeführt).',
    'wartung_flash_prefix_remapped'  => 'Hinweis: Das Backup wurde mit Tabellenprefix "{from}" erstellt und automatisch auf den aktuell konfigurierten Prefix "{to}" umgeschrieben.',
    'wartung_flash_deleted'          => 'Backup gelöscht.',
    'wartung_flash_settings_saved'   => 'Einstellungen gespeichert.',
    'wartung_error_no_tables'        => 'Keine Tabellen gefunden.',
    'wartung_error_compress'         => 'Fehler beim Komprimieren des Backups.',
    'wartung_error_write'            => 'Backup konnte nicht geschrieben werden ({path}). Bitte Schreibrechte prüfen.',
    'wartung_error_invalid_file'     => 'Ungültiger Dateiname.',
    'wartung_error_file_missing'     => 'Backup-Datei nicht gefunden.',
    'wartung_error_decompress'       => 'Fehler beim Entpacken des Backups.',
    'wartung_error_generic'          => 'Es ist ein Fehler aufgetreten.',

    // ── Import: Team-Namensabgleich (ungefähre Treffer vor dem eigentlichen Import) ─
    'imp_review_heading'      => 'Team-Abgleich ({n})',
    'imp_review_intro'        => 'Folgende Teams aus den hochgeladenen .l98-Dateien ähneln bereits vorhandenen Teams in der Datenbank, sind aber nicht exakt namensgleich. Wähle aus, bei welchen der Name aus der Datenbank übernommen werden soll – so entstehen keine Duplikate. Nicht angehakte Teams werden mit ihrem Namen aus der .l98-Datei als neues Team angelegt.',
    'imp_review_item'         => '{import} → ähnelt vorhandenem Team {db} (ID {id}).',
    'imp_review_item_multi'   => '{import} → ähnelt {n} vorhandenen Teams.',
    'imp_review_select_label' => 'Team aus der DB übernehmen:',
    'imp_review_option_new'   => '– Kein passendes Team – neues Team anlegen –',
    'imp_review_db_details'   => 'Kurz: {kurz} · Mittel: {mittel}',
    'imp_review_btn_confirm'  => 'Import fortsetzen',
    'imp_review_btn_cancel'   => 'Abbrechen',
    'imp_review_expired'      => 'Die Abgleich-Daten sind nicht mehr verfügbar. Bitte Datei(en) erneut hochladen.',

    // ── Spielerstatistik-Addon ──────────────────────────────────────────────
    'ld_btn_spielerstatistik'      => 'Spielerstatistik',
    'spst_back_link'               => '« zurück zur Liga',
    'spst_title'                   => 'Spielerstatistik: {liga}',
    'spst_heading_add_column'      => 'Spalte hinzufügen',
    'spst_heading_add_player'      => 'Spieler hinzufügen',
    'spst_heading_columns'         => 'Spalten löschen',
    'spst_heading_config'          => 'Konfiguration',
    'spst_heading_import'          => 'Alte Statistik importieren',
    'spst_col_name'                => 'Name',
    'spst_col_typ'                 => 'Typ',
    'spst_col_rolle'               => 'Rolle',
    'spst_col_formel'              => 'Formel',
    'spst_typ_zahl'                => 'Zahlen',
    'spst_typ_text'                => 'Text',
    'spst_typ_formel'               => 'Formel',
    'spst_rolle_normal'            => 'normal',
    'spst_rolle_verein'            => 'Verein',
    'spst_rolle_spielerlink'       => 'Spielerlink',
    'spst_btn_add_column'          => 'Spalte hinzufügen',
    'spst_btn_add_player'          => 'Spieler hinzufügen',
    'spst_btn_save_values'         => 'Werte speichern',
    'spst_btn_save_config'         => 'Konfiguration speichern',
    'spst_btn_import'              => 'Importieren',
    'spst_confirm_delete_player'   => 'Diesen Spieler wirklich löschen?',
    'spst_confirm_delete_column'   => 'Diese Spalte wirklich löschen? Alle Werte darin gehen verloren.',
    'spst_empty_hint'              => 'Noch keine Spalten/Spieler vorhanden. Lege oben eine Spalte und einen Spieler an, oder importiere eine alte Statistik-Datei.',
    'spst_cfg_per_page'            => 'Anzeige pro Seite (0 = alle)',
    'spst_cfg_link_label'          => 'Linkbezeichnung',
    'spst_cfg_show_zero'           => 'Nullwerte einblenden',
    'spst_cfg_show_per_club'       => 'Vereinsweise anzeigen',
    'spst_cfg_show_extra_sort'     => 'Extra Sortierspalte',
    'spst_team_col_unknown'        => 'kein aktuelles Team der Liga',
    'spst_import_hint'             => 'Lade eine .stat-Datei (und optional die zugehörige .cfg-Datei) aus dem alten LMO-Addon "Spielerstatistik" hoch. Alle drei bekannten Trennzeichen (§, |, #) werden automatisch erkannt.',
    'spst_import_replace_warning'  => 'Ein Import ersetzt die komplette bestehende Spielerstatistik dieser Liga.',
    'spst_flash_import_blocked'    => 'Import nicht möglich: Für diese Liga wurde bereits mindestens eine Spalte manuell angelegt. Der Import ist nur für eine noch komplett unkonfigurierte Spielerstatistik gedacht.',
    'spst_review_intro'            => 'Folgende Vereine aus der importierten Datei ähneln bereits vorhandenen Teams, sind aber nicht exakt namensgleich. Wähle aus, welcher Vereinsname übernommen werden soll.',
    'spst_flash_name_required'     => 'Bitte einen Namen angeben.',
    'spst_flash_column_added'      => 'Spalte hinzugefügt.',
    'spst_flash_column_deleted'    => 'Spalte gelöscht.',
    'spst_flash_player_added'      => 'Spieler hinzugefügt.',
    'spst_flash_player_deleted'    => 'Spieler gelöscht.',
    'spst_flash_updated'           => 'Werte gespeichert.',
    'spst_flash_config_saved'      => 'Konfiguration gespeichert.',
    'spst_flash_no_file'           => 'Bitte eine .stat-Datei hochladen.',
    'spst_flash_file_unreadable'   => 'Datei konnte nicht gelesen werden.',
    'spst_flash_parse_failed'      => 'Datei konnte nicht als Spielerstatistik erkannt werden.',
    'spst_import_success'          => 'Import abgeschlossen: {n} Spieler übernommen.',
    'spst_column_image_hint'       => 'Liegt im Ordner assets/addon/player/ eine Grafik mit exakt dem Spaltennamen (z.B. "Tore.png"), wird sie in der Besucheransicht statt des Textes als Spaltenüberschrift angezeigt.',
    'spst_btn_photo'                => 'Foto',
    'spst_btn_photo_remove'         => 'Foto entfernen',
    'spst_flash_photo_saved'        => 'Foto gespeichert.',
    'spst_flash_photo_removed'      => 'Foto entfernt.',
    'spst_photo_err_upload'         => 'Foto konnte nicht hochgeladen werden.',
    'spst_photo_err_format'         => 'Nicht unterstütztes Bildformat (erlaubt: JPG, PNG, GIF, SVG).',
    'spst_photo_err_invalid'        => 'Datei ist kein gültiges Bild.',

];
