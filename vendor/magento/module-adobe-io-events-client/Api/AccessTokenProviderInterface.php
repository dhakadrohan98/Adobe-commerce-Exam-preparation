<?php

namespace Magento\AdobeIoEventsClient\Api;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\Framework\Exception\AuthorizationException;

interface AccessTokenProviderInterface
{
    /**
     * Call IMS to fetch Access Token from Technical Account JWT
     *
     * @return TokenResponseInterface
     * @throws AuthorizationException
     */
    public function getAccessToken(): TokenResponseInterface;
}
