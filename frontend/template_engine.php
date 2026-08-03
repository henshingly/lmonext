<?php
/**
 * Project: LMOnext
 * Filename: template_engine.php
 * Fileversion: 2.6.0
 * Changelog: 2.6.0 - Neuer automatischer Platzhalter "TippspielLink" (analog zu
 *                     "Sprachauswahl"): renderTemplate() ruft tippRenderSiteLink() auf, das
 *                     selbst prüft ob das Tippspiel aktiv ist (tippIstAktiv()) und ggf. leer
 *                     bleibt - Controller/Templates müssen nichts Zusätzliches tun
 * Changelog: 2.5.1 - Neue globale Einstellung "Sprachauswahl anzeigen?" ausgewertet: die
 *                     Sprachauswahl im Footer/Header wird unterdrückt, wenn deaktiviert – gilt
 *                     zentral für alle Templates und alle Seiten (renderTemplate() wird sowohl
 *                     von home.php als auch liga.php genutzt)
 * Changelog: 2.5.0
 * Changelog: 2.5.0 - Template-Auswahl-Dropdown vom Header in den Footer verschoben: steht jetzt
 *                     direkt in der "Template: ..."-Zeile anstelle des Klartext-Namens (nur wenn
 *                     der Wechsel erlaubt ist und mehr als ein Template existiert – sonst wie
 *                     gehabt reiner Klartext). Der separate Header-Platzhalter "Vorlagenauswahl"
 *                     entfällt dadurch wieder
 * Changelog: 2.4.0 - Neue Funktion renderTemplateSwitcher(): sichtbares Dropdown, mit dem
 *                     Besucher (falls in den Einstellungen erlaubt) zwischen den vorhandenen
 *                     Templates wechseln können. Die Einstellung "Besucher erlauben, Template
 *                     zu wechseln" schaltete bisher nur den ?template=xxx-URL-Parameter frei,
 *                     ohne dass es dafür je eine sichtbare Bedienmöglichkeit für Besucher gab.
 *                     Neuer automatisch befüllter Platzhalter "Vorlagenauswahl" (analog zu
 *                     "Sprachauswahl"), erscheint nur bei mehr als einem verfügbaren Template
 * Changelog: 2.3.0 - renderTemplate() ergänzt zusätzlich Platzhalter "TemplateZeile" (zeigt den
 *                     Namen des aktiven Templates im Footer, z.B. "Template: Default")
 * Changelog: 2.2.0 - renderTemplate() ergänzt Platzhalter "Version" automatisch (aus composer.json,
 *                     für den Footer "LMOnext {Version}")
 * Changelog: 2.1.2 - Interne Bezeichner (TEMPLATE_SESSION_KEY, globale Variable) von "olv_" auf
 *                     "lmonext_" umgestellt
 * Changelog: 2.1.1 - Projektname auf "LMOnext" umgestellt (vorher "Online-Liga-Verwaltung Board" / "OLVBoard")
 * Changelog: 2.1.0 - renderTemplate() ergänzt Platzhalter "Berechnungszeit" automatisch
 *                     (Dauer Berechnungen u. Seitenaufbau, wie im alten LMO-Footer)
 * Changelog: 2.0.0 - Umbau auf reine Platzhalter-Templates (wie das alte LMO mit
 *                     HTML_Template_IT/<!--Platzhalter-->): Templates sind jetzt reine
 *                     .tpl.php-Dateien ohne jegliches PHP – nur Markup + <!--Platzhalter-->.
 *                     Alle Logik (Schleifen, Bedingungen, Datenaufbereitung) lebt in
 *                     frontend/*.php ("Grundgerüst"), das fertige HTML-Fragmente an die
 *                     Templates übergibt. Neu: renderPartial() für wiederkehrende Bausteine
 *                     (Tabellenzeile, Ordnereintrag, Dropdown-Option, …), jeweils eine eigene
 *                     .tpl.php-Datei unter template/<name>/partials/.
 * Changelog: 1.0.0 - Initiale Version (PHP-Include-basiert, überholt)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 *
 * ── Funktionsweise (angelehnt an das alte LMO) ───────────────────────────────
 * Ein Template ist ein Ordner unter /template/<name>/ mit:
 *   - template.json         Metadaten (Name, Beschreibung) – für die Admin-Auswahl
 *   - layout.tpl.php         HTML-Grundgerüst der ganzen Seite (<html>...<!--Hauptteil-->...)
 *   - <seite>.tpl.php        Seiteninhalt (z.B. home.tpl.php, liga.tpl.php) – wird in
 *                            "Hauptteil" von layout.tpl.php eingesetzt
 *   - partials/<name>.tpl.php  Wiederkehrende Bausteine (Tabellenzeile, Dropdown-Option, …)
 *
 * ALLE dieser Dateien enthalten AUSSCHLIESSLICH HTML/CSS und Platzhalter der Form
 * <!--Platzhalter--> – kein einziges <?php ?>-Tag. Die komplette Logik (Schleifen,
 * Bedingungen, DB-Zugriffe) steckt in frontend/bootstrap.php, frontend/data_home.php,
 * frontend/data_liga.php sowie den Root-Controllern (home.php, liga.php). Diese bauen
 * fertige HTML-Strings (auch aus mehreren renderPartial()-Aufrufen zusammengesetzt) und
 * übergeben sie als Platzhalter-Werte an renderTemplate().
 *
 * Ein neues Template erstellen:
 *   1. Ordner /template/<neuer-name>/ anlegen
 *   2. template.json mit {"name": "...", "description": "..."} anlegen
 *   3. layout.tpl.php, <seite>.tpl.php und partials/*.tpl.php nach Vorbild von
 *      template/default/ anlegen – reines Markup, gleiche Platzhalternamen
 *   4. Fertig – taucht automatisch in der Admin-Dropdown-Liste auf.
 */
declare(strict_types = 1);

const TEMPLATE_DIR         = __DIR__ . '/../template';
const TEMPLATE_SESSION_KEY = 'lmonext_template';
const DEFAULT_TEMPLATE     = 'default';

/**
 * Liefert alle gefundenen Templates: ['ordnername' => ['name'=>..,'description'=>..]]
 */
function getAvailableTemplates() : array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $result = [];
    foreach (glob(TEMPLATE_DIR . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $jsonFile = $dir . '/template.json';
        if (!is_file($jsonFile)) {
            continue;
        }
        $meta = json_decode((string)file_get_contents($jsonFile), true);
        if (!is_array($meta)) {
            continue;
        }
        $key = basename($dir);
        $result[$key] = [
            'name'        => $meta['name'] ?? $key,
            'description' => $meta['description'] ?? '',
        ];
    }
    if (empty($result)) {
        // Absoluter Fallback, falls kein Template gefunden wird (z.B. Ordner fehlt)
        $result[DEFAULT_TEMPLATE] = ['name' => 'Default', 'description' => ''];
    }
    return $cache = $result;
}

/**
 * Ermittelt das aktuell aktive Template für den Besucher.
 * Reihenfolge: expliziter Wechsel per ?template=xxx (nur wenn erlaubt) →
 * bereits gewähltes Template in der Session (nur wenn weiterhin erlaubt) →
 * Admin-Standard → absoluter Fallback.
 *
 * Kann bei ?template=xxx redirecten (muss also vor jeglichem Output aufgerufen werden).
 * Merkt sich das Ergebnis zusätzlich intern (siehe getActiveTemplateName()), damit
 * renderPartial() nicht bei jedem Aufruf den Namen mitgereicht bekommen muss.
 */
function resolveActiveTemplate(string $configuredDefault, bool $allowSwitch) : string
{
    $available = getAvailableTemplates();
    $result    = DEFAULT_TEMPLATE;

    if ($allowSwitch && isset($_GET['template']) && array_key_exists($_GET['template'], $available)) {
        $_SESSION[TEMPLATE_SESSION_KEY] = $_GET['template'];

        $qs = $_GET;
        unset($qs['template']);
        $self   = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
        $target = $self . (empty($qs) ? '' : ('?' . http_build_query($qs)));
        header('Location: ' . $target);
        exit;
    }

    if ($allowSwitch && isset($_SESSION[TEMPLATE_SESSION_KEY]) && array_key_exists($_SESSION[TEMPLATE_SESSION_KEY], $available)) {
        $result = $_SESSION[TEMPLATE_SESSION_KEY];
    } elseif (array_key_exists($configuredDefault, $available)) {
        $result = $configuredDefault;
    }

    setActiveTemplateName($result);
    return $result;
}

/** Merkt sich den Namen des aktiven Templates für renderPartial(). */
function setActiveTemplateName(string $name) : void
{
    $GLOBALS['__lmonext_active_template'] = $name;
}

/** Liefert den Namen des aktiven Templates (siehe setActiveTemplateName()). */
function getActiveTemplateName() : string
{
    return $GLOBALS['__lmonext_active_template'] ?? DEFAULT_TEMPLATE;
}

/**
 * Ersetzt Platzhalter der Form <!--Name--> im übergebenen HTML durch die
 * Werte aus $vars (Schlüssel = Platzhaltername ohne die Kommentar-Klammern).
 */
function substitutePlaceholders(string $html, array $vars) : string
{
    $search  = [];
    $replace = [];
    foreach ($vars as $key => $value) {
        $search[]  = '<!--' . $key . '-->';
        $replace[] = (string)$value;
    }
    return str_replace($search, $replace, $html);
}

/**
 * Lädt eine einzelne .tpl.php-Datei aus dem aktiven Template und ersetzt ihre
 * Platzhalter. Gibt einen leeren String zurück, falls die Datei nicht existiert.
 */
function loadTemplateFile(string $relativePath, array $vars) : string
{
    $dir  = TEMPLATE_DIR . '/' . getActiveTemplateName();
    $file = $dir . '/' . $relativePath;
    if (!is_file($file)) {
        $file = TEMPLATE_DIR . '/' . DEFAULT_TEMPLATE . '/' . $relativePath;
    }
    if (!is_file($file)) {
        return '';
    }
    return substitutePlaceholders((string)file_get_contents($file), $vars);
}

/**
 * Rendert einen wiederkehrenden Baustein (z.B. eine Tabellenzeile, einen
 * Dropdown-Eintrag, einen Archiv-Ordner) über template/<aktiv>/partials/<name>.tpl.php.
 * Wird typischerweise in einer Schleife im Grundgerüst (frontend/*.php) aufgerufen,
 * die die Ergebnis-Strings aneinanderhängt.
 */
function renderPartial(string $partialName, array $vars = []) : string
{
    return loadTemplateFile('partials/' . $partialName . '.tpl.php', $vars);
}

/**
 * Rendert eine ganze Seite: füllt zuerst template/<aktiv>/<page>.tpl.php mit den
 * übergebenen Platzhaltern, setzt das Ergebnis dann als "Hauptteil" in
 * template/<aktiv>/layout.tpl.php ein und gibt das fertige HTML aus.
 * "HtmlLang", "Sprachauswahl" und "Berechnungszeit" werden automatisch ergänzt
 * (wie im alten LMO zentral im Grundgerüst gesetzt) – Controller müssen sie
 * nicht selbst befüllen. "Berechnungszeit" nutzt PHPs eingebautes
 * REQUEST_TIME_FLOAT und wird bewusst als Letztes berechnet, damit die
 * Anzeige möglichst die tatsächliche Gesamtdauer widerspiegelt.
 */
/**
 * Baut das Dropdown, mit dem Besucher (falls in den Einstellungen erlaubt)
 * zwischen den vorhandenen Templates wechseln können – vom Aufbau her
 * bewusst analog zu renderLanguageSwitcher() (auto-submitting <form>, alle
 * übrigen GET-Parameter werden als Hidden-Felder mitgeführt). Gibt einen
 * leeren String zurück, wenn der Wechsel nicht erlaubt ist oder es ohnehin
 * nur ein einziges Template gibt (dann gäbe es nichts zur Auswahl).
 */
function renderTemplateSwitcher(bool $allowSwitch) : string
{
    $available = getAvailableTemplates();
    if (!$allowSwitch || count($available) < 2) {
        return '';
    }
    $current = getActiveTemplateName();
    $esc = static fn(string $v) : string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $html = '<form method="get" class="template-switch" onchange="this.submit()">';
    foreach ($_GET as $k => $v) {
        if ($k === 'template' || !is_scalar($v)) {
            continue;
        }
        $html .= '<input type="hidden" name="' . $esc((string)$k) . '" value="' . $esc((string)$v) . '">';
    }
    $html .= '<select name="template" aria-label="' . $esc(tf('template_switch_label')) . '">';
    foreach ($available as $key => $meta) {
        $sel = $key === $current ? ' selected' : '';
        $html .= '<option value="' . $esc($key) . '"' . $sel . '>' . $esc($meta['name']) . '</option>';
    }
    $html .= '</select></form>';
    return $html;
}

function renderTemplate(string $activeTemplate, string $page, array $vars = []) : void
{
    setActiveTemplateName($activeTemplate);
    $available     = getAvailableTemplates();
    $templateLabel = $available[$activeTemplate]['name'] ?? $activeTemplate;

    // Ist der Wechsel erlaubt (und gibt es überhaupt mehr als ein Template),
    // steht im Footer statt des reinen Namens das Auswahl-Dropdown – sonst
    // wie bisher nur Klartext "Template: {name}".
    $switcherHtml = renderTemplateSwitcher((bool)($GLOBALS['allowTemplateSwitch'] ?? false));
    $templateZeile = $switcherHtml !== ''
        ? h(tf('footer_template_prefix')) . ' ' . $switcherHtml
        : h(tf('footer_template', ['name' => $templateLabel]));

    $vars += [
        'HtmlLang'      => h(getCurrentLanguage('frontend')),
        'Sprachauswahl' => getAdminSetting('show_language_switcher', '1') === '1' ? renderLanguageSwitcher('frontend') : '',
        'Version'       => h(getAppVersion()),
        'TemplateZeile' => $templateZeile,
        'TippspielLink' => tippRenderSiteLink(),
    ];
    $pageHtml          = loadTemplateFile($page . '.tpl.php', $vars);
    $vars['Hauptteil'] = $pageHtml;

    $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    $vars['Berechnungszeit'] = h(tf('footer_render_time', [
        'sekunden' => number_format(microtime(true) - $startTime, 4, '.', ''),
    ]));

    echo loadTemplateFile('layout.tpl.php', $vars);
}
