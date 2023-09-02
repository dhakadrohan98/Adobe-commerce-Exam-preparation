<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Converter;

use Magento\AdobeCommerceEventsClient\Event\Converter\EventDataConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for converter class
 */
class EventDataConverterTest extends TestCase
{
    /**
     * @var EventDataConverter
     */
    private EventDataConverter $eventDataConverter;

    protected function setUp(): void
    {
        $this->eventDataConverter = new EventDataConverter();
    }

    public function testSimpleArrayIsCorrectlyConverted()
    {
        $simpleArray = [
            'category_id' => 10,
            'entity_id' => 15
        ];

        self::assertEquals($simpleArray, $this->eventDataConverter->convert($simpleArray));
    }

    public function testArrayWithObjectConverted()
    {
        $simpleArray = [
            'order' => $this->getObjectWithToArrayMethod(12),
            'product_id' => 100
        ];

        self::assertEquals(
            [
                'order' => ['entity_id' => 12],
                'product_id' => 100
            ],
            $this->eventDataConverter->convert($simpleArray)
        );
    }

    public function testOnlyObjectReturned()
    {
        $data = [
            'data_object' => $this->getObjectWithToArrayMethod(12),
            'collection' => $this->getObjectWithToArrayMethod(12),
            'additional_field2' => 100,
        ];

        self::assertEquals(
            ['entity_id' => 12],
            $this->eventDataConverter->convert($data)
        );
    }

    /**
     * @param integer $id
     */
    private function getObjectWithToArrayMethod(int $id)
    {
        return new class($id) {
            /**
             * @var integer
             */
            private $entityId;

            public function __construct($entityId)
            {
                $this->entityId = $entityId;
            }

            public function toArray(): array
            {
                return [
                    'entity_id' => $this->entityId
                ];
            }
        };
    }
}
