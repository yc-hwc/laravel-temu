<?php

namespace PHPTemu\V1;

class TemuClient extends Api
{
    protected string $apiName;

    public array $config = [
        'temuUrl'     => '',
        'accessToken' => '',
        'appKey'      => '',
        'appSecret'   => '',
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 12:21
     * @param string $apiName
     * @return static
     */
    public function api(string $apiName): static
    {
        $this->setHttpClient();
        $this->apiName = $apiName;
        return $this;
    }

    /**
     * @Author: hwj
     * @DateTime: 2025-08-15 12:17
     * @param array $config
     * @return static
     */
    public static function config(array $config): static
    {
        return new static($config);
    }
}
