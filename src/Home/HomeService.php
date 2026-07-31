<?php
/**
 * Project: LMOnext
 * Filename: src/Home/HomeService.php
 * Fileversion: 1.0.0
 * Changelog: 1.0.0 - Initiale Version: Teil der Umstrukturierung von frontend/data_home.php
 *                     (siehe frontend/data_home.php 3.0.0 für den vollen Kontext der
 *                     Umstellung). Fassade: kombiniert HomeRepository + HomeRenderer zu einer stabilen API für home.php.
 *
 * PHP version 8.2
 *
 * @author    Torsten Hofmann <https://bastel-code.de/>
 * @copyright 2026 Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types=1);

namespace LMOnext\Home;

final class HomeService
{
    public function __construct(
        private readonly HomeRepository $repository = new HomeRepository(),
        private readonly HomeRenderer $renderer = new HomeRenderer(),
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function getActiveLigenList(): array
    {
        return $this->repository->getActiveLigenList();
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    public function getArchivedLigenByFolder(): array
    {
        return $this->repository->getArchivedLigenByFolder();
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    public function getArchivFolderTree(): array
    {
        return $this->repository->getArchivFolderTree();
    }

    public function getLastError(): ?string
    {
        return $this->repository->getLastError();
    }

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->repository->getErrors();
    }

    /** @param array<int, array<int, array<string, mixed>>> $byParent */
    /** @param array<int, array<int, array<string, mixed>>> $ligenByFolder */
    public function renderArchivFolderTree(array $byParent, array $ligenByFolder, int $parentId = 0): string
    {
        return $this->renderer->renderArchivFolderTree($byParent, $ligenByFolder, $parentId);
    }

    /** @param array<string, mixed> $liga */
    public function renderLigaLink(array $liga): string
    {
        return $this->renderer->renderLigaLink($liga);
    }
}
