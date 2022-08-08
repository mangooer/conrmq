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

    protected $consumerTag = 'router';
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

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param string $jsonStr
     * @return void
     */
    public function sendJson(string $exchange, string $queue, string $routingKey, string $jsonStr)
    {
        $message = new AMQPMessage($jsonStr, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->sendMessage($exchange, $queue, $routingKey, $message);
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param AMQPMessage $AMQPMessage
     * @return void
     */
    public function sendMessage(string $exchange, string $queue, string $routingKey, AMQPMessage $AMQPMessage)
    {
        $this->handle($exchange, $queue, $routingKey, function (\PhpAmqpLib\Channel\AMQPChannel $channel) use ($AMQPMessage, $exchange, $routingKey) {
            $channel->basic_publish($AMQPMessage, $exchange, $routingKey);
        });
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param \Closure $callback
     * @return mixed
     */
    public function listener(string $exchange, string $queue, string $routingKey, \Closure $callback)
    {
        while (true) {
            $this->handle($exchange, $queue, $routingKey, function (\PhpAmqpLib\Channel\AMQPChannel $channel) use ($callback, $queue) {
                $this->doSomeThing($channel, $queue, $callback);
            });
        }
    }

    /**
     * @param \PhpAmqpLib\Channel\AMQPChannel $channel
     * @param string $queue
     * @param \Closure $callback
     * @return void
     * @throws \ErrorException
     */
    private function doSomeThing(\PhpAmqpLib\Channel\AMQPChannel $channel, string $queue, \Closure $callback)
    {
        $channel->basic_qos(null, 10, null);
        //无论如何都会确认，所以异常需要捕获并且记录日志
        $processMessage = function (AMQPMessage $message) use ($callback) {
            try {
                call_user_func($callback, $message);
                //测试时不释放
                $message->ack();
            } catch (\Throwable $e) {
                $message->ack();
            }
            // Send a message with the string "quit" to cancel the consumer.
            if ($message->body === 'quit') {
                $message->getChannel()->basic_cancel($message->getConsumerTag());
            }
        };
        $channel->basic_consume($queue, $this->consumerTag, false, false, false, false, $processMessage);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    /**
     * @param string $exchange
     * @param string $queue
     * @param string $routingKey
     * @param \Closure $callback
     * @return void
     */
    private function handle(string $exchange, string $queue, string $routingKey, \Closure $callback)
    {
        try {
            if (!$this->connection->isConnected() || $this->connection->isBlocked()) {
                $this->connection->reconnect();
            }
            $channel = $this->connection->channel(1);
            $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
            $channel->queue_declare($queue, false, true, false, false);
            $channel->queue_bind($queue, $exchange, $routingKey);
            call_user_func($callback, $channel);
            $this->reconnectTimes = 0;
        } catch (AMQPChannelClosedException|AMQPConnectionBlockedException|AMQPHeartbeatMissedException $exception) {
            $this->reconnectTimes += 1;
            if ($this->reconnectTimes <= $this->maxReconnectTimes) {
                $this->handle($exchange, $queue, $routingKey, $callback);
            } else {
                throw $exception;
            }
        }
    }

}
