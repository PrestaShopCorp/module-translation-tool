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

namespace AppBundle\PrestaShop;

class Version
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $majorVersion;

    /**
     * @var string
     */
    private $catalogsFolder;

    public function __construct(string $version)
    {
        $this->setVersion($version);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getMajorVersion(): string
    {
        return $this->majorVersion;
    }

    /**
     * @return string
     */
    public function getCatalogsFolder(): string
    {
        return $this->catalogsFolder;
    }

    /**
     * @return string
     */
    public function getDefaultCatalogFolder(): string
    {
        return $this->getCatalogsFolder() . '/default';
    }

    private function setVersion(string $version): void
    {
        $this->version = $version;
        $this->majorVersion = $version;
        $this->catalogsFolder = '/translations';
    }
}
