<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter;

/**
 * Operates with nested array elements.
 */
class NestedArrayOperator
{
    /**
     * Creates a nested element in the original array for a given array of keys and assigns $value to it.
     *
     * @param array $original
     * @param array $keys
     * @param $value
     * @return void
     */
    public function setNestedElement(array &$original, array $keys, $value): void
    {
        $data = &$original;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = [];
            }
            $data = &$data[$key];
        }

        $data = $value;
    }

    /**
     * Returns nested value by an array of keys. Returns null if the element does not exist.
     *
     * @param array $data
     * @param array $keys
     * @return mixed
     */
    public function getNestedElement(array $data, array $keys)
    {
        $size = count($keys);

        for ($i = 0; $i < $size; $i++) {
            if (!isset($data[$keys[$i]])) {
                return null;
            }

            $data = $data[$keys[$i]];
            if ($i === $size - 1) {
                return $data;
            }
        }
    }
}
