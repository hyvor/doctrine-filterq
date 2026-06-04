<?php

namespace Hyvor\FilterQ\Tests\Unit;

use Hyvor\FilterQ\Exceptions\InvalidValueException;
use Hyvor\FilterQ\Key;
use Hyvor\FilterQ\ValueValidator;
use PHPUnit\Framework\TestCase;

class ValueValidatorTest extends TestCase
{
    public function test_key_value(): void
    {
        $key = new Key('test');
        $key->values([200, 250]);

        $validated = ValueValidator::validate($key, 250);

        $this->assertSame(250, $validated);
    }

    public function test_key_value_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->values([200, 250]);
        ValueValidator::validate($key, 300);
    }

    public function test_key_value_type_string(): void
    {
        $key = new Key('test');
        $key->valueType('string');
        $value = ValueValidator::validate($key, 'some string');

        $this->assertSame('some string', $value);
    }

    public function test_key_value_type_string_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('string');
        ValueValidator::validate($key, 300);
    }

    public function test_key_value_type_int(): void
    {
        $key = new Key('test');
        $key->valueType('int');
        $value = ValueValidator::validate($key, 200);

        $this->assertSame(200, $value);
    }

    public function test_key_value_type_int_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('int');
        ValueValidator::validate($key, 'string');
    }

    public function test_key_value_type_float(): void
    {
        $key = new Key('test');
        $key->valueType('float');
        $value = ValueValidator::validate($key, 20.0);

        $this->assertSame(20.0, $value);
    }

    public function test_key_value_type_float_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('float');
        ValueValidator::validate($key, 20);
    }

    public function test_key_value_type_numeric(): void
    {
        $key = new Key('test');
        $key->valueType('numeric');

        $this->assertSame(20, ValueValidator::validate($key, 20));
        $this->assertSame('20', ValueValidator::validate($key, '20'));
        $this->assertSame(20.2, ValueValidator::validate($key, 20.2));
    }

    public function test_key_value_type_numeric_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('numeric');
        ValueValidator::validate($key, null);
    }

    public function test_key_value_type_bool(): void
    {
        $key = new Key('test');
        $key->valueType('bool');

        $this->assertSame(true, ValueValidator::validate($key, true));
        $this->assertSame(false, ValueValidator::validate($key, false));
    }

    public function test_key_value_type_bool_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('bool');
        ValueValidator::validate($key, null);
    }

    public function test_key_value_type_null(): void
    {
        $key = new Key('test');
        $key->valueType('null');

        $this->assertSame(null, ValueValidator::validate($key, null));
    }

    public function test_key_value_type_null_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('null');
        ValueValidator::validate($key, 1220);
    }

    public function test_key_value_type_date(): void
    {
        $key = new Key('test');
        $key->valueType('date');

        $result = ValueValidator::validate($key, '2020-02-10');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2020-02-10', $result->format('Y-m-d'));
    }

    public function test_key_value_type_date_relative(): void
    {
        $key = new Key('test');
        $key->valueType('date');

        $result = ValueValidator::validate($key, 'yesterday');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame(
            (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            $result->format('Y-m-d')
        );
    }

    public function test_key_value_type_date_unix(): void
    {
        $key = new Key('test');
        $key->valueType('date');

        $result = ValueValidator::validate($key, 1649358544);

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame(
            (new \DateTimeImmutable('@1649358544'))->getTimestamp(),
            $result->getTimestamp()
        );
    }

    public function test_key_value_type_date_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('date');
        ValueValidator::validate($key, 'fslerklwao');
    }

    public function test_key_value_type_union(): void
    {
        $key = new Key('test');
        $key->valueType('date|null');

        $this->assertSame(null, ValueValidator::validate($key, null));
    }

    public function test_key_value_type_union_wrong(): void
    {
        $this->expectException(InvalidValueException::class);

        $key = new Key('test');
        $key->valueType('date|null');

        ValueValidator::validate($key, true);
    }

    public function test_key_value_type_union_array(): void
    {
        $key = new Key('test');
        $key->valueType(['int', 'string']);

        $this->assertSame('string', ValueValidator::validate($key, 'string'));
    }
}
