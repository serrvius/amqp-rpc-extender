<?php

namespace Serrvius\AmqpRpcExtender\Transport;


use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcResultStamp;
use Serrvius\AmqpRpcExtender\Stamp\AmqpRpcStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpRpcTransport extends AmqpTransport
{

    protected Connection          $connection;
    protected SerializerInterface $serializer;
    protected AmqpFactory         $amqpFactory;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection  = $connection;
        $this->serializer  = $serializer;
        $this->amqpFactory = new AmqpFactory();

        parent::__construct($connection, $serializer);
    }


    public function get(): iterable
    {
        return parent::get();
    }

    /**
     * {@inheritdoc}
     */
    public function getFromQueues(array $queueNames): iterable
    {
        return parent::getFromQueues($queueNames);
    }

    /**
     * {@inheritdoc}
     */
    public function ack(Envelope $envelope): void
    {
        $a = 'b';
        parent::ack($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Envelope $envelope): void
    {
        parent::reject($envelope);
    }


    public function send(Envelope $envelope): Envelope
    {
        $amqpExtenderRcpStamp = $envelope->last(AmqpRpcStamp::class);

        if ($amqpExtenderRcpStamp) {
            $queueName     = $amqpExtenderRcpStamp->getReplayToQueue();
            $correlationId = $amqpExtenderRcpStamp->getCorrelationId();

            $rpcEnvelope = $envelope->with(new AmqpStamp(
                $amqpExtenderRcpStamp->getQueueName(),
                AMQP_NOPARAM,
                [
                    'headers'        => [
                        'procedure' => $amqpExtenderRcpStamp->getProcedureName()
                    ],
                    'correlation_id' => $amqpExtenderRcpStamp->getCorrelationId(),
                    'reply_to'       => $amqpExtenderRcpStamp->getReplayToQueue()
                ]
            ));

            $responseQueue = $this->createResponseQueue($amqpExtenderRcpStamp);

            parent::send($rpcEnvelope->withoutStampsOfType(AmqpRpcStamp::class));


            $startTime = time();

            do {
                /** @var \AMQPEnvelope $amqpEnvelope */
                $amqpEnvelope = $responseQueue->get();
                usleep(250);
            } while ((!$amqpEnvelope || $correlationId != $amqpEnvelope->getCorrelationId()) && time() - $startTime < $amqpExtenderRcpStamp->waitingResponseTTL);


            try {
                $body     = $amqpEnvelope->getBody()??false;
                if($body){
                    $envelope = $envelope->with(new AmqpRpcResultStamp(json_decode($body,true)));
                }
            } catch (MessageDecodingFailedException $exception) {
                // invalid message of some type
                $this->rejectAmqpEnvelope($amqpEnvelope, $queueName);

                throw $exception;
            }

            return $envelope->with(new AmqpReceivedStamp($amqpEnvelope, $amqpExtenderRcpStamp->getQueueName()));


        }

        return parent::send($envelope);
    }

    private function rejectAmqpEnvelope(\AMQPEnvelope $amqpEnvelope, string $queueName): void
    {
        try {
            $this->connection->nack($amqpEnvelope, $queueName, \AMQP_NOPARAM);
        } catch (\AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    protected function createResponseQueue(AmqpRpcStamp $amqpExtenderRcpStamp)
    {
        $responseQueue = $this->amqpFactory->createQueue($this->connection->channel());
        $responseQueue->setName($amqpExtenderRcpStamp->getReplayToQueue());
        $responseQueue->setFlags(AMQP_EXCLUSIVE);
        $responseQueue->declareQueue();

        $responseQueue->bind($this->connection->exchange()->getName(),$amqpExtenderRcpStamp->getReplayToQueue());


        return $responseQueue;
    }

    /**
     * {@inheritdoc}
     */
    public function setup(): void
    {
        parent::setup();
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCount(): int
    {
        return parent::getMessageCount();
    }


}