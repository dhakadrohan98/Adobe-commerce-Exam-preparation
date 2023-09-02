<?php
namespace Cloudways\RestApi\Api;

interface CustomInterface
{

    /**
     * @param string $api_endpoint
     * @param string $request_body
     * @param string $response_body
     * @param string $status_code
     * @return mixed
     */

    public function getData($api_endpoint,$request_body,$response_body,$status_code);
}