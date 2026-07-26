<?php
/**
 * Project: LMOnext
 * Filename: frontend/pdf_export.php
 * Fileversion: 1.6.7
 * Changelog: 1.6.7 - "Spielfrei: TEAMNAME"-Zeile jetzt auch im PDF-Export der Ergebnisse
 *                     (direkt nach den Ergebniszeilen, vor der Tore-Schnitt-Zeile - gleiche
 *                     Reihenfolge wie in der HTML-Ansicht), siehe liga.php 3.10.2
 * Changelog: 1.6.6
 * Changelog: 1.6.6 - Bugfix + Verbesserung: die Logos im Teamvergleich-Titel ("TeamA Logo vs
 *                     Logo TeamB") wurden immer in der kleinen Zeilen-Logo-Höhe (9.5pt)
 *                     gezeichnet, obwohl der reservierte Platz für 15pt berechnet war
 *                     ($drawTeamLogoAt() ignorierte $vsLogoHeightPt komplett) – dadurch wirkten
 *                     sie kleiner als beabsichtigt. $drawTeamLogoAt() akzeptiert jetzt eine
 *                     optionale Höhen-Override, Titel-Logos sind zusätzlich von 15pt auf 24pt
 *                     vergrößert (deutlich sichtbarer neben dem 17pt-Titeltext)
 * Changelog: 1.6.5
 * Changelog: 1.6.5 - Zwei Bugfixes: (1) pdfEstimateTextWidth() nutzte eine grobe "alle Zeichen
 *                     gleich breit"-Schätzung statt echter Helvetica-Zeichenbreiten (neue
 *                     Konstanten PDF_HELVETICA_WIDTHS/PDF_HELVETICA_BOLD_WIDTHS, aus den
 *                     offiziellen Adobe-AFM-Metriken) – bei Namen mit überdurchschnittlich
 *                     vielen breiten Zeichen (z.B. "FC Bayern München") unterschätzte die alte
 *                     Formel die tatsächliche Breite um mehrere Punkt, wodurch der Text ins
 *                     reservierte Logo-Feld hineinragte. (2) pdfInlineSvgClassStyles()
 *                     entfernt jetzt zusätzlich clip-path-Referenzen aus SVGs – manche
 *                     (insbesondere minimale) SVG-Renderer machen Elemente mit nicht
 *                     auflösbarer clip-path-Referenz komplett unsichtbar statt sie nur
 *                     ungeclippt darzustellen, wodurch z.B. das innere bayerische Rautenmuster
 *                     im FC-Bayern-Logo fehlte
 * Changelog: 1.6.4
 * Changelog: 1.6.4 - Bugfix: Team-Logos, die Füllfarben über CSS-Klassen im <style>-Block
 *                     definieren (übliches Muster bei aus Corel Draw/Illustrator/Inkscape
 *                     exportierten SVGs), erschienen auf manchen Servern als reine schwarze
 *                     Silhouette – der dortige SVG-Renderer (z.B. ImageMagicks eingebauter
 *                     "MSVG"-Delegate ohne echtes librsvg) unterstützt <style>-Klassen nicht
 *                     zuverlässig und fällt auf die SVG-Standardfüllung Schwarz zurück. Neue
 *                     Funktion pdfInlineSvgClassStyles(): schreibt die Füllfarbe VOR der
 *                     Rasterisierung direkt als fill="..."-Attribut ins Element, unabhängig von
 *                     der <style>-Unterstützung des jeweiligen Renderers
 * Changelog: 1.6.3
 * Changelog: 1.6.3 - Bugfix: der reservierte Platz für Logos neben dem Teamnamen war ein
 *                     fester Schätzwert (16pt) statt an der tatsächlichen Logo-Breite bemessen –
 *                     bei breiteren/nicht-quadratischen Logos (z.B. FC Bayern München) reichte
 *                     das nicht, wodurch das Logo mit der Ergebnis-Spalte kollidierte und
 *                     abgeschnitten wirkte. logoReserve wird jetzt aus der tatsächlich
 *                     breitesten geladenen Logo-Datei berechnet (in buildResultsPdf() UND
 *                     buildStandingsPdf())
 * Changelog: 1.6.2
 * Changelog: 1.6.2 - Neuer erster Best-Effort-Weg für SVG-Rasterisierung über die Imagick-
 *                     PHP-Erweiterung (pdfRasterizeSvgViaImagick()), braucht kein shell_exec()
 *                     und ist damit auf abgesichertem Shared-Hosting eher nutzbar als
 *                     rsvg-convert. Reihenfolge: Imagick zuerst, dann rsvg-convert, dann
 *                     Logo überspringen – je nachdem was auf dem jeweiligen Server verfügbar ist
 * Changelog: 1.6.1
 * Changelog: 1.6.1 - SVG-Logos werden jetzt per Best-Effort-Aufruf des externen Tools
 *                     "rsvg-convert" (falls vorhanden) zu PNG gerastert und dann wie ein
 *                     normales PNG eingebettet, statt immer übersprungen zu werden. Fehlt
 *                     shell_exec, GD oder rsvg-convert, bleibt das bisherige Verhalten (Logo
 *                     wird übersprungen, kein Absturz). GD-Rohpixel-Extraktion in eigene
 *                     Funktion pdfGdImageToRaw() ausgelagert (jetzt von PNG/GIF UND vom
 *                     SVG-Rasterisierungsweg gemeinsam genutzt)
 * Changelog: 1.6.0
 * Changelog: 1.6.0 - Team-Logos werden jetzt in allen PDF-Exporten eingebettet, wenn "Logo
 *                     anzeigen" für die Liga aktiv ist (Ergebnisse, Tabelle, Spielplan,
 *                     Teamvergleich) – Reihenfolge/Position spiegelt exakt die HTML-Ansicht.
 *                     Neue Funktionen pdfLoadTeamLogoImage()/pdfLoadTeamLogos(): JPEG wird
 *                     nativ per DCTDecode eingebettet (keine Bildbibliothek nötig), PNG/GIF nur
 *                     wenn GD verfügbar ist (sonst wird das einzelne Logo übersprungen statt
 *                     abzustürzen), SVG kann diese schlanke, selbstgeschriebene PDF-Engine
 *                     mangels Vektor-Renderer grundsätzlich nicht einbetten. assemblePdfBytes()
 *                     verallgemeinert: beliebig viele zusätzliche Bild-XObjects statt nur des
 *                     einen festen LMOnext-Logos. buildResultsPdf()/buildStandingsPdf() zeichnen
 *                     die Logos jetzt vor/nach dem jeweiligen Team-Namen je nach Spalte;
 *                     buildStandingsPdf() kann zusätzlich eine "TeamA Logo vs Logo TeamB"-
 *                     Titelzeile für den Teamvergleich-Export rendern
 * Changelog: 1.5.6
 * Changelog: 1.5.6 - Bugfix: bei Finale + Spiel um Platz 3 war nur die zweite Überschrift
 *                     ("Kleines Finale – Spiel um Platz 3") fett, die erste ("Finale") noch im
 *                     alten gedämpften/normalen Stil des Einzelabschnitt-Falls. Beide
 *                     Überschriften sind jetzt einheitlich fett, sobald es mehr als einen
 *                     Abschnitt gibt; der normale Einzelabschnitt-Fall (z.B. "Spieltag 5 ·
 *                     16.08.2025") bleibt bewusst unverändert im gedämpften Stil
 * Changelog: 1.5.5 - buildResultsPdf() verallgemeinert: nimmt jetzt ein Array von Abschnitten
 *                     entgegen (jeweils eigene Unterüberschrift + Tabelle + Tore-Schnitt-Zeile)
 *                     statt nur einer einzigen Tabelle. exportErgebnissePdf() nimmt passend
 *                     dazu jetzt $sectionSpecs statt Einzelparametern entgegen. Damit zeigt das
 *                     PDF bei Finale + Spiel um Platz 3 zwei getrennte Tabellen mit jeweils
 *                     eigenem Datum, statt beide Begegnungen in einer Tabelle mit einem
 *                     gemeinsamen (falschen) Datumsbereich zusammenzufassen (siehe liga.php für
 *                     den Aufbau der Abschnitte, mirrort die gleiche Bedingung wie die
 *                     HTML-Ansicht)
 * Changelog: 1.5.4 - Tabellenmarkierungen (Champions League/-Qualifikation/Euroleague/
 *                     Relegation/Absteiger/Meister, siehe Admin → Liga-Einstellungen →
 *                     Tabelle) werden jetzt auch im Tabelle-PDF-Export übernommen: neuer
 *                     optionaler $rowBorderColors-Parameter in buildStandingsPdf() zeichnet
 *                     einen 3pt breiten farbigen Rand am linken Zeilenrand, exportTabellePdf()
 *                     berechnet die Farben pro Zeile über dieselbe
 *                     computeStandingsMarkerColor() wie die HTML-Ansicht
 * Changelog: 1.5.3 - Neue Funktion exportH2hPdf(): PDF-Export für den direkten Vergleich zweier
 *                     Teams (Head-to-Head-Modal), teamübergreifend statt an eine Liga gebunden.
 *                     Titel "{TeamA} vs {TeamB}", Untertitel mit Sieg/Unentschieden-Bilanz,
 *                     Tabelle mit Datum/Runde (KO-aware Rundenname via runde_label aus
 *                     getHeadToHeadMatches())/Heim/Ergebnis/Gast. Nutzt dieselbe generische
 *                     Tabellen-Engine wie Tabelle/Spielplan-Export
 * Changelog: 1.5.2 - buildStandingsPdf() verallgemeinert: neuer optionaler $accentColIndex-
 *                     Parameter (vorher fest auf die letzte Spalte/Pkt hartkodiert, jetzt
 *                     abschaltbar), neue Rechtsbündig-Ausrichtung ("right") für Spalten. Neue
 *                     Funktion exportSpielplanPdf(): PDF-Export für den Spielplan eines
 *                     einzelnen Teams (reguläre Ligen), nutzt dieselbe generische Tabellen-
 *                     Engine wie die Tabelle (Standings), Titel ist hier der Teamname
 * Changelog: 1.5.1 - exportErgebnissePdf() nimmt jetzt ein bereits fertig formatiertes
 *                     Runden-Label statt einer Spieltag-Nummer entgegen ("Spieltag N" für
 *                     reguläre Ligen, Rundenname wie "Achtelfinale" für KO-Turniere) – damit
 *                     funktioniert der PDF-Export jetzt auch für KO-Ligen (siehe liga.php)
 * Changelog: 1.5.0 - Neue Funktionen buildStandingsPdf()/exportTabellePdf(): PDF-Export jetzt
 *                     auch für die Tabelle (Standings) regulärer Ligen, mit denselben
 *                     Formatvorgaben wie der Ergebnisse-Export (Logo, Liganame fett/farbig,
 *                     zentrierte Tabelle, Zebra-Streifen, Fußzeile). 9 Spalten (#, Team, Sp, S,
 *                     U, N, Tore, Diff, Pkt), Pkt-Spalte fett/Akzentfarbe wie in der
 *                     HTML-Ansicht (.st-pkt)
 * Changelog: 1.4.0 - Fußzeile ergänzt (zentriert, jede Seite): "© {Jahr}
 *                     www.liga-manager-online.org. Alle Rechte vorbehalten. Version {Version}".
 *                     Jahr über date('Y'), Version über die bereits vorhandene
 *                     getAppVersion() (liest composer.json) – bei mehrseitigen PDFs wird die
 *                     Fußzeile nachträglich an jeden fertigen Seiten-Content-Stream angehängt
 * Changelog: 1.3.2 - Bugfix: Text saß nach dem Ascender-Fix (1.3.1) zu weit oben im Streifen
 *                     statt mittig (Streifen begann direkt an der Textoberkante statt
 *                     symmetrisch Platz oben+unten zu lassen). Per Pixel-Messung neu justiert:
 *                     Streifen jetzt bei Grundlinie+12pt bis Grundlinie-5pt (Höhe weiter 17pt),
 *                     Text dadurch vertikal zentriert im Streifen
 * Changelog: 1.3.1 - Bugfix: Zeilen-Hintergrundstreifen begann nur 4pt über der Grundlinie,
 *                     Großbuchstaben (Cap-Height ca. 7pt bei 9.5pt Schrift) ragten oben über
 *                     den grauen Streifen hinaus. Streifen beginnt jetzt 8pt über der
 *                     Grundlinie (Höhe bleibt bei 17pt, also 9pt darunter statt 13pt)
 * Changelog: 1.3.0 - Ergebnis-Spalte jetzt zentriert (Header + Zeilen); die gesamte Tabelle
 *                     wird horizontal auf der Seite zentriert statt am linken Rand fixiert zu
 *                     sein; jede zweite Zeile bekommt einen leichten hellgrauen Hintergrund
 *                     (Zebra-Streifen) zur besseren Lesbarkeit. Neue Hilfsfunktionen
 *                     addTextCentered() und addRect()
 * Changelog: 1.2.0 - Tabelle nur noch so breit wie der tatsächlich benötigte Inhalt (vorherige
 *                     Version stretchte auf feste, teils zu breite Spaltenpositionen über
 *                     nahezu die volle Seitenbreite); Spaltenbreiten werden jetzt vorab aus der
 *                     längsten Zelle je Spalte geschätzt. Kopfzeilen getauscht: Liganame jetzt
 *                     fett/farbig obendrüber, Spieltag-Angabe normal darunter (vorher umgekehrt)
 * Changelog: 1.1.1 - Heim-Spalte jetzt rechtsbündig (Header + Zeilen), analog zur
 *                     rechtsbündigen .col-heim-Spalte in der HTML-Ergebnistabelle. Neue
 *                     addTextRight()-Hilfsfunktion nutzt die vorhandene
 *                     pdfEstimateTextWidth()-Schätzung, um den Text an einer festen rechten
 *                     Kante enden zu lassen
 * Changelog: 1.1.0 - Überarbeitetes Layout: LMOnext-Logo oben links (eingebettet als
 *                     vorkomprimiertes PNG->Rohbild in assets/pdf/, keine Bildbibliothek zur
 *                     Laufzeit nötig), zentrierter Titel in Akzentfarbe ("Ergebnisse Spieltag
 *                     N"), Liganame als Untertitel, Ergebnis jetzt im "H - G"-Format wie im
 *                     Referenzbeispiel, Tore-Schnitt-Zeile am Fuß des Dokuments ergänzt
 *                     (gleiche Werte wie computeSpieltagStats() auf der HTML-Seite).
 *                     Teamicons bewusst noch nicht eingebunden (folgt später)
 * Changelog: 1.0.1 - Bugfix: mb_strlen()/mb_substr() durch strlen()/substr() ersetzt, da
 *                     mbstring auf manchem Shared-Hosting fehlen kann. Kürzung passiert jetzt
 *                     erst NACH der Umwandlung nach CP1252 (Single-Byte), damit strlen() wieder
 *                     der sichtbaren Zeichenzahl entspricht; komplett ohne mbstring-Abhängigkeit
 * Changelog: 1.0.0 - Initiale Version: minimaler, abhängigkeitsfreier PDF-Generator (kein
 *                     Composer-Paket nötig) für den "Ergebnisse als PDF"-Export bei regulären
 *                     (Round-Robin-)Ligen. Baut das PDF-Dateiformat direkt in reinem PHP
 *                     zusammen (Catalog/Pages/Page/Contents-Objekte + xref/trailer von Hand),
 *                     Text über die PDF-Kernschriften Helvetica/Helvetica-Bold mit
 *                     WinAnsiEncoding (deckt deutsche Umlaute/ß ab)
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types = 1);

/**
 * Wandelt einen UTF-8-String in die Zielkodierung der PDF-Kernschriften
 * (WinAnsiEncoding/CP1252) um. Nach dieser Umwandlung ist die Kodierung
 * Single-Byte, d.h. strlen() entspricht wieder der sichtbaren Zeichenzahl –
 * das braucht der Rest dieser Datei (Kürzung/Escaping), ohne auf die
 * mbstring-Extension angewiesen zu sein, die auf manchen Shared-Hosting-
 * Umgebungen fehlen kann.
 */
function pdfConvertEncoding(string $s) : string
{
    $converted = false;
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
    }
    if ($converted === false && function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($s, 'CP1252', 'UTF-8');
    }
    if ($converted === false) {
        // Letzter Ausweg: Rohbytes durchreichen (Umlaute können dann falsch
        // erscheinen, ist aber besser als ein Fataler Fehler ohne PDF).
        $converted = $s;
    }

    return $converted;
}

/**
 * Escaped Klammern/Backslash, wie es das PDF-Stringformat "(...)" verlangt.
 * Erwartet bereits auf CP1252 umgewandelten Text (siehe pdfConvertEncoding()).
 */
function pdfEscapeText(string $s) : string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
}

/**
 * Kürzt einen (bereits auf CP1252 umgewandelten) Text bei Bedarf hart auf
 * $max Zeichen samt Auslassungspunkt, damit sehr lange Teamnamen im PDF
 * nicht über die nächste Spalte hinauslaufen. Arbeitet bewusst mit
 * strlen()/substr() statt mbstring-Funktionen, da CP1252 Single-Byte ist.
 */
function pdfTruncate(string $s, int $max) : string
{
    if ($max <= 0 || strlen($s) <= $max) {
        return $s;
    }
    return substr($s, 0, $max - 1) . "\x85"; // 0x85 = Auslassungspunkt "…" in CP1252
}

/**
 * Sehr grobe Schätzung der Textbreite in PDF-Einheiten (1/1000 em), um einen
 * Titel näherungsweise zentrieren zu können, ohne die volle AFM-Breitentabelle
 * der Kernschrift mitschleppen zu müssen. Für die kurzen, meist gemischten
 * Überschriften hier (Ligentitel, "Ergebnisse Spieltag N") reicht ein fixer
 * Durchschnittsfaktor pro Zeichen völlig aus.
 */
/**
 * Standard-Zeichenbreiten für Helvetica/Helvetica-Bold (1/1000 em, aus den
 * offiziellen Adobe-AFM-Metriken der 14 PDF-Basisschriften). Eine reine
 * "alle Zeichen gleich breit"-Schätzung (wie zuvor) unterschätzt Namen mit
 * überdurchschnittlich vielen breiten Zeichen (z.B. "FC Bayern München" mit
 * mehreren M/B/y) spürbar, was zu Kollisionen mit nachfolgenden Elementen
 * (z.B. dem Team-Logo) führen kann, weil die reservierte Spaltenbreite dann
 * zu knapp bemessen ist. Deckt WinAnsiEncoding-Grundbereich + deutsche
 * Umlaute ab; unbekannte Zeichen fallen auf eine mittlere Standardbreite
 * zurück statt das PDF fehlschlagen zu lassen.
 */
const PDF_HELVETICA_WIDTHS = [
    ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, "'" => 191,
    '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
    '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
    ':' => 278, ';' => 278, '<' => 584, '=' => 584, '>' => 584, '?' => 556, '@' => 1015,
    'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278, 'J' => 500,
    'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611,
    'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611,
    '[' => 278, '\\' => 278, ']' => 278, '^' => 469, '_' => 556, '`' => 333,
    'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556, 'h' => 556, 'i' => 222, 'j' => 222,
    'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556, 'p' => 556, 'q' => 556, 'r' => 333, 's' => 500, 't' => 278,
    'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500, 'z' => 500,
    '{' => 334, '|' => 260, '}' => 334, '~' => 584,
];
const PDF_HELVETICA_BOLD_WIDTHS = [
    ' ' => 278, '!' => 333, '"' => 474, '#' => 556, '$' => 556, '%' => 889, '&' => 722, "'" => 238,
    '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
    '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556, '8' => 556, '9' => 556,
    ':' => 333, ';' => 333, '<' => 584, '=' => 584, '>' => 584, '?' => 611, '@' => 975,
    'A' => 722, 'B' => 722, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278, 'J' => 556,
    'K' => 722, 'L' => 611, 'M' => 889, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611,
    'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611,
    '[' => 333, '\\' => 278, ']' => 333, '^' => 584, '_' => 556, '`' => 333,
    'a' => 556, 'b' => 611, 'c' => 556, 'd' => 611, 'e' => 556, 'f' => 333, 'g' => 611, 'h' => 611, 'i' => 278, 'j' => 278,
    'k' => 556, 'l' => 278, 'm' => 889, 'n' => 611, 'o' => 611, 'p' => 611, 'q' => 611, 'r' => 389, 's' => 556, 't' => 333,
    'u' => 611, 'v' => 556, 'w' => 778, 'x' => 556, 'y' => 556, 'z' => 500,
    '{' => 389, '|' => 280, '}' => 389, '~' => 584,
];

/**
 * Ergänzt die Zeichenbreiten-Tabellen um Zeichen aus dem oberen
 * CP1252-Bereich (deutsche Umlaute u.ä.) – als Einzel-Bytes über chr(),
 * NICHT als UTF-8-Literal in der PHP-Quelldatei. pdfEstimateTextWidth()
 * bekommt den Text bereits über pdfConvertEncoding() nach CP1252
 * umgewandelt und liest ihn byteweise; ein UTF-8-Zeichen wie 'ü' wäre dort
 * als Schlüssel (2 Bytes) nie zu einem einzelnen CP1252-Byte (0xFC) passend.
 */
function pdfHelveticaWidthsWithLatin1(array $base, bool $bold) : array
{
    $extra = $bold
        ? [0xC4 => 722, 0xD6 => 778, 0xDC => 722, 0xE4 => 611, 0xF6 => 611, 0xFC => 611, 0xDF => 611, 0xE9 => 556, 0xE8 => 556, 0xA7 => 556]
        : [0xC4 => 667, 0xD6 => 778, 0xDC => 722, 0xE4 => 556, 0xF6 => 556, 0xFC => 556, 0xDF => 611, 0xE9 => 556, 0xE8 => 556, 0xA7 => 556];
    foreach ($extra as $byte => $width) {
        $base[chr($byte)] = $width;
    }
    return $base;
}

function pdfEstimateTextWidth(string $s, float $size, bool $bold) : float
{
    static $tables = null;
    if ($tables === null) {
        $tables = [
            false => pdfHelveticaWidthsWithLatin1(PDF_HELVETICA_WIDTHS, false),
            true  => pdfHelveticaWidthsWithLatin1(PDF_HELVETICA_BOLD_WIDTHS, true),
        ];
    }
    $table = $tables[$bold];
    $defaultWidth = $bold ? 611 : 556; // Mittelwert für nicht gelistete Zeichen
    $units = 0;
    $len = strlen($s); // pdfConvertEncoding() liefert bereits Single-Byte (CP1252), byteweise iterieren ist hier korrekt
    for ($i = 0; $i < $len; $i++) {
        $ch = $s[$i];
        $units += $table[$ch] ?? $defaultWidth;
    }
    return $units / 1000 * $size;
}

/**
 * Lädt die vorbereiteten LMOnext-Logo-Rohdaten für die PDF-Einbettung (siehe
 * assets/pdf/logo_rgb.zz + logo_alpha.zz). Diese Dateien enthalten das Logo
 * bereits als rohe RGB- bzw. Graustufen-Pixel (aus assets/logo.svg gerendert),
 * jeweils mit gzcompress() (Flate) vorkomprimiert – zur Laufzeit ist dafür
 * keinerlei Bildbibliothek (GD o.ä.) nötig, nur ein einfaches file_get_contents().
 * Gibt null zurück, wenn die Dateien fehlen, statt das PDF fatal scheitern zu lassen.
 *
 * @return array{widthPx:int,heightPx:int,rgbZlib:string,alphaZlib:string}|null
 */
function pdfLoadLogoData() : ?array
{
    $dir = dirname(__DIR__) . '/assets/pdf';
    $rgbPath   = $dir . '/logo_rgb.zz';
    $alphaPath = $dir . '/logo_alpha.zz';
    if (!is_file($rgbPath) || !is_file($alphaPath)) {
        return null;
    }
    $rgb   = @file_get_contents($rgbPath);
    $alpha = @file_get_contents($alphaPath);
    if ($rgb === false || $alpha === false) {
        return null;
    }

    return [
        'widthPx'   => 320,
        'heightPx'  => 78,
        'rgbZlib'   => $rgb,
        'alphaZlib' => $alpha,
    ];
}

/**
 * Lädt ein Team-Logo (siehe assets/img/teams/{id}.{ext}) zur Laufzeit für die
 * PDF-Einbettung. Anders als pdfLoadLogoData() (fest vorbereitete Rohdaten
 * für das LMOnext-Logo) müssen Team-Logos zur Laufzeit gelesen werden, da
 * sie vom Admin hochgeladen werden und Format/Inhalt vorher nicht bekannt sind.
 *
 * - JPEG: wird unverändert als DCTDecode-Bildstream eingebettet (keine
 *   Bildbibliothek nötig, funktioniert auf jedem Hosting)
 * - PNG/GIF: wird über GD in rohe RGB+Alpha-Pixel zerlegt (gleiche Technik
 *   wie beim LMOnext-Logo), NUR wenn die GD-Erweiterung verfügbar ist – sonst
 *   wird das Logo für diese eine Datei übersprungen (Team erscheint im PDF
 *   nur mit Namen, kein Absturz)
 * - SVG: kann diese schlanke, von Grund auf selbst geschriebene PDF-Engine
 *   nicht selbst als Vektorgrafik rendern (kein Renderer vorhanden). Als
 *   Best-Effort-Zusatzweg wird zuerst versucht, über die Imagick-PHP-
 *   Erweiterung zu rastern (falls installiert und mit SVG-Unterstützung
 *   gebaut – kein shell_exec() nötig), danach über das externe
 *   Kommandozeilenwerkzeug "rsvg-convert" (Teil von librsvg2-bin, auf
 *   vielen Linux-Servern bereits vorhanden). Ist beides nicht verfügbar
 *   (z.B. auf eingeschränktem Shared-Hosting), wird das Logo wie bisher
 *   übersprungen (kein Absturz) – siehe pdfRasterizeSvgViaImagick()/
 *   pdfRasterizeSvgViaRsvgConvert()
 *
 * @return array{mode:string,widthPx:int,heightPx:int,jpegData?:string,rgbZlib?:string,alphaZlib?:string}|null
 */
function pdfLoadTeamLogoImage(string $relativePath) : ?array
{
    $absPath = dirname(__DIR__) . '/' . $relativePath;
    if (!is_file($absPath)) {
        return null;
    }
    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));

    if ($ext === 'jpg' || $ext === 'jpeg') {
        $info = @getimagesize($absPath);
        if ($info === false || (int)($info[2] ?? 0) !== IMAGETYPE_JPEG) {
            return null; // Inhalt stimmt nicht mit der Endung überein
        }
        $data = @file_get_contents($absPath);
        if ($data === false) {
            return null;
        }
        return ['mode' => 'jpeg', 'widthPx' => $info[0], 'heightPx' => $info[1], 'jpegData' => $data];
    }

    if ($ext === 'png' || $ext === 'gif') {
        if (!function_exists('imagecreatefromstring')) {
            return null; // keine GD-Erweiterung auf diesem Server -> Logo weglassen statt abzustürzen
        }
        $raw = @file_get_contents($absPath);
        if ($raw === false) {
            return null;
        }
        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return null;
        }
        return pdfGdImageToRaw($img);
    }

    if ($ext === 'svg') {
        $svgContent = @file_get_contents($absPath);
        if ($svgContent === false) {
            return null;
        }
        $inlined = pdfInlineSvgClassStyles($svgContent);
        $tmpSvg  = @tempnam(sys_get_temp_dir(), 'lmosvgpre') . '.svg';
        if (@file_put_contents($tmpSvg, $inlined) === false) {
            return null;
        }
        $result = pdfRasterizeSvgViaImagick($tmpSvg) ?? pdfRasterizeSvgViaRsvgConvert($tmpSvg);
        @unlink($tmpSvg);
        return $result;
    }

    return null; // unbekannte Endung -> nicht unterstützt
}

/**
 * Viele SVG-Exporte (Corel Draw, Illustrator, Inkscape) legen Füllfarben
 * nicht direkt als fill="..."-Attribut am Element ab, sondern über
 * CSS-Klassen in einem <style>-Block (z.B. ".fil0{fill:#1D9053}" +
 * class="fil0" am <path>). Nicht jeder SVG-Renderer (insbesondere
 * ImageMagicks eingebauter, minimaler "MSVG"-Delegate ohne echtes librsvg)
 * unterstützt diese <style>-Klassen zuverlässig – Elemente ohne erkannte
 * Füllfarbe fallen dann auf den SVG-Standard "schwarz" zurück, wodurch das
 * ganze Logo als einfarbige schwarze Silhouette erscheint.
 *
 * Diese Funktion schreibt die Füllfarbe stattdessen VORHER direkt als
 * fill="..."-Attribut in jedes betroffene Element, damit die Farbe
 * unabhängig von der <style>-Unterstützung des jeweiligen Renderers
 * ankommt. Versteht nur das einfache, in der Praxis übliche Muster
 * ".klasse{fill:wert}" – kein vollständiger CSS-Parser, aber ausreichend
 * für die üblichen Vektor-Export-Muster.
 */
function pdfInlineSvgClassStyles(string $svg) : string
{
    $classFills = [];
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $svg, $styleBlocks)) {
        foreach ($styleBlocks[1] as $css) {
            if (preg_match_all('/\.([\w-]+)\s*\{([^}]*)\}/s', $css, $rules, PREG_SET_ORDER)) {
                foreach ($rules as $rule) {
                    if (preg_match('/fill\s*:\s*([^;]+?)\s*(?:;|$)/i', $rule[2], $m)) {
                        $classFills[$rule[1]] = trim($m[1]);
                    }
                }
            }
        }
    }

    if (!empty($classFills)) {
        $svg = preg_replace_callback(
            '/<(path|polygon|circle|ellipse|rect|polyline)\b([^>]*?)(\/?)>/i',
            static function (array $m) use ($classFills) : string {
                [, $tag, $attrs, $selfClose] = $m;
                if (preg_match('/\bfill\s*=/i', $attrs) || preg_match('/\bstyle\s*=\s*"[^"]*fill\s*:/i', $attrs)) {
                    return $m[0]; // hat schon eine eigene Füllfarbe -> nicht anfassen
                }
                if (!preg_match('/\bclass\s*=\s*"([^"]*)"/i', $attrs, $cm)) {
                    return $m[0]; // keine Klasse -> nichts zu ergänzen
                }
                foreach (preg_split('/\s+/', trim($cm[1])) as $cls) {
                    if (isset($classFills[$cls])) {
                        return '<' . $tag . $attrs . ' fill="' . htmlspecialchars($classFills[$cls], ENT_QUOTES) . '"' . $selfClose . '>';
                    }
                }
                return $m[0];
            },
            $svg
        );
    }

    // clip-path-Referenzen entfernen: ein weiteres SVG-Feature, das nicht
    // jeder (insbesondere minimale) Renderer zuverlässig unterstützt. Anders
    // als bei fehlenden Füllfarben (die auf Schwarz zurückfallen) machen
    // manche Renderer ein Element mit einer nicht auflösbaren clip-path-
    // Referenz komplett UNSICHTBAR statt es nur ungeclippt darzustellen –
    // das betroffene Element/Muster fehlt dann vollständig im PDF (z.B. das
    // innere bayerische Rautenmuster im FC-Bayern-Logo). Ein Element ohne
    // Beschneidung darzustellen (ggf. mit leichtem Überstand über die
    // eigentliche Kontur hinaus) ist optisch fast immer besser als ein
    // komplett fehlendes Element.
    $svg = preg_replace('/\s*clip-path\s*=\s*"[^"]*"/i', '', $svg);
    $svg = preg_replace_callback(
        '/(<style[^>]*>)(.*?)(<\/style>)/is',
        static fn(array $m) : string => $m[1] . preg_replace('/\.[\w-]+\s*\{\s*clip-path\s*:[^}]*\}/i', '', $m[2]) . $m[3],
        $svg
    );

    return $svg;
}

/**
 * Zerlegt ein bereits geladenes GD-Bild (imagecreatefrom...()) in rohe
 * RGB+Alpha-Pixeldaten für die PDF-Einbettung (gemeinsame Hilfsfunktion für
 * PNG/GIF und für per rsvg-convert gerasterte SVGs). Zerstört das
 * GD-Bild-Handle am Ende.
 */
function pdfGdImageToRaw($img) : ?array
{
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= 0 || $h <= 0 || $w * $h > 4_000_000) {
        imagedestroy($img);
        return null; // unplausibel/zu groß -> lieber weglassen als eine sehr große PDF-Datei zu bauen
    }
    imagesavealpha($img, true);
    $rgb = '';
    $alpha = '';
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($img, $x, $y);
            $a = ($c >> 24) & 0x7F; // GD: 0 = deckend, 127 = komplett transparent
            $rgb   .= chr(($c >> 16) & 0xFF) . chr(($c >> 8) & 0xFF) . chr($c & 0xFF);
            $alpha .= chr((int)round((127 - $a) / 127 * 255));
        }
    }
    imagedestroy($img);
    return [
        'mode'      => 'raw',
        'widthPx'   => $w,
        'heightPx'  => $h,
        'rgbZlib'   => gzcompress($rgb, 9),
        'alphaZlib' => gzcompress($alpha, 9),
    ];
}

/**
 * Versucht, ein SVG-Logo für die PDF-Einbettung zu rastern, indem das
 * externe Kommandozeilenwerkzeug "rsvg-convert" aufgerufen wird (Teil von
 * librsvg2-bin, auf vielen Linux-Servern bereits vorhanden, da es eine
 * verbreitete Abhängigkeit anderer Software ist). Das ist ein
 * Best-Effort-Zusatzweg: fehlt entweder shell_exec (auf Shared-Hosting aus
 * Sicherheitsgründen oft deaktiviert), die GD-Erweiterung, oder ist
 * rsvg-convert schlicht nicht installiert, wird sauber null zurückgegeben –
 * das Logo fehlt dann im PDF (wie bisher), es gibt aber keinen Fehler.
 */
/**
 * Versucht, ein SVG-Logo über die Imagick-PHP-Erweiterung zu rastern (falls
 * installiert UND mit SVG-Unterstützung gebaut – das ist von Server zu
 * Server unterschiedlich, viele ImageMagick-Pakete bringen das über die
 * "librsvg"- oder eingebaute "MSVG"-Delegate mit). Anders als
 * pdfRasterizeSvgViaRsvgConvert() braucht dieser Weg kein shell_exec()
 * (Imagick ist eine normale PHP-Erweiterung, kein externes Kommandozeilen-
 * Tool) – das macht ihn auf abgesichertem Shared-Hosting eher nutzbar.
 * Best-Effort: schlägt der Aufruf fehl (Erweiterung fehlt, kein SVG-Delegate
 * vorhanden, o.ä.), wird sauber null zurückgegeben statt eines Fehlers.
 */
function pdfRasterizeSvgViaImagick(string $absPath) : ?array
{
    if (!class_exists('Imagick') || !function_exists('imagecreatefromstring')) {
        return null;
    }
    try {
        $im = new Imagick();
        $im->setBackgroundColor(new ImagickPixel('transparent'));
        $im->readImage($absPath);
        $im->setImageFormat('png32');
        $blob = $im->getImageBlob();
        $im->clear();
        $im->destroy();
    } catch (Throwable) {
        return null;
    }
    $img = @imagecreatefromstring($blob);
    if ($img === false) {
        return null;
    }
    return pdfGdImageToRaw($img);
}

function pdfRasterizeSvgViaRsvgConvert(string $absPath) : ?array
{
    if (!function_exists('shell_exec') || !function_exists('imagecreatefromstring')) {
        return null;
    }
    // Prüfen ob rsvg-convert überhaupt im PATH liegt, bevor wir es aufrufen
    // (schnellerer, sauberer Ausstieg statt eines fehlschlagenden Aufrufs).
    $which = @shell_exec('command -v rsvg-convert 2>/dev/null');
    if ($which === null || trim((string)$which) === '') {
        return null;
    }

    $tmpPng = @tempnam(sys_get_temp_dir(), 'lmosvg') . '.png';
    $cmd = 'rsvg-convert -h 200 ' . escapeshellarg($absPath) . ' -o ' . escapeshellarg($tmpPng) . ' 2>/dev/null';
    @shell_exec($cmd);

    if (!is_file($tmpPng) || filesize($tmpPng) === 0) {
        @unlink($tmpPng);
        return null;
    }
    $raw = @file_get_contents($tmpPng);
    @unlink($tmpPng);
    if ($raw === false) {
        return null;
    }
    $img = @imagecreatefromstring($raw);
    if ($img === false) {
        return null;
    }
    return pdfGdImageToRaw($img);
}

/**
 * Lädt für eine Liste von Team-IDs (nur die tatsächlich im Dokument
 * vorkommenden, doppelte werden nur einmal geladen) die jeweiligen Logos und
 * vergibt PDF-XObject-Namen dafür. Gibt eine Map teamId => ['name'=>'TL3',
 * 'image'=>[...]] zurück; Teams ohne (einbettbares) Logo fehlen einfach in
 * der Map, der Aufrufer zeigt für die dann nur den Namen.
 *
 * @param array<int,int> $teamIds
 * @return array<int,array{name:string,image:array}>
 */
function pdfLoadTeamLogos(array $teamIds) : array
{
    $result = [];
    $i = 0;
    foreach (array_unique($teamIds) as $teamId) {
        if ($teamId <= 0) {
            continue;
        }
        $path = findTeamLogoPathFrontend($teamId);
        if ($path === null) {
            continue; // kein Logo hochgeladen -> im PDF nur der Name (kein "nopic"-Platzhalter im PDF)
        }
        $image = pdfLoadTeamLogoImage($path);
        if ($image === null) {
            continue; // SVG oder nicht einbettbar -> überspringen
        }
        $result[$teamId] = ['name' => 'TL' . $i, 'image' => $image];
        $i++;
    }
    return $result;
}

/**
 * Baut ein A4-PDF mit der Ergebnisliste eines Spieltags zusammen: LMOnext-Logo
 * oben links, zentrierter Liganame in Akzentfarbe (fett) mit der
 * Spieltag-Angabe darunter (normale Schrift), je Begegnung Datum/Zeit + Heim
 * + "H - G" + Gast, und am Ende eine Tore-Schnitt-Zeile. Die Tabelle ist nur
 * so breit wie der tatsächlich benötigte Inhalt (nicht die volle Seitenbreite)
 * – die Spaltenbreiten werden dafür vorab aus den längsten Zellen je Spalte
 * geschätzt. Reines PHP, keine externe PDF-Bibliothek – das PDF-Dateiformat
 * (Objekte + xref/trailer) wird direkt von Hand geschrieben. Gibt die
 * fertigen PDF-Bytes zurück.
 *
 * @param array<int,array{datum:string,heim:string,gast:string,ergebnis:string}> $rows
 */
/**
 * Baut ein A4-PDF mit einer oder mehreren Ergebnis-Tabellen zusammen: Logo
 * oben links, Liganame einmal oben fett/farbig, danach pro Abschnitt eine
 * eigene Unterüberschrift + Tabelle + Tore-Schnitt-Zeile. Für die meisten
 * Aufrufe gibt es nur einen Abschnitt (z.B. "Spieltag 5"); bei der letzten
 * KO-Runde mit "Spiel um Platz 3" gibt es zwei getrennte Abschnitte
 * ("Finale" und "Spiel um Platz 3"), jeweils mit eigenem Datum statt einem
 * gemeinsamen Datumsbereich über beide Begegnungen hinweg.
 *
 * @param array<int,array{subtitle:string,rows:array,statsLine:string,spielfreiLine?:string}> $sections
 */
function buildResultsPdf(string $ligaName, array $sections, string $footerText = '', array $teamLogos = []) : string
{
    $pageWidth    = 595.28;
    $pageHeight   = 841.89;
    $marginX      = 42;
    $marginBottom = 46;
    $lineHeight   = 17;
    $colGap       = 20.0;
    $rowFontSize  = 9.5;
    $headFontSize = 8.5;

    $accentR = 0.145;
    $accentG = 0.388;
    $accentB = 0.922;
    $mutedR  = 0.412;
    $mutedG  = 0.443;
    $mutedB  = 0.51;
    $textR   = 0.122;
    $textG   = 0.141;
    $textB   = 0.188;

    $colTrunc = [0, 32, 0, 32]; // Datum, Heim, Ergebnis, Gast
    $showLogos        = !empty($teamLogos);
    $teamLogoHeightPt = 9.5; // Logo-Höhe ~ Zeilenhöhe der Schrift, damit es nicht aus der Zeile ragt
    // Reservierten Platz aus der tatsächlich breitesten geladenen Logo-Datei
    // berechnen (statt eines festen Schätzwerts) – sonst würden breitere
    // Logos (z.B. nicht-quadratische Wappen) mit der Ergebnis-Spalte
    // kollidieren/abgeschnitten wirken.
    $logoReserve = 0.0;
    if ($showLogos) {
        $maxLogoWidthPt = 0.0;
        foreach ($teamLogos as $entry) {
            $img = $entry['image'];
            $maxLogoWidthPt = max($maxLogoWidthPt, $teamLogoHeightPt * ($img['widthPx'] / $img['heightPx']));
        }
        $logoReserve = $maxLogoWidthPt + 6.0; // Logo-Breite + kleiner Abstand zum Text
    }

    // Wandelt UTF-8 in die Zielkodierung der PDF-Kernschriften um und kürzt
    // danach bei Bedarf (Kürzung muss auf der bereits umgewandelten,
    // Single-Byte-Fassung passieren, siehe pdfConvertEncoding()/pdfTruncate()).
    $prep = static function (string $utf8, int $maxLen = 0) : string {
        $converted = pdfConvertEncoding($utf8);
        return $maxLen > 0 ? pdfTruncate($converted, $maxLen) : $converted;
    };

    $columnHeaders = [tf('liga_col_datum'), tf('liga_col_heim'), tf('liga_col_ergebnis'), tf('liga_col_gast')];
    $headCells = [];
    foreach ($columnHeaders as $i => $label) {
        $headCells[$i] = $prep($label);
    }

    $logo = pdfLoadLogoData();
    $logoHeightPt = 26.0;
    $logoWidthPt  = $logo !== null ? $logoHeightPt * ($logo['widthPx'] / $logo['heightPx']) : 0.0;
    $headerAreaHeight = 44.0; // Platz für Logo-Zeile oben, bevor der Titel beginnt
    $marginTop = 40 + $headerAreaHeight;

    $pagesContent = [];
    $content = '';
    $y = $pageHeight - $marginTop;

    $setColor = function (float $r, float $g, float $b) use (&$content) : void {
        $content .= number_format($r, 3, '.', '') . ' ' . number_format($g, 3, '.', '') . ' '
            . number_format($b, 3, '.', '') . " rg\n";
    };

    $addText = function (string $text, float $x, float $yy, string $font, float $size) use (&$content) : void {
        $content .= 'BT /' . $font . ' ' . number_format($size, 1, '.', '') . ' Tf '
            . number_format($x, 2, '.', '') . ' ' . number_format($yy, 2, '.', '')
            . " Td (" . pdfEscapeText($text) . ") Tj ET\n";
    };

    // Wie $addText, aber $rightEdge ist die rechte Kante, an der der Text
    // enden soll (rechtsbündig) – für die Heim-Spalte, analog zur
    // rechtsbündigen ".col-heim"-Spalte in der HTML-Ergebnistabelle.
    $addTextRight = function (string $text, float $rightEdge, float $yy, string $font, float $size, bool $bold) use (&$addText) : void {
        $width = pdfEstimateTextWidth($text, $size, $bold);
        $addText($text, $rightEdge - $width, $yy, $font, $size);
    };

    // Wie $addText, aber $centerX ist die Mitte, um die der Text zentriert
    // werden soll – für die Ergebnis-Spalte.
    $addTextCentered = function (string $text, float $centerX, float $yy, string $font, float $size, bool $bold) use (&$addText) : void {
        $width = pdfEstimateTextWidth($text, $size, $bold);
        $addText($text, $centerX - $width / 2, $yy, $font, $size);
    };

    // Füllt ein Rechteck (für die abwechselnde Zeilen-Hintergrundfarbe).
    $addRect = function (float $x, float $yBottom, float $w, float $h) use (&$content) : void {
        $content .= number_format($x, 2, '.', '') . ' ' . number_format($yBottom, 2, '.', '') . ' '
            . number_format($w, 2, '.', '') . ' ' . number_format($h, 2, '.', '') . " re f\n";
    };

    $addRule = function (float $yy, float $x1, float $x2) use (&$content) : void {
        $content .= number_format(0.5, 1, '.', '') . " w\n"
            . number_format($x1, 2, '.', '') . ' ' . number_format($yy, 2, '.', '') . ' m '
            . number_format($x2, 2, '.', '') . ' ' . number_format($yy, 2, '.', '') . " l S\n";
    };

    // Zeichnet ein Team-Logo (falls für die Team-ID geladen) an der Position
    // $xLeft, vertikal zur Textzeile ausgerichtet. Gibt die tatsächlich
    // verwendete Breite zurück (0, wenn kein Logo geladen werden konnte).
    $addTeamLogoAt = function (int $teamId, float $xLeft, float $yBaseline) use (&$content, $teamLogos, $teamLogoHeightPt) : float {
        if (!isset($teamLogos[$teamId])) {
            return 0.0;
        }
        $entry = $teamLogos[$teamId];
        $img   = $entry['image'];
        $wPt   = $teamLogoHeightPt * ($img['widthPx'] / $img['heightPx']);
        $yBottom = $yBaseline - 1.3;
        $content .= "q\n" . number_format($wPt, 2, '.', '') . ' 0 0 ' . number_format($teamLogoHeightPt, 2, '.', '')
            . ' ' . number_format($xLeft, 2, '.', '') . ' ' . number_format($yBottom, 2, '.', '')
            . " cm\n/" . $entry['name'] . " Do\nQ\n";
        return $wPt;
    };

    $addLogo = function () use (&$content, $logo, $marginX, $pageHeight, $logoWidthPt, $logoHeightPt) : void {
        if ($logo === null) {
            return;
        }
        $x = $marginX;
        $yy = $pageHeight - 40 - $logoHeightPt;
        $content .= "q\n" . number_format($logoWidthPt, 2, '.', '') . ' 0 0 '
            . number_format($logoHeightPt, 2, '.', '') . ' ' . number_format($x, 2, '.', '') . ' '
            . number_format($yy, 2, '.', '') . " cm\n/Logo Do\nQ\n";
    };

    $startNewPage = function () use (&$content, &$pagesContent, &$y, $pageHeight, $marginTop) : void {
        $pagesContent[] = $content;
        $content = '';
        $y = $pageHeight - $marginTop;
    };

    // Logo oben links (nur auf der ersten Seite)
    $addLogo();

    // Liganame zentriert, fett, in Akzentfarbe – nur einmal ganz oben, gilt
    // für alle Abschnitte
    $nameSize = 17.0;
    $nameText = $prep($ligaName, 60);
    $nameX = ($pageWidth - pdfEstimateTextWidth($nameText, $nameSize, true)) / 2;
    $setColor($accentR, $accentG, $accentB);
    $addText($nameText, max($marginX, $nameX), $y, 'F2', $nameSize);
    $y -= 26;

    $stripeR = 0.957;
    $stripeG = 0.965;
    $stripeB = 0.976;

    foreach ($sections as $sectionIdx => $section) {
        $rows      = $section['rows'] ?? [];
        $subtitle  = $section['subtitle'] ?? '';
        $statsLine     = $section['statsLine'] ?? '';
        $spielfreiLine = $section['spielfreiLine'] ?? '';

        $bodyCells = [];
        $bodyRowIds = [];
        foreach ($rows as $row) {
            $bodyCells[] = [
                $prep($row['datum'], $colTrunc[0]),
                $prep($row['heim'], $colTrunc[1]),
                $prep($row['ergebnis'], $colTrunc[2]),
                $prep($row['gast'], $colTrunc[3]),
            ];
            $bodyRowIds[] = ['heim' => (int)($row['heimId'] ?? 0), 'gast' => (int)($row['gastId'] ?? 0)];
        }

        // Spaltenbreiten je Abschnitt eigenständig aus dem tatsächlichen
        // Inhalt schätzen, damit jede Tabelle nur so breit wird wie nötig.
        $colWidths = [];
        for ($i = 0; $i < 4; $i++) {
            $w = pdfEstimateTextWidth($headCells[$i], $headFontSize, true);
            foreach ($bodyCells as $cells) {
                $w = max($w, pdfEstimateTextWidth($cells[$i], $rowFontSize, false));
            }
            $colWidths[$i] = $w;
        }

        $tableWidth = $colWidths[0] + $colGap + $colWidths[1] + $logoReserve + $colGap + $colWidths[2] + $colGap + $logoReserve + $colWidths[3];
        $tableStartX = ($pageWidth - $tableWidth) / 2;

        $colX = [];
        $colX[0] = $tableStartX;
        $colX[1] = $colX[0] + $colWidths[0] + $colGap;
        $heimRightEdge = $colX[1] + $colWidths[1];
        $colX[2] = $heimRightEdge + $logoReserve + $colGap;
        $ergebnisCenterX = $colX[2] + $colWidths[2] / 2;
        $colX[3] = $colX[2] + $colWidths[2] + $colGap;
        $gastTextX = $colX[3] + $logoReserve;
        $tableRight = $gastTextX + $colWidths[3];

        // Genug Platz für Unterüberschrift + Tabellenkopf + mind. eine Zeile?
        // Sonst lieber auf der nächsten Seite mit diesem Abschnitt anfangen.
        $neededForHeader = ($subtitle !== '' ? 22 : 0) + 19 + $lineHeight;
        if ($y - $neededForHeader < $marginBottom) {
            $startNewPage();
        }

        $isMultiSection = count($sections) > 1;
        if ($subtitle !== '') {
            $labelSize = $isMultiSection ? 11.5 : 10.5;
            $labelText = $prep($subtitle, 90);
            $labelX = ($pageWidth - pdfEstimateTextWidth($labelText, $labelSize, $isMultiSection)) / 2;
            $setColor(...($isMultiSection ? [$textR, $textG, $textB] : [$mutedR, $mutedG, $mutedB]));
            $addText($labelText, max($marginX, $labelX), $y, $isMultiSection ? 'F2' : 'F1', $labelSize);
            $y -= 22;
        } else {
            $y -= 10;
        }

        // Kopfzeile (gedämpfte Farbe); Heim-Spalte rechtsbündig, analog zur
        // HTML-Ergebnistabelle (th.col-heim { text-align:right }). Die
        // Trennlinie geht nur über die tatsächliche Tabellenbreite, nicht die
        // ganze Seite.
        $setColor($mutedR, $mutedG, $mutedB);
        foreach ($headCells as $i => $label) {
            if ($i === 1) {
                $addTextRight($label, $heimRightEdge, $y, 'F2', $headFontSize, true);
            } elseif ($i === 2) {
                $addTextCentered($label, $ergebnisCenterX, $y, 'F2', $headFontSize, true);
            } else {
                $addText($label, $colX[$i], $y, 'F2', $headFontSize);
            }
        }
        $y -= 6;
        $addRule($y, $tableStartX, $tableRight);
        $setColor($textR, $textG, $textB);
        $y -= 13;

        $rowIndex = 0;
        foreach ($bodyCells as $cells) {
            if ($y < $marginBottom + $lineHeight) {
                $startNewPage();
            }
            if ($rowIndex % 2 === 1) {
                $setColor($stripeR, $stripeG, $stripeB);
                $addRect($tableStartX - 6, $y - 5, $tableWidth + 12, $lineHeight);
            }
            $setColor($textR, $textG, $textB);
            $ids = $bodyRowIds[$rowIndex] ?? ['heim' => 0, 'gast' => 0];
            foreach ($cells as $i => $cell) {
                if ($i === 1) {
                    // Heim: rechtsbündiger Name, Logo danach (rechts vom Text)
                    $addTextRight($cell, $heimRightEdge, $y, 'F1', $rowFontSize, false);
                    if ($showLogos) {
                        $addTeamLogoAt($ids['heim'], $heimRightEdge + 4, $y);
                    }
                } elseif ($i === 2) {
                    $addTextCentered($cell, $ergebnisCenterX, $y, 'F1', $rowFontSize, false);
                } elseif ($i === 3) {
                    // Gast: Logo zuerst, Name danach an fester Position (damit die
                    // Namen untereinander bündig bleiben, unabhängig davon ob das
                    // jeweilige Team ein Logo hat)
                    if ($showLogos) {
                        $addTeamLogoAt($ids['gast'], $colX[3], $y);
                    }
                    $addText($cell, $gastTextX, $y, 'F1', $rowFontSize);
                } else {
                    $addText($cell, $colX[$i], $y, 'F1', $rowFontSize);
                }
            }
            $y -= $lineHeight;
            $rowIndex++;
        }

        // "Spielfrei: TEAMNAME"-Zeile, falls ein Team an diesem Spieltag keine
        // Partie hat (siehe findSpielfreiTeams() in data_liga.php) - direkt
        // nach den Ergebniszeilen, vor der Tore-Schnitt-Zeile (gleiche
        // Reihenfolge wie in der HTML-Ansicht)
        if ($spielfreiLine !== '') {
            if ($y < $marginBottom + $lineHeight) {
                $startNewPage();
            }
            $y -= 4;
            $setColor($mutedR, $mutedG, $mutedB);
            $addText($prep($spielfreiLine, 120), $tableStartX, $y, 'F1', $headFontSize);
            $y -= $lineHeight - 4;
        }

        // Tore-Schnitt-Zeile am Ende der Ergebnisliste dieses Abschnitts
        if ($statsLine !== '') {
            if ($y < $marginBottom + $lineHeight) {
                $startNewPage();
            }
            $y -= 4;
            $setColor($mutedR, $mutedG, $mutedB);
            $addText($prep($statsLine, 120), $tableStartX, $y, 'F1', $headFontSize);
        }

        // Abstand vor dem nächsten Abschnitt (z.B. "Spiel um Platz 3" nach "Finale")
        if ($sectionIdx < count($sections) - 1) {
            $y -= 26;
        }
    }

    $pagesContent[] = $content;

    // Fußzeile (Copyright/Version) zentriert am unteren Seitenrand, auf jeder
    // Seite. Wird erst hier im Nachgang an jeden fertigen Seiten-Content-
    // Stream angehängt, damit sie bei mehrseitigen PDFs nicht vergessen wird.
    if ($footerText !== '') {
        $footerSize = 7.5;
        $footerTextPrepped = $prep($footerText, 140);
        $footerX = ($pageWidth - pdfEstimateTextWidth($footerTextPrepped, $footerSize, false)) / 2;
        $footerContent = number_format($mutedR, 3, '.', '') . ' ' . number_format($mutedG, 3, '.', '') . ' '
            . number_format($mutedB, 3, '.', '') . " rg\n"
            . 'BT /F1 ' . number_format($footerSize, 1, '.', '') . ' Tf '
            . number_format(max($marginX, $footerX), 2, '.', '') . ' 24.00'
            . " Td (" . pdfEscapeText($footerTextPrepped) . ") Tj ET\n";
        foreach ($pagesContent as $i => $pc) {
            $pagesContent[$i] = $pc . $footerContent;
        }
    }

    return assemblePdfBytes($pagesContent, $pageWidth, $pageHeight, $logo, $teamLogos);
}

/**
 * Baut ein A4-PDF mit einer generischen, zentrierten Tabelle zusammen: Logo
 * oben links, Titel fett/farbig, Untertitel, Zebra-Streifen, Fußzeile –
 * dasselbe Grundgerüst wie buildResultsPdf(), aber mit einer beliebigen
 * Anzahl Spalten (statt der festen 4 Ergebnis-Spalten). Genutzt für die
 * Tabelle (Standings) UND den Team-Spielplan-Export.
 *
 * @param array<int,string> $columnHeaders
 * @param array<int,string> $columnAligns 'left', 'right' oder 'center' je Spalte
 * @param array<int,array<int,string>> $rows Je Zeile eine Liste von Zellen-Strings
 * @param int|null $accentColIndex Spalte (0-basiert), die fett/in Akzentfarbe
 *        hervorgehoben wird (z.B. die Pkt-Spalte der Tabelle) – null = keine
 */
function buildStandingsPdf(string $ligaName, string $subtitleLabel, array $columnHeaders, array $columnAligns, array $rows, string $footerText = '', ?int $accentColIndex = null, array $rowBorderColors = [], array $teamLogos = [], array $logoCols = [], array $rowTeamIds = [], ?array $vsTitleTeams = null) : string
{
    $pageWidth    = 595.28;
    $pageHeight   = 841.89;
    $marginX      = 42;
    $marginBottom = 46;
    $lineHeight   = 17;
    $colGap       = 16.0;
    $rowFontSize  = 9.5;
    $headFontSize = 8.5;
    $colCount     = count($columnHeaders);

    $accentR = 0.145;
    $accentG = 0.388;
    $accentB = 0.922;
    $mutedR  = 0.412;
    $mutedG  = 0.443;
    $mutedB  = 0.51;
    $textR   = 0.122;
    $textG   = 0.141;
    $textB   = 0.188;

    $prep = static function (string $utf8, int $maxLen = 0) : string {
        $converted = pdfConvertEncoding($utf8);
        return $maxLen > 0 ? pdfTruncate($converted, $maxLen) : $converted;
    };

    $headCells = [];
    foreach ($columnHeaders as $i => $label) {
        $headCells[$i] = $prep($label);
    }
    $bodyCells = [];
    foreach ($rows as $row) {
        $prepped = [];
        foreach ($row as $i => $cell) {
            $prepped[$i] = $prep((string)$cell, $i === 1 ? 26 : 0);
        }
        $bodyCells[] = $prepped;
    }

    $teamLogoHeightPt = 9.5;
    // Reservierten Platz aus der tatsächlich breitesten geladenen Logo-Datei
    // berechnen (statt eines festen Schätzwerts) – sonst würden breitere
    // Logos mit dem folgenden Spalteninhalt kollidieren/abgeschnitten wirken.
    $logoReserve = 0.0;
    if (!empty($teamLogos)) {
        $maxLogoWidthPt = 0.0;
        foreach ($teamLogos as $entry) {
            $img = $entry['image'];
            $maxLogoWidthPt = max($maxLogoWidthPt, $teamLogoHeightPt * ($img['widthPx'] / $img['heightPx']));
        }
        $logoReserve = $maxLogoWidthPt + 6.0;
    }

    $colWidths = [];
    for ($i = 0; $i < $colCount; $i++) {
        $bold = $i === $accentColIndex;
        $w = pdfEstimateTextWidth($headCells[$i], $headFontSize, true);
        foreach ($bodyCells as $cells) {
            $w = max($w, pdfEstimateTextWidth($cells[$i], $rowFontSize, $bold));
        }
        if (isset($logoCols[$i])) {
            $w += $logoReserve;
        }
        $colWidths[$i] = $w;
    }

    $tableWidth = array_sum($colWidths) + ($colCount - 1) * $colGap;
    $tableStartX = ($pageWidth - $tableWidth) / 2;

    $colX = [];
    $colX[0] = $tableStartX;
    for ($i = 1; $i < $colCount; $i++) {
        $colX[$i] = $colX[$i - 1] + $colWidths[$i - 1] + $colGap;
    }
    $tableRight = $colX[$colCount - 1] + $colWidths[$colCount - 1];

    $logo = pdfLoadLogoData();
    $logoHeightPt = 26.0;
    $logoWidthPt  = $logo !== null ? $logoHeightPt * ($logo['widthPx'] / $logo['heightPx']) : 0.0;
    $headerAreaHeight = 44.0;
    $marginTop = 40 + $headerAreaHeight;

    $pagesContent = [];
    $content = '';
    $y = $pageHeight - $marginTop;

    $setColor = function (float $r, float $g, float $b) use (&$content) : void {
        $content .= number_format($r, 3, '.', '') . ' ' . number_format($g, 3, '.', '') . ' '
            . number_format($b, 3, '.', '') . " rg\n";
    };

    $addText = function (string $text, float $x, float $yy, string $font, float $size) use (&$content) : void {
        $content .= 'BT /' . $font . ' ' . number_format($size, 1, '.', '') . ' Tf '
            . number_format($x, 2, '.', '') . ' ' . number_format($yy, 2, '.', '')
            . " Td (" . pdfEscapeText($text) . ") Tj ET\n";
    };

    $addTextCentered = function (string $text, float $centerX, float $yy, string $font, float $size, bool $bold) use (&$addText) : void {
        $width = pdfEstimateTextWidth($text, $size, $bold);
        $addText($text, $centerX - $width / 2, $yy, $font, $size);
    };

    $addTextRight = function (string $text, float $rightEdge, float $yy, string $font, float $size, bool $bold) use (&$addText) : void {
        $width = pdfEstimateTextWidth($text, $size, $bold);
        $addText($text, $rightEdge - $width, $yy, $font, $size);
    };

    $addRect = function (float $x, float $yBottom, float $w, float $h) use (&$content) : void {
        $content .= number_format($x, 2, '.', '') . ' ' . number_format($yBottom, 2, '.', '') . ' '
            . number_format($w, 2, '.', '') . ' ' . number_format($h, 2, '.', '') . " re f\n";
    };

    // Wandelt einen "#rrggbb"-Hexwert (z.B. aus den Tabellenmarkierungen-
    // Einstellungen) in 0..1-RGB-Anteile für den PDF-Farboperator um.
    $hexToRgb01 = static function (string $hex) : array {
        $hex = ltrim($hex, '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [0.0, 0.0, 0.0];
        }
        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    };

    $addRule = function (float $yy, float $x1, float $x2) use (&$content) : void {
        $content .= number_format(0.5, 1, '.', '') . " w\n"
            . number_format($x1, 2, '.', '') . ' ' . number_format($yy, 2, '.', '') . ' m '
            . number_format($x2, 2, '.', '') . ' ' . number_format($yy, 2, '.', '') . " l S\n";
    };

    $addLogo = function () use (&$content, $logo, $marginX, $pageHeight, $logoWidthPt, $logoHeightPt) : void {
        if ($logo === null) {
            return;
        }
        $x = $marginX;
        $yy = $pageHeight - 40 - $logoHeightPt;
        $content .= "q\n" . number_format($logoWidthPt, 2, '.', '') . ' 0 0 '
            . number_format($logoHeightPt, 2, '.', '') . ' ' . number_format($x, 2, '.', '') . ' '
            . number_format($yy, 2, '.', '') . " cm\n/Logo Do\nQ\n";
    };

    $startNewPage = function () use (&$content, &$pagesContent, &$y, $pageHeight, $marginTop) : void {
        $pagesContent[] = $content;
        $content = '';
        $y = $pageHeight - $marginTop;
    };

    $drawTeamLogoAt = function (int $teamId, float $xLeft, float $yBaseline, ?float $heightOverride = null) use (&$content, $teamLogos, $teamLogoHeightPt) : float {
        if (!isset($teamLogos[$teamId])) {
            return 0.0;
        }
        $entry = $teamLogos[$teamId];
        $img   = $entry['image'];
        $h     = $heightOverride ?? $teamLogoHeightPt;
        $wPt   = $h * ($img['widthPx'] / $img['heightPx']);
        $yBottom = $yBaseline - 1.3;
        $content .= "q\n" . number_format($wPt, 2, '.', '') . ' 0 0 ' . number_format($h, 2, '.', '')
            . ' ' . number_format($xLeft, 2, '.', '') . ' ' . number_format($yBottom, 2, '.', '')
            . " cm\n/" . $entry['name'] . " Do\nQ\n";
        return $wPt;
    };

    // Rendert eine Zelle gemäß ihres Ausrichtungs-Modus ('left'/'right'/'center').
    // Spalten mit einem Eintrag in $logoCols ('before'/'after') bekommen
    // zusätzlich das Team-Logo vor bzw. nach dem Text gezeichnet, sofern für
    // die übergebene $teamId eins geladen werden konnte.
    $renderCell = function (string $text, int $i, float $yy, string $font, float $size, bool $bold, ?int $teamId = null) use (&$addText, &$addTextRight, &$addTextCentered, &$drawTeamLogoAt, $colX, $colWidths, $columnAligns, $logoCols, $logoReserve) : void {
        $align = $columnAligns[$i] ?? 'left';
        $order = $logoCols[$i] ?? null;

        if ($align === 'right') {
            $rightEdge = $colX[$i] + $colWidths[$i];
            if ($order === 'after') {
                $rightEdge -= $logoReserve;
            }
            $addTextRight($text, $rightEdge, $yy, $font, $size, $bold);
            if ($order === 'after' && $teamId !== null) {
                $drawTeamLogoAt($teamId, $rightEdge + 4, $yy);
            }
        } elseif ($align === 'center') {
            $addTextCentered($text, $colX[$i] + $colWidths[$i] / 2, $yy, $font, $size, $bold);
        } else {
            $leftEdge = $colX[$i];
            if ($order === 'before') {
                if ($teamId !== null) {
                    $drawTeamLogoAt($teamId, $leftEdge, $yy);
                }
                $leftEdge += $logoReserve;
            }
            $addText($text, $leftEdge, $yy, $font, $size);
        }
    };

    $addLogo();

    $nameSize = 17.0;
    if ($vsTitleTeams !== null) {
        // Spezielle Titel-Zeile für den Teamvergleich: "TeamA [Logo] vs [Logo] TeamB" –
        // die Logos "schauen" zum "vs" in der Mitte, wie im Direkter-Vergleich-Modal
        // in der HTML-Ansicht.
        $vsText  = ' vs ';
        $nameAText = $prep($vsTitleTeams['nameA'], 40);
        $nameBText = $prep($vsTitleTeams['nameB'], 40);
        $segW = [
            'a'  => pdfEstimateTextWidth($nameAText, $nameSize, true),
            'lA' => 0.0,
            'vs' => pdfEstimateTextWidth($vsText, $nameSize, false),
            'lB' => 0.0,
            'b'  => pdfEstimateTextWidth($nameBText, $nameSize, true),
        ];
        $logoAEntry = $teamLogos[$vsTitleTeams['idA']] ?? null;
        $logoBEntry = $teamLogos[$vsTitleTeams['idB']] ?? null;
        $vsLogoHeightPt = 24.0; // deutlich größer als die Zeilen-Logos, gut sichtbar neben dem 17pt-Titeltext
        $vsLogoYOffset  = -3.0; // vertikal grob mittig zur Textzeile ausgerichtet
        if ($logoAEntry !== null) {
            $segW['lA'] = 5.0 + $vsLogoHeightPt * ($logoAEntry['image']['widthPx'] / $logoAEntry['image']['heightPx']);
        }
        if ($logoBEntry !== null) {
            $segW['lB'] = 5.0 + $vsLogoHeightPt * ($logoBEntry['image']['widthPx'] / $logoBEntry['image']['heightPx']);
        }
        $totalW = array_sum($segW);
        $xCur = max($marginX, ($pageWidth - $totalW) / 2);
        $setColor($accentR, $accentG, $accentB);
        $addText($nameAText, $xCur, $y, 'F2', $nameSize);
        $xCur += $segW['a'];
        if ($logoAEntry !== null) {
            $xCur += 5.0;
            $drawTeamLogoAt($vsTitleTeams['idA'], $xCur, $y + $vsLogoYOffset, $vsLogoHeightPt);
            $xCur += $segW['lA'] - 5.0;
        }
        $setColor($mutedR, $mutedG, $mutedB);
        $addText($vsText, $xCur, $y, 'F1', $nameSize);
        $xCur += $segW['vs'];
        if ($logoBEntry !== null) {
            $drawTeamLogoAt($vsTitleTeams['idB'], $xCur, $y + $vsLogoYOffset, $vsLogoHeightPt);
            $xCur += $segW['lB'];
        } else {
            $xCur += 5.0;
        }
        $setColor($accentR, $accentG, $accentB);
        $addText($nameBText, $xCur, $y, 'F2', $nameSize);
    } else {
        $nameText = $prep($ligaName, 60);
        $nameX = ($pageWidth - pdfEstimateTextWidth($nameText, $nameSize, true)) / 2;
        $setColor($accentR, $accentG, $accentB);
        $addText($nameText, max($marginX, $nameX), $y, 'F2', $nameSize);
    }
    $y -= 20;

    if ($subtitleLabel !== '') {
        $labelSize = 10.5;
        $labelText = $prep($subtitleLabel, 90);
        $labelX = ($pageWidth - pdfEstimateTextWidth($labelText, $labelSize, false)) / 2;
        $setColor($mutedR, $mutedG, $mutedB);
        $addText($labelText, max($marginX, $labelX), $y, 'F1', $labelSize);
        $y -= 22;
    } else {
        $y -= 10;
    }

    $setColor($mutedR, $mutedG, $mutedB);
    foreach ($headCells as $i => $label) {
        $renderCell($label, $i, $y, 'F2', $headFontSize, true);
    }
    $y -= 6;
    $addRule($y, $tableStartX, $tableRight);
    $setColor($textR, $textG, $textB);
    $y -= 13;

    $stripeR = 0.957;
    $stripeG = 0.965;
    $stripeB = 0.976;

    $rowIndex = 0;
    foreach ($bodyCells as $cells) {
        if ($y < $marginBottom + $lineHeight) {
            $startNewPage();
        }
        if ($rowIndex % 2 === 1) {
            $setColor($stripeR, $stripeG, $stripeB);
            $addRect($tableStartX - 6, $y - 5, $tableWidth + 12, $lineHeight);
        }
        $borderColorHex = $rowBorderColors[$rowIndex] ?? '';
        if ($borderColorHex !== '') {
            $setColor(...$hexToRgb01($borderColorHex));
            $addRect($tableStartX - 6, $y - 5, 3, $lineHeight);
        }
        foreach ($cells as $i => $cell) {
            $bold = $accentColIndex !== null && $i === $accentColIndex;
            $setColor(...($bold ? [$accentR, $accentG, $accentB] : [$textR, $textG, $textB]));
            $cellTeamId = $rowTeamIds[$rowIndex][$i] ?? null;
            $renderCell($cell, $i, $y, $bold ? 'F2' : 'F1', $rowFontSize, $bold, $cellTeamId);
        }
        $y -= $lineHeight;
        $rowIndex++;
    }

    $pagesContent[] = $content;

    if ($footerText !== '') {
        $footerSize = 7.5;
        $footerTextPrepped = $prep($footerText, 140);
        $footerX = ($pageWidth - pdfEstimateTextWidth($footerTextPrepped, $footerSize, false)) / 2;
        $footerContent = number_format($mutedR, 3, '.', '') . ' ' . number_format($mutedG, 3, '.', '') . ' '
            . number_format($mutedB, 3, '.', '') . " rg\n"
            . 'BT /F1 ' . number_format($footerSize, 1, '.', '') . ' Tf '
            . number_format(max($marginX, $footerX), 2, '.', '') . ' 24.00'
            . " Td (" . pdfEscapeText($footerTextPrepped) . ") Tj ET\n";
        foreach ($pagesContent as $i => $pc) {
            $pagesContent[$i] = $pc . $footerContent;
        }
    }

    return assemblePdfBytes($pagesContent, $pageWidth, $pageHeight, $logo, $teamLogos);
}

/**
 * Setzt aus fertigen Seiten-Content-Streams ein vollständiges PDF-Dokument
 * zusammen: Catalog-, Pages-, Font-, ggf. Bild- und Page/Contents-Objekte
 * plus xref-Tabelle und Trailer, alles von Hand geschrieben (kein PDF-Toolkit).
 *
 * @param array<int,string> $pagesContent Ein Content-Stream (PDF-Operatoren) pro Seite
 * @param array{widthPx:int,heightPx:int,rgbZlib:string,alphaZlib:string}|null $logo
 */
function assemblePdfBytes(array $pagesContent, float $pageWidth, float $pageHeight, ?array $logo = null, array $teamLogos = []) : string
{
    $n = count($pagesContent);
    if ($n === 0) {
        $pagesContent = [''];
        $n = 1;
    }

    $mediaBox = '[0 0 ' . number_format($pageWidth, 2, '.', '') . ' ' . number_format($pageHeight, 2, '.', '') . ']';

    $objects = [];
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

    $kids = [];
    for ($i = 0; $i < $n; $i++) {
        $kids[] = (4 + $i * 2) . ' 0 R';
    }
    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';

    $fontF1Num = 4 + $n * 2;
    $fontF2Num = $fontF1Num + 1;
    $objects[$fontF1Num] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
    $objects[$fontF2Num] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

    $nextObjNum   = $fontF2Num + 1;
    $xobjectNames = []; // "/Name N 0 R" Paare für die Resources-Dict

    if ($logo !== null) {
        $smaskNum = $nextObjNum++;
        $imgNum   = $nextObjNum++;
        $objects[$smaskNum] = '<< /Type /XObject /Subtype /Image /Width ' . $logo['widthPx']
            . ' /Height ' . $logo['heightPx'] . ' /ColorSpace /DeviceGray /BitsPerComponent 8'
            . ' /Filter /FlateDecode /Length ' . strlen($logo['alphaZlib']) . " >>\nstream\n"
            . $logo['alphaZlib'] . "\nendstream";
        $objects[$imgNum] = '<< /Type /XObject /Subtype /Image /Width ' . $logo['widthPx']
            . ' /Height ' . $logo['heightPx'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8'
            . ' /SMask ' . $smaskNum . ' 0 R /Filter /FlateDecode /Length ' . strlen($logo['rgbZlib']) . " >>\nstream\n"
            . $logo['rgbZlib'] . "\nendstream";
        $xobjectNames[] = '/Logo ' . $imgNum . ' 0 R';
    }

    // Team-Logos: jeweils eigenes XObject, je nach geladenem Modus entweder
    // als natives JPEG (DCTDecode, keine Bildbibliothek zur Ausgabezeit nötig)
    // oder als rohe RGB+Alpha-Pixel (gleiche Technik wie beim LMOnext-Logo).
    foreach ($teamLogos as $entry) {
        $img = $entry['image'];
        if ($img['mode'] === 'jpeg') {
            $imgNum = $nextObjNum++;
            $objects[$imgNum] = '<< /Type /XObject /Subtype /Image /Width ' . $img['widthPx']
                . ' /Height ' . $img['heightPx'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8'
                . ' /Filter /DCTDecode /Length ' . strlen($img['jpegData']) . " >>\nstream\n"
                . $img['jpegData'] . "\nendstream";
            $xobjectNames[] = '/' . $entry['name'] . ' ' . $imgNum . ' 0 R';
        } else { // 'raw'
            $smaskNum = $nextObjNum++;
            $imgNum   = $nextObjNum++;
            $objects[$smaskNum] = '<< /Type /XObject /Subtype /Image /Width ' . $img['widthPx']
                . ' /Height ' . $img['heightPx'] . ' /ColorSpace /DeviceGray /BitsPerComponent 8'
                . ' /Filter /FlateDecode /Length ' . strlen($img['alphaZlib']) . " >>\nstream\n"
                . $img['alphaZlib'] . "\nendstream";
            $objects[$imgNum] = '<< /Type /XObject /Subtype /Image /Width ' . $img['widthPx']
                . ' /Height ' . $img['heightPx'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8'
                . ' /SMask ' . $smaskNum . ' 0 R /Filter /FlateDecode /Length ' . strlen($img['rgbZlib']) . " >>\nstream\n"
                . $img['rgbZlib'] . "\nendstream";
            $xobjectNames[] = '/' . $entry['name'] . ' ' . $imgNum . ' 0 R';
        }
    }

    $xobjectEntry = !empty($xobjectNames) ? ' /XObject << ' . implode(' ', $xobjectNames) . ' >>' : '';

    for ($i = 0; $i < $n; $i++) {
        $pageObjNum    = 4 + $i * 2;
        $contentObjNum = $pageObjNum + 1;
        $objects[$pageObjNum] = '<< /Type /Page /Parent 2 0 R /MediaBox ' . $mediaBox
            . ' /Resources << /Font << /F1 ' . $fontF1Num . ' 0 R /F2 ' . $fontF2Num . ' 0 R >>' . $xobjectEntry . ' >>'
            . ' /Contents ' . $contentObjNum . ' 0 R >>';
        $stream = $pagesContent[$i];
        $objects[$contentObjNum] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
    }

    ksort($objects);

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [];
    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $num . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xrefStart = strlen($pdf);
    $maxNum    = max(array_keys($objects));
    $pdf .= 'xref' . "\n" . '0 ' . ($maxNum + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $maxNum; $i++) {
        $pdf .= isset($offsets[$i])
            ? sprintf("%010d 00000 n \n", $offsets[$i])
            : "0000000000 00000 f \n";
    }
    $pdf .= 'trailer' . "\n" . '<< /Size ' . ($maxNum + 1) . ' /Root 1 0 R >>' . "\n";
    $pdf .= 'startxref' . "\n" . $xrefStart . "\n%%EOF";

    return $pdf;
}

/**
 * Baut aus einer Partien-Liste (wie sie renderResultsTable() auch bekommt)
 * das PDF und sendet es direkt als Download an den Browser (setzt Header,
 * gibt die Bytes aus). Beendet danach das Skript nicht selbst – der
 * Aufrufer (liga.php) macht nach dem Aufruf ein exit.
 *
 * @param array<int,array<string,mixed>> $partien
 */
/**
 * Baut aus einer oder mehreren Abschnitts-Spezifikationen (Runde/Spieltag +
 * die zugehörigen Partien) das Ergebnisse-PDF und sendet es als Download.
 * Normalerweise nur ein Abschnitt; bei der letzten KO-Runde mit "Spiel um
 * Platz 3" übergibt der Aufrufer (liga.php) zwei Abschnitte (Finale +
 * Spiel um Platz 3), die dann als zwei getrennte Tabellen mit jeweils
 * eigener Überschrift auf derselben PDF-Seite erscheinen.
 *
 * @param array<int,array{label:string,partien:array,spieltagStart:?string}> $sectionSpecs
 */
function exportErgebnissePdf(string $ligaName, array $sectionSpecs, bool $showLogos = false) : void
{
    $sections = [];
    $allTeamIds = [];
    foreach ($sectionSpecs as $spec) {
        $partien = $spec['partien'];
        $rows = [];
        foreach ($partien as $p) {
            $gespielt = $p['h_tore'] !== null && $p['g_tore'] !== null;
            $ergebnis = $gespielt
                ? $p['h_tore'] . ' - ' . $p['g_tore'] . statusSuffix($p)
                : '- - -';
            $heimId = (int)($p['heim_id'] ?? 0);
            $gastId = (int)($p['gast_id'] ?? 0);
            $rows[] = [
                'datum'    => partieZeitDisplay($p, $spec['spieltagStart'] ?? null),
                'heim'     => partieTeamName($p, 'heim'),
                'gast'     => partieTeamName($p, 'gast'),
                'ergebnis' => $ergebnis,
                'heimId'   => $heimId,
                'gastId'   => $gastId,
            ];
            $allTeamIds[] = $heimId;
            $allTeamIds[] = $gastId;
        }

        $stats     = computeSpieltagStats($partien);
        $statsLine = tf('liga_stats_line', [
            'heim'     => $stats['schnittHeim'],
            'gast'     => $stats['schnittGast'],
            'tore'     => $stats['tore'],
            'proSpiel' => $stats['toreProSpiel'],
        ]);

        $spielfreiTeams = $spec['spielfrei'] ?? [];
        $spielfreiLine  = $spielfreiTeams === [] ? '' : tf('liga_spielfrei_label') . ' '
            . implode(', ', array_map(static fn(array $t) : string => (string)$t['name'], $spielfreiTeams));

        $sections[] = [
            'subtitle'      => $spec['label'],
            'rows'          => $rows,
            'statsLine'     => $statsLine,
            'spielfreiLine' => $spielfreiLine,
        ];
    }

    $teamLogos = $showLogos ? pdfLoadTeamLogos($allTeamIds) : [];

    $footerText = tf('liga_pdf_footer', [
        'year'    => date('Y'),
        'version' => getAppVersion(),
    ]);

    $pdfBytes = buildResultsPdf($ligaName, $sections, $footerText, $teamLogos);

    $firstLabel   = $sectionSpecs[0]['label'] ?? 'Ergebnisse';
    $filenameBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $ligaName . '_' . $firstLabel);
    $filenameBase = trim((string)$filenameBase, '_');
    $filename     = ($filenameBase !== '' ? $filenameBase : 'ergebnisse') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    echo $pdfBytes;
}

/**
 * Baut aus der aktuellen Tabelle (Standings) einer regulären Liga das PDF und
 * sendet es direkt als Download an den Browser. Beendet danach das Skript
 * nicht selbst – der Aufrufer (liga.php) macht nach dem Aufruf ein exit.
 */
function exportTabellePdf(string $ligaName, int $ligaId, array $allSpieltage, bool $showLogos = false) : void
{
    $opts    = getLigaOptions($ligaId);
    $teams   = getLigaTeamsList($ligaId);
    $partien = getAllLigaPartien($allSpieltage);
    $rows    = computeStandings($teams, $partien, $opts);

    $headers = [
        tf('liga_standings_col_platz'),
        tf('liga_standings_col_team'),
        tf('liga_standings_col_sp'),
        tf('liga_standings_col_s'),
        tf('liga_standings_col_u'),
        tf('liga_standings_col_n'),
        tf('liga_standings_col_tore'),
        tf('liga_standings_col_diff'),
        tf('liga_standings_col_pkt'),
    ];
    $aligns = ['center', 'left', 'center', 'center', 'center', 'center', 'center', 'center', 'center'];

    $tableRows = [];
    $rowBorderColors = [];
    $rowTeamIds = [];
    $totalTeams = count($rows);
    foreach ($rows as $i => $r) {
        $diff = $r['tore_h'] - $r['tore_g'];
        $tableRows[] = [
            (string)($i + 1),
            $r['name'],
            (string)$r['sp'],
            (string)$r['s'],
            (string)$r['u'],
            (string)$r['n'],
            $r['tore_h'] . ':' . $r['tore_g'],
            ($diff > 0 ? '+' : '') . $diff,
            (string)$r['pkt'],
        ];
        $rowBorderColors[$i] = computeStandingsMarkerColor($i, $totalTeams, $opts);
        $rowTeamIds[$i] = [1 => (int)$r['id']]; // Spalte 1 = "Team"
    }

    $teamLogos = $showLogos ? pdfLoadTeamLogos(array_column($rows, 'id')) : [];
    $logoCols  = $showLogos ? [1 => 'before'] : []; // Logo vor dem Namen, wie in der HTML-Tabelle

    $footerText = tf('liga_pdf_footer', [
        'year'    => date('Y'),
        'version' => getAppVersion(),
    ]);

    $pdfBytes = buildStandingsPdf($ligaName, tf('liga_tab_tabelle'), $headers, $aligns, $tableRows, $footerText, count($headers) - 1, $rowBorderColors, $teamLogos, $logoCols, $rowTeamIds);

    $filenameBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $ligaName . '_Tabelle');
    $filenameBase = trim((string)$filenameBase, '_');
    $filename     = ($filenameBase !== '' ? $filenameBase : 'tabelle') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    echo $pdfBytes;
}

/**
 * Baut aus dem vollständigen Spielplan eines Teams (reguläre Liga) das PDF
 * und sendet es direkt als Download an den Browser. Gleiche Formatvorgaben
 * wie Ergebnisse-/Tabelle-Export (Logo, zentrierte Tabelle, Zebra-Streifen,
 * Fußzeile) – Titel ist hier der Teamname (das ist der Fokus dieser Seite),
 * Untertitel der Liganame.
 */
function exportSpielplanPdf(string $ligaName, int $ligaId, array $allSpieltage, int $selectedTeamId, bool $showLogos = false) : void
{
    try {
        $teamName = (string)(getDB()->query(
            'SELECT name FROM ' . tbl('teams_global') . ' WHERE id=' . (int)$selectedTeamId
        )->fetchColumn() ?: '');
    } catch (Throwable) {
        $teamName = '';
    }
    if ($teamName === '') {
        $teamName = $ligaName;
    }

    $partien = getAllLigaPartien($allSpieltage);
    $tableRows = [];
    $rowTeamIds = [];
    $allTeamIds = [];
    foreach ($partien as $p) {
        $hId = (int)($p['heim_id'] ?? 0);
        $gId = (int)($p['gast_id'] ?? 0);
        if ($hId !== $selectedTeamId && $gId !== $selectedTeamId) {
            continue;
        }
        $gespielt = $p['h_tore'] !== null && $p['g_tore'] !== null;
        $ergebnis = $gespielt
            ? $p['h_tore'] . ' - ' . $p['g_tore'] . statusSuffix($p)
            : '- - -';
        $tableRows[] = [
            (string)($p['_spieltag_nummer'] ?? ''),
            partieZeitDisplay($p, null),
            partieTeamName($p, 'heim'),
            $ergebnis,
            partieTeamName($p, 'gast'),
        ];
        $rowTeamIds[] = [2 => $hId, 4 => $gId]; // Spalte 2 = Heim, Spalte 4 = Gast
        $allTeamIds[] = $hId;
        $allTeamIds[] = $gId;
    }

    $headers = [
        tf('liga_col_nr'),
        tf('liga_col_datum'),
        tf('liga_col_heim'),
        tf('liga_col_ergebnis'),
        tf('liga_col_gast'),
    ];
    $aligns = ['center', 'left', 'right', 'center', 'left'];

    $teamLogos = $showLogos ? pdfLoadTeamLogos($allTeamIds) : [];
    // Heim (rechtsbündig): Name zuerst, Logo danach ("after"). Gast
    // (linksbündig): Logo zuerst, Name danach ("before") – spiegelt exakt
    // dieselbe Reihenfolge wie der reguläre Spielplan in der HTML-Ansicht.
    $logoCols = $showLogos ? [2 => 'after', 4 => 'before'] : [];

    $footerText = tf('liga_pdf_footer', [
        'year'    => date('Y'),
        'version' => getAppVersion(),
    ]);

    $pdfBytes = buildStandingsPdf($teamName, $ligaName, $headers, $aligns, $tableRows, $footerText, null, [], $teamLogos, $logoCols, $rowTeamIds);

    $filenameBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $ligaName . '_Spielplan_' . $teamName);
    $filenameBase = trim((string)$filenameBase, '_');
    $filename     = ($filenameBase !== '' ? $filenameBase : 'spielplan') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    echo $pdfBytes;
}

/**
 * Baut aus dem direkten Vergleich zweier Teams (Head-to-Head, siehe
 * getHeadToHeadMatches() in data_liga.php) das PDF und sendet es direkt als
 * Download an den Browser. Teamübergreifend – nicht an eine bestimmte Liga
 * gebunden, da sich zwei Teams über mehrere Ligen/Saisons hinweg begegnet
 * sein können. Gleiche Formatvorgaben wie die anderen PDF-Exporte (Logo,
 * zentrierte Tabelle, Zebra-Streifen, Fußzeile); Titel ist "{TeamA} vs
 * {TeamB}", Untertitel die Sieg/Unentschieden-Bilanz.
 */
function exportH2hPdf(int $teamAId, int $teamBId, bool $showLogos = false) : void
{
    try {
        $s = getDB()->prepare('SELECT id, name FROM ' . tbl('teams_global') . ' WHERE id IN (?,?)');
        $s->execute([$teamAId, $teamBId]);
        $byId = [];
        foreach ($s->fetchAll() as $r) {
            $byId[(int)$r['id']] = $r['name'];
        }
        $nameA = $byId[$teamAId] ?? '?';
        $nameB = $byId[$teamBId] ?? '?';
    } catch (Throwable) {
        $nameA = '?';
        $nameB = '?';
    }

    $matches = getHeadToHeadMatches($teamAId, $teamBId);
    $winsA = 0; $winsB = 0; $draws = 0;
    $tableRows = [];
    $rowTeamIds = [];
    $allTeamIds = [$teamAId, $teamBId];
    foreach ($matches as $m) {
        if ($m['h_tore'] === $m['g_tore']) {
            $draws++;
        } elseif (($m['heim_id'] === $teamAId && $m['h_tore'] > $m['g_tore'])
            || ($m['gast_id'] === $teamAId && $m['g_tore'] > $m['h_tore'])) {
            $winsA++;
        } else {
            $winsB++;
        }

        $datum = '–';
        if (!empty($m['zeit'])) {
            try {
                $datum = (new DateTime($m['zeit']))->format('d.m.Y');
            } catch (Throwable) {
                // $datum bleibt '–'
            }
        }
        $heimId = (int)($m['heim_id'] ?? 0);
        $gastId = (int)($m['gast_id'] ?? 0);
        $tableRows[] = [
            $datum,
            $m['runde_label'],
            partieTeamName($m, 'heim'),
            $m['h_tore'] . ' - ' . $m['g_tore'] . statusSuffix($m),
            partieTeamName($m, 'gast'),
        ];
        $rowTeamIds[] = [2 => $heimId, 4 => $gastId];
        $allTeamIds[] = $heimId;
        $allTeamIds[] = $gastId;
    }

    $title    = $nameA . ' vs ' . $nameB;
    $subtitle = tf('liga_h2h_wins', ['team' => $nameA]) . ': ' . $winsA
        . ' · ' . $draws . ' ' . tf('liga_h2h_draw')
        . ' · ' . tf('liga_h2h_wins', ['team' => $nameB]) . ': ' . $winsB;

    $headers = [tf('liga_col_datum'), tf('liga_col_spieltag_long'), tf('liga_col_heim'), tf('liga_col_ergebnis'), tf('liga_col_gast')];
    $aligns  = ['left', 'left', 'right', 'center', 'left'];

    $teamLogos = $showLogos ? pdfLoadTeamLogos($allTeamIds) : [];
    $logoCols  = $showLogos ? [2 => 'after', 4 => 'before'] : [];
    $vsTitleTeams = $showLogos ? ['idA' => $teamAId, 'nameA' => $nameA, 'idB' => $teamBId, 'nameB' => $nameB] : null;

    $footerText = tf('liga_pdf_footer', [
        'year'    => date('Y'),
        'version' => getAppVersion(),
    ]);

    $pdfBytes = buildStandingsPdf($title, $subtitle, $headers, $aligns, $tableRows, $footerText, null, [], $teamLogos, $logoCols, $rowTeamIds, $vsTitleTeams);

    $filenameBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $nameA . '_vs_' . $nameB);
    $filenameBase = trim((string)$filenameBase, '_');
    $filename     = ($filenameBase !== '' ? $filenameBase : 'vergleich') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    echo $pdfBytes;
}
