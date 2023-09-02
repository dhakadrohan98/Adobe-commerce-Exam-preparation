<?php

namespace Magento\AdobeIoEventsClient\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class NoRegistrationException extends LocalizedException
{
    public function __construct()
    {
        parent::__construct(new Phrase("No registration found for this event code"));
    }
}