<?php
/**
 * Project: LMOnext
 * Filename: frontend/pdf_export.php
 * Fileversion: 1.9.1
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Kompatibilitätsschicht für die frühere monolithische frontend/pdf_export.php.
 * Die eigentliche Implementierung liegt jetzt unter src/Pdf/PdfExporter.php.
 * Diese Datei lädt die Klasse und stellt alle bisherigen globalen Funktionsnamen
 * unverändert als dünne Delegations-Wrapper bereit.
 */
declare(strict_types = 1);

require_once __DIR__ . '/../src/Pdf/PdfExporter.php';

use LMOnext\Pdf\PdfExporter;

function pdfConvertEncoding(string $s) : string { return PdfExporter::pdfConvertEncoding($s); }
function pdfEscapeText(string $s) : string { return PdfExporter::pdfEscapeText($s); }
function pdfTruncate(string $s, int $max) : string { return PdfExporter::pdfTruncate($s, $max); }
function pdfHelveticaWidthsWithLatin1(array $base, bool $bold) : array { return PdfExporter::pdfHelveticaWidthsWithLatin1($base, $bold); }
function pdfEstimateTextWidth(string $s, float $size, bool $bold) : float { return PdfExporter::pdfEstimateTextWidth($s, $size, $bold); }
function pdfWrapText(string $text, float $maxWidth, float $size, bool $bold = false) : array { return PdfExporter::pdfWrapText($text, $maxWidth, $size, $bold); }
function pdfLoadLogoData() : ?array { return PdfExporter::pdfLoadLogoData(); }
function pdfLoadTeamLogoImage(string $relativePath) : ?array { return PdfExporter::pdfLoadTeamLogoImage($relativePath); }
function pdfInlineSvgClassStyles(string $svg) : string { return PdfExporter::pdfInlineSvgClassStyles($svg); }
function pdfGdImageToRaw($img) : ?array { return PdfExporter::pdfGdImageToRaw($img); }
function pdfRasterizeSvgViaImagick(string $absPath) : ?array { return PdfExporter::pdfRasterizeSvgViaImagick($absPath); }
function pdfRasterizeSvgViaRsvgConvert(string $absPath) : ?array { return PdfExporter::pdfRasterizeSvgViaRsvgConvert($absPath); }
function pdfLoadTeamLogos(array $teamIds) : array { return PdfExporter::pdfLoadTeamLogos($teamIds); }
function buildResultsPdf(string $ligaName, array $sections, string $footerText = '', array $teamLogos = []) : string { return PdfExporter::buildResultsPdf($ligaName, $sections, $footerText, $teamLogos); }
function buildStandingsPdf(string $ligaName, string $subtitleLabel, array $columnHeaders, array $columnAligns, array $rows, string $footerText = '', ?int $accentColIndex = null, array $rowBorderColors = [], array $teamLogos = [], array $logoCols = [], array $rowTeamIds = [], ?array $vsTitleTeams = null, ?string $noteLine = null, ?array $footnotes = null, bool $landscape = false) : string { return PdfExporter::buildStandingsPdf($ligaName, $subtitleLabel, $columnHeaders, $columnAligns, $rows, $footerText, $accentColIndex, $rowBorderColors, $teamLogos, $logoCols, $rowTeamIds, $vsTitleTeams, $noteLine, $footnotes, $landscape); }
function assemblePdfBytes(array $pagesContent, float $pageWidth, float $pageHeight, ?array $logo = null, array $teamLogos = []) : string { return PdfExporter::assemblePdfBytes($pagesContent, $pageWidth, $pageHeight, $logo, $teamLogos); }
function exportErgebnissePdf(string $ligaName, array $sectionSpecs, bool $showLogos = false) : void { PdfExporter::exportErgebnissePdf($ligaName, $sectionSpecs, $showLogos); }
function exportTabellePdf(string $ligaName, int $ligaId, array $allSpieltage, bool $showLogos = false, string $tableMode = 'gesamt', string $tmode = '') : void { PdfExporter::exportTabellePdf($ligaName, $ligaId, $allSpieltage, $showLogos, $tableMode, $tmode); }
function exportSpielplanPdf(string $ligaName, int $ligaId, array $allSpieltage, int $selectedTeamId, bool $showLogos = false) : void { PdfExporter::exportSpielplanPdf($ligaName, $ligaId, $allSpieltage, $selectedTeamId, $showLogos); }
function exportH2hPdf(int $teamAId, int $teamBId, bool $showLogos = false) : void { PdfExporter::exportH2hPdf($teamAId, $teamBId, $showLogos); }
