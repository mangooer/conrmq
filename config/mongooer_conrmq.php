<?php
return [
    "channel" => [
        "default" => [
            "host" => "mq.gagctv.com",
            "port" => "5678",
            "user" => "gagc",
            "password" => "gagc@2020",
            "vhost" => "gagc_vhost",
            "insist" => false,
            "login_method" => "AMQPLAIN",
            "login_response" => null,
            "locale" => "en_US",
            "connection_timeout" => 60,
            "read_write_timeout" => 60,
            "context" => null,
            "keepalive" => true,
            "heartbeat" => 30 * 1,
            "channel_rpc_timeout" => 0.0,
            "ssl_protocol" => null,
        ],
    ],
];
