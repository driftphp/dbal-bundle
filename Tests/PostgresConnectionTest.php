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

namespace Drift\DBAL\Tests;

/**
 * Class PostgresConnectionTest.
 */
class PostgresConnectionTest extends ConnectionTest
{
    /**
     * Get configuration.
     *
     * @return array
     */
    protected static function getDBALConfiguration(): array
    {
        return [
            'connections' => [
                'main' => [
                    'driver' => 'postgres',
                    'host' => '127.0.0.1',
                    'port' => 5432,
                    'user' => 'root',
                    'password' => 'root',
                    'dbname' => 'test',
                ],
                'pool_with_one' => [
                    'driver' => 'postgres',
                    'host' => '127.0.0.1',
                    'port' => 5432,
                    'user' => 'root',
                    'password' => 'root',
                    'dbname' => 'test',
                    'number_of_connections' => 1,
                ],
                'pool_with_many' => [
                    'driver' => 'postgres',
                    'host' => '127.0.0.1',
                    'port' => 5432,
                    'user' => 'root',
                    'password' => 'root',
                    'dbname' => 'test',
                    'number_of_connections' => 10,
                ],
                'pool_with_too_many' => [
                    'driver' => 'postgres',
                    'host' => '127.0.0.1',
                    'port' => 5432,
                    'user' => 'root',
                    'password' => 'root',
                    'dbname' => 'test',
                    'number_of_connections' => 100,
                ],
            ],
        ];
    }
}
