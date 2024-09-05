<?php

declare(strict_types=1);
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;

require __DIR__.'/../vendor/autoload.php';

// how can we make the effect use an effect..

/**
 * @var KleisliEffect<string, string, mixed> $writeLineEffect
 */
$writeLineEffect = KleisliEffect::liftImpure(function (string $message) {
    $f = null;

    try {
        $f = fopen('php://output', 'w');
        $result = fputs($f, $message);
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

/**
 * @var KleisliEffect<string, void, mixed> $readLineEffect
 */
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

$programEffect = $writeLineEffect->andThen($readLineEffect);

$runtime = BasicRuntime::create([KleisliEffect::class => new KleisliEffectHandler()]);
$program = $runtime->perform($programEffect);
$program->run('Please enter you name: ')->match(
    function (string $name) {
        echo "I got this: {$name}";
    },
    function ($_) {
        echo 'there was an error';
    }
);
