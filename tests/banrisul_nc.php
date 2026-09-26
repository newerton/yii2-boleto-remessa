<?php

require __DIR__ . '/../vendor/autoload.php';

use Newerton\Yii2Boleto\CalculoDV;

// Banrisul CNAB 400, item 4.1. Nosso numero 00009274 has NC 22.
// 00009194 has a mod 11 remainder of 1, so the first digit is increased and the NC is 38.
// When that first digit is 9, it becomes 0 and the second digit is calculated again.
$cases = [
    '00009274' => '22',
    '00009194' => '38',
    '00000265' => '06',
    '00000270' => '06',
];

foreach ($cases as $nossoNumero => $nc) {
    $got = CalculoDV::banrisulNossoNumero($nossoNumero);
    if ($got !== $nc) {
        fwrite(STDERR, "$nossoNumero returned $got, Banrisul layout says $nc\n");
        exit(1);
    }
}

echo "ok\n";
