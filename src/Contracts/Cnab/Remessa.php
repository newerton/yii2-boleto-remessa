<?php
namespace Newerton\Yii2Boleto\Contracts\Cnab;

interface Remessa extends Cnab
{
    public function gerar();
}
