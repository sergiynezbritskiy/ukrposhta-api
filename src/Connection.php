<?php

declare(strict_types=1);

namespace SergiyNezbritskiy\Ukrposhta;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Connection
 *
 * Low level HTTP layer. Every request is authorized with the `Authorization: Bearer` header
 * and the counterparty `token` query parameter, except the address classifier requests which
 * are authorized with the bearer only.
 *
 * @see https://app.swaggerhub.com/apis-docs/Ukrpost/ukrpost-api/1.0
 * @see https://dev.ukrposhta.ua/documentation address classifier
 */
class Connection
{
    public const OPTION_SANDBOX = 'sandbox';
    public const OPTION_CLASSIFIER_URI = 'classifier_uri';

    private const API_URI = 'https://www.ukrposhta.ua/ecom/0.0.1/';
    private const FORMS_URI = 'https://www.ukrposhta.ua/forms/ecom/0.0.1/';
    private const SANDBOX_API_URI = 'https://dev.ukrposhta.ua/ecom/0.0.1/';
    private const SANDBOX_FORMS_URI = 'https://dev.ukrposhta.ua/forms/ecom/0.0.1/';
    /** The address classifier has no documented sandbox host, so it is used for the sandbox as well */
    private const CLASSIFIER_URI = 'https://www.ukrposhta.ua/address-classifier-ws/';
    private const ERROR_MSG_TEMPLATE = 'Connection to Ukrposhta API failed: %s';

    private string $bearer;
    private string $token;
    private HttpClient $client;
    private array $options; //@phpstan-ignore-line
    private string $apiUri;
    private string $formsUri;
    private string $classifierUri;

    /**
     * @param string $bearer Bearer eCom, issued by Ukrposhta
     * @param string $token Counterparty token, issued by Ukrposhta
     * @param HttpClient $client
     * @param array $options Guzzle request options. The `sandbox` option (bool) switches to the sandbox hosts,
     *     the `classifier_uri` option (string) overrides the address classifier base URL.
     */
    public function __construct(string $bearer, string $token, HttpClient $client, array $options = [])
    {
        $this->bearer = $bearer;
        $this->token = $token;
        $this->client = $client;

        $sandbox = (bool)($options[self::OPTION_SANDBOX] ?? false);
        $this->classifierUri = (string)($options[self::OPTION_CLASSIFIER_URI] ?? self::CLASSIFIER_URI);
        unset($options[self::OPTION_SANDBOX], $options[self::OPTION_CLASSIFIER_URI]);
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
        return $this->decode($this->request('GET', $this->apiUri . $uri, $this->buildOptions($query)));
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
        return $this->decode($this->request('POST', $this->apiUri . $uri, $this->buildOptions($query, $body)));
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
        return $this->decode($this->request('PUT', $this->apiUri . $uri, $this->buildOptions($query, $body)));
    }

    /**
     * @param string $uri
     * @param array $query
     * @return array
     * @throws UkrposhtaApiException
     */
    public function delete(string $uri, array $query = []): array
    {
        return $this->decode($this->request('DELETE', $this->apiUri . $uri, $this->buildOptions($query)));
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
        return $this->request('GET', $this->formsUri . $uri, $this->buildOptions($query));
    }

    /**
     * Fetches data from the address classifier API, which is authorized with the bearer only
     *
     * @param string $uri
     * @param array $query
     * @return array
     * @throws UkrposhtaApiException
     */
    public function getClassifier(string $uri, array $query = []): array
    {
        $options = $this->buildClassifierOptions($query);
        return $this->decode($this->request('GET', $this->classifierUri . $uri, $options));
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options Guzzle request options, see self::buildOptions()
     * @return string response body
     * @throws UkrposhtaApiException
     */
    private function request(string $method, string $uri, array $options): string
    {
        try {
            $response = $this->client->request($method, $uri, $options);
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
     * Build the options of an eCom API request: the counterparty token is sent in the query string
     *
     * @param array $query
     * @param array|null $body
     * @return array
     * @throws UkrposhtaApiException
     */
    public function buildOptions(array $query, ?array $body = null): array
    {
        $query['token'] = $this->token;
        $options = $this->mergeOptions($query, ['Accept' => 'application/json, application/pdf']);
        if ($body !== null) {
            $options[RequestOptions::BODY] = $this->encode($body);
            $options[RequestOptions::HEADERS]['Content-Type'] = 'application/json';
        }
        return $options;
    }

    /**
     * @param array $body
     * @return string
     * @throws UkrposhtaApiException
     */
    private function encode(array $body): string
    {
        try {
            return json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new UkrposhtaApiException('Invalid request body: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build the options of an address classifier request: authorized with the bearer only
     *
     * @param array $query
     * @return array
     */
    public function buildClassifierOptions(array $query): array
    {
        return $this->mergeOptions($query, ['Accept' => 'application/json']);
    }

    /**
     * Merge the default options, the client options and the mandatory options of a request
     *
     * @param array $query
     * @param array $headers
     * @return array
     */
    private function mergeOptions(array $query, array $headers): array
    {
        $defaultOptions = [
            RequestOptions::TIMEOUT => 30,
        ];

        $headers['Authorization'] = 'Bearer ' . $this->bearer;
        $mandatoryOptions = [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::QUERY => array_filter($query),
            RequestOptions::HEADERS => $headers,
        ];

        return array_merge($defaultOptions, $this->options, $mandatoryOptions);
    }
}
