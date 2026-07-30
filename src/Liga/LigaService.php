<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/LigaService.php
 * Fileversion: 1.0.0
 * Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_liga.php in
 *                     fokussierte Traits (siehe frontend/data_liga.php 3.0.0 für den vollen
 *                     Kontext der Umstellung). Fassade: fasst alle Liga-Traits zu einer stabilen statischen API zusammen.
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @copyright 2026 Dietmar Kersting
 * @license   GPL-3.0-only
 */
declare(strict_types=1);

namespace LMOnext\Liga;

/**
 * Facade for all functionality formerly implemented in frontend/data_liga.php.
 *
 * The implementation is split into focused PSR-4 loadable traits while this
 * class keeps one stable static API. The legacy global function names are
 * provided by frontend/data_liga.php as compatibility wrappers.
 */
final class LigaService
{
    use LigaRepositoryTrait;
    use SpieltagRepositoryTrait;
    use TeamRepositoryTrait;
    use TeamFormattingTrait;
    use HeadToHeadTrait;
    use TournamentTrait;
    use StandingsTrait;
    use StatisticsTrait;
    use RenderViewsTrait;
}
