<?php

namespace Newerton\Yii2Boleto\Support;

/**
 * Interface Jsonable
 * @package Newerton\Yii2Boleto\Support
 */
interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0);
}
