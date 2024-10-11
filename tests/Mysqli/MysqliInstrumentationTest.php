<?php

declare(strict_types=1);

namespace Shin1x1\Tests\OpenTelemetry\Auto\Db\Mysqli;

use ArrayObject;
use mysqli;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\TraceAttributes;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MysqliInstrumentationTest extends TestCase
{
    /** @var ArrayObject<int, SpanDataInterface> */
    private ArrayObject $storage;
    private ScopeInterface $scope;

    #[Test]
    public function mysqi_construct(): void
    {
        // Arrange & Act
        self::createMysqli();

        // Assert
        $span = $this->storage[0] ?? null;
        $this->assertInstanceOf(SpanDataInterface::class, $span);
        $this->assertSame('mysqli::__construct', $span->getName());
    }

    #[Test]
    public function mysqi_connect(): void
    {
        // Arrange & Act
        mysqli_connect($this->getDBHost(), $this->getDBUser(), $this->getDBPass(), $this->getDBName());

        // Assert
        $span = $this->storage[0] ?? null;
        $this->assertInstanceOf(SpanDataInterface::class, $span);

        $this->assertSame('mysqli_connect', $span->getName());
    }

    #[Test]
    public function mysqli_method_query(): void
    {
        // Arrange
        $mysqli = self::createMysqli();
        $sql = 'SELECT 1';

        // Act
        $mysqli->query($sql);

        // Assert
        $span = $this->storage[1] ?? null;
        $this->assertInstanceOf(SpanDataInterface::class, $span);

        $this->assertSame('mysqli::query ' . $sql, $span->getName());
        $this->assertSame($sql, $span->getAttributes()->get(TraceAttributes::DB_STATEMENT));
    }

    #[Test]
    public function mysqli_query(): void
    {
        // Arrange
        $mysqli = self::createMysqli();
        $sql = 'SELECT 1';

        // Act
        mysqli_query($mysqli, $sql);

        // Assert
        $span = $this->storage[1] ?? null;
        $this->assertInstanceOf(SpanDataInterface::class, $span);

        $this->assertSame('mysqli_query ' . $sql, $span->getName());
        $this->assertSame($sql, $span->getAttributes()->get(TraceAttributes::DB_STATEMENT));
    }

    #[Test]
    public function mysqli_method_prepare_and_execute(): void
    {
        // Arrange
        $mysqli = self::createMysqli();
        $mysqli->query($this->getFixtureSQL());
        $sql = 'SELECT * FROM users WHERE name = ?';

        // Act
        $stmt = $mysqli->prepare($sql) ?: throw new \RuntimeException('Failed to prepare');
        $stmt->execute(['Alice']);

        // Assert
        $span = $this->storage[2] ?? null;
        $this->assertInstanceOf(SpanDataInterface::class, $span);

        $this->assertSame('mysqli_stmt::execute SELECT * FROM users ...', $span->getName());
        $this->assertSame($sql . ' ' . json_encode(['Alice']), $span->getAttributes()->get(TraceAttributes::DB_STATEMENT));
    }

    #[Test]
    public function mysqli_prepare_and_execute(): void
    {
        // Arrange
        $mysqli = self::createMysqli();
        $mysqli->query($this->getFixtureSQL());
        $sql = 'SELECT * FROM users WHERE name = ?';

        // Act
        $stmt = mysqli_prepare($mysqli, $sql) ?: throw new \RuntimeException('Failed to prepare');
        $stmt->execute(['Alice']);

        // Assert
        $span = $this->storage[2] ?? null;
        $this->assertInstanceOf(SpanDataInterface::class, $span);

        $this->assertSame('mysqli_stmt::execute SELECT * FROM users ...', $span->getName());
        $this->assertSame($sql . ' ' . json_encode(['Alice']), $span->getAttributes()->get(TraceAttributes::DB_STATEMENT));
    }

    public function setUp(): void
    {
        $this->storage = new ArrayObject();
        $tracerProvider = new TracerProvider(
            new SimpleSpanProcessor(
                new InMemoryExporter($this->storage),
            ),
        );

        $this->scope = Configurator::create()
            ->withTracerProvider($tracerProvider)
            ->activate();
    }

    public function tearDown(): void
    {
        $this->createMysqli()->query('DROP TABLE IF EXISTS users');
        $this->scope->detach();
    }

    private function getDBHost(): string
    {
        return getenv('DB_HOST') ?: 'localhost';
    }

    private function getDBUser(): string
    {
        return getenv('DB_USER') ?: 'user';
    }

    private function getDBPass(): string
    {
        return getenv('DB_PASS') ?: 'pass';
    }

    private function getDBName(): string
    {
        return getenv('DB_NAME') ?: 'app';
    }

    private function createMysqli(): mysqli
    {
        return new mysqli(
            $this->getDBHost(),
            $this->getDBUser(),
            $this->getDBPass(),
            $this->getDBName(),
        );
    }

    private function getFixtureSQL(): string
    {
        return <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            points INT NOT NULL
        );

        SQL;
    }
}
