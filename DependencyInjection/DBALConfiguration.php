<?php

/*
 * This file is part of the Drift Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\DBAL\DependencyInjection;

use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class DBALConfiguration.
 */
class DBALConfiguration extends BaseConfiguration
{
    /**
     * Configure the root node.
     *
     * @param ArrayNodeDefinition $rootNode Root node
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('connections')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('driver')
                                ->isRequired()
                            ->end()
                            ->scalarNode('driver_version')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('host')
                                ->defaultValue('')
                            ->end()
                            ->scalarNode('port')
                                ->defaultValue(0)
                            ->end()
                            ->scalarNode('user')
                                ->isRequired()
                            ->end()
                            ->scalarNode('password')
                                ->defaultValue('')
                            ->end()
                            ->scalarNode('dbname')
                                ->isRequired()
                            ->end()
                            ->integerNode('number_of_connections')
                                ->defaultValue(1)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
