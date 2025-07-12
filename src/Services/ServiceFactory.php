<?php

namespace Sourceinja\RegisterModule\Services;

use Sourceinja\RegisterModule\Exceptions\SourceinjaException;

class ServiceFactory
{
    public static function make($service = null): RepositoryServiceInterface
    {
        $service = $service ?? config('sourceinja.default_service', 'gitlab');

        return match($service) {
            'github' => new GitHubService(),
            'gitlab' => new GitLabService(),
            default => throw new SourceinjaException("Unknown service: {$service}", 500),
        };
    }
}