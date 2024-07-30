<?php

use PHPUnit\Framework\TestCase;
use AppBundle\Command\PushCommand;
use AppBundle\Github\Api;

class PushCommandTest extends TestCase
{
    public function testExtractOwnerName()
    {
        $repositoryUrl = 'https://oauth2:secret_github_token@github.com/owner_name/repository_name.git';
        $api = new Api('secret_github_token');
        $pushCommand = new PushCommand($api);

        $this->assertEquals('owner_name', $pushCommand->extractOwnerName($repositoryUrl));
    }

    public function testExtractOwnerNameWithNotUrl()
    {
        $repositoryUrl = '000000';
        $api = new Api('secret_github_token');
        $pushCommand = new PushCommand($api);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot extract owner name from repository URL.');

        $pushCommand->extractOwnerName($repositoryUrl);
    }
}
