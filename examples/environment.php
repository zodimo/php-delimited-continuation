<?php

declare(strict_types=1);
use Zodimo\BaseReturn\IOMonad;
use Zodimo\BaseReturn\Tuple;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;
use Zodimo\DCF\Effect\Router\BasicEffectRouter;

require __DIR__.'/../vendor/autoload.php';

/**
 * read from some environment
 * read from stdin
 * match value in environment
 * customize message to print.
 */
/**
 * @var callable(string):KleisliEffect<null,Tuple, mixed>
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
 * @var callable(string):KleisliEffect<null, Tuple, mixed> $writeLine
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

/**
 * @var KleisliEffect<null, array<string>, mixed> $environmentEffect
 */
$environmentEffect = KleisliEffect::liftImpure(fn () => ['name' => 'Joe']);

$programEffect = function () use ($writeLine, $readLineEffect, $environmentEffect): KleisliEffect {
    return KleisliEffect::liftPure(fn () => Tuple::create(null, null))
        ->andThen(
            KleisliEffect::merge(
                $environmentEffect,
                $writeLine('Please write your name: ')->andThen($readLineEffect)
            )
            // @phpstan-ignore argument.type
            // @phpstan-ignore argument.type
                ->andThen(KleisliEffect::arr(
                    function (Tuple $both): IOMonad {
                        $env = $both->fst();
                        $stdinResult = $both->snd();

                        /**
                         * @var IOMonad<string, mixed> $readResult
                         */
                        $readResult = $stdinResult->fst();

                        return $readResult->match(
                            function (string $name) use ($env) {
                                $_name = trim($name);
                                if ($_name == $env['name']) {
                                    $message = "Welcome {$_name}!";
                                } else {
                                    $message = "Hello guest[{$_name}]";
                                }

                                return IOMonad::pure($message);
                            },
                            fn () => $readResult
                        );
                    }
                ))
        )
    ;
};

$runtime = BasicEffectRouter::create([KleisliEffect::class => new KleisliEffectHandler()]);
$programArrow = $runtime->perform($programEffect());
$programArrow->run(null)->match(
    function (string $message) {
        echo "Success: {$message}";
    },
    function ($_) {
        echo 'ERROR !';
    }
);
