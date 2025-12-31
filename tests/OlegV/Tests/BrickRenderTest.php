<?php

namespace OlegV\Tests;

use OlegV\BrickManager;
use OlegV\Tests\Components\Button\Button;
use PHPUnit\Framework\TestCase;

class BrickRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BrickManager::getInstance()->clear();
        BrickManager::disableDebug();
    }

    protected function tearDown(): void
    {
        BrickManager::getInstance()->clear();
        parent::tearDown();
    }

    public function testRenderReturnsHtmlWhenSuccessful(): void
    {
        $button = new Button('Test Button');
        $html = $button->render();

        $this->assertStringContainsString('button', $html);
        $this->assertStringContainsString('Test Button', $html);
        $this->assertStringContainsString('btn-primary', $html);
    }

    public function testRenderWithDifferentVariants(): void
    {
        $primary = new Button('Primary', 'primary');
        $secondary = new Button('Secondary', 'secondary');

        $this->assertStringContainsString('btn-primary', $primary->render());
        $this->assertStringContainsString('btn-secondary', $secondary->render());
    }

    public function testRenderDisabledButton(): void
    {
        $button = new Button('Disabled', 'primary', true);
        $html = $button->render();

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('class="', $html);
    }

    public function testToStringNeverThrowsExceptions(): void
    {
        $button = new Button('Test');
        $string = (string)$button;

        $this->assertIsString($string);
        $this->assertNotEmpty($string);
    }
}