<?php

declare(strict_types=1);

namespace Mezzio\CorsTest;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function is_object;
use function is_string;

abstract class AbstractFactoryTest extends TestCase
{
    /**
     * @psalm-var callable(ContainerInterface $container):object
     */
    protected $factory;

    /**
     * @psalm-var MockObject&ContainerInterface
     */
    private $container;

    /**
     * @return array<string|class-string,class-string|array<string,mixed>>
     */
    abstract protected function dependencies(): array;

    /**
     * @psalm-return callable(ContainerInterface $container):object
     */
    abstract protected function factory(): callable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory   = $this->factory();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->setupContainer($this->container);
    }

    /**
     * @psalm-param ContainerInterface&MockObject $container
     */
    private function setupContainer(MockObject $container): void
    {
        $dependencies = $this->dependencies();
        if (! $dependencies) {
            return;
        }

        $consecutiveParameters = $consecutiveReturnValues = [];
        foreach ($dependencies as $dependency => $definition) {
            $consecutiveParameters[]   = [$dependency];
            $consecutiveReturnValues[] = $this->createReturnValueFromDefinition($definition);
        }

        $container
            ->expects($this->any())
            ->method('get')
            ->withConsecutive(...$consecutiveParameters)
            ->willReturnOnConsecutiveCalls(...$consecutiveReturnValues);
    }

    public function testInstantiation(): void
    {
        $factory  = $this->factory;
        $instance = $factory($this->container);
        $this->postCreationAssertions($instance);
    }

    /**
     * @psalm-param class-string|array<string,mixed>|object $definition
     * @psalm-return array<mixed>|object
     */
    private function createReturnValueFromDefinition($definition)
    {
        if (is_string($definition)) {
            return $this->createMock($definition);
        }

        if (is_object($definition)) {
            return $definition;
        }

        return $definition;
    }

    /**
     * Implement this for post creation assertions.
     *
     * @param mixed $instance
     */
    abstract protected function postCreationAssertions($instance): void;
}
