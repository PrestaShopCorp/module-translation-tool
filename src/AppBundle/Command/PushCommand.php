<?php

namespace AppBundle\Command;

use AppBundle\Github\Api;
use AppBundle\Services\GitRepository;
use Cz\Git\GitException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class PushCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Api
     */
    private $githubApi;

    public function __construct(Api $githubApi) {
        parent::__construct();

        $this->fs = new Filesystem();
        $this->githubApi = $githubApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('prestashop:translation:push-on-git')
            ->setDescription('Push updates on repository on GitHub and creates a Pull Request')
            ->addArgument('repository-url', InputArgument::REQUIRED, 'The repository URL with authentication params')
            ->addArgument('module', InputArgument::REQUIRED, 'Name of the module')
            ->addArgument('workdir', InputArgument::REQUIRED, 'The directory where the sources are')
            ->addArgument('source-branch', InputArgument::REQUIRED, 'The branch which translations are extracted')
            ->addOption('push-on-branch', null, InputOption::VALUE_OPTIONAL, 'If option is used the changes are pushed on a branch (if option is left empty an automatic name is generated).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'If option is used then no changes are not committed in the repository allowing you to check the differences.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repositoryUrl = $input->getArgument('repository-url');
        $moduleName = $input->getArgument('module');
        $workDir = $input->getArgument('workdir');
        $sourceBranch = $input->getArgument('source-branch');

        $pushOnBranch = $input->getOption('push-on-branch');
        $commitChanges = !$input->getOption('dry-run');

        $gitDirectoryPath = sprintf('%s/%s', rtrim($workDir, '/'), $moduleName);

        if (!$this->fs->exists($gitDirectoryPath)) {
            $output->writeln(sprintf('<error>Module directory [%s] not found</error>', $gitDirectoryPath));

            return 1;
        }

        $output->writeln(sprintf('Getting existing repository at %s', $gitDirectoryPath));
        $repository = new GitRepository($gitDirectoryPath);
        $repository->setRemoteUrl('origin', $repositoryUrl);

        $dateTime = date('Y-m-d-H_i_s');

        // When option is null it means no specific value has been specified so an automatic branch name is generated
        $branchName = $pushOnBranch ?? sprintf('update-translations-%s-%s', $sourceBranch, $dateTime);
        $remoteBranches = $repository->getRemoteBranches();
        if (in_array('origin/' . $branchName, $remoteBranches)) {
            $output->writeln(sprintf('Checking out to existing branch %s', $branchName));
            $repository->checkout($branchName);
        } else {
            $output->writeln(sprintf('Creating new branch %s', $branchName));
//            $repository->execute('stash');
            $repository->checkout($sourceBranch);
//            @$repository->execute(['stash', 'pop']);
            $repository->createBranch($branchName, true);
        }

        // Push changes
        if (!$repository->hasChanges()) {
            $this->output->writeln('<comment>No changes to push!</comment>');
        } elseif ($commitChanges) {
            try {
                // Commit changes
                $repository->addAllChanges();
                $repository->commit(sprintf('Translation catalogue update for version %s %s', $sourceBranch, $dateTime));
                $repository->push($repositoryUrl, [$branchName]);
                $output->writeln('<info>Translations pushed</info>');

                // Create the pull request
                $repositoryName = GitRepository::extractRepositoryNameFromUrl($repositoryUrl);
                $repositoryUsername = GitRepository::extractUsernameFromUrl($repositoryUrl);

                if (!empty($repositoryUsername)) {
                    $pullRequest = $this->githubApi->createPullRequest($repositoryUsername, $repositoryName, [
                        'base'  => $sourceBranch,
                        'head'  => $branchName,
                        'title' => sprintf('Translation catalogue update for version %s %s', $sourceBranch, $dateTime),
                        'body'  => sprintf('This pull request contains the default catalogue updates introduced in the branch [%s]', $sourceBranch),
                    ]);

                    $output->writeln(sprintf('<info>Pull request created [#%d]</info>', (int) $pullRequest['number']));
                } else {
                    $output->writeln('<error>Error getting the repository username</error>');
                }
            } catch (GitException $e) {
                $output->writeln(sprintf('GitException occurred: %s-%s', $e->getCode(), $e->getMessage()));

                return 1;
            }
        } else {
            $output->writeln(sprintf(
                'Some changes have been made but not committed, you can check them at %s',
                $gitDirectoryPath
            ));
        }

        return 0;
    }
}
