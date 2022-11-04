<?php

/**
 * 2007-2016 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ExportDefaultCatalogCommand extends BaseCommand
{
    const SUCCESS_EXPORT = 0;

    const FAILURE_ON_COPY = 2;
    const FAILURE_ON_FOLDER_CREATION = 3;
    const FAILURE_ON_EMPTY_CATALOG = 4;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(
        string $translationDirDump,
        string $locale
    ) {
        parent::__construct($translationDirDump);
        $this->locale = $locale;
        $this->fs = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('prestashop:translation:export')
            ->setDescription('Build module default catalog')
            ->addArgument('module', InputArgument::REQUIRED, 'Module folder where the catalog will be exported')
            ->addArgument('module-folder', InputArgument::REQUIRED, 'Module folder where the catalog will be exported')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translatableFolder = $this->translationDirDump . DIRECTORY_SEPARATOR . 'translatables';

        $localeFolder = $translatableFolder . DIRECTORY_SEPARATOR . $this->locale;
        $defaultFolder = $translatableFolder . DIRECTORY_SEPARATOR . 'default';

        try {
            $this->fs->remove($defaultFolder);
            $this->fs->mkdir($defaultFolder);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<error>An error occurred while creating your directory at ' . $e->getMessage() . '</error>');

            return self::FAILURE_ON_FOLDER_CREATION;
        }

        $files = (new Finder())
            ->files()
            ->in($localeFolder)
            ->name('*.xlf');

        if (!$files->count()) {
            $output->writeln('<error>Your ' . $localeFolder . ' folder is empty. No xlf :(</error>');

            return self::FAILURE_ON_EMPTY_CATALOG;
        }

        // Copy to default folder
        try {
            $this->copyCatalogFiles($files, $defaultFolder, $output);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<error>An error occurred while copying your file at ' . $e->getMessage() . '</error>');

            return self::FAILURE_ON_COPY;
        }

        // Copy to module folder
        $module = $input->getArgument('module');
        $moduleFolder = $input->getArgument('module-folder');
        $version = $this->getVersionFromModuleFolder($moduleFolder, $module);
        $catalogFolder = rtrim($moduleFolder, '/') . rtrim($version->getCatalogsFolder(), '/') . '/' . $this->locale;
        $output->writeln(sprintf('Exporting catalog to module folder %s', $catalogFolder));

        $this->fs->remove($catalogFolder);
        $this->fs->mkdir($catalogFolder);

        try {
            $this->copyCatalogFiles($files, $catalogFolder, $output);
        } catch (IOExceptionInterface $e) {
            $output->writeln('<error>An error occurred while copying your file at ' . $e->getMessage() . '</error>');

            return self::FAILURE_ON_COPY;
        }

        return self::SUCCESS_EXPORT;
    }

    private function copyCatalogFiles(Finder $files, string $exportFolder, OutputInterface $output): void
    {
        foreach ($files as $file) {
            $filename = $file->getRelativePathName();

            if (!empty($suffix)) {
                $filename = sprintf(
                    '%s.%s',
                    $file->getFilenameWithoutExtension(),
                    $file->getExtension()
                );
            }
            $this->fs->copy(
                $file->getPathName(),
                $exportFolder . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '', $filename),
                true
            );
        }

        $output->writeln('<info>Your default catalog is exported at ' . $exportFolder . '</info>');
    }
}
