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
use Drift\DBAL\Driver\Mysql\MysqlDriver;
use React\EventLoop\LoopInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class MysqlConnectionCompilerPass.
 */
final class MysqlConnectionCompilerPass extends ConnectionCompilerPass
{
    /**
     * @inheritDoc
     */
    protected function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * Get driver definition
     *
     * @return Definition
     */
    protected function getDriverDefinition() : Definition
    {
        return new Definition(MysqlDriver::class, [
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
        return new Definition(MySqlPlatform::class);
    }
}
