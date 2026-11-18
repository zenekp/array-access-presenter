<?php

namespace Zenekp\ArrayAccessPresenter;

use ArrayAccess;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Zenekp\ArrayAccessPresenter\Interfaces\PresenterInterface;

abstract class AbstractPresenter
{
    /**
     * @param  array<array-key, mixed>|ArrayAccess<array-key, mixed>  $data
     */
    public function __construct(protected array|ArrayAccess $data) {}

    /**
     * Resolve presenter methods from their corresponding data keys.
     */
    public function __call(string $method, array $arguments): mixed
    {
        $key = self::snakeCase($method);

        if ($this->hasKey($key)) {
            return $this->getValue($key);
        }

        if (str_starts_with($key, 'has_')) {
            $attribute = substr($key, 4);

            return $this->hasKey($attribute) && ! empty($this->getValue($attribute));
        }

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $location = isset($caller['file'], $caller['line'])
            ? sprintf(' in %s at line %d', $caller['file'], $caller['line'])
            : '';

        throw new RuntimeException(sprintf(
            'Call to %s::%s() failed: data key "%s" is not set%s.',
            static::class,
            $method,
            $key,
            $location,
        ));
    }

    /**
     * @return array<array-key, mixed>|ArrayAccess<array-key, mixed>
     */
    public function getData(): array|ArrayAccess
    {
        return $this->data;
    }

    /**
     * @param  class-string<AbstractPresenter>  $presenterClass
     * @throws ReflectionException
     */
    protected function setRelationPresenter(string $relation, string $presenterClass): void
    {
        if (! empty($this->$relation)) {
            return;
        }

        $this->validatePresenterClass($presenterClass);

        $relationData = $this->relationData($relation);
        if ($this->isArrayable($relationData)) {
            $presenter = new $presenterClass($relationData);
            $this->$relation = $presenter;
        }
    }

    /**
     * @param  class-string<AbstractPresenter>  $presenterClass
     * @throws ReflectionException
     */
    protected function setCollectionRelationPresenters(string $relation, string $presenterClass): void
    {
        if (! empty($this->$relation)) {
            return;
        }

        $this->validatePresenterClass($presenterClass);

        $this->$relation = [];
        $collection = $this->relationData($relation);

        if (! $this->isArrayable($collection)) {
            return;
        }

        foreach ($collection as $item) {
            if (! $this->isArrayable($item)) {
                continue;
            }
            if (is_array($item) && array_values($item) === $item) {
                continue;
            }
            $presenter = new $presenterClass($item);
            $this->{$relation}[] = $presenter;
        }
    }

    /**
     * @throws ReflectionException
     */
    private function validatePresenterClass(string $presenterClass): void
    {
        if (
            ! is_a($presenterClass, AbstractPresenter::class, true)
            || ! is_a($presenterClass, PresenterInterface::class, true)
        ) {
            throw new InvalidArgumentException(sprintf(
                'Presenter class "%s" must be concrete and implement "%s".',
                $presenterClass,
                PresenterInterface::class
            ));
        }

        $reflection = new ReflectionClass($presenterClass);
        if (! $reflection->isInstantiable()) {
            throw new InvalidArgumentException(sprintf(
                'Presenter class "%s" must be concrete and implement "%s".',
                $presenterClass,
                PresenterInterface::class
            ));
        }
    }

    private function relationData(string $relation): mixed
    {
        foreach (array_unique([$relation, self::snakeCase($relation)]) as $key) {
            if ($this->hasKey($key)) {
                return $this->getValue($key);
            }
        }

        return null;
    }

    private function isArrayable(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    private function hasKey(string $key): bool
    {
        return is_array($this->data)
            ? array_key_exists($key, $this->data)
            : $this->data->offsetExists($key);
    }

    private function getValue(string $key): mixed
    {
        return is_array($this->data)
            ? $this->data[$key]
            : $this->data->offsetGet($key);
    }

    private static function snakeCase(string $value): string
    {
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $value);
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value);

        return strtolower($value);
    }
}
