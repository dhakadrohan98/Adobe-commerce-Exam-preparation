<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;
use Magento\AdobeCommerceEventsClient\Event\Filter\NestedArrayOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventFieldsFilter class
 */
class EventFieldsFilterTest extends TestCase
{
    /**
     * @var EventFieldsFilter
     */
    private EventFieldsFilter $filter;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventListMock = $this->createMock(EventList::class);
        $this->eventMock = $this->createMock(Event::class);
        $this->filter = new EventFieldsFilter($this->eventListMock, new NestedArrayOperator());
    }

    public function testDataFiltered(): void
    {
        $eventData = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key3_1' => 'value3_1',
                'key3_2' => 'value3_2',
            ],
            'key4' => 'value4'
        ];

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn($this->eventMock);
        $this->eventMock->expects(self::exactly(2))
            ->method('getFields')
            ->willReturn(['key1', 'key3', 'key5']);

        self::assertEquals(
            [
                'key1' => 'value1',
                'key3' => [
                    'key3_1' => 'value3_1',
                    'key3_2' => 'value3_2',
                ],
                'key5' => null
            ],
            $this->filter->filter('some.event', $eventData)
        );
    }

    public function testDataNotFilteredIfEventNotExists(): void
    {
        $eventData = ['key' => 'value'];

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn(null);

        self::assertEquals(
            $eventData,
            $this->filter->filter('some.event', $eventData)
        );
    }

    public function testNestedFieldsFilter(): void
    {
        $eventData = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key3_1' => 'value3_1',
                'key3_2' => [
                    'key3_2_1' => 'value3_2_1',
                    'key3_2_2' => [
                        'key3_2_2_1' => 'value3_2_2_1',
                        'key3_2_2_2' => 'value3_2_2_2',
                    ]
                ]
            ],
            'key4' => [
                'key4_1' => 'value4_1'
            ]
        ];

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn($this->eventMock);
        $this->eventMock->expects(self::exactly(2))
            ->method('getFields')
            ->willReturn([
                'key1',
                'key3.key3_2.key3_2_2.key3_2_2_2',
                'key3.key3_2.key3_2_2.key_not_exists',
                'key3.key3_1',
                'key_not_exists.key_not_exists.key_not_exists.key_not_exists.key_not_exists.key_not_exists'
            ]);

        self::assertEquals(
            [
                'key1' => 'value1',
                'key3' => [
                    'key3_1' => 'value3_1',
                    'key3_2' => [
                        'key3_2_2' => [
                            'key3_2_2_2' => 'value3_2_2_2',
                            'key_not_exists' => null,
                        ]
                    ]
                ],
                'key_not_exists' => [
                    'key_not_exists' => [
                        'key_not_exists' => [
                            'key_not_exists' => [
                                'key_not_exists' => [
                                    'key_not_exists' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->filter->filter('some.event', $eventData)
        );
    }
}
