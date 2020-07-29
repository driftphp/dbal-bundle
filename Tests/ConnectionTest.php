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

use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Drift\DBAL\Connection;
use Drift\DBAL\DBALBundle;
use function Clue\React\Block\await;
use Mmoreram\BaseBundle\Kernel\DriftBaseKernel;
use Mmoreram\BaseBundle\Tests\BaseFunctionalTest;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class ConnectionTest.
 */
abstract class ConnectionTest extends BaseFunctionalTest
{
    /**
     * Get kernel.
     *
     * @return KernelInterface
     */
    protected static function getKernel(): KernelInterface
    {
        $configuration = [
            'parameters' => [
                'kernel.secret' => 'gdfgfdgd',
            ],
            'framework' => [
                'test' => true,
            ],
            'services' => [
                'dbal.main_connection_test' => [
                    'alias' => 'dbal.main_connection',
                    'public' => true,
                ],
                'dbal.main_connection_alias_test' => [
                    'alias' => Connection::class,
                    'public' => true,
                ],
                'dbal.nopassword_connection_test' => [
                    'alias' => 'dbal.nopassword_connection',
                    'public' => true,
                ],
                'reactphp.event_loop' => [
                    'class' => LoopInterface::class,
                    'public' => true,
                    'factory' => [
                        Factory::class,
                        'create',
                    ],
                ],
            ],
            'dbal' => static::getDBALConfiguration(),
        ];

        return new DriftBaseKernel(
            [
                FrameworkBundle::class,
                DBALBundle::class,
            ],
            $configuration,
            [],
            'dev', true
        );
    }

    /**
     * Get DBAL configuration.
     *
     * @return array
     */
    abstract protected static function getDBALConfiguration(): array;

    /**
     * Test connection is built.
     */
    public function testConnectionIsBuilt()
    {
        $this->expectNotToPerformAssertions();

        $this->get('dbal.main_connection_test');
        $this->get('dbal.main_connection_alias_test');
    }

    /**
     * Test find one element.
     * 
     * @dataProvider connectionsToFindOneAgainst
     */
    public function testFindOneSimpleElement($connectionKey)
    {
        $connection = $this->get($connectionKey);

        $promise = $connection
            ->dropTable('test')
            ->otherwise(function (TableNotFoundException $_) use ($connection) {
                return $connection;
            })
            ->then(function (Connection $connection) {
                return $connection->createTable('test', ['id' => 'string']);
            })
            ->otherwise(function (TableExistsException $_) use ($connection) {
                // Silent pass

                return $connection;
            })
            ->then(function (Connection $connection) {
                return $connection
                    ->insert('test', ['id' => '1'])
                    ->then(function () use ($connection) {
                        return $connection;
                    });
            })
            ->then(function (Connection $connection) {
                return $connection->findOneBy('test', ['id' => '1']);
            })
            ->then(function (array $result) {
                $this->assertEquals('1', $result['id']);
            });

        await($promise, self::get('reactphp.event_loop'));
    }

    public function connectionsToFindOneAgainst()
    {
        return [
            ['dbal.main_connection_test'],
            ['dbal.nopassword_connection_test']
        ];
    }
}
