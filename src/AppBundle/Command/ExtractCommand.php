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

use AppBundle\Services\TranslationFileLoader\XliffFileLoader;
use PrestaShop\TranslationToolsBundle\Configuration;
use AppBundle\Extract\Dumper\XliffFileDumper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Component\Translation\MessageCatalogue;

class ExtractCommand extends BaseCommand
{
    protected static $defaultName = 'prestashop:translation:extract';

    /**
     * @var string
     */
    private $configFilePath;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var XliffFileDumper
     */
    private $xliffFileDumper;

    /**
     * @var ChainExtractor
     */
    private $chainExtractor;

    public function __construct(
        string $translationDirDump,
        string $locale,
        XliffFileDumper $xliffFileDumper,
        ChainExtractor $chainExtractor
    ) {
        parent::__construct($translationDirDump);
        $this->locale = $locale;
        $this->xliffFileDumper = $xliffFileDumper;
        $this->chainExtractor = $chainExtractor;
    }

    protected function configure()
    {
        $this
            ->setName('prestashop:translation:extract')
            ->addArgument('module', InputArgument::REQUIRED, 'Name of the module')
            ->addArgument('configfile', InputArgument::REQUIRED, 'Path to the config file')
            ->addOption('from-scratch', null, InputOption::VALUE_OPTIONAL, 'Build the catalogue from scratch instead of incrementally', false)
            ->addOption('default_locale', null, InputOption::VALUE_OPTIONAL, 'Default locale', 'en-US')
            ->addOption('domain_pattern', null, InputOption::VALUE_OPTIONAL, 'A regex to filter domain names', '#^Modules*|messages#')
            ->setDescription('Extract translation');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleName = $input->getArgument('module');
        $configFile = $this->configFilePath = $input->getArgument('configfile');
        $buildFromScratch = (bool) $input->getOption('from-scratch');

        if (!file_exists($configFile)) {
            throw new FileNotFoundException(null, 0, null, $configFile);
        }

        Configuration::fromYamlFile($configFile);

        $output->writeln(sprintf('Extracting Translations for locale <info>%s</info>', $this->locale));

        $moduleFolder = dirname($configFile);
        $version = $this->getVersionFromModuleFolder($moduleFolder, $moduleName);

        $output->writeln('<info>Found version ' . $version->getVersion() . ' of the module</info>');

        $catalogRelativePath = $version->getDefaultCatalogFolder();
        $catalog = $this->extract($buildFromScratch, $catalogRelativePath);

        $catalog = $this->filterCatalogue($catalog, $input->getOption('domain_pattern'));

        $path = sprintf('%s%s%s', $this->translationDirDump, DIRECTORY_SEPARATOR, 'translatables');
        $locale = $input->getOption('default_locale');
        $this->xliffFileDumper->dump($catalog, [
            'path' => $path,
            'root_dir' => dirname($configFile),
            'default_locale' => $locale,
        ]);

        $output->writeln('<info>Dump ' . $path . '</info>');

        return 0;
    }

    protected function extract(bool $buildFromScratch, string $catalogRelativePath): MessageCatalogue
    {
        $catalog = new MessageCatalogue($this->locale);

        if (!$buildFromScratch) {
            $this->loadExistingCatalog($catalog, $catalogRelativePath);
        }

        $this->chainExtractor->extract(Configuration::getProjectDirectory(), $catalog);

        return $catalog;
    }

    /**
     * Loads the existing catalog into the provided one
     */
    private function loadExistingCatalog(MessageCatalogue $catalog, string $catalogRelativePath)
    {
        $catalogPath = dirname($this->configFilePath) . $catalogRelativePath;

        $loader = new XliffFileLoader();

        $finder = new Finder();

        $finder->ignoreUnreadableDirs();

        $finder->files()->in($catalogPath)->name('*.xlf');

        foreach ($finder as $file) {
            $fileName = $file->getFilename();
            $domainName = $this->buildDomainName($fileName);
            $catalog->addCatalogue(
                $loader->load($file->getPathname(), $this->locale, $domainName)
            );
        }
    }

    /**
     * Builds a domain name like My.Domain.Name from a filename like MyDomainName.xlf
     */
    private function buildDomainName(string $fileName): string
    {
        $baseName = substr($fileName, 0, -4);
        // explode CamelCaseWords into Camel.Case.Words
        $return = preg_replace('/((?<=[a-z0-9])[A-Z])/', '.\1', $baseName);
        if (!is_string($return)) {
            throw new \RuntimeException('Unexpected replacement return: ' . print_r($return, true));
        }

        return $return;
    }

    /**
     * Filter the catalogue given with the domain matching the pattern.
     */
    private function filterCatalogue(MessageCatalogue $catalog, string $domainPattern): MessageCatalogue
    {
        $newCatalogue = new MessageCatalogue($catalog->getLocale());
        $metadata = $catalog->getMetadata('', '');

        foreach ($catalog->all() as $domain => $messages) {
            if (preg_match($domainPattern, $domain)) {
                $newCatalogue->add(
                    $messages,
                    $domain
                );

                if (isset($metadata[$domain])) {
                    foreach ($metadata[$domain] as $key => $value) {
                        $newCatalogue->setMetadata($key, $value, $domain);
                    }
                }
            }
        }

        return $newCatalogue;
    }
}
