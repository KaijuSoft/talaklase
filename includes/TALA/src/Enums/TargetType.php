<?php

declare(strict_types=1);

namespace Tala\Engine\Enums;

/**
 * Logical targets handled by the schema planning layer.
 */
enum TargetType: string
{
    case TABLE = 'table';
    case COLUMN = 'column';
    case INDEX = 'index';
    case FOREIGN_KEY = 'foreign_key';
}
