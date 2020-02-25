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

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Drift\DBAL\Driver\PostgreSQL\PostgreSQLDriver;
use React\EventLoop\LoopInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PostgresConnectionCompilerPass.
 */
final class PostgresConnectionCompilerPass extends ConnectionCompilerPass
{
    /**
     * @inheritDoc
     */
    protected function getDriverName(): string
    {
        return 'postgres';
    }

    /**
     * Get driver definition
     *
     * @return Definition
     */
    protected function getDriverDefinition() : Definition
    {
        return new Definition(PostgreSQLDriver::class, [
            new Reference(LoopInterface::class)
        ]);
    }

    /**
     * Get platform definition
     *
     * @return Definition
     */
    protected function getPlatformDefinition() : Definition
    {
        return new Definition(PostgreSqlPlatform::class);
    }
}
