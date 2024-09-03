<?php

declare(strict_types=1);

namespace Zodimo\DCF\Arrow;

/**
 * @template FIRST
 * @template SECOND
 */
class Tuple
{
    /**
     * @var FIRST
     */
    private $_first;

    /**
     * @var SECOND
     */
    private $_second;

    private function __construct($first, $second)
    {
        $this->_first = $first;
        $this->_second = $second;
    }

    /**
     * @template _FIRST
     * @template _SECOND
     *
     * @param _FIRST  $first
     * @param _SECOND $second
     *
     * @return Tuple<_FIRST, _SECOND>
     */
    public static function create($first, $second): Tuple
    {
        return new Tuple($first, $second);
    }

    /**
     * @return FIRST
     */
    public function fst()
    {
        return $this->_first;
    }

    /**
     * @return SECOND
     */
    public function snd()
    {
        return $this->_second;
    }
}
