<?php

namespace Sourceinja\RegisterModule\Services;

class GitLabService implements RepositoryServiceInterface
{

    public function getModules()
    {
        // TODO: Implement getModules() method.
    }

    public function installModule($moduleId)
    {
        // TODO: Implement installModule() method.
    }

    public function checkApiKey()
    {
        // TODO: Implement checkApiKey() method.
    }

    public function getAllGroups(): array
    {
        // TODO: Implement getAllGroups() method.
    }

    public function getSubGroups($id): array
    {
        // TODO: Implement getSubGroups() method.
    }

    public function getProjects($id, bool $withRelease = false): Collection
    {
        // TODO: Implement getProjects() method.
    }

    public function getReleases($id): mixed
    {
        // TODO: Implement getReleases() method.
    }

    public function cloneRepo(array $project): void
    {
        // TODO: Implement cloneRepo() method.
    }
}