Examples
========

Manipulate keys and values
--------------------------

This example show the power of a lazy library and highlight also how to use
it in a wrong way.

Unlike regular PHP arrays where there can only be one key of type int or
string, a lazy library can have multiple times the same keys and they can
be of any type !

.. code-block:: bash

    // This following example is perfectly valid, despite that having array for keys
    // in a regular PHP arrays is impossible.
    $input = static function () {
        yield ['a'] => 'a';
        yield ['b'] => 'b';
        yield ['c'] => 'c';
    };
    Collection::fromIterable($input());

A lazy collection library can also have multiple times the same key.

Here we are going to make a frequency analysis on the text and see the
result. We can see that some data are missing, why ?

.. code-block:: bash

    $string = 'aaaaabbbbcccddddeeeee';

    $collection = Collection::with($string)
        // Run the frequency analysis tool.
        ->frequency()
        // Convert to regular array.
        ->all(); // [5 => 'e', 4 => 'd', 3 => 'c']

The reason that the frequency analysis for letters 'a' and 'b' are missing
is because when you call the method ->all(), the collection converts the
lazy collection into a regular PHP array, and PHP doesn't allow having
multiple time the same key, so it overrides the previous data and there are
missing information in the resulting array.

In order to circumvent this, you can either wrap the final result or
normalize it.
A better way would be to not convert this into an array and use the lazy
collection as an iterator.

Wrapping the result will wrap each result into a PHP array.
Normalizing the result will replace keys with a numerical index, but then
you might lose some information then.

It's up to you to decide which one you want to use.

.. code-block:: bash

    $collection = Collection::with($string)
        // Run the frequency analysis tool.
        ->frequency()
        // Wrap each result into an array.
        ->wrap()
        // Convert to regular array.
        ->all();
    /**
     * [
     *   [5 => 'a'],
     *   [4 => 'b'],
     *   [3 => 'c'],
     *   [4 => 'd'],
     *   [5 => 'e'],
     * ]
     */

Manipulate strings
------------------

.. code-block:: bash

    $string = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.
      Quisque feugiat tincidunt sodales.
      Donec ut laoreet lectus, quis mollis nisl.
      Aliquam maximus, orci vel placerat dapibus, libero erat aliquet nibh, nec imperdiet felis dui quis est.
      Vestibulum non ante sit amet neque tincidunt porta et sit amet neque.
      In a tempor ipsum. Duis scelerisque libero sit amet enim pretium pulvinar.
      Duis vitae lorem convallis, egestas mauris at, sollicitudin sem.
      Fusce molestie rutrum faucibus.';

    // By default will have the same behavior as str_split().
    Collection::with($string)
        ->explode(' ')
        ->count(); // 71

    // Or add a separator if needed, same behavior as explode().
    Collection::with($string, ',')
      ->count(); // 9

Random number generation
------------------------

.. code-block:: bash

    // Generate 300 distinct random numbers between 0 and 1000
    $random = static function() {
        return mt_rand() / mt_getrandmax();
    };

    $random_numbers = Collection::iterate($random)
        ->map(
            static function ($value) {
                return floor($value * 1000) + 1;
            }
        )
        ->distinct()
        ->limit(300)
        ->normalize()
        ->all();

Approximate the number e
------------------------

.. code-block:: bash

    <?php

    declare(strict_types=1);

    include 'vendor/autoload.php';

    use loophp\collection\Collection;

    $multiplication = static function ($value1, $value2) {
        return $value1 * $value2;
    };

    $addition = static function ($value1, $value2) {
        return $value1 + $value2;
    };

    $fact = static function (int $number) use ($multiplication) {
        return Collection::range(1, $number + 1)
            ->reduce(
                $multiplication,
                1
            );
    };

    $e = static function (int $value) use ($fact): float {
        return $value / $fact($value);
    };

    $number_e_approximation = Collection::times(INF, $e)
        ->until(static function (float $value): bool {return $value < 10 ** -12;})
        ->reduce($addition);

    var_dump($number_e_approximation); // 2.718281828459

Approximate the number Pi
-------------------------

.. code-block:: php

    <?php

    declare(strict_types=1);

    include 'vendor/autoload.php';

    use loophp\collection\Collection;

    $monteCarloMethod = static function ($in = 0, $total = 1) {
        $randomNumber1 = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
        $randomNumber2 = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();

        if (1 >= (($randomNumber1 ** 2) + ($randomNumber2 ** 2))) {
            ++$in;
        }

        return ['in' => $in, 'total' => ++$total];
    };

    $precision = new class() {

        /**
         * @var array
         */
        private $state;

        /**
         * @var float
         */
        private $precision;

        /**
         * @var int
         */
        private $row;

        /**
         * Precision constructor.
         *
         * @param float $precision
         * @param int $row
         */
        public function __construct(float $precision = 10 ** -5, int $row = 20)
        {
            $this->precision = $precision;
            $this->row = $row;
            $this->state = [
                'prev' => null,
                'found' => 0,
            ];
        }

        /**
         * @param float $value
         *
         * @return bool
         */
        public function __invoke(float $value): bool
        {
            if (null === $this->state['prev']) {
                $this->state['prev'] = $value;
                $this->state['found'] = 0;

                return false;
            }

            if ($value === $this->state['prev']) {
                $this->state['found'] = 0;

                return false;
            }

            if (abs($value - $this->state['prev']) <= $this->precision) {
                ++$this->state['found'];

                return false;
            }

            if ($this->state['found'] >= $this->row) {
                $this->state['found'] = 0;

                return true;
            }

            $this->state['prev'] = $value;
            $this->state['found'] = 0;

            return false;
        }
    };

    $pi_approximation = Collection::iterate($monteCarloMethod)
        ->map(
            static function ($value) {
                return 4 * $value['in'] / $value['total'];
            }
        )
        ->nth(50)
        ->until($precision)
        ->last();

    print_r($pi_approximation);


Find Prime numbers
------------------

.. code-block:: php

    <?php

    declare(strict_types=1);

    include 'vendor/autoload.php';

    use loophp\collection\Collection;
    use function in_array;

    use const INF;

    /**
     * Get the divisor of a given number.
     *
     * @param float $num
     *   The number.
     * @param int $start
     *   The start.
     *
     * @return \Traversable
     *   The divisors of the number.
     */
    function factors(float $num, int $start = 1): Traversable
    {
        if (0 === $num % $start) {
            yield $start => $start;

            yield $num / $start => $num / $start;
        }

        if (ceil(sqrt($num)) >= $start) {
            yield from factors($num, $start + 1);
        }
    }

    /**
     * Check if a number is a multiple of 2.
     *
     * @param $value
     *   The number.
     *
     * @return bool
     *   Whether or not the number is a multiple of 2.
     */
    $notMultipleOf2 = static function ($value): bool {
        return 0 !== $value % 2;
    };

    /**
     * Check if a number is a multiple of 3.
     *
     * @param $value
     *   The number.
     *
     * @return bool
     *   Whether or not the number is a multiple of 3.
     */
    $notMultipleOf3 = static function ($value): bool {
        $sumIntegers = static function ($value): float {
            return array_reduce(
                mb_str_split((string) $value),
                static function ($carry, $value) {
                    return $value + $carry;
                },
                0
            );
        };

        $sum = $sumIntegers($value);

        while (10 < $sum) {
            $sum = $sumIntegers($sum);
        }

        return 0 !== $sum % 3;
    };

    /**
     * Check if a number is a multiple of 5.
     *
     * @param $value
     *   The number.
     *
     * @return bool
     *   Whether or not the number is a multiple of 5.
     */
    $notMultipleOf5 = static function ($value): bool {
        return !in_array(mb_substr((string) $value, -1), ['0', '5'], true);
    };

    /**
     * Check if a number is a multiple of 7.
     *
     * @param $value
     *   The number.
     *
     * @return bool
     *   Whether or not the number is a multiple of 7.
     */
    $notMultipleOf7 = static function ($value): bool {
        $number = $value;

        while (14 <= $number) {
            $lastDigit = mb_substr((string) $number, -1);

            if ('0' === $lastDigit) {
                return true;
            }

            $number = (int) abs((int) mb_substr((string) $number, 0, -1) - 2 * (int) $lastDigit);
        }

        return !(0 === $number || 7 === $number);
    };

    /**
     * Check if a number is a multiple of 11.
     *
     * @param $value
     *   The number.
     *
     * @return bool
     *   Whether or not the number is a multiple of 11.
     */
    $notMultipleOf11 = static function ($value): bool {
        $number = $value;

        while (11 < $number) {
            $lastDigit = mb_substr((string) $number, -1);

            if ('0' === $lastDigit) {
                return true;
            }

            $number = (int) abs((int) mb_substr((string) $number, 0, -1) - (int) $lastDigit);
        }

        return !(0 === $number || 11 === $number);
    };

    /**
     * Check if a number have more than 2 divisors.
     *
     * @param $value
     *   The number.
     *
     * @return bool
     *   Whether or not the number has more than 2 divisors.
     */
    $valueHavingMoreThan2Divisors = static function ($value): bool {
        $i = 0;

        foreach (factors($value) as $factor) {
            if (2 < $i++) {
                return false;
            }
        }

        return true;
    };

    $primes = Collection::range(9, INF, 2) // Count from 10 to infinity
        ->filter($notMultipleOf2) // Filter out multiples of 2
        ->filter($notMultipleOf3) // Filter out multiples of 3
        ->filter($notMultipleOf5) // Filter out multiples of 5
        ->filter($notMultipleOf7) // Filter out multiples of 7
        ->filter($notMultipleOf11) // Filter out multiples of 11
        ->filter($valueHavingMoreThan2Divisors) // Filter out remaining values having more than 2 divisors.
        ->prepend(2, 3, 5, 7) // Add back digits that were removed
        ->normalize() // Re-index the keys
        ->limit(100); // Take the 100 first prime numbers.

    print_r($primes->all());

Text analysis
-------------

.. code-block:: php

    <?php

    declare(strict_types=1);

    include __DIR__ . '/vendor/autoload.php';

    use loophp\collection\Collection;

    $collection = Collection::with(file_get_contents('http://loripsum.net/api'))
        // Filter out some characters.
        ->filter(
            static function ($item, $key): bool {
                return (bool) preg_match('/^[a-zA-Z]+$/', $item);
            }
        )
        // Lowercase each character.
        ->map(static function (string $letter): string {
            return mb_strtolower($letter);
        })
        // Run the frequency tool.
        ->frequency()
        // Flip keys and values.
        ->flip()
        // Sort values.
        ->sort()
        // Convert to array.
        ->all();

    print_r($collection);

Random number distribution
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    declare(strict_types=1);

    include 'vendor/autoload.php';

    use loophp\collection\Collection;
    use loophp\collection\Contract\Operation\Sortable;

    $min = 0;
    $max = 1000;
    $groups = 100;

    $randomGenerator = static function () use ($min, $max): int {
        return random_int($min, $max);
    };

    $distribution = Collection::iterate($randomGenerator)
        ->limit($max * $max)
        ->associate(
            static function ($key, $value) use ($max, $groups): string {
                for ($i = 0; ($max / $groups) > $i; ++$i) {
                    if ($i * $groups <= $value && ($i + 1) * $groups >= $value) {
                        return sprintf('%s <= x <= %s', $i * $groups, ($i + 1) * $groups);
                    }
                }
            }
        )
        ->group()
        ->map(
            static function ($value): int {
                return \count($value);
            }
        )
        ->sort(
            Sortable::BY_KEYS,
            static function (array $left, array $right): int {
                $left = current($left);
                $right = current($right);

                [$left_min_limit] = explode(' ', $left);
                [$right_min_limit] = explode(' ', $right);

                return $left_min_limit <=> $right_min_limit;
            }
        );

    /*
    Array
    (
        [0 <= x <= 100] => 101086
        [100 <= x <= 200] => 100144
        [200 <= x <= 300] => 99408
        [300 <= x <= 400] => 100079
        [400 <= x <= 500] => 99514
        [500 <= x <= 600] => 100227
        [600 <= x <= 700] => 99983
        [700 <= x <= 800] => 99942
        [800 <= x <= 900] => 99429
        [900 <= x <= 1000] => 100188
    )
    */
