<?php

namespace OlegV\Tests\Traits;

use JsonException;
use OlegV\BrickManager;
use OlegV\Tests\Components\CachedButton;
use OlegV\Tests\Components\CachedButtonTtl;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

require_once __DIR__ . '/../Components/CachedButton/CachedButton.php';
require_once __DIR__ . '/../Components/CachedButtonTtl/CachedButtonTtl.php';
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
        BrickManager::getInstance()->clear();
    }

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function testCachedButtonRendersWithCache(): void
    {
        // Устанавливаем кэш
        BrickManager::getInstance()->setCache($this->cacheMock);

        // Настраиваем мок
        $this->cacheMock->method('get')
            ->willReturn('<button class="btn btn-primary">Cached</button>');

        // Создаем компонент
        $button = new CachedButton('Test Button');
        $result = $button->render();

        $this->assertEquals('<button class="btn btn-primary">Cached</button>', $result);

        // Проверяем что CSS все равно регистрируется
        $css = BrickManager::getInstance()->renderCss();
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
        BrickManager::getInstance()->setCache($this->cacheMock);

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
    /**
     * @throws ReflectionException
     */
    public function testGetTtlReturnsExplicitValueFromComponent(): void
    {
        // Создаем компонент с явным TTL (600 из конструктора CachedButtonTtl)
        $button = new CachedButtonTtl('Test Button');

        // Используем Reflection для вызова защищенного метода getTtl
        $reflection = new ReflectionClass($button);
        $method = $reflection->getMethod('getTtl');

        $ttl = $method->invoke($button);

        // Проверяем что возвращается значение из конструктора CachedButtonTtl (600)
        $this->assertEquals(600, $ttl);
        // Проверяем что это НЕ значение по умолчанию из BrickManager
        $this->assertNotEquals(BrickManager::$cacheTtl, $ttl);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetTtlReturnsDefaultWhenNotSet(): void
    {
        // CachedButton не устанавливает ttl в конструкторе
        $button = new CachedButton('Test Button');

        // Используем Reflection для вызова защищенного метода getTtl
        $reflection = new ReflectionClass($button);
        $method = $reflection->getMethod('getTtl');

        $ttl = $method->invoke($button);

        // Проверяем что возвращается значение по умолчанию из BrickManager
        $this->assertEquals(BrickManager::$cacheTtl, $ttl);
    }

    public function testTtlPropertyIsReadonly(): void
    {
        // Проверяем что свойство ttl действительно readonly
        $button1 = new CachedButtonTtl('Button 1');
        $button2 = new CachedButton('Button 2');

        $reflection1 = new ReflectionClass($button1);
        $property1 = $reflection1->getProperty('ttl');

        $reflection2 = new ReflectionClass($button2);
        $property2 = $reflection2->getProperty('ttl');

        $this->assertTrue($property1->isReadOnly());
        $this->assertTrue($property2->isReadOnly());
    }

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function testCacheSetCalledWithExplicitTtl(): void
    {
        // Устанавливаем кэш
        BrickManager::setCache($this->cacheMock);

        // Настраиваем мок для кэш-промаха и проверяем что set вызывается с правильным TTL
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('CachedButtonTtl'), // ключ
                $this->stringContains('<button'), // HTML результат рендеринга (второй аргумент!)
                $this->equalTo(600) // TTL из CachedButtonTtl
            )
            ->willReturn(true);

        // Создаем и рендерим компонент
        $button = new CachedButtonTtl('Test Button');
        $button->render();

    }

    /**
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function testCacheSetCalledWithDefaultTtl(): void
    {
        // Устанавливаем кэш
        BrickManager::setCache($this->cacheMock);

        // Настраиваем мок для кэш-промаха
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('CachedButton'), // ключ
                $this->stringContains('<button'), // HTML результат рендеринга (второй аргумент!)
                $this->equalTo(BrickManager::$cacheTtl) // Дефолтный TTL из BrickManager
            )
            ->willReturn(true);

        // Создаем и рендерим компонент (CachedButton не устанавливает ttl)
        $button = new CachedButton('Test Button');
        $button->render();
    }

    /**
     * @throws ReflectionException
     */
    public function testDifferentComponentsCanHaveDifferentTtl(): void
    {
        $button1 = new CachedButton('Button 1'); // Использует дефолтный TTL
        $button2 = new CachedButtonTtl('Button 2'); // Использует явный TTL = 600

        $reflection1 = new ReflectionClass($button1);
        $method1 = $reflection1->getMethod('getTtl');
        $ttl1 = $method1->invoke($button1);

        $reflection2 = new ReflectionClass($button2);
        $method2 = $reflection2->getMethod('getTtl');
        $ttl2 = $method2->invoke($button2);

        $this->assertEquals(BrickManager::$cacheTtl, $ttl1);
        $this->assertEquals(600, $ttl2);
        $this->assertNotEquals($ttl1, $ttl2);
    }

    /**
     * @throws ReflectionException
     */
    public function testTtlPropertyValueIsCorrect(): void
    {
        // Проверяем через рефлексию, что свойство ttl содержит правильные значения
        $button1 = new CachedButton('Button 1');
        $button2 = new CachedButtonTtl('Button 2');

        $reflection1 = new ReflectionClass($button1);
        $property1 = $reflection1->getProperty('ttl');

        $reflection2 = new ReflectionClass($button2);
        $property2 = $reflection2->getProperty('ttl');

        // Для CachedButtonTtl: свойство инициализировано как 600
        $this->assertEquals(600, $property2->getValue($button2));

        // Для CachedButton: проверяем через getTtl() что используется дефолтное значение
        $method1 = $reflection1->getMethod('getTtl');
        $ttl1 = $method1->invoke($button1);
        $this->assertEquals(BrickManager::$cacheTtl, $ttl1);
    }
}