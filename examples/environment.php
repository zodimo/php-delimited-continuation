<?php

declare(strict_types=1);
use Zodimo\DCF\Arrow\IOMonad;
use Zodimo\DCF\Arrow\Tuple;
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;

require __DIR__.'/../vendor/autoload.php';

/**
 * read from some environment
 * read from stdin
 * match value in environment
 * customize message to print.
 */
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
 * @var callable(string):KleisliEffect<mixed, Tuple, mixed> $writeLine
 */
$writeLine = fn (string $message) => $stdoutWriterEffect($message);

/**
 * @var KleisliEffect<mixed, Tuple, mixed> $readLineEffect
 */
$readLineEffect = KleisliEffect::liftImpure(function () {
    $result = fopen('php://stdin', 'r');

    if (is_resource($result)) {
        return $result;
    }

    throw new Exception('Could not read stdin');
})
    ->bracket(
        KleisliEffect::liftImpure(function ($stdin) {
            $result = fgets($stdin);
            if (false === $result) {
                throw new Exception('Could not read stdin');
            }

            return $result;
        }),
        // @phpstan-ignore argument.type
        KleisliEffect::liftImpure(function ($stdin) {
            if (is_resource($stdin)) {
                fclose($stdin);
            }
        })
    )
;

$environmentEffect = KleisliEffect::liftImpure(fn () => ['name' => 'Joe']);

$programEffect = function () use ($writeLine, $readLineEffect, $environmentEffect): KleisliEffect {
    return $environmentEffect
        ->flatmap(
            fn (array $env) => $writeLine('Please write your name: ')
                ->andThen($readLineEffect)
                ->andThen(KleisliEffect::arr(
                    function (Tuple $stdinResult) use ($env) {
                        /**
                         * @var IOMonad<string, mixed> $result
                         */
                        $result = $stdinResult->fst();

                        return $result->match(
                            function (string $name) use ($env) {
                                $_name = trim($name);
                                if ($_name == $env['name']) {
                                    $message = "Welcome {$_name}!";
                                } else {
                                    $message = "Hello guest[{$_name}]";
                                }

                                return IOMonad::pure($message);
                            },
                            fn () => $result
                        );
                    }
                ))
        )
    ;
};

$runtime = BasicRuntime::create([KleisliEffect::class => new KleisliEffectHandler()]);
$programArrow = $runtime->perform($programEffect());
$programArrow->run(null)->match(
    function (string $message) {
        echo "Success: {$message}";
    },
    function ($_) {
        echo 'ERROR !';
    }
);
