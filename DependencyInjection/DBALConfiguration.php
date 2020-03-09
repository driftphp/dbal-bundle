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
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
                        ->beforeNormalization()
                            ->always(function (array $connection) {
                                if ('sqlite' !== $connection['driver']) {
                                    if (empty($connection['host'])) {
                                        throw new InvalidConfigurationException(sprintf('Host must be configured using driver %s', $connection['driver']));
                                    }

                                    if (empty($connection['port'])) {
                                        throw new InvalidConfigurationException(sprintf('Port must be configured using driver %s', $connection['driver']));
                                    }
                                }

                                return $connection;
                            })
                        ->end()
                        ->children()
                            ->enumNode('driver')
                                ->values(['mysql', 'sqlite', 'postgres'])
                                ->isRequired()
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
                                ->isRequired()
                            ->end()
                            ->scalarNode('dbname')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
