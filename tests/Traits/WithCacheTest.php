<?php

namespace OlegV\Tests\Traits;

use JsonException;
use OlegV\Brick;
use OlegV\Tests\Components\CachedButton;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

require_once __DIR__ . '/../Components/CachedButton/CachedButton.php';
class WithCacheTest extends TestCase
{
    //private $root;
    private CacheInterface $cacheMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Мок кэша
        $this->cacheMock = $this->createMock(CacheInterface::class);

        // Очищаем Brick
        Brick::clear();
    }

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function testCachedButtonRendersWithCache(): void
    {
        // Устанавливаем кэш
        CachedButton::setCache($this->cacheMock);

        // Настраиваем мок
        $this->cacheMock->method('get')
            ->willReturn('<button class="btn btn-primary">Cached</button>');

        // Создаем компонент
        $button = new CachedButton('Test Button');
        $result = $button->render();

        $this->assertEquals('<button class="btn btn-primary">Cached</button>', $result);

        // Проверяем что CSS все равно регистрируется
        $css = Brick::renderCss();
        $this->assertStringContainsString('.btn { display: inline-block; }', $css);
    }

    /**
     * @throws ReflectionException
     */
    public function testRenderOriginalMethod(): void
    {
        // Не устанавливаем кэш, чтобы проверить оригинальный рендеринг
        $button = new CachedButton('Test Button', 'success');

        // Используем Reflection для вызова защищенного метода renderOriginal
        $reflection = new ReflectionClass($button);
        $method = $reflection->getMethod('renderOriginal');

        $result = $method->invoke($button);

        $this->assertStringContainsString('<button', $result);
        $this->assertStringContainsString('class="btn btn-success"', $result);
        $this->assertStringContainsString('>Test Button<', $result);
    }

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function testCacheMissCallsRenderOriginal(): void
    {
        // Устанавливаем кэш
        CachedButton::setCache($this->cacheMock);

        // Настраиваем мок для кэш-промаха
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->willReturn(null); // Кэш-промах

        // Проверяем что результат render() вызывает оригинальный рендеринг
        $button = new CachedButton('Cache Miss Test', 'danger');
        $result = $button->render();

        $this->assertStringContainsString('<button', $result);
        $this->assertStringContainsString('class="btn btn-danger"', $result);
        $this->assertStringContainsString('>Cache Miss Test<', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetCacheHashMethod(): void
    {
        $button = new CachedButton('Test', 'warning', true);

        // Используем Reflection для вызова защищенного метода getCacheHash
        $reflection = new ReflectionClass($button);
        $method = $reflection->getMethod('getCacheHash');

        $hash = $method->invoke($button);

        // Проверяем что хэш - это MD5 строка
        $this->assertIsString($hash);
        $this->assertEquals(32, strlen($hash)); // Длина MD5 хэша
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $hash);
    }

    /**
     * @throws ReflectionException
     */
    public function testCacheKeyChangesWithProperties(): void
    {
        $button1 = new CachedButton('Button 1', 'primary');
        $button2 = new CachedButton('Button 2', 'secondary');
        $button3 = new CachedButton('Button 1', 'primary'); // Те же свойства что и button1

        $reflection = new ReflectionClass($button1);
        $method = $reflection->getMethod('getCacheHash');

        $hash1 = $method->invoke($button1);
        $hash2 = $method->invoke($button2);
        $hash3 = $method->invoke($button3);

        // Разные свойства - разные хэши
        $this->assertNotEquals($hash1, $hash2);

        // Одинаковые свойства - одинаковые хэши
        $this->assertEquals($hash1, $hash3);
    }
}