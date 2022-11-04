<?php

/**
 * 2007-2015 PrestaShop.
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

namespace AppBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class IgnoreTwigFiltersAndFunctionsPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('twig')) {
            return;
        }

        $definition = $container->getDefinition('twig');

        $methodArguments = [
            [
                'AppBundle\DependencyInjection\CompilerPass\IgnoreTwigFiltersAndFunctionsPass',
                'ignoreFunction',
            ],
        ];

        $methodArgumentsFilter = [
            [
                'AppBundle\DependencyInjection\CompilerPass\IgnoreTwigFiltersAndFunctionsPass',
                'ignoreFilter',
            ],
        ];

        $definition->addMethodCall('registerUndefinedFunctionCallback', $methodArguments);
        $definition->addMethodCall('registerUndefinedFilterCallback', $methodArgumentsFilter);
    }

    /**
     * When a function is not found, return nothing.
     *
     * @return Twig_SimpleFunction
     */
    public static function ignoreFunction()
    {
        return new Twig_SimpleFunction('ignore', function () {
            return '';
        });
    }

    /**
     * When a filter is not found, return nothing.
     *
     * @return Twig_SimpleFilter
     */
    public static function ignoreFilter()
    {
        return new Twig_SimpleFilter('ignore', function () {
            return '';
        });
    }
}
