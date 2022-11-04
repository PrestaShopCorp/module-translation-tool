<?php
/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

namespace AppBundle\PrestaShop;

use Monolog\Logger;

class LocaleMapper
{
    private $mapping;
    private $logger;
    private $parent = [
        'at' => 'de',
        'bs' => 'en',
        'eu' => 'es',
        'hi' => 'en',
        'lu' => 'de',
        'pe' => 'es',
        'sw' => 'en',
        'ta' => 'en',
        'te' => 'en',
        'vi' => 'en',
    ];

    public function __construct(
        string $filePath,
        Logger $logger
    ) {
        $this->mapping = json_decode(
            file_get_contents($filePath),
            true
        );
        $this->logger = $logger;
    }

    public function getLegacyIso($locale)
    {
        if (isset($this->mapping[$locale])) {
            return $this->mapping[$locale];
        } else {
            $this->logger->warning("Locale $locale can't be mapped to legacy iso code");

            return $locale;
        }
    }

    public function getAllLegacyIso()
    {
        return array_values($this->mapping);
    }

    public function getAllLocale()
    {
        return array_keys($this->mapping);
    }

    public function getParentLegacyIso($iso)
    {
        if (isset($this->parent[$iso])) {
            return $this->parent[$iso];
        }

        return false;
    }
}
