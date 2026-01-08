<?php

declare(strict_types=1);

namespace OlegV\Tests\Traits;

use OlegV\BrickManager;
use OlegV\Tests\Components\Button\Button;
use OlegV\Tests\Components\Card\Card;
use OlegV\Tests\Components\PrimaryButton\PrimaryButton;
use PHPUnit\Framework\TestCase;

// Подключаем реальные компоненты
require_once __DIR__.'/../Components/Button/Button.php';
require_once __DIR__.'/../Components/PrimaryButton/PrimaryButton.php';
require_once __DIR__.'/../Components/Card/Card.php';
require_once __DIR__.'/../Components/InvalidComponent/InvalidComponent.php';

class WithInheritanceTest extends TestCase
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

    public function testButtonComponentRendersCorrectly(): void
    {
        $button = new Button('Click me', 'primary');
        $result = $button->render();

        $this->assertStringContainsString('class="btn btn-primary"', $result);
        $this->assertStringContainsString('>Click me<', $result);
        $this->assertStringNotContainsString('disabled', $result);
    }

    public function testButtonWithDisabledState(): void
    {
        $button = new Button('Submit', 'secondary', true);
        $result = $button->render();

        $this->assertStringContainsString('class="btn btn-secondary disabled"', $result);
        $this->assertStringContainsString('disabled', $result);
    }

    public function testPrimaryButtonInheritsFromButton(): void
    {
        $button = new PrimaryButton('Special Button');
        $result = $button->render();

        // Должен использовать шаблон от Button
        $this->assertStringContainsString('class="btn btn-primary"', $result);
        $this->assertStringContainsString('>Special Button<', $result);
    }

    public function testPrimaryButtonInheritsFromButton2(): void
    {
        $button = new PrimaryButton('Special Button');
        $result = $button->render();
        $button2 = new PrimaryButton('Special Button2');
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
        echo new Button();
        echo new PrimaryButton();

        $css = BrickManager::getInstance()->renderCss();

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

        $this->assertLessThan(
            $primaryOverridePos,
            $buttonPos,
            'Базовые стили Button должны быть перед переопределениями PrimaryButton',
        );
    }

    public function testJsInheritance(): void
    {
        echo new Button();
        $js = BrickManager::getInstance()->renderJs();

        $this->assertStringContainsString('Button clicked:', $js);
        $this->assertStringContainsString('DOMContentLoaded', $js);
    }

    public function testCardComponent(): void
    {
        $card = new Card(
            'Test Title',
            '<p>Test content with <strong>HTML</strong></p>',
            'Footer text',
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
        $card = new Card('', 'Just content');
        $result = $card->render();

        $this->assertStringNotContainsString('card-header', $result);
        $this->assertStringContainsString('Just content', $result);
        $this->assertStringNotContainsString('card-footer', $result);
    }

    public function testCardCssAssets(): void
    {
        echo new Card();
        $css = BrickManager::getInstance()->renderCss();

        $this->assertStringContainsString('.card {', $css);
        $this->assertStringContainsString('border-radius: 8px', $css);
        $this->assertStringContainsString('box-shadow: 0 2px 8px', $css);
    }

    public function testRenderAllAssets(): void
    {
        // Создаем все компоненты
        echo new Button();
        echo new PrimaryButton();
        echo new Card();

        $assets = BrickManager::getInstance()->renderAssets();

        $this->assertStringContainsString('<style>', $assets);
        $this->assertStringContainsString('<script>', $assets);
        $this->assertStringContainsString('.btn {', $assets);
        $this->assertStringContainsString('.card {', $assets);
        $this->assertStringContainsString('DOMContentLoaded', $assets);
    }
}