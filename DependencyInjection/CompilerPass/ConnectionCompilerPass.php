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

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Drift\DBAL\Connection;
use Drift\DBAL\Credentials;
use Drift\DBAL\Driver\Mysql\MysqlDriver;
use Drift\DBAL\Driver\PostgreSQL\PostgreSQLDriver;
use Drift\DBAL\Driver\SQLite\SQLiteDriver;
use Exception;
use React\EventLoop\LoopInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
            $connectionConfiguration = array_map(function ($value) use ($container) {
                return $container->resolveEnvPlaceholders($value, true);
            }, $connectionConfiguration);

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
        if ('sqlite' !== $connectionConfiguration['driver']) {
            if (empty($connectionConfiguration['host'])) {
                throw new InvalidConfigurationException(sprintf('Host must be configured using driver %s', $connectionConfiguration['driver']));
            }

            if (empty($connectionConfiguration['port'])) {
                throw new InvalidConfigurationException(sprintf('Port must be configured using driver %s', $connectionConfiguration['driver']));
            }
        }

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
            $this->getPlatformDefinition(
                $connectionConfiguration['driver'],
                $connectionConfiguration['driver_version'] ?? null
            )
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

        $connectionDefinition->setPublic(false);
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
                    $connectionConfiguration['password'] ?? '',
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
     * @param string      $driver
     * @param string|null $driverVersion
     *
     * @return Definition
     *
     * @throws Exception Invalid Driver
     */
    private function getPlatformDefinition(
        string $driver,
        ?string $driverVersion
    ): Definition {
        switch ($driver) {
            case 'mysql':
                $driverVersion = $driverVersion ?? '57';
                $driverNamespace = "Doctrine\\DBAL\\Platforms\\MySQL{$driverVersion}Platform";

                return new Definition($driverNamespace);
            case 'postgres':

                $driverVersion = $driverVersion ?? '94';
                $driverNamespace = "Doctrine\\DBAL\\Platforms\\PostgreSQL{$driverVersion}Platform";

                return new Definition($driverNamespace);
            case 'sqlite':

                return new Definition(SqlitePlatform::class);
        }

        throw new \Exception('Invalid driver');
    }
}
