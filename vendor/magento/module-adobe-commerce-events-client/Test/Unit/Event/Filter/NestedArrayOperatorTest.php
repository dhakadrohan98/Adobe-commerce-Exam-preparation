<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\Filter\NestedArrayOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see NestedArrayOperator class
 */
class NestedArrayOperatorTest extends TestCase
{
    /**
     * @param mixed $expected
     * @param array $original
     * @param array $keys
     * @dataProvider getNestedElementDataProvider
     */
    public function testGetNestedElement($expected, array $original, array $keys): void
    {
        $this->assertSame($expected, (new NestedArrayOperator())->getNestedElement($original, $keys));
    }

    /**
     * @return array
     */
    public function getNestedElementDataProvider(): array
    {
        return [
            'simple' => [
                'one',
                ['test' => 'one'],
                ['test'],
            ],
            'nested' => [
                'one',
                ['test' => ['test2' => 'one']],
                ['test', 'test2'],
            ],
            'nested deep' => [
                'one',
                [
                    'test_old' => 'two',
                    'test' => [
                        'test2' => [
                            'test3' => [
                                'test4' => [
                                    'test5' => 'one'
                                ]
                            ]
                        ]
                    ]
                ],
                ['test', 'test2', 'test3', 'test4', 'test5'],
            ],
            'nested deep not exts' => [
                null,
                [
                    'test_old' => 'two',
                    'test' => [
                        'test2' => [
                            'test3' => [
                                'test4' => [
                                    'test5' => 'one'
                                ]
                            ]
                        ]
                    ]
                ],
                ['test', 'test2', 'test3', 'test4', 'test5', 'test6'],
            ],
        ];
    }
    
    /**
     * @param array $expected
     * @param array $original
     * @param array $keys
     * @param mixed $val
     * @dataProvider setNestedElementDataProvider
     */
    public function testSetNestedElement(array $expected, array $original, array $keys, $val): void
    {
        (new NestedArrayOperator())->setNestedElement($original, $keys, $val);
        $this->assertSame($expected, $original);
    }

    /**
     * @return array
     */
    public function setNestedElementDataProvider(): array
    {
        return [
            'simple' => [
                ['test' => 'one'],
                [],
                ['test'],
                'one',
            ],
            'multiple' => [
                ['test' => ['test2' => 'one']],
                [],
                ['test', 'test2'],
                'one',
            ],
            'appending' => [
                ['test_old' => 'two', 'test' => ['test2' => 'one']],
                ['test_old' => 'two'],
                ['test', 'test2'],
                'one',
            ],
            'appending deep' => [
                [
                    'test_old' => 'two',
                    'test' => [
                        'test2' => [
                            'test3' => [
                                'test4' => [
                                    'test5' => 'one'
                                ]
                            ]
                        ]
                    ]
                ],
                ['test_old' => 'two'],
                ['test', 'test2', 'test3', 'test4', 'test5'],
                'one',
            ],
        ];
    }
}
