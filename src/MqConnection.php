<?php

namespace Mongooer\Conrmq;

use Mongooer\Conrmq\Contracts\MqConnectionInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class MqConnection implements MqConnectionInterface
{

    protected $exchange;
    protected $queue;
    protected $reBind = false;
    protected $consumerTag = 'router';
    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    protected $channel;

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
        $this->channel = $this->connection->channel();
    }

    public function setExChange(string $exChangeName): MqConnectionInterface
    {
        $this->channel->exchange_declare($exChangeName, AMQPExchangeType::DIRECT, false, true, false);
        if ($this->exchange != $exChangeName) {
            $this->reBind = true;
        }
        $this->exchange = $exChangeName;
        return $this;
    }

    public function setQueue(string $queueName): MqConnectionInterface
    {
        $this->channel->queue_declare($queueName, false, true, false, false);
        if ($this->queue != $queueName) {
            $this->reBind = true;
        }
        $this->queue = $queueName;
        return $this;
    }

    public function publisherArray(array $messageArray)
    {
        $messageBody = json_encode($messageArray);
        $this->publisherString($messageBody);
    }

    public function publisherString(string $messageBody)
    {
        if (!$this->channel) {
            throw new \RuntimeException("channel not defined");
        }
        if (!$this->queue) {
            throw new \RuntimeException("queue not defined");
        }
        if (!$this->exchange) {
            throw new \RuntimeException("exchange not defined");
        }
        if ($this->reBind) {
            $this->channel->queue_bind($this->queue, $this->exchange);
            $this->reBind = true;
        }
        $message = new AMQPMessage($messageBody, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->channel->basic_publish($message, $this->exchange);

    }

    public function listener(\Closure $callback)
    {
        while (true) {
            try {
                $this->doSomeThing($callback);
            } catch (AMQPRuntimeException | \ErrorException | \RuntimeException $e) {
                $this->reconnect();
            }
        }
    }

    private function doSomeThing(\Closure $callback)
    {
        $this->channel->basic_qos(null, 10, null);
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
        $this->channel->basic_consume($this->queue, $this->consumerTag, false, false, false, false, $processMessage);
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function reconnect()
    {
        try {
            if ($this->connection !== null) {
                $this->connection->reconnect();
            }
        } catch (\ErrorException $e) {
        }
    }


}
