<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for the relational cache index. Routes every query to the
 * connection configured under `adr-manager.database.connection` (falling back
 * to the application default when unset).
 */
abstract class AdrModel extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = [];

    public function getConnectionName(): ?string
    {
        $connection = config('adr-manager.database.connection');

        return is_string($connection) ? $connection : parent::getConnectionName();
    }
}
