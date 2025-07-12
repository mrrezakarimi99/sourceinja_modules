<?php

namespace Sourceinja\RegisterModule\Services;

interface RepositoryServiceInterface
{
    public function checkApiKey();

    public function getAllGroups(): array;

    public function getSubGroups($id): array;

    public function getProjects($id, bool $withRelease = false): Collection;

    public function getReleases($id): mixed;

    public function cloneRepo(array $project): void;
}