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
final class Has implements Transformation
{
    /**
     * @var callable
     * @psalm-var callable(TKey, T):(bool)
     */
    private $callback;

    /**
     * @psalm-param callable(TKey, T):(bool) $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Iterator<TKey, T> $collection
     *
     * @return bool
     */
    public function __invoke(Iterator $collection)
    {
        $callback = $this->callback;

        foreach ($collection as $key => $value) {
            if ($callback($key, $value) === $value) {
                return true;
            }
        }

        return false;
    }
}
