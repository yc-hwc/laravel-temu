# laravel-temu
temu client

#### 安装教程
````
composer require yc-hwc/laravel-temu
````

### 用法
***

#### 配置
````
       $config = [
           'temuUrl'     => '',
           'accessToken' => '',
           'appKey'      => '',
           'appSecret'   => '',
       ];
    
       $temuClient = \PHPTemu\V1\TemuClient::config($config);
````

#### [订单列表](https://partner.temu.com/documentation?menu_code=fb16b05f7a904765aac4af3a24b87d4a&sub_menu_code=554fd46b45ee49269cbdd6d4008a5dc1)
````
       $config = [
           'temuUrl'     => '',
           'accessToken' => '',
           'appKey'      => '',
           'appSecret'   => '',
       ];

        $params = [
            'pageNumber' => 1,
            'pageSize'   => 10
        ];
        $temuClient = \PHPTemu\V1\TemuClient::config($config);
        $res = $temuClient->api('bg.order.list.v2.get')->withBody($params)->post();
        print_r($res);
````
