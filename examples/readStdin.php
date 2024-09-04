<?php

declare(strict_types=1);
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;

require __DIR__.'/../vendor/autoload.php';

$readLineEffect = KleisliEffect::liftImpure(function () {
    $f = null;

    try {
        $f = fopen('php://stdin', 'r');
        $result = fgets($f);
        if (false === $result) {
            throw new Exception('Could not read stdin');
        }
    } finally {
        if (is_resource($f)) {
            fclose($f);
        }
    }

    return $result;
});

$runtime = BasicRuntime::create([KleisliEffect::class => new KleisliEffectHandler()]);
$readLineArrow = $runtime->perform($readLineEffect);
$getNameResult = $readLineArrow->run(null)->match(
    function (string $name) {
        echo "I got this: {$name}";
    },
    function ($_) {
        echo 'there was an error';
    }
);
