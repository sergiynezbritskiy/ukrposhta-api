<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Connection
 *
 * Low level HTTP layer. Every request is authorized with the `Authorization: Bearer` header
 * and the counterparty `token` query parameter.
 *
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0
 */
class Connection
{
    public const OPTION_SANDBOX = 'sandbox';

    private const API_URI = 'https://www.ukrposhta.ua/ecom/0.0.1/';
    private const FORMS_URI = 'https://www.ukrposhta.ua/forms/ecom/0.0.1/';
    private const SANDBOX_API_URI = 'https://dev.ukrposhta.ua/ecom/0.0.1/';
    private const SANDBOX_FORMS_URI = 'https://dev.ukrposhta.ua/forms/ecom/0.0.1/';
    private const ERROR_MSG_TEMPLATE = 'Connection to Ukrposhta API failed: %s';

    private string $bearer;
    private string $token;
    private HttpClient $client;
    private array $options; //@phpstan-ignore-line
    private string $apiUri;
    private string $formsUri;

    /**
     * @param string $bearer Bearer eCom, issued by Ukrposhta
     * @param string $token Counterparty token, issued by Ukrposhta
     * @param HttpClient $client
     * @param array $options Guzzle request options. The `sandbox` option (bool) switches to the sandbox hosts.
     */
    public function __construct(string $bearer, string $token, HttpClient $client, array $options = [])
    {
        $this->bearer = $bearer;
        $this->token = $token;
        $this->client = $client;

        $sandbox = (bool)($options[self::OPTION_SANDBOX] ?? false);
        unset($options[self::OPTION_SANDBOX]);
        $this->options = $options;

        $this->apiUri = $sandbox ? self::SANDBOX_API_URI : self::API_URI;
        $this->formsUri = $sandbox ? self::SANDBOX_FORMS_URI : self::FORMS_URI;
    }

    /**
     * @param string $uri
     * @param array $query
     * @return array
     * @throws UkrposhtaApiException
     */
    public function get(string $uri, array $query = []): array
    {
        return $this->decode($this->request('GET', $this->apiUri . $uri, $query));
    }

    /**
     * @param string $uri
     * @param array $body
     * @param array $query
     * @return array
     * @throws UkrposhtaApiException
     */
    public function post(string $uri, array $body = [], array $query = []): array
    {
        return $this->decode($this->request('POST', $this->apiUri . $uri, $query, $body));
    }

    /**
     * @param string $uri
     * @param array $body
     * @param array $query
     * @return array
     * @throws UkrposhtaApiException
     */
    public function put(string $uri, array $body = [], array $query = []): array
    {
        return $this->decode($this->request('PUT', $this->apiUri . $uri, $query, $body));
    }

    /**
     * @param string $uri
     * @param array $query
     * @return array
     * @throws UkrposhtaApiException
     */
    public function delete(string $uri, array $query = []): array
    {
        return $this->decode($this->request('DELETE', $this->apiUri . $uri, $query));
    }

    /**
     * Fetches a binary document (PDF) from the forms API
     *
     * @param string $uri
     * @param array $query
     * @return string raw file content
     * @throws UkrposhtaApiException
     */
    public function getFile(string $uri, array $query = []): string
    {
        return $this->request('GET', $this->formsUri . $uri, $query);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $query
     * @param array|null $body
     * @return string response body
     * @throws UkrposhtaApiException
     */
    private function request(string $method, string $uri, array $query = [], ?array $body = null): string
    {
        try {
            $response = $this->client->request($method, $uri, $this->buildOptions($query, $body));
            $content = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw $this->createException($response, $content);
            }
            return $content;
        } catch (GuzzleException $e) {
            throw new UkrposhtaApiException(sprintf(self::ERROR_MSG_TEMPLATE, $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param ResponseInterface $response
     * @param string $content
     * @return UkrposhtaApiException
     */
    private function createException(ResponseInterface $response, string $content): UkrposhtaApiException
    {
        $body = json_decode($content, true);
        $body = is_array($body) ? $body : [];
        $message = $body['message']
            ?? $body['error_description']
            ?? $body['error']
            ?? $response->getReasonPhrase();
        $apiErrorCode = isset($body['code']) ? (string)$body['code'] : null;
        return new UkrposhtaApiException(
            sprintf(self::ERROR_MSG_TEMPLATE, $message),
            $response->getStatusCode(),
            null,
            $apiErrorCode
        );
    }

    /**
     * @param string $content
     * @return array
     * @throws UkrposhtaApiException
     */
    private function decode(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }
        $result = json_decode($content, true);
        if (!is_array($result)) {
            throw new UkrposhtaApiException('Invalid response from Ukrposhta API');
        }
        return $result;
    }

    /**
     * @param array $query
     * @param array|null $body
     * @return array
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function buildOptions(array $query, ?array $body = null): array
    {
        $defaultOptions = [
            RequestOptions::TIMEOUT => 30,
        ];

        $mandatoryOptions = [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::QUERY => array_filter(array_merge($query, ['token' => $this->token])),
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $this->bearer,
                'Accept' => 'application/json, application/pdf',
            ],
        ];

        if ($body !== null) {
            $mandatoryOptions[RequestOptions::BODY] = Utils::jsonEncode($body, JSON_UNESCAPED_UNICODE);
            $mandatoryOptions[RequestOptions::HEADERS]['Content-Type'] = 'application/json';
        }

        return array_merge($defaultOptions, $this->options, $mandatoryOptions);
    }
}
