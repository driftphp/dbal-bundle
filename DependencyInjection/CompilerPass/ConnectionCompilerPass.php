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

namespace Drift\DBAL\DependencyInjection\CompilerPass;

use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ConnectionCompilerPass.
 */
abstract class ConnectionCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $connectionsConfiguration = $container->getParameter('dbal.connections');

        $connectionsConfiguration = array_filter(
            $connectionsConfiguration,
            function(array $connectionConfiguration) {
                return $this->getDriverName() === $connectionConfiguration['driver'];
            }
        );

        if (empty($connectionsConfiguration)) {
            return;
        }

        foreach ($connectionsConfiguration as $connectionName => $connectionConfiguration) {
            $this->createCredentials(
                $container,
                $connectionName,
                $connectionConfiguration
            );

            $connectionDefinitionName = "dbal.{$connectionName}_connection";
            $driverDefinitionName = "dbal.{$connectionName}_driver";
            $container->setDefinition($driverDefinitionName, $this->getDriverDefinition());

            $platformDefinitionName = "dbal.{$connectionName}_platform";
            $container->setDefinition($platformDefinitionName, $this->getPlatformDefinition());

            $connectionDefinition = new Definition(Connection::class, [
                new Reference($driverDefinitionName),
                new Reference("dbal.{$connectionName}_credentials"),
                new Reference($platformDefinitionName)
            ]);

            $connectionDefinition->setFactory([
                Connection::class,
                'createConnected'
            ]);

            $connectionDefinition->setPrivate(true);
            $connectionDefinition->addTag('preload');
            $connectionDefinition->addTag('await');

            $container->setDefinition($connectionDefinitionName, $connectionDefinition);
            $container->setAlias(Connection::class, $connectionDefinitionName);
            $container->registerAliasForArgument($connectionDefinitionName, Connection::class, "{$connectionName} connection");
        }
    }

    /**
     * Get driver name
     *
     * @return string
     */
    abstract protected function getDriverName() : string;

    /**
     * Get driver definition
     *
     * @return Definition
     */
    abstract protected function getDriverDefinition() : Definition;

    /**
     * Get platform definition
     *
     * @return Definition
     */
    abstract protected function getPlatformDefinition() : Definition;

    /**
     * Initializes the compiler pass. This method is only called when there is,
     * at least, one connection available for this driver
     *
     * @param ContainerBuilder $container
     * @param string $connectionName
     * @param array $connectionConfiguration
     */
    private function createCredentials(
        ContainerBuilder $container,
        string $connectionName,
        array $connectionConfiguration
    )
    {
        $credentialsName = "dbal.{$connectionName}_credentials";
        $container->setDefinition(
            $credentialsName,
            new Definition(
                Credentials::class,
                [
                    $connectionConfiguration['host'],
                    $connectionConfiguration['port'],
                    $connectionConfiguration['user'],
                    $connectionConfiguration['password'],
                    $connectionConfiguration['dbname'],
                    $connectionConfiguration['options'] ?? []
                ]
            )
        );

        $container->registerAliasForArgument($credentialsName, Credentials::class, "{$connectionName} credentials");
    }
}
