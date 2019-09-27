<?php

namespace Trello;

use InvalidArgumentException;
use RuntimeException;
use Trello\Model\Action;
use Trello\Model\Board;
use Trello\Model\Card;
use Trello\Model\Organization;

class Client
{

    const MODEL_BOARDS = 'boards';
    const MODEL_ACTIONS = 'actions';
    const MODEL_CARDS = 'cards';
    const MODEL_CHECKLISTS = 'checklists';
    const MODEL_LISTS = 'lists';
    const MODEL_MEMBERS = 'members';
    const MODEL_NOTIFICATIONS = 'notifications';
    const MODEL_ORGANIZATIONS = 'organizations';
    const MODEL_SEARCH = 'search';
    const MODEL_TOKEN = 'tokens';
    const MODEL_TYPE = 'types';
    const MODEL_WEBHOOKS = 'webhooks';
    private $_api_key;
    private $_api_secret;
    private $_access_token;
    private $_api_url = 'https://trello.com/1';
    private $_debug_info;
    private $_raw_response;
    private $_curl_handle;

    /**
     * @param string $api_key
     * @param string $secret
     * @param string $access_token
     * @throws InvalidArgumentException
     */
    public function __construct(string $api_key, string $access_token = null, $secret = null)
    {

        if (empty($api_key)) {
            throw new InvalidArgumentException('Invalid API key');
        }

        $this->_api_key = trim($api_key);

        if (!empty($secret)) {
            $this->_api_secret = trim($secret);
        }

        if (!empty($access_token)) {
            $this->setAccessToken($access_token);
        }

    }

    /**
     * Get's a URL to redirect the user to for them to login and authroize your app
     *
     * @param string $application_name
     * @param string $return_url
     * @param array $scopes
     * @param string $expiration
     * @param string $callback_method
     * @return string
     * @throws InvalidArgumentException
     *
     */
    public function getAuthorizationUrl(
        string $application_name,
        string $return_url,
        array $scopes = ['read'],
        string $expiration = '30days',
        string $callback_method = 'fragment'
    ): string {

        /**
         * @param string $argumentName
         * @param string $value
         * @param array $validArray
         * @throws InvalidArgumentException
         */
        $triggerArgumentError = static function (string $argumentName, string $value, array $validArray) {
            throw new InvalidArgumentException(sprintf('Invalid %1$s %2$s. Valid %1$ss are [%3$s]', $argumentName, $value, implode(', ', $validArray)));
        };

        $valid_expirations = ['1hour', '1day', '30days', 'never'];
        $valid_scopes = ['read', 'write', 'account'];
        $valid_callback_methods = ['postMessage', 'fragment'];

        if (!in_array($expiration, $valid_expirations, true)) {
            $triggerArgumentError('expiration', $expiration, $valid_expirations);
        }

        foreach ($scopes as $v) {
            if (!in_array($v, $valid_scopes, true)) {
                $triggerArgumentError('scope', $v, $valid_scopes);
            }
        }

        if (!in_array($callback_method, $valid_callback_methods, true)) {
            $triggerArgumentError('callback method', $callback_method, $valid_callback_methods);
        }

        return sprintf('%s/authorize?callback_method=%s&return_url=%s&scope=%s&expiration=%s&name=%s&key=%s',
            $this->getApiBaseUrl(),
            $callback_method,
            $return_url,
            implode(',', $scopes),
            $expiration,
            $application_name,
            $this->getApiKey()
        );
    }

    /**
     * Get the APIs base url
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->_api_url;
    }

    /**
     * Get the API eky
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->_api_key;
    }

    /**
     * Set the APIs base url
     *
     * @param string $url
     *
     * @return Client
     */
    public function setApiBaseUrl(string $url): Client
    {
        $this->_api_url = rtrim($url, ' /');

        return $this;
    }

    /**
     * Get a board
     *
     * @param string $id
     *
     * @return Board
     */
    public function getBoard(string $id): Board
    {
        $obj = new Board($this);
        $obj->setId($id);
        return $obj->get();
    }

    /**
     * Get a card
     *
     * @param string $id
     *
     * @return Card
     */
    public function getCard(string $id): Model\Card
    {
        $obj = new Card($this);
        $obj->setId($id);
        return $obj->get();
    }

    /**
     * Get an action
     *
     * @param string $id
     *
     * @return Action
     */
    public function getAction(string $id): Action
    {
        $obj = new Action($this);
        $obj->setId($id);
        return $obj->get();
    }

    /**
     *
     * @param string $id
     * @return Organization
     */
    public function getOrganization(string $id): Organization
    {
        $obj = new Organization($this);
        $obj->setId($id);
        return $obj->get();
    }

    /**
     * Get the API secret
     *
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->_api_secret;
    }

    /**
     * Make a GET request
     *
     * @param string $path
     * @param array $payload
     *
     * @return array
     */
    public function get(string $path, array $payload = []): array
    {
        return $this->_makeRequest($path, $payload);
    }

    /**
     * Make a CURL request
     *
     * @param string $url
     * @param array $payload
     * @param string $method
     * @param array $headers
     * @param array $curl_options
     * @return array
     * @throws RuntimeException
     *
     */
    protected function _makeRequest(string $url, array $payload = [], string $method = 'GET', array $headers = [], array $curl_options = []): array
    {

        $url = sprintf('%s/%s?key=%s', $this->getApiBaseUrl(), $url, $this->getApiKey());
        if ($this->getAccessToken()) {
            $url .= '&token=' . $this->getAccessToken();
        }

        $ch = $this->_getCurlHandle();
        $method = strtoupper($method);

        /**
         * CURLOPT_SSL_VERIFYPEER needed here to avoid ssl verifications problems on hosts without properly configured ssl-client
         * @noinspection CurlSslServerSpoofingInspection
         */
        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ];


        if (!empty($payload)) {
            if (strtoupper($method) === 'GET') {
                $options[CURLOPT_URL] .= '&' . http_build_query($payload, '&');
            } else {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = http_build_query($payload);
                $headers[] = 'Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]);
                $options[CURLOPT_HTTPHEADER] = $headers;
            }
        }

        if (!empty($curl_options)) {
            $options = array_merge($options, $curl_options);
        }

        curl_setopt_array($ch, $options);
        $this->_raw_response = curl_exec($ch);
        $this->_debug_info = curl_getinfo($ch);

        if ($this->_raw_response === false) {
            throw new RuntimeException('Request Error: ' . curl_error($ch));
        }

        if ($this->_debug_info['http_code'] < 200 || $this->_debug_info['http_code'] >= 400) {
            throw new RuntimeException('API Request failed - Response: ' . $this->_raw_response, $this->_debug_info['http_code']);
        }

        $response = json_decode($this->_raw_response, true);

        if ($response === null || !is_array($response)) {
            throw new RuntimeException('Could not decode response JSON - Response: ' . $this->_raw_response, $this->_debug_info['http_code']);
        }

        return $response;

    }

    /**
     * Get the access token
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->_access_token;
    }

    /**
     * After a user has authenticated and approved your app, they're presented with an access token. Set it here
     *
     * @param string $token
     *
     * @return Client
     */
    public function setAccessToken($token): Client
    {
        $this->_access_token = trim($token);
        return $this;
    }

    /**
     * Singleton to get a CURL handle
     *
     * @return false|resource
     */
    protected function _getCurlHandle()
    {
        if (!$this->_curl_handle) {
            $this->_curl_handle = curl_init();
        }
        return $this->_curl_handle;
    }

    /**
     * Make a POST request
     *
     * @param string $path
     * @param array $payload
     * @param array $headers
     *
     * @return array
     */
    public function post(string $path, array $payload = [], array $headers = []): array
    {
        return $this->_makeRequest($path, $payload, 'POST', $headers);
    }

    /**
     * Make a PUT request
     *
     * @param string $path
     * @param array $payload
     * @param array $headers
     *
     * @return array
     */
    public function put(string $path, array $payload = [], array $headers = []): array
    {
        return $this->_makeRequest($path, $payload, 'PUT', $headers);
    }

    /**
     * Make a DELETE request
     *
     * @param string $path
     * @return array
     */
    public function delete(string $path): array
    {
        return $this->_makeRequest($path, [], 'DELETE');
    }

    /**
     * Get the raw unparsed response returned from the CURL request
     *
     * @return string
     */
    public function getRawResponse(): string
    {
        return $this->_raw_response;
    }

    public function getDebugInfo()
    {
        return $this->_debug_info;
    }

    /**
     * Closes the currently open CURL handle
     */
    public function __destruct()
    {
        if ($this->_curl_handle) {
            curl_close($this->_curl_handle);
        }
    }
}