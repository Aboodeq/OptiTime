<?php

namespace App\Scheduling;




final class SeededRandom
{
    public function __construct(?int $seed)
    {
        if ($seed !== null) {
            mt_srand($seed);
        } else {
            mt_srand(random_int(1, PHP_INT_MAX));
        }
    }

    public function nextInt(int $min, int $max): int
    {
        if ($min > $max) {
            return $min;
        }

        return mt_rand($min, $max);
    }

    
    public function nextFloat(): float
    {
        return mt_rand() / (mt_getrandmax() + 1);
    }
}
