<?php

namespace Magento\AdobeIoEventsClient\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class RequestThrottledException extends LocalizedException
{
    public function __construct()
    {
        parent::__construct(new Phrase("Request was throttled"));
    }
}
