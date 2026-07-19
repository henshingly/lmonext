<?php
/**
 * Project: LMOnext
 * Filename: admin.php
 * Fileversion: 1.4.2
 * Changelog: 1.4.2 - Route für "reset_password" (Passwort-Reset-Landingpage aus der E-Mail) ergänzt
 * Changelog: 1.4.1 - Route für "import_review" (Team-Namensabgleich beim .l98-Import) ergänzt
 * Changelog: 1.4.0 - Route + Handler für "Wartung" (Datenbank-Backup/Wiederherstellung) ergänzt
 * Changelog: 1.3.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.3.0 - Route für archiv-Action
 * Changelog: 1.2.0 - Route für teams-Action hinzugefügt
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

// ── Pfad zu den Include-Dateien ───────────────────────────────────────────────
define('ADMIN_INC', __DIR__ . '/admin');

// ── Bootstrap: Config, DB, Hilfsfunktionen ───────────────────────────────────
require_once ADMIN_INC . '/bootstrap.php';  // inkl. session_start()
require_once ADMIN_INC . '/templates.php';
require_once ADMIN_INC . '/schluesselring_data.php';

// ── Aktion ────────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

// ── POST-Handler (laufen vor HTML-Ausgabe) ────────────────────────────────────
require_once ADMIN_INC . '/handler_user.php';
require_once ADMIN_INC . '/handler_settings.php';
require_once ADMIN_INC . '/handler_ko.php';            // koHeimatTeamA() muss vor import_export bekannt sein
require_once ADMIN_INC . '/handler_import_export.php';
require_once ADMIN_INC . '/handler_wizard.php';
require_once ADMIN_INC . '/handler_export.php';
require_once ADMIN_INC . '/handler_liga.php';
require_once ADMIN_INC . '/handler_backup.php';

// ── Daten laden (vor HTML-Ausgabe) ───────────────────────────────────────────
require_once ADMIN_INC . '/data_loader.php';

// ── HTML-Kopf: CSS, Meta, Flash-Funktion ─────────────────────────────────────
require_once ADMIN_INC . '/html_start.php';

// ── Login-Seite: kein Sidebar-Layout ─────────────────────────────────────────
if ($action === 'login') {
    require ADMIN_INC . '/view_login.php';
    exit;
}

// ── Passwort-Reset-Landingpage (aus der E-Mail erreicht): ebenfalls kein
// Sidebar-Layout, da hier noch nicht eingeloggt ─────────────────────────────
if ($action === 'reset_password') {
    require ADMIN_INC . '/view_reset_password.php';
    exit;
}

// ── Sidebar + Main-Wrapper für alle anderen Views ────────────────────────────
require ADMIN_INC . '/html_layout.php';

// ── Views ─────────────────────────────────────────────────────────────────────
if ($action === 'create_liga') {
    require ADMIN_INC . '/view_wizard.php';

} elseif ($action === 'liga_detail' && $ligaDetail) {
    require ADMIN_INC . '/view_liga_detail.php';

} elseif ($action === 'spieltag' && $spieltagData) {
    require ADMIN_INC . '/view_spieltag.php';

} elseif ($action === 'tabelle' && $tabelleData) {
    require ADMIN_INC . '/view_tabelle.php';

} elseif ($action === 'archiv') {
    require ADMIN_INC . '/view_archiv.php';

} elseif ($action === 'teams') {
    require ADMIN_INC . '/view_teams.php';

} elseif ($action === 'import') {
    require ADMIN_INC . '/view_import.php';

} elseif ($action === 'import_review') {
    require ADMIN_INC . '/view_import_review.php';

} elseif ($action === 'users') {
    require ADMIN_INC . '/view_users.php';

} elseif ($action === 'liga_settings' && $ligaSettingsData) {
    require ADMIN_INC . '/view_liga_settings.php';

} elseif ($action === 'settings') {
    require ADMIN_INC . '/view_settings.php';

} elseif ($action === 'wartung') {
    require ADMIN_INC . '/view_wartung.php';

} else {
    // Fallback + Dashboard
    require ADMIN_INC . '/view_liga_list.php';
}

?>
  </div><!-- /#content -->
  </div><!-- /.main -->
</div><!-- /.app-layout -->
</body>
</html>
