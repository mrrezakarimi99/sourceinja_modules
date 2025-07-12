<?php

namespace Sourceinja\RegisterModule;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Sourceinja\RegisterModule\Exceptions\SourceinjaException;

/**
 * Register Module from Gitlab to Project
 *
 * @package Sourceinja\RegisterModule
 */
class RegisterModule
{
    private string $gitlab_url;
    private string $gitlab_api_key;
    private mixed $cachePrefix;

    /**
     * @throws SourceinjaException
     */
    public function __construct()
    {
        $this->cachePrefix = config('sourceinja.prefix_cache');
        $this->gitlab_url = config('sourceinja.gitlab_url');
        $this->gitlab_api_key = config('sourceinja.gitlab_api_key');
    }

    /**
     * @return array
     */
    public function getAllGroups(): array
    {
        if (Cache::has($this->cachePrefix . 'groups')) {
            $response = Cache::get($this->cachePrefix . 'groups');
        } else {
            $response = Http::withHeaders([
                'PRIVATE-TOKEN' => $this->gitlab_api_key
            ])->get($this->gitlab_url . '/groups?search=sourceInja');
            if ($response->status() != 200) {
                throw new SourceinjaException('Error in get groups from gitlab' , $response->status());
            }
            $response = $response->json();
            Cache::put($this->cachePrefix . 'groups' , $response , 3600);
        }
        return $this->mapGroups($response);
    }

    /**
     * @param $id
     * @return array
     */
    public function getSubGroups($id): array
    {
        if (Cache::has($this->cachePrefix . 'sub_groups')) {
            $response = Cache::get($this->cachePrefix . 'sub_groups');
        } else {
            $response = Http::withHeaders([
                'PRIVATE-TOKEN' => $this->gitlab_api_key
            ])->get($this->gitlab_url . "/groups/$id/subgroups?search=modules");
            $response = $response->json();
            Cache::put($this->cachePrefix . 'sub_groups' , $response , 3600);
        }
        return $this->mapSubGroups($response);
    }

    /**
     * @param $id
     * @param bool $withRelease
     * @return Collection
     */
    public function getProjects($id , bool $withRelease = false): Collection
    {
        if (Cache::has($this->cachePrefix . 'projects')) {
            $response = Cache::get($this->cachePrefix . 'projects');
        } else {
            $response = Http::withHeaders([
                'PRIVATE-TOKEN' => $this->gitlab_api_key
            ])->get($this->gitlab_url . "/groups/$id/projects?per_page=100");
            $response = $response->json();
            Cache::put($this->cachePrefix . 'projects' , $response , 3600);
        }
        return $withRelease ? $this->mapProjectsWithRelease($response) : $this->mapProjects($response);
    }

    /**
     * @param $id
     * @return array|mixed
     */
    public function getReleases($id): mixed
    {
        if (Cache::has($this->cachePrefix . 'releases_' . $id)) {
            $response = Cache::get($this->cachePrefix . 'releases_' . $id);
        } else {
            $response = Http::withHeaders([
                'PRIVATE-TOKEN' => $this->gitlab_api_key
            ])->get($this->gitlab_url . "/projects/$id/releases");
            $response = $response->json();
            Cache::put($this->cachePrefix . 'releases_' . $id , $response , 3600);
        }
        return $response;
    }

    /**
     * @param $groups
     * @return array
     */
    public function mapGroups($groups): array
    {
        return collect($groups)->filter(function ($group) {
            return $group['full_path'] == 'sourceInja/core';
        })->first();
    }

    /**
     * @param $subGroups
     * @return array
     */
    public function mapSubGroups($subGroups): array
    {
        return collect($subGroups)->filter(function ($sub) {
            return Str::contains($sub['full_path'] , 'laravel-modules');
        })->first();
    }

    /**
     * @param $projects
     * @return Collection
     */
    public function mapProjects($projects): Collection
    {
        return collect($projects)->map(function ($project) {
            return [
                'id'         => $project['id'] ,
                'name'       => $project['name'] ,
                'updated_at' => $project['updated_at'] ? Carbon::parse($project['updated_at'])->diffForHumans() : 'No update date' ,
            ];
        });
    }

    /**
     * @param $projects
     * @return Collection
     */
    public function mapProjectsWithRelease($projects): Collection
    {
        return collect($projects)->map(function ($project) {
            $releases = $this->getReleases($project['id']);
            if (count($releases) > 0) {
                $releases = collect($releases)->map(function ($release) {
                    return [
                        'name'        => $release['name'] ,
                        'tag_name'    => $release['tag_name'] ,
                        'released_at' => $release['released_at'] ? Carbon::parse($release['released_at'])->diffForHumans() : 'No release date' ,
                        'assets'      => collect($release['assets']['sources'])->filter(function ($asset) {
                            return $asset['format'] == 'tar.gz';
                        })->toArray() ,
                    ];
                })->toArray();
            }
            return [
                'id'               => $project['id'] ,
                'name'             => $project['name'] ,
                'default_branch'   => $project['default_branch'] ,
                'http_url_to_repo' => $project['http_url_to_repo'] ,
                'updated_at'       => $project['updated_at'] ? Carbon::parse($project['updated_at'])->diffForHumans() : 'No update date' ,
                'release'          => $releases ,
            ];
        });
    }

    public function getBranches($project): array
    {
        $response = Http::withHeaders([
            'PRIVATE-TOKEN' => $this->gitlab_api_key
        ])->get($this->gitlab_url . "/projects/{$project['id']}/repository/branches");
        if ($response->status() != 200) {
            throw new SourceinjaException('Error in get branches from gitlab', $response->status());
        }

        return $this->mapBranches($response->json());
    }

    /**
     * @param $branches
     * @return array
     */
    public function mapBranches($branches): array
    {
        return collect($branches)->map(function ($branch) {
            return [
                'name' => $branch['name'],
                'last_commit_id' => $branch['commit']['short_id'],
                'last_commit_title' => $branch['commit']['title'],
            ];
        })->toArray();
    }

    /**
     * @throws SourceinjaException
     */
    public function checkApiKey()
    {
        if (empty($this->gitlab_url) || empty($this->gitlab_api_key)) {
            throw new SourceinjaException('Please set gitlab url and api key in config file' , 500);
        }
    }
}
