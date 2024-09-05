<?php

declare(strict_types=1);
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;

require __DIR__.'/../vendor/autoload.php';

// how can we make the effect use an effect..

$stdoutWriterEffect = fn (string $message) => KleisliEffect::liftImpure(function () {
    $result = fopen('php://output', 'w');

    if (is_resource($result)) {
        return $result;
    }

    throw new Exception('Could not read stdin');
})
    ->bracket(
        KleisliEffect::liftImpure(function ($stdout) use ($message) {
            $result = fputs($stdout, $message);
            if (false === $result) {
                throw new Exception('Could not read stdin');
            }
        }),
        // @phpstan-ignore argument.type
        KleisliEffect::liftImpure(function ($stdout) {
            if (is_resource($stdout)) {
                fclose($stdout);
            }
        })
    )
;

/**
 * @var callable(string):KleisliEffect<void, string, mixed> $writeLine
 */
$writeLine = fn (string $message) => $stdoutWriterEffect($message);

KleisliEffect::liftImpure(function (string $message) {
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

$programEffect = $writeLine('Please write your name: ')->andThen($readLineEffect);

$runtime = BasicRuntime::create([KleisliEffect::class => new KleisliEffectHandler()]);
$program = $runtime->perform($programEffect);
$program->run(null)->match(
    function (string $name) {
        echo "I got this: {$name}";
    },
    function ($_) {
        echo 'there was an error';
    }
);
