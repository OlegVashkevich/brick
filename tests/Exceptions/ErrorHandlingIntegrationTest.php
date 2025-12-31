<?php

declare(strict_types=1);

namespace OlegV\Tests\Exceptions;

use OlegV\BrickManager;
use OlegV\Tests\Components\Button\Button;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ErrorHandlingIntegrationTest extends TestCase
{
    public function testCacheReuseAfterTemplateFix(): void
    {
// Тест на повторное использование кэша после исправления шаблона
        $reflection = new ReflectionClass(Button::class);
        $dir = dirname((string)$reflection->getFileName());
        $templatePath = $dir.'/template.php';
        $backupPath = $dir.'/template_backup.php';

        copy($templatePath, $backupPath);

        try {
// 1. Создаем битый шаблон
            file_put_contents($templatePath, '<?php throw new \Exception("Broken"); ?>');
            BrickManager::getInstance()->clear();

            $button1 = new Button('Test');
            $html1 = $button1->render(); // Ошибка

// 2. Исправляем шаблон
            copy($backupPath, $templatePath);
            BrickManager::getInstance()->clear(); // Очищаем кэш

            $button2 = new Button('Fixed');
            $html2 = $button2->render(); // Должен работать

            $this->assertStringContainsString('Fixed', $html2);
            $this->assertStringNotContainsString('Broken', $html2);
        } finally {
            if (file_exists($backupPath)) {
                copy($backupPath, $templatePath);
                unlink($backupPath);
            }
            BrickManager::getInstance()->clear();
        }
    }
}