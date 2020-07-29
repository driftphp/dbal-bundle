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
 * Class MysqlConnectionTest.
 */
class MysqlConnectionTest extends ConnectionTest
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
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'user' => 'root',
                    'password' => 'root',
                    'dbname' => 'test',
                ],
                'nopassword' => [
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'user' => 'root',
                    'password' => null,
                    'dbname' => 'test',
                ]
            ],
        ];
    }
}
