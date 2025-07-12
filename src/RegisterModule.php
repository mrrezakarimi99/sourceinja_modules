<?php

namespace Sourceinja\RegisterModule;

use Sourceinja\RegisterModule\Services\RepositoryServiceInterface;
use Sourceinja\RegisterModule\Services\ServiceFactory;

/**
 * Register Module from Gitlab to Project
 *
 * @package Sourceinja\RegisterModule
 */
class RegisterModule
{
    private RepositoryServiceInterface $service;

    public function __construct(RepositoryServiceInterface $service = null)
    {
        $this->service = $service ?? ServiceFactory::make();
    }

    public function checkApiKey()
    {
        return $this->service->checkApiKey();
    }

    public function getAllGroups()
    {
        return $this->service->getAllGroups();
    }

    public function getSubGroups($id)
    {
        return $this->service->getSubGroups($id);
    }

    public function getProjects($id, bool $withRelease = false)
    {
        return $this->service->getProjects($id, $withRelease);
    }

    public function getReleases($id)
    {
        return $this->service->getReleases($id);
    }

    public function cloneRepo(array $project)
    {
        return $this->service->cloneRepo($project);
    }
}
