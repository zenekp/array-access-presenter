<?php

namespace Zenekp\ArrayAccessPresenter\Interfaces;

use ArrayAccess;

interface PresenterInterface
{
    public function __construct(array|ArrayAccess $data);

    public function getData(): array|ArrayAccess;
}
