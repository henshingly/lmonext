<?php
/**
 * Project: LMOnext
 * Filename: handler_user.php
 * Fileversion: 1.5.2
 * Changelog: 1.5.2 - save_admin_settings speichert jetzt zusätzlich show_pdf_buttons (neue
 *                     Einstellung "PDF-Export für Besucher anzeigen?" im Besucherbereich)
 * Changelog: 1.5.1 - E-Mail-Adresse jetzt auch nachträglich in der Benutzerverwaltung editierbar
 *                     (create_user + edit_user), nicht mehr nur beim Erst-Setup in install.php
 *                     möglich. Validiert per filter_var(FILTER_VALIDATE_EMAIL), leeres Feld
 *                     löscht die hinterlegte Adresse wieder (NULL)
 * Changelog: 1.5.0 - "Passwort vergessen"-Handler ergänzt: request_password_reset (E-Mail mit
 *                     4h gültigem Link verschicken, invalidiert vorherige offene Anfragen
 *                     desselben Users) und do_reset_password (Token prüfen, neues Passwort
 *                     setzen, Token danach verbraucht)
 * Changelog: 1.4.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 1.4.0 - save_admin_settings: aktualisiert nur tatsächlich gesendete Felder (Bugfix, da jetzt
 *                     mehrere Formulare dieselbe Action nutzen); speichert active_template + allow_template_switch
 * Changelog: 1.3.2 - langSessionKey('admin') statt entfernter LANG_SESSION_KEY-Konstante (i18n.php domain-fähig)
 * Changelog: 1.3.1 - save_admin_settings speichert jetzt auch die Standardsprache ("language")
 * Changelog: 1.3.0 - Flash-Meldungen über t() übersetzt
 * Changelog: 1.2.3 - save_admin_settings Handler (Zeitzone)
 * Changelog: 1.2.2 - ensureLastLoginColumn() vor UPDATE aufrufen (neue DB fix)
 * Changelog: 1.2.1 - last_login Zeitstempel beim Login speichern
 * Changelog: 1.2.0 - password_hash Spaltenname -> password
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 */

// ── Benutzer-Handler (Login, Logout, CRUD) ─────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? ''); $pass = $_POST['password'] ?? '';
    try {
        $stmt = getDB()->prepare('SELECT id,password FROM '.tbl('admin_users').' WHERE username=?');
        $stmt->execute([$user]); $row = $stmt->fetch();
        if ($row && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true; $_SESSION['admin_user'] = $user;
            // Letzten Login-Zeitpunkt speichern (Spalte ggf. erst anlegen)
            try {
                ensureLastLoginColumn();
                getDB()->prepare('UPDATE '.tbl('admin_users').' SET last_login=NOW() WHERE id=?')
                       ->execute([$row['id']]);
            } catch (Throwable) {}
            redirect('?action=dashboard');
        }
        flash(t('flash_invalid_credentials'), 'error');
    } catch (Throwable $e) { flash(t('flash_db_error', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=login');
}

if ($action === 'logout') { session_destroy(); redirect('?action=login'); }

// ── Passwort vergessen: E-Mail mit Reset-Link anfordern ───────────────────────
if ($action === 'request_password_reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ensurePasswordResetSchema();
    $email = trim($_POST['reset_email'] ?? '');
    try {
        $db = getDB();
        $userId = null;
        if ($email !== '') {
            $s = $db->prepare('SELECT id FROM '.tbl('admin_users').' WHERE email=? AND email IS NOT NULL AND email <> \'\'');
            $s->execute([$email]);
            $found = $s->fetchColumn();
            $userId = $found !== false ? (int)$found : null;
        }
        if ($userId === null) {
            flash(t('flash_reset_email_not_found'), 'error');
        } else {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 4 * 3600); // 4 Stunden gültig
            // Vorherige offene Reset-Anfragen desselben Users verwerfen, damit
            // immer nur der zuletzt verschickte Link funktioniert.
            $db->prepare('DELETE FROM '.tbl('admin_password_resets').' WHERE user_id=?')->execute([$userId]);
            $db->prepare('INSERT INTO '.tbl('admin_password_resets').' (user_id,token,expires_at) VALUES (?,?,?)')
               ->execute([$userId, $token, $expires]);
            $link = getSiteBaseUrl() . '/admin.php?action=reset_password&token=' . $token;
            sendPasswordResetEmail($email, $link);
            flash(t('flash_reset_email_sent'));
        }
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=login');
}

// ── Passwort vergessen: neues Passwort speichern (Formular von der
// Reset-Landingpage, siehe view_reset_password.php) ──────────────────────────
if ($action === 'do_reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    ensurePasswordResetSchema();
    $token = (string)($_POST['token'] ?? '');
    $new1  = $_POST['new_password']  ?? '';
    $new2  = $_POST['new_password2'] ?? '';
    try {
        $db = getDB();
        $s  = $db->prepare('SELECT user_id FROM '.tbl('admin_password_resets').' WHERE token=? AND expires_at >= NOW()');
        $s->execute([$token]);
        $userId = $s->fetchColumn();
        if ($userId === false) {
            flash(t('flash_reset_token_invalid'), 'error');
            redirect('?action=login');
        }
        if ($new1 !== $new2) {
            flash(t('flash_password_mismatch'), 'error');
            redirect('?action=reset_password&token=' . urlencode($token));
        }
        if (strlen($new1) < 8) {
            flash(t('flash_password_min_length'), 'error');
            redirect('?action=reset_password&token=' . urlencode($token));
        }
        $db->prepare('UPDATE '.tbl('admin_users').' SET `password`=? WHERE id=?')
           ->execute([password_hash($new1, PASSWORD_BCRYPT), (int)$userId]);
        // Token verbraucht -> alle offenen Reset-Anfragen dieses Users löschen
        $db->prepare('DELETE FROM '.tbl('admin_password_resets').' WHERE user_id=?')->execute([(int)$userId]);
        flash(t('flash_reset_password_success'));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=login');
}

if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $cur = $_POST['current_password'] ?? ''; $new1 = $_POST['new_password'] ?? ''; $new2 = $_POST['new_password2'] ?? '';
    if ($new1 !== $new2) { flash(t('flash_password_mismatch'), 'error'); }
    elseif (strlen($new1) < 8) { flash(t('flash_password_min_length'), 'error'); }
    else {
        try {
            $db = getDB();
            $s  = $db->prepare('SELECT password FROM '.tbl('admin_users').' WHERE username=?');
            $s->execute([$_SESSION['admin_user']]); $h = $s->fetchColumn();
            if ($h && password_verify($cur, $h)) {
                $db->prepare('UPDATE '.tbl('admin_users').' SET `password`=? WHERE username=?')
                   ->execute([password_hash($new1, PASSWORD_BCRYPT), $_SESSION['admin_user']]);
                flash(t('flash_password_changed'));
            } else { flash(t('flash_current_password_wrong'), 'error'); }
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=settings');
}

if ($action === 'create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    ensurePasswordResetSchema();
    $u = trim($_POST['new_username'] ?? ''); $p = $_POST['new_user_password'] ?? '';
    $email = trim($_POST['new_user_email'] ?? '');
    if (!$u || strlen($p) < 8) { flash(t('flash_user_password_required'), 'error'); }
    elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash(t('err_adminemail_invalid'), 'error'); }
    else {
        try {
            getDB()->prepare('INSERT INTO '.tbl('admin_users').' (username,`password`,email) VALUES (?,?,?)')
                   ->execute([$u, password_hash($p, PASSWORD_BCRYPT), $email !== '' ? $email : null]);
            flash(t('flash_user_created', ['user' => $u]));
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=users');
}

if ($action === 'edit_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    ensurePasswordResetSchema();
    $id      = (int)($_POST['user_id']      ?? 0);
    $newName = trim($_POST['edit_username'] ?? '');
    $newEmail= trim($_POST['edit_email']    ?? '');
    $newPass = $_POST['edit_password']      ?? '';
    $newPass2= $_POST['edit_password2']     ?? '';
    if ($id <= 0 || $newName === '') { flash(t('flash_invalid_input'), 'error'); redirect('?action=users'); }
    if ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) { flash(t('err_adminemail_invalid'), 'error'); redirect('?action=users'); }
    if ($newPass !== '' && $newPass !== $newPass2) { flash(t('flash_password_mismatch'), 'error'); redirect('?action=users'); }
    if ($newPass !== '' && strlen($newPass) < 8)   { flash(t('flash_password_min_length'), 'error'); redirect('?action=users'); }
    try {
        $db = getDB();
        // Alten Namen holen (für Session-Update)
        $sOld = $db->prepare('SELECT username FROM '.tbl('admin_users').' WHERE id=?');
        $sOld->execute([$id]); $oldName = $sOld->fetchColumn();
        // Prüfen ob neuer Name schon vergeben (durch anderen User)
        $sChk = $db->prepare('SELECT id FROM '.tbl('admin_users').' WHERE username=? AND id!=?');
        $sChk->execute([$newName, $id]);
        if ($sChk->fetchColumn()) { flash(t('flash_username_taken'), 'error'); redirect('?action=users'); }
        $emailVal = $newEmail !== '' ? $newEmail : null;
        if ($newPass !== '') {
            $db->prepare('UPDATE '.tbl('admin_users').' SET username=?, email=?, `password`=? WHERE id=?')
               ->execute([$newName, $emailVal, password_hash($newPass, PASSWORD_BCRYPT), $id]);
        } else {
            $db->prepare('UPDATE '.tbl('admin_users').' SET username=?, email=? WHERE id=?')
               ->execute([$newName, $emailVal, $id]);
        }
        // Session aktualisieren wenn eigener Account umbenannt
        if ($oldName === ($_SESSION['admin_user'] ?? '')) {
            $_SESSION['admin_user'] = $newName;
        }
        flash(t('flash_user_updated', ['user' => $newName]));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=users');
}

if ($action === 'delete_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id > 0) {
        try {
            $db = getDB();
            $s  = $db->prepare('SELECT username FROM '.tbl('admin_users').' WHERE id=?');
            $s->execute([$id]); $u = $s->fetchColumn();
            if ($u === ($_SESSION['admin_user'] ?? '')) { flash(t('flash_own_account_undeletable'), 'error'); }
            else {
                $db->prepare('DELETE FROM '.tbl('admin_users').' WHERE id=?')->execute([$id]);
                flash(t('flash_user_deleted', ['user' => $u]));
            }
        } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    }
    redirect('?action=users');
}


// ── Admin-Systemeinstellungen speichern ───────────────────────────────────────
// Wird von mehreren Formularen genutzt (Systemeinstellungen, Besucherbereich),
// die jeweils nur ihre eigenen Felder senden – daher wird jedes Feld nur
// aktualisiert, wenn es im POST tatsächlich vorhanden ist (sonst würde das
// Speichern des einen Formulars die Werte des anderen auf Default zurücksetzen).
if ($action === 'save_admin_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    try {
        $db = getDB();
        $s  = $db->prepare('INSERT INTO '.tbl('admin_settings').' (`key`,`value`) VALUES (?,?)
            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');

        if (isset($_POST['timezone'])) {
            $timezone = trim($_POST['timezone']);
            try { new DateTimeZone($timezone); } catch (Throwable) { $timezone = 'Europe/Berlin'; }
            $s->execute(['timezone', $timezone]);
        }

        if (isset($_POST['language'])) {
            $language = trim($_POST['language']);
            if (!array_key_exists($language, AVAILABLE_LANGUAGES)) { $language = DEFAULT_LANGUAGE; }
            $s->execute(['language', $language]);
            // Sofort auch für die aktuelle Sitzung übernehmen
            $_SESSION[langSessionKey('admin')] = $language;
        }

        if (isset($_POST['active_template'])) {
            require_once dirname(__DIR__) . '/frontend/template_engine.php';
            $activeTpl = trim($_POST['active_template']);
            if (!array_key_exists($activeTpl, getAvailableTemplates())) { $activeTpl = DEFAULT_TEMPLATE; }
            $s->execute(['active_template', $activeTpl]);
        }

        if (isset($_POST['allow_template_switch'])) {
            $s->execute(['allow_template_switch', $_POST['allow_template_switch'] === '1' ? '1' : '0']);
        }

        if (isset($_POST['show_pdf_buttons'])) {
            $s->execute(['show_pdf_buttons', $_POST['show_pdf_buttons'] === '1' ? '1' : '0']);
        }

        flash(t('flash_settings_saved'));
    } catch (Throwable $e) { flash(t('flash_error_prefix', ['msg' => $e->getMessage()]), 'error'); }
    redirect('?action=settings');
}
