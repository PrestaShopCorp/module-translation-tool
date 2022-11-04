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

use AppBundle\PrestaShop\Version;
use PrestaShop\TranslationToolsBundle\Translation\Extractor\Util\Flattenizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use ZipArchive;

abstract class BaseCommand extends Command
{
    /**
     * @var string
     */
    protected $translationDirDump;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(
        string $translationDirDump
    ) {
        parent::__construct();
        $this->translationDirDump = $translationDirDump;
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

    protected function getVersionFromModuleFolder(string $moduleFolder, string $moduleName): Version
    {
        $moduleMainFile = sprintf('%s/%s.php', rtrim($moduleFolder, '/'), $moduleName);
        if (! file_exists($moduleMainFile)) {
            throw new InvalidArgumentException(sprintf('Could not find module main file at %s', $moduleMainFile));
        }

        $moduleClassContent = file_get_contents($moduleMainFile);
        preg_match('/const[ ]+VERSION[ ]*=[ ]*\'(.+)\'/', $moduleClassContent, $matches);

        if (!empty($matches)) {
            $version = $matches[1];
        } else {
            preg_match('/this->version[ ]*=[ ]*\'(.+)\'/', $moduleClassContent, $matches);

            if (!empty($matches)) {
                $version = $matches[1];
            }
        }

        if (empty($version)) {
            throw new InvalidArgumentException(sprintf('Could not find module version in the main file at %s', $moduleMainFile));
        }

        return new Version($version);
    }
}
