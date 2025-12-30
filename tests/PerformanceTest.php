<?php

declare(strict_types=1);

namespace OlegV\Tests;

use OlegV\BrickManager;
use OlegV\Tests\Components\Button\Button;
use OlegV\Tests\Components\Card\Card;
use OlegV\Tests\Components\PrimaryButton\PrimaryButton;
use PHPUnit\Framework\TestCase;

class PerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BrickManager::getInstance()->clear();
    }

    protected function tearDown(): void
    {
        BrickManager::getInstance()->clear();
        parent::tearDown();
    }

    public function testRender100Buttons(): void
    {
        $start = microtime(true);
        $output = '';

        for ($i = 0; $i < 100; $i++) {
            $button = new Button("Button $i", $i % 2 === 0 ? 'primary' : 'secondary');
            $output .= $button->render();
        }

        $time = microtime(true) - $start;

        // Проверяем что всё отрендерилось
        $this->assertStringContainsString('Button 0', $output);
        $this->assertStringContainsString('Button 99', $output);
        $this->assertLessThan(0.5, $time, "Рендеринг 100 кнопок должен занимать < 0.5 секунды");

        // Логируем результат
        fwrite(STDERR, "✓ Рендеринг 100 кнопок: ".round($time * 1000, 2)." ms\n");
    }

    public function testRenderMixedComponents(): void
    {
        $start = microtime(true);
        $output = '';

        for ($i = 0; $i < 50; $i++) {
            // Смешиваем разные типы компонентов
            $button = new Button("Button $i");
            $output .= $button->render();

            $primaryButton = new PrimaryButton("Primary $i");
            $output .= $primaryButton->render();

            $card = new Card("Card $i", "Content $i", "Footer $i");
            $output .= $card->render();
        }

        $time = microtime(true) - $start;

        $this->assertStringContainsString('Button 0', $output);
        $this->assertStringContainsString('Primary 49', $output);
        $this->assertStringContainsString('Card 49', $output);
        $this->assertLessThan(1.0, $time, "Рендеринг 150 компонентов должен занимать < 1 секунды");

        fwrite(STDERR, "✓ Рендеринг 150 смешанных компонентов: ".round($time * 1000, 2)." ms\n");
    }

    public function testMemoryUsageFor100Components(): void
    {
        $memoryBefore = memory_get_usage();
        $components = [];

        for ($i = 0; $i < 100; $i++) {
            $components[] = new Button("Button $i");
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Ожидаем что 100 компонентов не займут больше 1MB
        $this->assertLessThan(1024 * 1024, $memoryUsed, "100 компонентов должны занимать < 1MB памяти");

        fwrite(STDERR, "✓ Память для 100 компонентов: ".round($memoryUsed / 1024, 2)." KB\n");
    }

    public function testCachePerformance(): void
    {
        // Первый рендеринг (без кэша)
        $start1 = microtime(true);
        $button1 = new Button('Test Button');
        $output1 = $button1->render();
        $time1 = microtime(true) - $start1;

        // Второй рендеринг того же класса (должен использовать кэш метаданных)
        $start2 = microtime(true);
        $button2 = new Button('Another Button');
        $output2 = $button2->render();
        $time2 = microtime(true) - $start2;

        // Второй рендеринг должен быть быстрее
        $this->assertLessThanOrEqual($time1, $time2, "Второй рендеринг должен быть не медленнее первого");
        $this->assertStringContainsString('Test Button', $output1);
        $this->assertStringContainsString('Another Button', $output2);

        fwrite(
            STDERR,
            "✓ Кэширование метаданных: первый = ".round($time1 * 1000, 3).
            " ms, второй = ".round($time2 * 1000, 3)." ms\n",
        );
    }

    public function testAssetRenderingPerformance(): void
    {
        // Создаем много компонентов
        for ($i = 0; $i < 50; $i++) {
            new Button("Button $i");
            new PrimaryButton("Primary $i");
            new Card("Card $i", "Content $i");
        }

        // Рендерим ассеты
        $start = microtime(true);
        $assets = BrickManager::getInstance()->renderAssets();
        $time = microtime(true) - $start;

        $this->assertStringContainsString('<style>', $assets);
        $this->assertStringContainsString('<script>', $assets);
        $this->assertLessThan(0.1, $time, "Рендеринг ассетов для 150 компонентов должен занимать < 0.1 секунды");

        fwrite(STDERR, "✓ Рендеринг ассетов (150 компонентов): ".round($time * 1000, 2)." ms\n");
    }

    public function testStringCastVsRenderMethod(): void
    {
        $button = new Button('Test');

        // Тестируем __toString
        $start1 = microtime(true);
        $stringResult = (string)$button;
        $time1 = microtime(true) - $start1;

        // Тестируем render()
        $start2 = microtime(true);
        $renderResult = $button->render();
        $time2 = microtime(true) - $start2;

        $this->assertEquals($stringResult, $renderResult);

        // Оба метода должны быть быстрыми
        $this->assertLessThan(0.001, $time1, "__toString должен быть быстрым");
        $this->assertLessThan(0.001, $time2, "render() должен быть быстрым");

        fwrite(
            STDERR,
            "✓ Сравнение __toString и render(): ".
            round($time1 * 1000000, 2)." µs vs ".
            round($time2 * 1000000, 2)." µs\n",
        );
    }
}