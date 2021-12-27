# conrmq
laravel 连接 rabbitMq
## 使用说明
1. 安装  
`composer require mongooer/conrmq`  
2. 发布配置文件  
   `php artisan vendor:publish --provider="Mongooer\Conrmq\MongooerConrmqProvider"`
3. 使用  
   `MongooerConMq::channel("default")->setExChange("exchangeName")->setQueue("queueName")->setRoutingKey("routingKey")->publisherJson($sendData);`  
    驱动定义在 `mongooer_conrmq.php` 中 `dirver` 数组，插件会默认使用 `default` 驱动。

