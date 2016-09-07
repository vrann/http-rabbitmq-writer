<?php
/**
 * Sends responses to Facebook API from the RabbitMQ
 */

include __DIR__ . "/vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$ACCESS_TOKEN = getenv('FB_ACCESS_TOKEN');

$logger = new Logger('my_logger');
$logger->pushHandler(new StreamHandler('/tmp/http-rabbitmq-responser.log', Logger::DEBUG));
$logger->addInfo('Responser activated');

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
/** @var \PhpAmqpLib\Channel\AMQPChannel $channel */

$channel = $connection->channel();
$queue = 'queue.vrann.magebot.api.messageSenderInterface.sendMessage';

/**
 * @param $msg
 */
$callback = function($msg) use ($logger, $ACCESS_TOKEN) {
    /**
     * @var Logger $logger
     */
    $message = json_decode($msg->body, true)['message'];
    $logger->addDebug(' [x] ' . $msg->delivery_info['routing_key'] . ':' . $message . "\n");
    $transport = new \Vrann\FbChatBot\Transport\Http($ACCESS_TOKEN, $logger);
    try {
        $transport->send($message);
        $logger->addInfo('Response is sent to Facebook API');
    } catch (\Vrann\FbChatBot\CommunicationException $e) {
        echo $e->getMessage();
    }

};

$channel->basic_consume($queue, '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();