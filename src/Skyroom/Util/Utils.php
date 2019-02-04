<?php

namespace Skyroom\Util;


class Utils
{
    /**
     * Search array with user defined condition
     *
     * @param array $array Array to search in
     * @param callable $predicate
     *
     * @return int
     */
    public static function arrayFind(array $array, callable $predicate)
    {
        foreach ($array as $key => $item) {
            if ($predicate($item) === true) {
                return $key;
            }
        }

        return -1;
    }
}