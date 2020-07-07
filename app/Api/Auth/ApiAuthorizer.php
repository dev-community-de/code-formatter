<?php

namespace DevCommunityDE\CodeFormatter\Api\Auth;

/**
 * Class ApiAuthorizer.
 */
class ApiAuthorizer
{
    /**
     * @var string
     */
    protected $api_key;

    public function __construct()
    {
        $this->api_key = getenv('API_KEY') ?: '';
    }

    public function authorize()
    {
        if (!$this->checkRequestMethod()) {
            // set http status 405 method not allowed
            http_response_code(405);
            exit;
        }

        if (
            !isset($_GET['api_key']) ||
            !$this->checkApiKey()
        ) {
            // set http status 401 unauthorized
            http_response_code(401);
            exit;
        }
    }

    /**
     * @return bool
     */
    protected function checkRequestMethod(): bool
    {
        return 'POST' === strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @return bool
     */
    protected function checkApiKey(): bool
    {
        return hash_equals($this->api_key, $_GET['api_key']);
    }
}
