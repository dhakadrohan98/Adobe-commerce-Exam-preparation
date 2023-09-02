<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\EventMetadataCollector;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\PackageInfo;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EventMetadataCollector class
 */
class EventMetadataCollectorTest extends TestCase
{
    /**
     * @var EventMetadataCollector
     */
    private EventMetadataCollector $metadataCollector;

    /**
     * @var ProductMetadataInterface|MockObject
     */
    private $commerceMetadataMock;

    /**
     * @var PackageInfo|MockObject
     */
    private $packageInfoMock;
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    protected function setUp(): void
    {
        $this->commerceMetadataMock = $this->getMockForAbstractClass(ProductMetadataInterface::class);
        $this->packageInfoMock = $this->createMock(PackageInfo::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $this->metadataCollector = new EventMetadataCollector(
            $this->commerceMetadataMock,
            $this->packageInfoMock,
            $this->storeManagerMock
        );
    }

    /**
     * Test field existence, loading methods are only called once, and appropriate data transformations are applied
     *
     * @param string $productEdition
     * @param string $expectedEdition
     * @return void
     * @dataProvider getMetadataDataProvider
     */
    public function testGetMetadata(string $productEdition, string $expectedEdition): void
    {
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $storeMock->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $storeMock->expects(self::once())
            ->method('getWebsiteId')
            ->willReturn(1);
        $storeMock->expects(self::once())
            ->method('getStoreGroupId')
            ->willReturn(1);
        $this->packageInfoMock->expects(self::once())
            ->method('getVersion')
            ->with('Magento_AdobeCommerceEventsClient')
            ->willReturn('100.0.0');
        $this->commerceMetadataMock->expects(self::once())
            ->method('getEdition')
            ->willReturn($productEdition);
        $this->commerceMetadataMock->expects(self::once())
            ->method('getVersion')
            ->willReturn('2.4.5');
        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willReturn($storeMock);

        for ($i = 0; $i < 2; $i++) {
            $metadata = $this->metadataCollector->getMetadata();
            $this->assertArrayHasKey('commerceEdition', $metadata);
            $this->assertArrayHasKey('commerceVersion', $metadata);
            $this->assertArrayHasKey('eventsClientVersion', $metadata);
            $this->assertEquals($expectedEdition, $metadata['commerceEdition']);
            $this->assertEquals('2.4.5', $metadata['commerceVersion']);
            $this->assertEquals('100.0.0', $metadata['eventsClientVersion']);
            $this->assertEquals(1, $metadata['storeId']);
            $this->assertEquals(1, $metadata['websiteId']);
            $this->assertEquals(1, $metadata['storeGroupId']);
        }
    }

    /**
     * @return string[][]
     */
    public function getMetadataDataProvider(): array
    {
        return [['Community', 'Open Source'], ['Enterprise', 'Adobe Commerce'], ['B2B', 'Adobe Commerce + B2B']];
    }

    /**
     * Tests that getMetadata method returns expected data in case of exception while retrieving a store
     *
     * @return void
     */
    public function testExceptionWhileRetrievingStore()
    {
        $this->storeManagerMock->expects(self::once())
            ->method('getStore')
            ->willThrowException(new NoSuchEntityException(__('store not found')));
        $this->packageInfoMock->expects(self::once())
            ->method('getVersion')
            ->with('Magento_AdobeCommerceEventsClient')
            ->willReturn('100.0.0');
        $this->commerceMetadataMock->expects(self::once())
            ->method('getEdition')
            ->willReturn('Enterprise');
        $this->commerceMetadataMock->expects(self::once())
            ->method('getVersion')
            ->willReturn('2.4.5');

        $metadata = $this->metadataCollector->getMetadata();
        $this->assertEquals('Adobe Commerce', $metadata['commerceEdition']);
        $this->assertEquals('2.4.5', $metadata['commerceVersion']);
        $this->assertEquals('100.0.0', $metadata['eventsClientVersion']);
        $this->assertEquals('', $metadata['storeId']);
        $this->assertEquals('', $metadata['websiteId']);
        $this->assertEquals('', $metadata['storeGroupId']);
    }
}
