<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Models;

/**
 * A directed link between two records in the cache index.
 *
 * @property int $id
 * @property string $parent_id
 * @property string $child_id
 * @property string $relation_type
 */
final class AdrRelation extends AdrModel
{
    public const string SUPERSEDES = 'supersedes';

    public const string BACKLINKS = 'backlinks';

    protected $table = 'adr_relations';
}
