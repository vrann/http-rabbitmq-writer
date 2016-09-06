<?php
/**
 * Sends responses to Facebook API from the RabbitMQ
 */

include __DIR__ . "/vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('my_logger');
$logger->pushHandler(new StreamHandler('/tmp/http-rabbitmq-writer.log', Logger::DEBUG));
$logger->addInfo('Responser activated');

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
/** @var \PhpAmqpLib\Channel\AMQPChannel $channel */

$channel = $connection->channel();
$topic = 'callback.received';
$queue = 'queue.vrann.magebot.api.messageSenderInterface.sendMessage';

/**
 * @param $msg
 */
$callback = function($msg) use ($logger) {
    $logger->addDebug(' [x] ',$msg->delivery_info['routing_key'], ':', $msg->body, "\n");
    $transport = new \Vrann\FbChatBot\Transport\Http(self::TOKEN, $logger);
    $transport->send($msg->body);
};

$channel->basic_consume($queue, '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
$logger->addInfo('Response is sent');