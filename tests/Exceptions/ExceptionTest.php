<?php

declare(strict_types=1);

namespace OlegV\Tests\Exceptions;

use OlegV\BrickManager;
use OlegV\Exceptions\ComponentNotFoundException;
use OlegV\Exceptions\RenderException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BrickManager::disableDebug();
    }

    public function testComponentNotFoundExceptionProduction(): void
    {
        $exception = new ComponentNotFoundException('Button component');
        $html = $exception->toHtml();

        $this->assertStringContainsString('<!-- Brick component not found -->', $html);
    }

    public function testComponentNotFoundExceptionDebug(): void
    {
        BrickManager::enableDebug();

        $exception = new ComponentNotFoundException('Button component');
        $html = $exception->toHtml();

        $this->assertStringContainsString('Brick Component Not Found', $html);
        $this->assertStringContainsString('Button component', $html);

        BrickManager::disableDebug();
    }

    public function testRenderExceptionProduction(): void
    {
        $exception = new RenderException('Render failed');
        $html = $exception->toHtml();

        $this->assertStringContainsString('<!-- Brick render error -->', $html);
    }

    public function testRenderExceptionDebug(): void
    {
        BrickManager::enableDebug();

        $exception = new RenderException('Template syntax error');
        $html = $exception->toHtml();

        $this->assertStringContainsString('Brick Render Error', $html);
        $this->assertStringContainsString('Template syntax error', $html);

        BrickManager::disableDebug();
    }
}