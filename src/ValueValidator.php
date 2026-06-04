<?php

namespace Hyvor\FilterQ;

use Hyvor\FilterQ\Exceptions\InvalidValueException;

class ValueValidator
{
    /** @var string[] */
    public const SUPPORTED_VALUES = [
        'int',
        'float',
        'string',
        'null',
        'bool',
        'numeric',
        'date',
    ];

    /**
     * @throws InvalidValueException
     */
    public static function validate(Key $key, mixed $value): mixed
    {
        $keyName = $key->getName();

        $supportedValues = $key->getSupportedValues();
        if (is_array($supportedValues)) {
            if (!in_array($value, $supportedValues, true)) {
                $values = implode(', ', array_map(
                    fn(mixed $v): string => is_scalar($v) || $v === null ? (string) $v : gettype($v),
                    $supportedValues
                ));
                $valueStr = is_scalar($value) ? (string) $value : gettype($value);
                throw new InvalidValueException(
                    "The key $keyName only supports the following values for filtering: $values. '$valueStr' given"
                );
            }
        }

        $supportedValueTypes = $key->getSupportedValueTypes();
        if (is_array($supportedValueTypes)) {
            $isValid = false;
            $valueType = gettype($value);

            foreach ($supportedValueTypes as $supportedValueType) {
                if (
                    ($supportedValueType === 'int' && $valueType === 'integer') ||
                    ($supportedValueType === 'float' && $valueType === 'double') ||
                    ($supportedValueType === 'string' && $valueType === 'string') ||
                    ($supportedValueType === 'bool' && $valueType === 'boolean') ||
                    ($supportedValueType === 'null' && $valueType === 'NULL') ||
                    ($supportedValueType === 'numeric' && is_numeric($value))
                ) {
                    $isValid = true;
                    break;
                } elseif ($supportedValueType === 'date') {
                    if (is_int($value)) {
                        $timestamp = $value;
                    } elseif (is_string($value)) {
                        $timestamp = strtotime($value);
                    } else {
                        $timestamp = false;
                    }

                    if ($timestamp !== false) {
                        $isValid = true;
                        $value = new \DateTimeImmutable('@' . $timestamp);
                        break;
                    }
                }
            }

            if (!$isValid) {
                $validTypesString = implode('|', $supportedValueTypes);
                throw new InvalidValueException("Value for $keyName should be one of: $validTypesString");
            }
        }

        return $value;
    }
}
