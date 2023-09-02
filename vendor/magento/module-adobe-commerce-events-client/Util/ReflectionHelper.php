<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Util;

use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Reflection\MethodReflection;
use Magento\Framework\Reflection\TypeProcessor;
use ReflectionException;
use ReflectionType;

class ReflectionHelper
{
    public const TYPE_VOID = 'void';

    private array $simpleTypes = [
        'bool',
        'boolean',
        'int',
        'integer',
        'string',
        'object',
        'float',
        'array',
        'mixed',
        'null',
        'void',
    ];

    /**
     * @var TypeProcessor
     */
    private TypeProcessor $typeProcessor;

    /**
     * @param TypeProcessor $typeProcessor
     */
    public function __construct(TypeProcessor $typeProcessor)
    {
        $this->typeProcessor = $typeProcessor;
    }

    /**
     * Gets method return type.
     *
     * @param MethodReflection $methodReflection
     * @param ClassReflection $classReflection
     * @return string|null
     */
    public function getReturnType(MethodReflection $methodReflection, ClassReflection $classReflection): ?string
    {
        try {
            $returnType = $this->typeProcessor->getGetterReturnType($methodReflection)['type'];
        } catch (\Throwable $e) {
            return 'mixed';
        }

        if ($returnType === null) {
            return 'null';
        }

        if ($this->isSimple($returnType)) {
            return $returnType;
        }

        if (in_array($returnType, ['$this', 'this', 'self'])) {
            $returnType = $classReflection->getName();
        }

        return $this->typeProcessor->resolveFullyQualifiedClassName($classReflection, $returnType);
    }

    /**
     * Converts array type to single type by removing `[]` part
     *
     * @param string $type
     * @return string
     */
    public function arrayTypeToSingle(string $type): string
    {
        return str_replace('[]', '', $type);
    }

    /**
     * Retrieves all objects properties by a class name in [type, name] format.
     *
     * @param string $fqcn
     * @return array
     * @throws ReflectionException
     */
    public function getObjectProperties(string $fqcn): array
    {
        $result = [];

        $refClass = new ClassReflection($fqcn);
        $methodList = $refClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methodList as $method) {
            if ($method->class != $refClass->getName()) {
                continue;
            }
            $name = $this->getPropertyName($method->getName());
            if ($name === null) {
                continue;
            }

            $propName = $this->pascalCase2SnakeCase($name);
            $result[] = [
                'type' => str_replace('|null', '', $this->getReturnType($method, $refClass)),
                'name' => $propName
            ];
        }

        return $result;
    }

    /**
     * Checks if a method name is a getter or is.
     *
     * @param string $methodName
     * @return string|null
     */
    private function getPropertyName(string $methodName): ?string
    {
        if (strpos($methodName, 'get') === 0) {
            return substr($methodName, 3);
        }

        if (strpos($methodName, 'is') === 0) {
            return substr($methodName, 2);
        }

        return null;
    }

    /**
     * Converts Pascal Case to Snake Case.
     *
     * @param string $input
     * @return string
     */
    public function pascalCase2SnakeCase(string $input): string
    {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $input)), '_');
    }

    /**
     * Checks if a type is an array.
     *
     * @param string|null $input
     * @return bool
     */
    public function isArray(?string $input): bool
    {
        if ($input == null) {
            return false;
        }

        return $input === 'array' || strpos($input, '[]') !== false;
    }

    /**
     * Checks if input string is a simple type.
     *
     * @param string $input
     * @return bool
     */
    public function isSimple(string $input): bool
    {
        return in_array($this->arrayTypeToSingle($input), $this->simpleTypes);
    }

    /**
     * Returns list of method parameters.
     *
     * @param MethodReflection $methodReflection
     * @return array
     */
    public function getMethodParameters(MethodReflection $methodReflection): array
    {
        $params = [];

        foreach ($methodReflection->getParameters() as $parameterReflection) {
            $params[] = [
                'name' => $parameterReflection->getName(),
                'type' => $parameterReflection->detectType(),
            ];
        }

        return $params;
    }
}
