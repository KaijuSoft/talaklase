<?php

declare(strict_types=1);

namespace Tala\Engine\Enums;

/**
 * TALA Engine
 * Operation Types
 *
 * Defines every operation that can be executed by the SchemaExecutor.
 *
 * @since RC3.0.0
 */
enum OperationType: string
{
    /*
    |--------------------------------------------------------------------------
    | Table Operations
    |--------------------------------------------------------------------------
    */

    case CREATE_TABLE = 'create_table';
    case DROP_TABLE   = 'drop_table';

    /*
    |--------------------------------------------------------------------------
    | Column Operations
    |--------------------------------------------------------------------------
    */

    case ADD_COLUMN    = 'add_column';
    case MODIFY_COLUMN = 'modify_column';
    case DROP_COLUMN   = 'drop_column';

    /*
    |--------------------------------------------------------------------------
    | Index Operations
    |--------------------------------------------------------------------------
    */

    case CREATE_INDEX = 'create_index';
    case DROP_INDEX   = 'drop_index';

    /*
    |--------------------------------------------------------------------------
    | Foreign Key Operations
    |--------------------------------------------------------------------------
    */

    case CREATE_FOREIGN_KEY = 'create_foreign_key';
    case DROP_FOREIGN_KEY   = 'drop_foreign_key';
}