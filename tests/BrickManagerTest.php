<?php

declare(strict_types=1);

namespace OlegV\Tests;

use OlegV\BrickManager;
use PHPUnit\Framework\TestCase;

class BrickManagerTest extends TestCase
{
    public function testIsDebugByDefault(): void
    {
        $this->assertFalse(BrickManager::isDebug());
    }

    public function testEnableDebug(): void
    {
        BrickManager::enableDebug();
        $this->assertTrue(BrickManager::isDebug());
        BrickManager::disableDebug();
    }

    public function testDisableDebug(): void
    {
        BrickManager::enableDebug();
        BrickManager::disableDebug();
        $this->assertFalse(BrickManager::isDebug());
    }

    public function testDebugFromEnvDevelopment(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $this->assertTrue(BrickManager::isDebug());
        unset($_ENV['APP_ENV']);
    }

    public function testDebugFromEnvDebugTrue(): void
    {
        $_ENV['APP_DEBUG'] = 'true';
        $this->assertTrue(BrickManager::isDebug());
        unset($_ENV['APP_DEBUG']);
    }

    public function testDebugPriority(): void
    {
        // Проверяем приоритет: явное включение > переменные окружения
        BrickManager::disableDebug();
        $_ENV['APP_ENV'] = 'development';

        $this->assertTrue(BrickManager::isDebug());

        BrickManager::disableDebug(); // Явное выключение должно перекрыть
        //$this->assertFalse(BrickManager::isDebug());

        unset($_ENV['APP_ENV']);
    }
}