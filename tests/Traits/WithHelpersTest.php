<?php

declare(strict_types=1);

namespace OlegV\Tests\Traits;

use DateTime;
use JsonException;
use OlegV\Traits\WithHelpers;
use PHPUnit\Framework\TestCase;

class WithHelpersTest extends TestCase
{
    use WithHelpers;

    // ==================== BASE TESTS ====================

    public function testEscape(): void
    {
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $this->e('<script>alert("xss")</script>'));
        $this->assertEquals('I&apos;m a &quot;test&quot;', $this->e('I\'m a "test"'));
        $this->assertEquals('Привет мир', $this->e('Привет мир'));
    }

    public function testClassListSimple(): void
    {
        $result = $this->classList(['btn', 'btn-primary', 'active']);
        $this->assertEquals('btn btn-primary active', $result);
    }

    public function testClassListConditional(): void
    {
        $isActive = true;
        $isDisabled = false;
        $result = $this->classList([
            'btn' => true,
            'active' => $isActive,
            'disabled' => $isDisabled,
            'primary' => true,
        ]);

        $this->assertStringContainsString('btn', $result);
        $this->assertStringContainsString('active', $result);
        $this->assertStringContainsString('primary', $result);
        $this->assertStringNotContainsString('disabled', $result);
    }

    public function testClassListMixed(): void
    {
        $result = $this->classList([
            'btn',
            'active' => true,
            'btn-primary',
            'disabled' => false,
            'hidden' => true,
        ]);

        $this->assertEquals('btn active btn-primary hidden', $result);
    }

    public function testClassListWithEmptyValues(): void
    {
        $result = $this->classList(['', 'btn', 'test']);
        $this->assertEquals('btn', $result);
    }

    public function testClassListRemovesDuplicates(): void
    {
        $result = $this->classList(['btn', 'btn', 'active', 'active']);
        $this->assertEquals('btn active', $result);
    }

    public function testAttributes(): void
    {
        $result = $this->attr([
            'id' => 'test-id',
            'class' => 'btn btn-primary',
            'data-value' => 123,
            'disabled' => true,
            'readonly' => false,
            'custom' => null,
        ]);

        $this->assertStringContainsString('id="test-id"', $result);
        $this->assertStringContainsString('class="btn btn-primary"', $result);
        $this->assertStringContainsString('data-value="123"', $result);
        $this->assertStringContainsString('disabled', $result);
        $this->assertStringNotContainsString('readonly', $result);
        $this->assertStringNotContainsString('custom', $result);
    }

    public function testAttributesWithSpecialChars(): void
    {
        $result = $this->attr([
            'data-text' => 'Test "quote" and \'apos\' & ampersand',
            'onclick' => 'alert("test")',
        ]);

        $this->assertStringContainsString('data-text="Test &quot;quote&quot; and &apos;apos&apos; &amp; ampersand"', $result);
        $this->assertStringContainsString('onclick="alert(&quot;test&quot;)"', $result);
    }

    // ==================== FORMAT TESTS ====================

    public function testNumberFormatting(): void
    {
        $this->assertEquals('1 000 000', $this->number(1000000));
        $this->assertEquals('1,000,000', $this->number(1000000, 0, '.', ','));
        $this->assertEquals('1 000 000,50', $this->number(1000000.5, 2));
        $this->assertEquals('999,99', $this->number(999.99, 2, ',', ''));
    }

    public function testDateFormatting(): void
    {
        $timestamp = 1672531199; // 2022-31-12 23:59:59 UTC

        $this->assertEquals('31.12.2022', $this->date($timestamp));
        $this->assertEquals('2022-12-31', $this->date($timestamp, 'Y-m-d'));
        $this->assertEquals('31.12.2022 23:59', $this->date($timestamp, 'd.m.Y H:i'));

        // Test with DateTime object
        $dateTime = new DateTime('2023-12-31');
        $this->assertEquals('31.12.2023', $this->date($dateTime));

        // Test with string
        $this->assertEquals('15.07.2023', $this->date('2023-07-15'));
    }

    public function testDateWithInvalidInput(): void
    {
        $this->assertEquals('', $this->date('invalid-date'));
    }

    /**
     * @throws JsonException
     */
    public function testJsonEncoding(): void
    {
        $data = [
            'name' => 'Test <script>',
            'value' => 'It\'s "quoted" & special',
            'count' => 42,
            'active' => true,
        ];

        $result = $this->json($data);
        $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);

        $this->assertEquals($data['name'], $decoded['name']);
        $this->assertEquals($data['value'], $decoded['value']);
        $this->assertEquals($data['count'], $decoded['count']);
        $this->assertEquals($data['active'], $decoded['active']);

        // Check that special chars are escaped
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('\u003Cscript\u003E', $result);
    }

    // ==================== TEXT TESTS ====================

    public function testTruncate(): void
    {
        $text = 'Это очень длинный текст, который нужно обрезать';

        $this->assertEquals('Это очень длинный текс...', $this->truncate($text, 25));
        $this->assertEquals($text, $this->truncate($text, 100));
        $this->assertEquals('Тест...', $this->truncate('Тестирование', 7));
        $this->assertEquals('Testing', $this->truncate('Testing', 7, '***')); // Не обрезаем, т.к. длина равна лимиту
        $this->assertEquals('Tes***', $this->truncate('Testing', 6, '***')); // Обрезаем 'Testing' до 6 с суффиксом
    }

    public function testUrlGeneration(): void
    {
        $this->assertEquals(
            '/page',
            $this->url('/page')
        );

        $this->assertEquals(
            '/page?id=1&amp;search=test',
            $this->url('/page', ['id' => 1, 'search' => 'test'])
        );

        // Исправленный тест - параметр page должен быть заменен
        $this->assertEquals(
            '/page?page=2&amp;query=hello+world',
            $this->url('/page?page=1', ['page' => 2, 'query' => 'hello world'])
        );

        $this->assertEquals(
            '/search?q=test&amp;page=1',
            $this->url('/search', ['q' => 'test', 'page' => 1, 'sort' => null])
        );

        // Дополнительные тесты на замену параметров
        $this->assertEquals(
            '/item?id=2',
            $this->url('/item?id=1', ['id' => 2])
        );

        $this->assertEquals(
            '/test?a=3&amp;b=4',
            $this->url('/test?a=1&b=2', ['a' => 3, 'b' => 4])
        );

        // Test with special characters
        $this->assertStringContainsString(
            'q=test%26value',
            $this->url('/search', ['q' => 'test&value'])
        );

        // Test with fragment
        $this->assertEquals(
            '/page#section',
            $this->url('/page#section')
        );

        $this->assertEquals(
            '/page?param=value#section',
            $this->url('/page?param=old#section', ['param' => 'value'])
        );
    }

    public function testUniqueId(): void
    {
        $id1 = $this->uniqueId();
        $id2 = $this->uniqueId();
        $id3 = $this->uniqueId('custom_');

        $this->assertEquals('id_1', $id1);
        $this->assertEquals('id_2', $id2);
        $this->assertEquals('custom_3', $id3);
    }

    public function testWordCount(): void
    {
        $this->assertEquals(3, $this->wordCount('Hello world test'));
        $this->assertEquals(4, $this->wordCount('Привет мир тест здесь'));
        $this->assertEquals(0, $this->wordCount(''));
        $this->assertEquals(0, $this->wordCount('   '));
        $this->assertEquals(1, $this->wordCount('Single'));
        $this->assertEquals(2, $this->wordCount('Два слова'));
        $this->assertEquals(4, $this->wordCount('Слова с   несколькими   пробелами'));
        $this->assertEquals(4, $this->wordCount("Строка\nс\nразными\nразделителями"));
        $this->assertEquals(3, $this->wordCount("Word1 word2-word3")); // Дефис считается частью слова
        $this->assertEquals(4, $this->wordCount("It's a test string")); // Апостроф в слове
    }

    public function testPluralForms(): void
    {
        // Russian plural rules
        $forms = ['комментарий', 'комментария', 'комментариев'];

        $this->assertEquals('комментарий', $this->plural(1, $forms));
        $this->assertEquals('комментария', $this->plural(2, $forms));
        $this->assertEquals('комментария', $this->plural(3, $forms));
        $this->assertEquals('комментария', $this->plural(4, $forms));
        $this->assertEquals('комментариев', $this->plural(5, $forms));
        $this->assertEquals('комментариев', $this->plural(11, $forms));
        $this->assertEquals('комментария', $this->plural(22, $forms));
        $this->assertEquals('комментария', $this->plural(23, $forms));
        $this->assertEquals('комментариев', $this->plural(25, $forms));
        $this->assertEquals('комментариев', $this->plural(111, $forms));

        // English example
        $englishForms = ['apple', 'apples', 'apples'];
        $this->assertEquals('apple', $this->plural(1, $englishForms));
        $this->assertEquals('apples', $this->plural(0, $englishForms));
        $this->assertEquals('apples', $this->plural(2, $englishForms));
        $this->assertEquals('apples', $this->plural(5, $englishForms));
    }

    public function testPluralWithNegativeNumbers(): void
    {
        $forms = ['комментарий', 'комментария', 'комментариев'];

        $this->assertEquals('комментария', $this->plural(-2, $forms));
        $this->assertEquals('комментариев', $this->plural(-5, $forms));
        $this->assertEquals('комментариев', $this->plural(-15, $forms));
    }

    // ==================== EDGE CASES ====================

    public function testEmptyInputs(): void
    {
        $this->assertEquals('', $this->e(''));
        $this->assertEquals('', $this->classList([]));
        $this->assertEquals('', $this->attr([]));
        $this->assertEquals('', $this->truncate('', 10));
        $this->assertEquals(0, $this->wordCount(''));
        $this->assertEquals('', $this->date(''));
    }

    public function testMultibyteStrings(): void
    {
        $cyrillic = 'Привет мир';
        $this->assertEquals('Приве...', $this->truncate($cyrillic, 8));
        $this->assertEquals(2, $this->wordCount($cyrillic));
        $this->assertEquals($cyrillic, $this->e($cyrillic));

        $emoji = 'Hello 👋 World 🌍';
        $this->assertEquals('Hello 👋 Wo...', $this->truncate($emoji, 13));
    }
}