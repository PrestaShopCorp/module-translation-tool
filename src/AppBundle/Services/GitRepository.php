<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace AppBundle\Services;

use Cz\Git\GitException;
use Cz\Git\GitRepository as BaseRepository;

/**
 * This class was introduced as an overlay of the initial GitRepository class because it lacked
 * details when an error occurred in a git command, no output was displayed so we override the tun
 * method to allow easier debugging.
 */
class GitRepository extends BaseRepository
{
    protected function run($cmd/*, $options = NULL*/)
    {
        $args = func_get_args();
        $cmd = self::processCommand($args);
        exec($cmd . ' 2>&1', $output, $ret);
        $outputString = var_export($output, true);

        if ($ret !== 0) {
            throw new GitException("Command '$cmd' failed (exit-code $ret) output: $outputString.", $ret);
        }

        return $this;
    }


    /**
     * @param  string  host:PrestaShopCorp/repo.git | host:xz/foo.git | ...
     * @return string  PrestaShopCorp | xz | ...
     */
    public static function extractUsernameFromUrl($url): ?string
    {
        if (preg_match('#https:\/\/(.*)@github.com\/(.*)\/(.*).git#', $url, $matches)) {
            return $matches[2];
        }

        return null;
    }
}
