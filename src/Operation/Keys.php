<?php

declare(strict_types=1);

namespace drupol\collection\Operation;

/**
 * Class Keys.
 */
final class Keys extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function on(iterable $collection): \Closure
    {
        return static function () use ($collection): \Generator {
            foreach ($collection as $key => $value) {
                yield $key;
            }
        };
    }
}
