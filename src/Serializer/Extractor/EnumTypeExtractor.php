<?php

namespace Serrvius\AmqpRpcExtender\Serializer\Extractor;

use BackedEnum;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class EnumTypeExtractor implements PropertyTypeExtractorInterface
{
    /**
     * @param string $class
     * @param string $property
     * @param array $context
     * @return Type[]|null
     */
    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        try {
            $classReflection = new \ReflectionClass($class);
            $property = $classReflection->getProperty($property);
            $type = $property->getType();
            if ($type->getName() === BackedEnum::class) {
                return [new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    $type->allowsNull(),
                    BackedEnum::class,
                    false
                )
                ];
            }
        } catch (\Throwable $exception) {
        }

        return null;
    }
}