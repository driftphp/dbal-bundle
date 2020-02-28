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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\Mysql\MysqlDriver;
use Drift\DBAL\Driver\PostgreSQL\PostgreSQLDriver;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use Exception;
use React\EventLoop\LoopInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ConnectionCompilerPass.
 */
class ConnectionCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $connectionsConfiguration = $container->getParameter('dbal.connections');
        if (empty($connectionsConfiguration)) {
            return;
        }

        foreach ($connectionsConfiguration as $connectionName => $connectionConfiguration) {
            $this->createConnection(
                $container,
                $connectionName,
                $connectionConfiguration
            );
        }
    }

    /**
     * Create a connection.
     *
     * @param ContainerBuilder $container
     * @param string           $connectionName
     * @param array            $connectionConfiguration
     */
    protected function createConnection(
        ContainerBuilder $container,
        string $connectionName,
        array $connectionConfiguration
    ) {
        $this->createCredentials(
            $container,
            $connectionName,
            $connectionConfiguration
        );

        $connectionDefinitionName = "dbal.{$connectionName}_connection";
        $driverDefinitionName = "dbal.{$connectionName}_driver";
        $container->setDefinition(
            $driverDefinitionName,
            $this->getDriverDefinition($connectionConfiguration['driver'])
        );

        $platformDefinitionName = "dbal.{$connectionName}_platform";
        $container->setDefinition(
            $platformDefinitionName,
            $this->getPlatformDefinition($connectionConfiguration['driver'])
        );

        $connectionDefinition = new Definition(Connection::class, [
            new Reference($driverDefinitionName),
            new Reference("dbal.{$connectionName}_credentials"),
            new Reference($platformDefinitionName),
        ]);

        $connectionDefinition->setFactory([
            Connection::class,
            'createConnected',
        ]);

        $connectionDefinition->setPrivate(true);
        $connectionDefinition->addTag('preload');
        $connectionDefinition->addTag('await');

        $container->setDefinition($connectionDefinitionName, $connectionDefinition);
        $container->setAlias(Connection::class, $connectionDefinitionName);
        $container->registerAliasForArgument($connectionDefinitionName, Connection::class, "{$connectionName} connection");
    }

    /**
     * Initializes the compiler pass. This method is only called when there is,
     * at least, one connection available for this driver.
     *
     * @param ContainerBuilder $container
     * @param string           $connectionName
     * @param array            $connectionConfiguration
     */
    private function createCredentials(
        ContainerBuilder $container,
        string $connectionName,
        array $connectionConfiguration
    ) {
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
                    $connectionConfiguration['options'] ?? [],
                ]
            )
        );

        $container->registerAliasForArgument($credentialsName, Credentials::class, "{$connectionName} credentials");
    }

    /**
     * Get driver definition.
     *
     * @param string $driver
     *
     * @return Definition
     *
     * @throws Exception Invalid Driver
     */
    private function getDriverDefinition(string $driver): Definition
    {
        switch ($driver) {
            case 'mysql':

                return new Definition(MysqlDriver::class, [
                    new Reference(LoopInterface::class),
                ]);
            case 'postgres':

                return new Definition(PostgreSQLDriver::class, [
                    new Reference(LoopInterface::class),
                ]);
            case 'sqlite':

                return new Definition(SQLiteDriver::class, [
                    new Reference(LoopInterface::class),
                ]);
        }

        throw new Exception('Invalid driver');
    }

    /**
     * Get platform definition.
     *
     * @param string $driver
     *
     * @return Definition
     *
     * @throws Exception Invalid Driver
     */
    private function getPlatformDefinition(string $driver): Definition
    {
        switch ($driver) {
            case 'mysql':

                return new Definition(MySqlPlatform::class);
            case 'postgres':

                return new Definition(PostgreSqlPlatform::class);
            case 'sqlite':

                return new Definition(SqlitePlatform::class);
        }

        throw new \Exception('Invalid driver');
    }
}
