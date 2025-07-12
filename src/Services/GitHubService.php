<?php

namespace Sourceinja\RegisterModule\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Sourceinja\RegisterModule\Exceptions\SourceinjaException;

class GitHubService implements RepositoryServiceInterface
{
    private string $api_url;
    private string $api_key;
    private ?string $organization;
    private string $username;
    private mixed $cachePrefix;
    private bool $isPersonalAccount;

    public function __construct()
    {
        $this->cachePrefix = config('sourceinja.prefix_cache');
        $this->api_url = config('sourceinja.services.github.api_url', 'https://api.github.com');
        $this->api_key = config('sourceinja.services.github.api_key');
        $this->organization = config('sourceinja.services.github.organization');
        $this->username = config('sourceinja.services.github.username');

        // Use personal account if no organization is set or username is explicitly set
        $this->isPersonalAccount = empty($this->organization) || !empty($this->username);
    }

    public function checkApiKey()
    {
        if (empty($this->api_url) || empty($this->api_key)) {
            throw new SourceinjaException('Please set GitHub URL and API key in config file', 500);
        }

        if ($this->isPersonalAccount && empty($this->username)) {
            throw new SourceinjaException('Please set GitHub username in config file for personal account', 500);
        }

        if (!$this->isPersonalAccount && empty($this->organization)) {
            throw new SourceinjaException('Please set GitHub organization in config file for organization account', 500);
        }
    }

    public function getAllGroups(): array
    {
        if (Cache::has($this->cachePrefix . 'github_groups')) {
            $response = Cache::get($this->cachePrefix . 'github_groups');
        } else {
            $endpoint = $this->isPersonalAccount
                ? '/user'
                : '/orgs/' . $this->organization;

            $response = Http::withHeaders([
                'Authorization' => 'token ' . $this->api_key,
                'Accept' => 'application/vnd.github.v3+json'
            ])->get($this->api_url . $endpoint);

            if ($response->status() != 200) {
                throw new SourceinjaException('Error in get user/organization from GitHub', $response->status());
            }

            $response = $response->json();
            Cache::put($this->cachePrefix . 'github_groups', $response, 3600);
        }

        return [
            'id' => $response['id'],
            'name' => $response['name'] ?? $response['login'],
            'full_path' => $response['login']
        ];
    }

    public function getSubGroups($id): array
    {
        // For personal accounts, we'll just create a virtual "modules" group
        if ($this->isPersonalAccount) {
            return [
                'id' => $id,  // Just pass through the user ID
                'name' => 'modules',
                'full_path' => ($this->username ?? 'user') . '/modules'
            ];
        }

        // Organization logic remains the same
        if (Cache::has($this->cachePrefix . 'github_teams')) {
            $response = Cache::get($this->cachePrefix . 'github_teams');
        } else {
            $response = Http::withHeaders([
                'Authorization' => 'token ' . $this->api_key,
                'Accept' => 'application/vnd.github.v3+json'
            ])->get($this->api_url . '/orgs/' . $this->organization . '/teams');

            $response = $response->json();
            Cache::put($this->cachePrefix . 'github_teams', $response, 3600);
        }

        $modulesTeam = collect($response)->filter(function ($team) {
            return $team['name'] == 'modules';
        })->first();

        if (!$modulesTeam) {
            $modulesTeam = $response[0] ?? ['id' => $id, 'name' => 'default', 'slug' => 'default'];
        }

        return [
            'id' => $modulesTeam['id'],
            'name' => $modulesTeam['name'],
            'full_path' => $this->organization . '/' . $modulesTeam['slug']
        ];
    }

    public function getProjects($id, bool $withRelease = false): Collection
    {
        if (Cache::has($this->cachePrefix . 'github_projects')) {
            $response = Cache::get($this->cachePrefix . 'github_projects');
        } else {
            $endpoint = $this->isPersonalAccount
                ? '/user/repos?per_page=100'
                : '/orgs/' . $this->organization . '/repos?per_page=100';

            $response = Http::withHeaders([
                'Authorization' => 'token ' . $this->api_key,
                'Accept' => 'application/vnd.github.v3+json'
            ])->get($this->api_url . $endpoint);

            $response = $response->json();
            Cache::put($this->cachePrefix . 'github_projects', $response, 3600);
        }

        return $withRelease ? $this->mapProjectsWithRelease($response) : $this->mapProjects($response);
    }

    // Rest of the methods remain the same
    public function getReleases($id): mixed
    {
        // TODO: Implement getReleases() method.
    }

    public function cloneRepo(array $project): void
    {
        // TODO: Implement cloneRepo() method.
    }
}