<?php

namespace Serrvius\AmqpRpcExtender\Serializer;

use Serrvius\AmqpRpcExtender\Serializer\Extractor\UuidTypeExtractor;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

class AmqpRpcSerializer extends Serializer implements SymfonySerializerInterface
{
    private SymfonySerializerInterface $symfonySerializer;

    public function __construct(SymfonySerializerInterface $serializer = null,
                                string                     $format = 'json',
                                array                      $context = []
    ) {
        $this->symfonySerializer = $serializer ?? self::create()->symfonySerializer;

        parent::__construct($this->symfonySerializer, $format, $context);
    }

    public static function create(): self
    {
        if (!class_exists(SymfonySerializer::class)) {
            throw new LogicException(sprintf('The "%s" class requires Symfony\'s Serializer component. Try running "composer require symfony/serializer" or use "%s" instead.',
                                             __CLASS__, PhpSerializer::class
                                     )
            );
        }

        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [
            new UidNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(
                propertyTypeExtractor: new PropertyInfoExtractor(
                                           typeExtractors: [new UuidTypeExtractor()]
                                       )
            )
        ];
        $serializer = new SymfonySerializer($normalizers, $encoders);

        return new self($serializer);
    }

    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return $this->symfonySerializer->serialize($data, $format, $context);
    }

    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        return $this->symfonySerializer->deserialize($data, $type, $format, $context);
    }
}