<?php
/**
 * Project: LMOnext
 * Filename: frontend/pdf_export.php
 * Fileversion: 1.5.1
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
function pdfEstimateTextWidth(string $s, float $size, bool $bold) : float
{
    $factor = $bold ? 0.56 : 0.5;
    return strlen($s) * $size * $factor;
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
function buildResultsPdf(string $ligaName, string $spieltagLabel, array $columnHeaders, array $rows, string $statsLine, string $footerText = '') : string
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

    // Wandelt UTF-8 in die Zielkodierung der PDF-Kernschriften um und kürzt
    // danach bei Bedarf (Kürzung muss auf der bereits umgewandelten,
    // Single-Byte-Fassung passieren, siehe pdfConvertEncoding()/pdfTruncate()).
    $prep = static function (string $utf8, int $maxLen = 0) : string {
        $converted = pdfConvertEncoding($utf8);
        return $maxLen > 0 ? pdfTruncate($converted, $maxLen) : $converted;
    };

    // ── Erst alle Zellen aufbereiten + Spaltenbreiten aus dem tatsächlichen
    // Inhalt schätzen, damit die Tabelle nur so breit wird wie nötig ─────────
    $headCells = [];
    foreach ($columnHeaders as $i => $label) {
        $headCells[$i] = $prep($label);
    }
    $bodyCells = [];
    foreach ($rows as $row) {
        $bodyCells[] = [
            $prep($row['datum'], $colTrunc[0]),
            $prep($row['heim'], $colTrunc[1]),
            $prep($row['ergebnis'], $colTrunc[2]),
            $prep($row['gast'], $colTrunc[3]),
        ];
    }

    $colWidths = [];
    for ($i = 0; $i < 4; $i++) {
        $w = pdfEstimateTextWidth($headCells[$i], $headFontSize, true);
        foreach ($bodyCells as $cells) {
            $w = max($w, pdfEstimateTextWidth($cells[$i], $rowFontSize, false));
        }
        $colWidths[$i] = $w;
    }

    // Spalten-Startpositionen (Datum, Heim, Ergebnis, Gast) aus den
    // geschätzten Breiten + fixem Spaltenabstand ableiten. Die ganze Tabelle
    // wird horizontal auf der Seite zentriert (nicht am linken Rand fixiert).
    $tableWidth = $colWidths[0] + $colGap + $colWidths[1] + $colGap + $colWidths[2] + $colGap + $colWidths[3];
    $tableStartX = ($pageWidth - $tableWidth) / 2;

    $colX = [];
    $colX[0] = $tableStartX;
    $colX[1] = $colX[0] + $colWidths[0] + $colGap;
    $heimRightEdge = $colX[1] + $colWidths[1];
    $colX[2] = $heimRightEdge + $colGap;
    $ergebnisCenterX = $colX[2] + $colWidths[2] / 2;
    $colX[3] = $colX[2] + $colWidths[2] + $colGap;
    $tableRight = $colX[3] + $colWidths[3];

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

    // Liganame zentriert, fett, in Akzentfarbe
    $nameSize = 17.0;
    $nameText = $prep($ligaName, 60);
    $nameX = ($pageWidth - pdfEstimateTextWidth($nameText, $nameSize, true)) / 2;
    $setColor($accentR, $accentG, $accentB);
    $addText($nameText, max($marginX, $nameX), $y, 'F2', $nameSize);
    $y -= 20;

    // Spieltag-Angabe zentriert, normale Schrift, gedämpfte Farbe
    if ($spieltagLabel !== '') {
        $labelSize = 10.5;
        $labelText = $prep($spieltagLabel, 90);
        $labelX = ($pageWidth - pdfEstimateTextWidth($labelText, $labelSize, false)) / 2;
        $setColor($mutedR, $mutedG, $mutedB);
        $addText($labelText, max($marginX, $labelX), $y, 'F1', $labelSize);
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
        $setColor($textR, $textG, $textB);
        foreach ($cells as $i => $cell) {
            if ($i === 1) {
                $addTextRight($cell, $heimRightEdge, $y, 'F1', $rowFontSize, false);
            } elseif ($i === 2) {
                $addTextCentered($cell, $ergebnisCenterX, $y, 'F1', $rowFontSize, false);
            } else {
                $addText($cell, $colX[$i], $y, 'F1', $rowFontSize);
            }
        }
        $y -= $lineHeight;
        $rowIndex++;
    }

    // Tore-Schnitt-Zeile am Ende der Ergebnisliste
    if ($statsLine !== '') {
        if ($y < $marginBottom + $lineHeight) {
            $startNewPage();
        }
        $y -= 4;
        $setColor($mutedR, $mutedG, $mutedB);
        $addText($prep($statsLine, 120), $tableStartX, $y, 'F1', $headFontSize);
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

    return assemblePdfBytes($pagesContent, $pageWidth, $pageHeight, $logo);
}

/**
 * Baut ein A4-PDF mit der aktuellen Tabelle (Standings) einer regulären Liga
 * zusammen: gleiches Grundgerüst wie buildResultsPdf() (Logo oben links,
 * Liganame fett/farbig, Untertitel, zentrierte Tabelle mit Zebra-Streifen,
 * Fußzeile) – nur mit den 9 Tabellen-Spalten (#, Team, Sp, S, U, N, Tore,
 * Diff, Pkt) statt der 4 Ergebnis-Spalten. Die Pkt-Spalte wird wie in der
 * HTML-Ansicht (.st-pkt) fett und in Akzentfarbe hervorgehoben.
 *
 * @param array<int,string> $columnHeaders
 * @param array<int,string> $columnAligns 'left' oder 'center' je Spalte
 * @param array<int,array<int,string>> $rows Je Zeile eine Liste von Zellen-Strings
 */
function buildStandingsPdf(string $ligaName, string $subtitleLabel, array $columnHeaders, array $columnAligns, array $rows, string $footerText = '') : string
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
    $pktColIndex  = $colCount - 1;

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

    $colWidths = [];
    for ($i = 0; $i < $colCount; $i++) {
        $bold = $i === $pktColIndex;
        $w = pdfEstimateTextWidth($headCells[$i], $headFontSize, true);
        foreach ($bodyCells as $cells) {
            $w = max($w, pdfEstimateTextWidth($cells[$i], $rowFontSize, $bold));
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

    $addRect = function (float $x, float $yBottom, float $w, float $h) use (&$content) : void {
        $content .= number_format($x, 2, '.', '') . ' ' . number_format($yBottom, 2, '.', '') . ' '
            . number_format($w, 2, '.', '') . ' ' . number_format($h, 2, '.', '') . " re f\n";
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

    $addLogo();

    $nameSize = 17.0;
    $nameText = $prep($ligaName, 60);
    $nameX = ($pageWidth - pdfEstimateTextWidth($nameText, $nameSize, true)) / 2;
    $setColor($accentR, $accentG, $accentB);
    $addText($nameText, max($marginX, $nameX), $y, 'F2', $nameSize);
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
        $centerX = $colX[$i] + $colWidths[$i] / 2;
        if ($columnAligns[$i] === 'center') {
            $addTextCentered($label, $centerX, $y, 'F2', $headFontSize, true);
        } else {
            $addText($label, $colX[$i], $y, 'F2', $headFontSize);
        }
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
        foreach ($cells as $i => $cell) {
            $bold = $i === $pktColIndex;
            if ($bold) {
                $setColor($accentR, $accentG, $accentB);
            } else {
                $setColor($textR, $textG, $textB);
            }
            $centerX = $colX[$i] + $colWidths[$i] / 2;
            if ($columnAligns[$i] === 'center') {
                $addTextCentered($cell, $centerX, $y, $bold ? 'F2' : 'F1', $rowFontSize, $bold);
            } else {
                $addText($cell, $colX[$i], $y, $bold ? 'F2' : 'F1', $rowFontSize);
            }
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

    return assemblePdfBytes($pagesContent, $pageWidth, $pageHeight, $logo);
}

/**
 * Setzt aus fertigen Seiten-Content-Streams ein vollständiges PDF-Dokument
 * zusammen: Catalog-, Pages-, Font-, ggf. Bild- und Page/Contents-Objekte
 * plus xref-Tabelle und Trailer, alles von Hand geschrieben (kein PDF-Toolkit).
 *
 * @param array<int,string> $pagesContent Ein Content-Stream (PDF-Operatoren) pro Seite
 * @param array{widthPx:int,heightPx:int,rgbZlib:string,alphaZlib:string}|null $logo
 */
function assemblePdfBytes(array $pagesContent, float $pageWidth, float $pageHeight, ?array $logo = null) : string
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

    $xobjectEntry = '';
    if ($logo !== null) {
        $smaskNum = $fontF2Num + 1;
        $imgNum   = $smaskNum + 1;
        $objects[$smaskNum] = '<< /Type /XObject /Subtype /Image /Width ' . $logo['widthPx']
            . ' /Height ' . $logo['heightPx'] . ' /ColorSpace /DeviceGray /BitsPerComponent 8'
            . ' /Filter /FlateDecode /Length ' . strlen($logo['alphaZlib']) . " >>\nstream\n"
            . $logo['alphaZlib'] . "\nendstream";
        $objects[$imgNum] = '<< /Type /XObject /Subtype /Image /Width ' . $logo['widthPx']
            . ' /Height ' . $logo['heightPx'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8'
            . ' /SMask ' . $smaskNum . ' 0 R /Filter /FlateDecode /Length ' . strlen($logo['rgbZlib']) . " >>\nstream\n"
            . $logo['rgbZlib'] . "\nendstream";
        $xobjectEntry = ' /XObject << /Logo ' . $imgNum . ' 0 R >>';
    }

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
function exportErgebnissePdf(string $ligaName, string $roundLabel, string $dateRangeText, array $partien, ?string $spieltagStart) : void
{
    $rows = [];
    foreach ($partien as $p) {
        $gespielt = $p['h_tore'] !== null && $p['g_tore'] !== null;
        $ergebnis = $gespielt
            ? $p['h_tore'] . ' - ' . $p['g_tore'] . statusSuffix($p)
            : '- - -';
        $rows[] = [
            'datum'    => partieZeitDisplay($p, $spieltagStart),
            'heim'     => partieTeamName($p, 'heim'),
            'gast'     => partieTeamName($p, 'gast'),
            'ergebnis' => $ergebnis,
        ];
    }

    $spieltagLabel = $roundLabel . ($dateRangeText !== '' ? ' · ' . $dateRangeText : '');
    $headers  = [tf('liga_col_datum'), tf('liga_col_heim'), tf('liga_col_ergebnis'), tf('liga_col_gast')];

    $stats     = computeSpieltagStats($partien);
    $statsLine = tf('liga_stats_line', [
        'heim'     => $stats['schnittHeim'],
        'gast'     => $stats['schnittGast'],
        'tore'     => $stats['tore'],
        'proSpiel' => $stats['toreProSpiel'],
    ]);

    $footerText = tf('liga_pdf_footer', [
        'year'    => date('Y'),
        'version' => getAppVersion(),
    ]);

    $pdfBytes = buildResultsPdf($ligaName, $spieltagLabel, $headers, $rows, $statsLine, $footerText);

    $filenameBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $ligaName . '_' . $roundLabel);
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
function exportTabellePdf(string $ligaName, int $ligaId, array $allSpieltage) : void
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
    }

    $footerText = tf('liga_pdf_footer', [
        'year'    => date('Y'),
        'version' => getAppVersion(),
    ]);

    $pdfBytes = buildStandingsPdf($ligaName, tf('liga_tab_tabelle'), $headers, $aligns, $tableRows, $footerText);

    $filenameBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $ligaName . '_Tabelle');
    $filenameBase = trim((string)$filenameBase, '_');
    $filename     = ($filenameBase !== '' ? $filenameBase : 'tabelle') . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    echo $pdfBytes;
}
