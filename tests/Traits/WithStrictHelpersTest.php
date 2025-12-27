<?php


declare(strict_types=1);

namespace OlegV\Tests\Traits;

use OlegV\Traits\WithStrictHelpers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

// Создаем тестовый класс для использования трейта
class WithStrictHelpersTestClass
{
    use WithStrictHelpers;

    // Просто обертка для методов трейта
}

class WithStrictHelpersTest extends TestCase
{
    private WithStrictHelpersTestClass $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new WithStrictHelpersTestClass();
    }

    // ==================== STRING HELPERS TESTS ====================

    public function testHasString(): void
    {
        $this->assertTrue($this->helper->hasString('text'));
        $this->assertTrue($this->helper->hasString(' '));
        $this->assertFalse($this->helper->hasString(''));
        $this->assertFalse($this->helper->hasString(null));
    }

    public function testGetString(): void
    {
        $this->assertEquals('text', $this->helper->getString('text'));
        $this->assertEquals('text', $this->helper->getString('text', 'default'));
        $this->assertEquals('default', $this->helper->getString('', 'default'));
        $this->assertEquals('default', $this->helper->getString(null, 'default'));
        $this->assertEquals('', $this->helper->getString(null));
    }

    public function testStringEquals(): void
    {
        $this->assertTrue($this->helper->stringEquals('text', 'text'));
        $this->assertFalse($this->helper->stringEquals('text', 'other'));
        $this->assertFalse($this->helper->stringEquals(null, 'text'));
        $this->assertFalse($this->helper->stringEquals(null, '')); // null !== ''
        $this->assertTrue($this->helper->stringEquals('', '')); // '' === ''
    }

    public function testStringContains(): void
    {
        $this->assertTrue($this->helper->stringContains('hello world', 'world'));
        $this->assertFalse($this->helper->stringContains('hello world', 'test'));
        $this->assertFalse($this->helper->stringContains('', 'test'));
        $this->assertFalse($this->helper->stringContains(null, 'test'));
    }

    // ==================== NUMBER HELPERS TESTS ====================

    public function testHasNumber(): void
    {
        $this->assertTrue($this->helper->hasNumber(42));
        $this->assertTrue($this->helper->hasNumber(3.14));
        $this->assertFalse($this->helper->hasNumber(null));
    }

    public function testGetNumber(): void
    {
        $this->assertEquals(42, $this->helper->getNumber(42));
        $this->assertEquals(3.14, $this->helper->getNumber(3.14));
        $this->assertEquals(10, $this->helper->getNumber(null, 10));
        $this->assertEquals(0, $this->helper->getNumber(null));
    }

    public function testIsPositive(): void
    {
        $this->assertTrue($this->helper->isPositive(42));
        $this->assertTrue($this->helper->isPositive(0.1));
        $this->assertFalse($this->helper->isPositive(0));
        $this->assertFalse($this->helper->isPositive(-5));
        $this->assertFalse($this->helper->isPositive(null));
    }

    // ==================== ARRAY HELPERS TESTS ====================

    public function testHasArray(): void
    {
        $this->assertTrue($this->helper->hasArray([1, 2, 3]));
        $this->assertTrue($this->helper->hasArray(['key' => 'value']));
        $this->assertFalse($this->helper->hasArray([]));
        $this->assertFalse($this->helper->hasArray(null));
    }

    public function testGetArray(): void
    {
        $testArray = [1, 2, 3];
        $this->assertSame($testArray, $this->helper->getArray($testArray));
        $this->assertSame([], $this->helper->getArray([]));
        $this->assertSame([], $this->helper->getArray(null));
    }

    public function testArrayHasKey(): void
    {
        $array = ['name' => 'John', 'age' => 30];

        $this->assertTrue($this->helper->arrayHasKey($array, 'name'));
        $this->assertTrue($this->helper->arrayHasKey($array, 'age'));
        $this->assertFalse($this->helper->arrayHasKey($array, 'email'));
        $this->assertFalse($this->helper->arrayHasKey(null, 'name'));
        $this->assertTrue($this->helper->arrayHasKey([0 => 'a', 1 => 'b'], 0));
    }

    public function testArrayGet(): void
    {
        $array = ['name' => 'John', 'age' => 30];

        $this->assertEquals('John', $this->helper->arrayGet($array, 'name'));
        $this->assertEquals(30, $this->helper->arrayGet($array, 'age'));
        $this->assertNull($this->helper->arrayGet($array, 'email'));
        $this->assertEquals('default', $this->helper->arrayGet($array, 'email', 'default'));
        $this->assertNull($this->helper->arrayGet(null, 'name'));
        $this->assertEquals('default', $this->helper->arrayGet(null, 'name', 'default'));
    }

    // ==================== BOOLEAN HELPERS TESTS ====================

    public function testIsTrue(): void
    {
        // Булевые значения
        $this->assertTrue($this->helper->isTrue(true));
        $this->assertFalse($this->helper->isTrue(false));

        // Скалярные значения
        $this->assertTrue($this->helper->isTrue(1));
        $this->assertTrue($this->helper->isTrue('1'));
        $this->assertTrue($this->helper->isTrue('yes'));
        $this->assertTrue($this->helper->isTrue('on'));
        $this->assertTrue($this->helper->isTrue('true'));

        $this->assertFalse($this->helper->isTrue(0));
        $this->assertFalse($this->helper->isTrue('0'));
        $this->assertFalse($this->helper->isTrue('no'));
        $this->assertFalse($this->helper->isTrue('off'));
        $this->assertFalse($this->helper->isTrue('false'));
        $this->assertFalse($this->helper->isTrue(''));
        $this->assertFalse($this->helper->isTrue(' '));

        // Null
        $this->assertFalse($this->helper->isTrue(null));

        // Массивы
        $this->assertTrue($this->helper->isTrue([1, 2, 3]));
        $this->assertTrue($this->helper->isTrue(['key' => 'value']));
        $this->assertFalse($this->helper->isTrue([]));

        // Объекты
        $this->assertTrue($this->helper->isTrue(new stdClass()));
        $this->assertTrue($this->helper->isTrue($this->helper));

        // Ресурсы и другие типы
        $resource = fopen('php://memory', 'r');
        if (is_resource($resource)) {
            $this->assertFalse($this->helper->isTrue($resource)); // Не скалярное
            fclose($resource);
        }
    }

    public function testIsFalse(): void
    {
        $this->assertTrue($this->helper->isFalse(false));
        $this->assertFalse($this->helper->isFalse(true));
        $this->assertFalse($this->helper->isFalse(null));
        $this->assertFalse($this->helper->isFalse(0));
        $this->assertFalse($this->helper->isFalse(''));
    }

    // ==================== OBJECT HELPERS TESTS ====================

    public function testHasObject(): void
    {
        $this->assertTrue($this->helper->hasObject(new stdClass()));
        $this->assertTrue($this->helper->hasObject($this->helper));
        $this->assertFalse($this->helper->hasObject(null));
    }

    // ==================== TYPE CASTING HELPERS TESTS ====================

    public function testToString(): void
    {
        $this->assertEquals('text', $this->helper->toString('text'));
        $this->assertEquals('42', $this->helper->toString(42));
        $this->assertEquals('3.14', $this->helper->toString(3.14));
        $this->assertEquals('1', $this->helper->toString(true));
        $this->assertEquals('', $this->helper->toString(false));
        $this->assertEquals('', $this->helper->toString(null));
        $this->assertEquals('', $this->helper->toString([]));

        // Объект с __toString
        $object = new class {
            public function __toString(): string
            {
                return 'object string';
            }
        };
        $this->assertEquals('object string', $this->helper->toString($object));

        // Объект без __toString
        $object2 = new stdClass();
        $this->assertEquals('', $this->helper->toString($object2));
    }

    public function testToInt(): void
    {
        $this->assertEquals(42, $this->helper->toInt(42));
        $this->assertEquals(42, $this->helper->toInt('42'));
        $this->assertEquals(42, $this->helper->toInt('42.7'));
        $this->assertEquals(1, $this->helper->toInt(true));
        $this->assertEquals(0, $this->helper->toInt(false));
        $this->assertEquals(0, $this->helper->toInt(null));
        $this->assertEquals(10, $this->helper->toInt(null, 10));
        $this->assertEquals(0, $this->helper->toInt('not a number'));
        $this->assertEquals(5, $this->helper->toInt('not a number', 5));
        $this->assertEquals(0, $this->helper->toInt([]));
    }

    public function testToFloat(): void
    {
        $this->assertEquals(3.14, $this->helper->toFloat(3.14));
        $this->assertEquals(3.14, $this->helper->toFloat('3.14'));
        $this->assertEquals(42.0, $this->helper->toFloat(42));
        $this->assertEquals(1.0, $this->helper->toFloat(true));
        $this->assertEquals(0.0, $this->helper->toFloat(false));
        $this->assertEquals(0.0, $this->helper->toFloat(null));
        $this->assertEquals(10.5, $this->helper->toFloat(null, 10.5));
        $this->assertEquals(0.0, $this->helper->toFloat('not a number'));
        $this->assertEquals(5.5, $this->helper->toFloat('not a number', 5.5));
    }

    public function testToBool(): void
    {
        // Должен вести себя так же как isTrue
        $this->assertTrue($this->helper->toBool(true));
        $this->assertFalse($this->helper->toBool(false));
        $this->assertTrue($this->helper->toBool(1));
        $this->assertFalse($this->helper->toBool(0));
        $this->assertFalse($this->helper->toBool(null));
        $this->assertTrue($this->helper->toBool([1, 2]));
        $this->assertFalse($this->helper->toBool([]));
    }

    // ==================== COMPARISON HELPERS TESTS ====================

    public function testEquals(): void
    {
        $this->assertTrue($this->helper->equals(42, 42));
        $this->assertTrue($this->helper->equals('text', 'text'));
        $this->assertTrue($this->helper->equals(null, null));
        $this->assertTrue($this->helper->equals(true, true));

        $this->assertFalse($this->helper->equals(42, '42')); // Разные типы
        $this->assertFalse($this->helper->equals(42, 43));
        $this->assertFalse($this->helper->equals('text', 'other'));
        $this->assertFalse($this->helper->equals(null, false));
    }

    public function testInArray(): void
    {
        $array = ['apple', 'banana', 'cherry', 42, true];

        $this->assertTrue($this->helper->inArray('apple', $array));
        $this->assertTrue($this->helper->inArray('banana', $array));
        $this->assertTrue($this->helper->inArray(42, $array));
        $this->assertTrue($this->helper->inArray(true, $array));

        $this->assertFalse($this->helper->inArray('orange', $array));
        $this->assertFalse($this->helper->inArray(43, $array));
        $this->assertFalse($this->helper->inArray(false, $array));
        $this->assertFalse($this->helper->inArray('42', $array)); // Строгий поиск
        $this->assertFalse($this->helper->inArray(1, $array)); // true !== 1
    }

    // ==================== EDGE CASES TESTS ====================

    public function testEdgeCases(): void
    {
        // Проверка с очень длинной строкой
        $longString = str_repeat('a', 10000);
        $this->assertTrue($this->helper->hasString($longString));
        $this->assertEquals($longString, $this->helper->getString($longString));

        // Проверка с специальными символами
        $specialString = "test\n\t\r\x00";
        $this->assertTrue($this->helper->hasString($specialString));
        $this->assertEquals($specialString, $this->helper->getString($specialString));

        // Проверка stringContains с пустой needle
        $this->assertTrue($this->helper->stringContains('test', ''));

        // Проверка с большими числами
        $this->assertTrue($this->helper->isPositive(PHP_INT_MAX));
        $this->assertFalse($this->helper->isPositive(-PHP_INT_MAX));

        // Проверка arrayGet с разными типами ключей
        /* @noinspection PhpDuplicateArrayKeysInspection */
        $array = [
            'string' => 'value1',
            0 => 'value2',
            '0' => 'value3', // Перезапишет числовой 0
            null => 'value4',
        ];

        $this->assertEquals('value1', $this->helper->arrayGet($array, 'string'));
        $this->assertEquals('value3', $this->helper->arrayGet($array, 0)); // Строгий поиск, ключ '0'
        $this->assertEquals('value3', $this->helper->arrayGet($array, '0'));
        $this->assertEquals('value4', $this->helper->arrayGet($array, ''));
    }

    public function testPerformance(): void
    {
        // Простая проверка производительности (не должна быть медленной)
        $start = microtime(true);

        $result = [];
        for ($i = 0; $i < 1000; $i++) {
            $result[] = $this->helper->hasString('test');
            $result[] = $this->helper->getString('test');
            $result[] = $this->helper->toInt($i);
            $result[] = $this->helper->isTrue(true);
        }

        $this->assertCount(4000, $result);

        $end = microtime(true);
        $executionTime = $end - $start;

        // 4000 вызовов должны выполняться быстро
        $this->assertLessThan(
            0.1,
            $executionTime,
            "4000 method calls took $executionTime seconds",
        );
    }

    // ==================== TYPE SAFETY TESTS ====================

    #[DataProvider('scalarIsTrueProvider')]
    public function testScalarIsTrue(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, $this->helper->isTrue($value));
    }

    public static function scalarIsTrueProvider(): array
    {
        return [
            // Булевые значения
            [true, true],
            [false, false],

            // Числа
            [1, true],
            [0, false],
            [-1, false], // -1 не считается true для FILTER_VALIDATE_BOOLEAN

            // Строки
            ['1', true],
            ['0', false],
            ['true', true],
            ['false', false],
            ['yes', true],
            ['no', false],
            ['on', true],
            ['off', false],
            ['', false],
            [' ', false], // Пробел не считается true
            ['null', false], // Строка 'null' не считается true
            ['NULL', false], // Строка 'NULL' не считается true

            // Дополнительные случаи
            ['y', false], // Только 'yes', не 'y'
            ['t', false], // Только 'true', не 't'
            ['ok', false],
            ['да', false], // Не английское
        ];
    }
}