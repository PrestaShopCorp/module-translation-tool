<?php

namespace AppBundle\Github;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Github\Api\AbstractApi;
use Github\AuthMethod;
use Github\Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class Api extends AbstractApi
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $githubToken;

    public function __construct(string $ghToken)
    {
        $filesystemAdapter = new Local(__DIR__ . '/../../../var/');
        $filesystem = new Filesystem($filesystemAdapter);
        $pool = new FilesystemCachePool($filesystem);

        $this->client = new Client();
        $this->client->addCache($pool);

        $this->githubToken = $ghToken;
        $this->client->authenticate($this->githubToken, 'anything', AuthMethod::ACCESS_TOKEN);

        parent::__construct($this->client);
    }

    /**
     * Params examples
     *  'base'  => 'master',
     *  'head'  => 'testbranch',
     *  'title' => 'My nifty pull request',
     *  'body'  => 'This pull request contains a bunch of enhancements and bug-fixes, happily shared with you'
     *
     * @param string $username
     * @param string $repository
     * @param array $params
     *
     * @return mixed
     */
    public function createPullRequest(string $username, string $repository, array $params)
    {
        return $this->post(
            '/repos/'.rawurlencode($username).'/'.rawurlencode($repository).'/pulls',
            $params,
            [
                'Authorization' => sprintf('token %s', $this->githubToken),
            ]
        );
    }

    /**
     * Get a list of pull requests for a repository
     *
     * @param string $username
     * @param string $repository
     * @param array $params
     *
     * @return array
     */
    public function getPullRequests(string $username, string $repository, array $params = [])
    {
        return $this->client->api('pull_request')->all($username, $repository, $params);
    }
}
