<?php

namespace Serrvius\AmqpRpcExtender\Serializer;

use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcCommandInterface;
use Serrvius\AmqpRpcExtender\Interfaces\AmqpRpcQueryInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AmqpRpcMessageSerializer implements SerializerInterface
{

    public function __construct(
        private readonly Serializer $defaultSerializer,
        private ServiceLocator $queryExecutors,
        private ServiceLocator $commandExecutors
    ) {
    }


    public function decode(array $encodedEnvelope): Envelope
    {

        $executor = $encodedEnvelope['headers']['executor'] ?? null;

        if ($executor) {
            if ($this->queryExecutors->has($executor)) {
                $queryExecutorObject                = $this->queryExecutors->get($executor);
                $encodedEnvelope['headers']['type'] = get_class($queryExecutorObject);

                if ($queryExecutorObject instanceof AmqpRpcQueryInterface) {
                    $decodedQueryBody = json_decode($encodedEnvelope['body'] ?? '[]', true);
                    $encodedEnvelope['body'] = json_encode([
                        'queryData' => $decodedQueryBody['queryData']??$decodedQueryBody
                    ]);
                } else {
                    $encodedEnvelope['body'] = $encodedEnvelope['body'] ?? false;
                }
            }

            if ($this->commandExecutors->has($executor)) {
                $commandExecutorObject              = $this->commandExecutors->get($executor);
                $encodedEnvelope['headers']['type'] = get_class($commandExecutorObject);
                if ($commandExecutorObject instanceof AmqpRpcCommandInterface) {
                    $decodedCommandData = json_decode($encodedEnvelope['body'] ?? '[]', true);
                    $encodedEnvelope['body'] = json_encode([
                        'commandData' => $decodedCommandData['commandData']??$decodedCommandData
                    ]);
                } else {
                    $encodedEnvelope['body'] = $encodedEnvelope['body'] ?? false;
                }
            }

            unset($encodedEnvelope['headers']['executor']);

        }

        return $this->defaultSerializer->decode($encodedEnvelope);
    }


    public function encode(Envelope $envelope): array
    {
        $envelope = $envelope->with(new SerializerStamp([
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => static function(mixed $data) {
                $circularReferenceResponse = '#CIRCULAR_REFERENCE#';
                $suggestedMethods = ['getId','getTitle','getName'];
                foreach ($suggestedMethods as $method){
                    if(method_exists($data,$method)){
                        $circularReferenceResponse = $data->{$method}();
                    }
                }

                return $circularReferenceResponse;
            }
        ]));

        return $this->defaultSerializer->encode($envelope);
    }

}