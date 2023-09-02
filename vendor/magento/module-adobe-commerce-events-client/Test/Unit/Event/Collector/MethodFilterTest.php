<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Collector;

use Magento\AdobeCommerceEventsClient\Event\Collector\MethodFilter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the MethodFilter Class
 */
class MethodFilterTest extends TestCase
{
    public function testIsExclude(): void
    {
        $methodFilter = new MethodFilter([
            'method1',
            'method2',
            '/^get.*/'
        ]);

        self::assertTrue($methodFilter->isExcluded('method1'));
        self::assertTrue($methodFilter->isExcluded('method2'));
        self::assertTrue($methodFilter->isExcluded('getName'));
        self::assertFalse($methodFilter->isExcluded('nameGet'));
        self::assertFalse($methodFilter->isExcluded('method'));
        self::assertFalse($methodFilter->isExcluded('method3'));
    }
}
