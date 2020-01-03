<?php

declare(strict_types=1);

namespace loophp\collection\Operation;

use Closure;
use Generator;
use loophp\collection\Contract\Operation;

use function in_array;

/**
 * Class Distinct.
 */
final class Distinct implements Operation
{
    /**
     * {@inheritdoc}
     */
    public function on(iterable $collection): Closure
    {
        return static function () use ($collection): Generator {
            $seen = [];

            foreach ($collection as $key => $value) {
                if (true === in_array($value, $seen, true)) {
                    continue;
                }

                $seen[] = $value;

                yield $key => $value;
            }
        };
    }
}
