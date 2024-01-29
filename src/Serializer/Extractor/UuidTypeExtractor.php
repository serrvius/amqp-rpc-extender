<?php

namespace Serrvius\AmqpRpcExtender\Serializer\Extractor;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Uid\Uuid;

class UuidTypeExtractor implements PropertyTypeExtractorInterface
{
    public function getTypes(string $class, string $property, array $context = [])
    {
        try {
            $classReflection = new \ReflectionClass($class);
            $property = $classReflection->getProperty($property);
            $type = $property->getType();
            if ($type->getName() === Uuid::class) {
                return [new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    $type->allowsNull(),
                    Uuid::class,
                    false
                )
                ];
            }
        }catch (\Throwable $exception){
        }

        return null;
    }
}