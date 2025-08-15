<?php

namespace PHPTemu\V1;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class Api
{
    public string $url;

    public string $fullUrl;

    protected array $queryString = [];

    protected array $body;

    protected string $requestMethod = 'post';

    protected int $timeout = 60;

    protected int $times = 3;

    protected int $sleep = 100;

    protected $httpClient;

    protected $response;

    protected array $headers = [
        'Content-type' => 'application/json',
        'Accept'       => 'application/json',
    ];

    protected array $options = [
        'verify' => false
    ];

    abstract public function api(string $apiName);

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @return static
     */
    public function setHttpClient(): static
    {
        $this->httpClient = Http::withOptions($this->options)->timeout($this->timeout)->retry($this->times, $this->sleep);
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @return PendingRequest
     */
    public function httpClient(): PendingRequest
    {
        return $this->httpClient;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @param array $options
     * @return static
     */
    public function withOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        $this->httpClient()->withOptions($this->options);
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @param int $timeout
     * @return static
     */
    public function setTimeout(int $timeout = 60): static
    {
        $this->timeout = $timeout;
        $this->httpClient->timeout($this->timeout);
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @param int $times
     * @param int $sleep
     * @return static
     */
    public function setRetry(int $times, int $sleep = 0): static
    {
        $this->times = $times;
        $this->sleep = $sleep;
        $this->httpClient->retry($times, $sleep);
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:49
     * @param array $body
     * @return string
     * @throws \JsonException
     */
    protected function formatBody(array $body): string
    {
        $body = array_merge($body, $this->getBodyCommonParameters());
        $body['sign'] = $this->generateSign($body, $this->config['appSecret']);
        return json_encode($body, JSON_THROW_ON_ERROR);
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @param $requestMethod
     * @return static
     */
    public function setRequestMethod($requestMethod): static
    {
        $this->requestMethod = $requestMethod;
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:50
     * @param array $body
     * @param string $contentType
     * @return static
     * @throws \JsonException
     */
    public function withBody(array $body = [], string $contentType = 'application/json'): static
    {
        $this->body = $body;
        $this->httpClient()->withBody($this->formatBody($body), $contentType);
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @param array $queryString
     * @return static
     */
    public function withQueryString(array $queryString): static
    {
        $this->queryString = $queryString;
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @param mixed $headers
     * @return static
     */
    public function withHeaders(mixed $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        $this->httpClient()->withHeaders($this->headers);
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 15:56
     * @return mixed
     * @throws RequestException
     * @throws \JsonException
     */
    public function post(): mixed
    {
        if (empty($this->body)) {
            $this->withBody();
        }

        return $this->setRequestMethod('post')->run();
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @return array|mixed
     * @throws RequestException
     */
    public function get(): mixed
    {
        return $this->setRequestMethod('get')->run();
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @return array|mixed
     * @throws RequestException
     */
    public function run(): mixed
    {
        $resource = $this->fullUrl();

        $response = match ($this->requestMethod) {
            'get'  => $this->httpClient()->get($resource),
            'post' => $this->httpClient()->post($resource),
        };

        $this->setResponse($response);
        $response->throw();
        return $response->json()?: $response->body();
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:02
     * @param Response $response
     * @return Response
     */
    public function setResponse(Response $response): Response
    {
        return $this->response = $response;
    }

    public function fullUrl(): string
    {
        $this->url = $this->config['temuUrl'];
        return $this->fullUrl = sprintf('%s?%s', ...[
            $this->url,
            http_build_query($this->queryString?? [])
        ]);
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 14:42
     * @return array
     */
    protected function getBodyCommonParameters(): array
    {
        return [
            'type'         => $this->apiName,
            'app_key'      => $this->config['appKey'],
            'timestamp'    => time(),
            'access_token' => $this->config['accessToken'],
            'data_type'    => 'JSON',
        ];
    }

    /**
     * 生成签名
     * @Author: hwj
     * @DateTime: 2025-08-15 14:59
     * @param array $params
     * @param string $appSecret
     * @return string
     * @throws \JsonException
     */
    protected function generateSign(array $params, string $appSecret): string
    {
        ksort($params);

        $queryString = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // 如果值是数组（复杂结构体），转换为 JSON 字符串
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $queryString .= $key.$value;
        }

        $stringToSign = $appSecret.$queryString.$appSecret;

        return strtoupper(md5($stringToSign));
    }
}
