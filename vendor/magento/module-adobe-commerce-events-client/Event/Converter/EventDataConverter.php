<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdobeCommerceEventsClient\Event\Converter;

/**
 * Class for converting data to event suitable format
 */
class EventDataConverter
{
    /**
     * Convert object or array of objects to array format
     *
     * @param mixed $objectOrArray
     * @return array
     * @throws \Exception
     */
    public function convert($objectOrArray): array
    {
        if (is_object($objectOrArray)) {
            if (method_exists($objectOrArray, 'toArray')) {
                return $this->cleanData($objectOrArray->toArray());
            }

            throw new \Exception(sprintf('Object %s can not be converted to array', get_class($objectOrArray)));
        }

        if (is_array($objectOrArray)) {
            return $this->convertArray($objectOrArray);
        }

        throw new \Exception('Wrong type of input argument');
    }

    /**
     * Converts event data to the array.
     *
     * @param array $data
     * @return array
     */
    private function convertArray(array $data): array
    {
        foreach (['data_object', 'collection', 'object'] as $key) {
            if (isset($data[$key]) && method_exists($data[$key], 'toArray')) {
                return $this->cleanData($data[$key]->toArray());
            }
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $result[$key] = $this->cleanData($value->toArray());
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Clears array from the cached items and objects which can't be converted to the array
     *
     * @param array $data
     * @return array
     */
    private function cleanData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (strpos($key, '_cache') === 0) {
                unset($data[$key]);
            }

            if (is_object($value)) {
                if (method_exists($value, 'toArray')) {
                    $data[$key] = $value->toArray();
                } else {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }
}
