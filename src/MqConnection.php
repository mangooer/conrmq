<?php

namespace Mongooer\Conrmq;

use Mongooer\Conrmq\Contracts\MqConnectionInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionBlockedException;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class MqConnection implements MqConnectionInterface
{

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;
    /**
     * @var string
     */
    protected $channelList = [];

    protected $maxReconnectTimes = 3;
    protected $reconnectTimes = 0;
    /**
     * @var \PhpAmqpLib\Channel\AbstractChannel|\PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel;

    public function __construct(array $config)
    {
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
            $config['insist'],
            $config['login_method'],
            $config['login_response'],
            $config['locale'],
            $config['connection_timeout'],
            $config['read_write_timeout'],
            $config['context'],
            $config['keepalive'],
            $config['heartbeat'],
            $config['channel_rpc_timeout'],
            $config['ssl_protocol']
        );
//        $this->channel = $this->connection->channel();
    }


    public function sendMessage(string $exchange, string $queue, string $routingKey, AMQPMessage $AMQPMessage)
    {
        try {
            if (!$this->connection->isConnected() || $this->connection->isBlocked()) {
                $this->connection->reconnect();
//                $this->channel = $this->connection->channel();
            }
            $this->connection->channel(1)->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
            $this->connection->channel(1)->queue_declare($queue, false, true, false, false);
            $this->connection->channel(1)->queue_bind($queue, $exchange, $routingKey);
            $this->connection->channel(1)->basic_publish($AMQPMessage, $exchange, $routingKey);
            $this->reconnectTimes = 0;
        } catch (AMQPChannelClosedException|AMQPConnectionBlockedException|AMQPHeartbeatMissedException $exception) {
            $this->reconnectTimes += 1;
            if ($this->reconnectTimes <= $this->maxReconnectTimes) {
                $this->sendMessage($exchange, $queue, $routingKey, $AMQPMessage);
            } else {
                throw $exception;
            }
        }

    }

    public function sendJson(string $exchange, string $queue, string $routingKey, string $jsonStr)
    {
        $message = new AMQPMessage($jsonStr, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->sendMessage($exchange, $queue, $routingKey, $message);
    }


}
