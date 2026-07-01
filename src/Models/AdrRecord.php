<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Cached, queryable projection of a single ADR file.
 *
 * @property string $id
 * @property int $sequence_number
 * @property string $title
 * @property string $status
 * @property string|null $author
 * @property array<string, mixed> $metadata
 * @property string|null $content_summary
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class AdrRecord extends AdrModel
{
    public $incrementing = false;

    protected $table = 'adr_records';

    protected $keyType = 'string';

    /**
     * @return HasMany<AdrRelation, $this>
     */
    public function relations(): HasMany
    {
        return $this->hasMany(AdrRelation::class, 'parent_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
