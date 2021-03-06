<?php

declare(strict_types=1);

namespace loophp\collection\Transformation;

use Iterator;
use loophp\collection\Contract\Transformation;

/**
 * @psalm-template TKey
 * @psalm-template TKey of array-key
 * @psalm-template T
 *
 * @implements Transformation<TKey, T>
 */
final class All implements Transformation
{
    /**
     * @return array<TKey, T>
     * @phpstan-return array<TKey, T>
     * @psalm-return array<TKey, T>
     */
    public function __invoke(Iterator $collection): array
    {
        return iterator_to_array($collection);
    }
}
