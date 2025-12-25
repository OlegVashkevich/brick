<?php
namespace OlegV\Tests\Traits;

use OlegV\Brick;
use OlegV\Tests\Components;
use PHPUnit\Framework\TestCase;
use RuntimeException;

// Подключаем реальные компоненты
require_once __DIR__ . '/../Components/Button/Button.php';
require_once __DIR__ . '/../Components/PrimaryButton/PrimaryButton.php';
require_once __DIR__ . '/../Components/Card/Card.php';
require_once __DIR__ . '/../Components/InvalidComponent/InvalidComponent.php';

class WithInheritanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Brick::clear();
    }

    protected function tearDown(): void
    {
        Brick::clear();
        parent::tearDown();
    }

    public function testButtonComponentRendersCorrectly(): void
    {
        $button = new Components\Button('Click me', 'primary');
        $result = $button->render();

        $this->assertStringContainsString('class="btn btn-primary"', $result);
        $this->assertStringContainsString('>Click me<', $result);
        $this->assertStringNotContainsString('disabled', $result);
    }

    public function testButtonWithDisabledState(): void
    {
        $button = new Components\Button('Submit', 'secondary', true);
        $result = $button->render();

        $this->assertStringContainsString('class="btn btn-secondary disabled"', $result);
        $this->assertStringContainsString('disabled', $result);
    }

    public function testPrimaryButtonInheritsFromButton(): void
    {
        $button = new Components\PrimaryButton('Special Button');
        $result = $button->render();

        // Должен использовать шаблон от Button
        $this->assertStringContainsString('class="btn btn-primary"', $result);
        $this->assertStringContainsString('>Special Button<', $result);
    }

    public function testPrimaryButtonInheritsFromButton2(): void
    {
        $button = new Components\PrimaryButton('Special Button');
        $result = $button->render();
        $button2 = new Components\PrimaryButton('Special Button2');
        $result2 = $button2->render();

        // Должен использовать шаблон от Button
        $this->assertStringContainsString('class="btn btn-primary"', $result);
        $this->assertStringContainsString('>Special Button<', $result);

        // Должен использовать шаблон от Button
        $this->assertStringContainsString('class="btn btn-primary"', $result2);
        $this->assertStringContainsString('>Special Button2<', $result2);
    }

    public function testCssInheritanceAndOverride(): void
    {
        // Создаем оба компонента для регистрации CSS
        $button = new Components\Button();
        $primaryButton = new Components\PrimaryButton();

        $css = Brick::renderCss();

        // Проверяем что есть CSS от Button
        $this->assertStringContainsString('.btn {', $css);
        $this->assertStringContainsString('.btn-primary {', $css);
        $this->assertStringContainsString('background-color: #007bff', $css);

        // Проверяем переопределение от PrimaryButton
        $this->assertStringContainsString('background-color: #28a745', $css);
        $this->assertStringContainsString('font-weight: bold', $css);
        $this->assertStringContainsString('box-shadow: 0 2px 4px', $css);

        // Проверяем порядок (Button должен быть перед PrimaryButton)
        $buttonPos = strpos($css, '.btn {');
        $primaryOverridePos = strpos($css, 'background-color: #28a745');

        $this->assertLessThan($primaryOverridePos, $buttonPos,
            'Базовые стили Button должны быть перед переопределениями PrimaryButton');
    }

    public function testJsInheritance(): void
    {
        $button = new Components\Button();
        $js = Brick::renderJs();

        $this->assertStringContainsString('Button clicked:', $js);
        $this->assertStringContainsString('DOMContentLoaded', $js);
    }

    public function testCardComponent(): void
    {
        $card = new Components\Card(
            'Test Title',
            '<p>Test content with <strong>HTML</strong></p>',
            'Footer text'
        );

        $result = $card->render();

        $this->assertStringContainsString('card-header', $result);
        $this->assertStringContainsString('>Test Title<', $result);
        $this->assertStringContainsString('<p>Test content with <strong>HTML</strong></p>', $result);
        $this->assertStringContainsString('card-footer', $result);
        $this->assertStringContainsString('>Footer text<', $result);
    }

    public function testCardWithoutOptionalParts(): void
    {
        $card = new Components\Card('', 'Just content');
        $result = $card->render();

        $this->assertStringNotContainsString('card-header', $result);
        $this->assertStringContainsString('Just content', $result);
        $this->assertStringNotContainsString('card-footer', $result);
    }

    public function testCardCssAssets(): void
    {
        $card = new Components\Card();
        $css = Brick::renderCss();

        $this->assertStringContainsString('.card {', $css);
        $this->assertStringContainsString('border-radius: 8px', $css);
        $this->assertStringContainsString('box-shadow: 0 2px 8px', $css);
    }

    public function testRenderAllAssets(): void
    {
        // Создаем все компоненты
        new Components\Button();
        new Components\PrimaryButton();
        new Components\Card();

        $assets = Brick::renderAssets();

        $this->assertStringContainsString('<style>', $assets);
        $this->assertStringContainsString('<script>', $assets);
        $this->assertStringContainsString('.btn {', $assets);
        $this->assertStringContainsString('.card {', $assets);
        $this->assertStringContainsString('DOMContentLoaded', $assets);
    }

    public function testComponentWithoutTemplateThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('template.php не найден');

        new Components\InvalidComponent();
    }
}