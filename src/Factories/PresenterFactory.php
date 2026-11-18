<?php

namespace Zenekp\ArrayAccessPresenter\Factories;

use ArrayAccess;
use InvalidArgumentException;
use Zenekp\ArrayAccessPresenter\AbstractPresenter;
use Zenekp\ArrayAccessPresenter\Interfaces\PresenterInterface;

class PresenterFactory
{
    public static function decorate(array|ArrayAccess $data, string $presenterClass): PresenterInterface
    {
        if (!is_subclass_of($presenterClass, AbstractPresenter::class)) {
            throw new InvalidArgumentException("The presenter class must extend " . AbstractPresenter::class);
        }
        $presenter = new $presenterClass($data, $presenterClass);
        if (!$presenter instanceof PresenterInterface) {
            throw new InvalidArgumentException("The presenter class must implement " . PresenterInterface::class);
        }
        return $presenter;
    }

    public static function decorateAll(array|ArrayAccess $data, string $presenterClass): array|ArrayAccess
    {
        return array_map(function ($value) use ($presenterClass) {
            return self::decorate($value, $presenterClass);
        }, $data);
    }
}
