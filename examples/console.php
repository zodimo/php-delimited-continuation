<?php

declare(strict_types=1);
use Zodimo\BaseReturn\IOMonad;
use Zodimo\BaseReturn\Tuple;
use Zodimo\DCF\Effect\BasicRuntime;
use Zodimo\DCF\Effect\KleisliEffect;
use Zodimo\DCF\Effect\KleisliEffectHandler;

require __DIR__.'/../vendor/autoload.php';

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

$programEffect = $writeLine('Please write your name: ')->andThen($readLineEffect);

$runtime = BasicRuntime::create([KleisliEffect::class => new KleisliEffectHandler()]);
$programArrow = $runtime->perform($programEffect);
$programArrow->run(null)->match(
    function (Tuple $result) {
        /**
         * @var Tuple<IOMonad<string, mixed>, IOMonad<null, mixed>> $result
         */
        $result->fst()->match(
            function (string $name) {
                echo "Hello {$name}!";
            },
            function ($_) {
                echo 'ERROR on first!';
            }
        );
    },
    function ($_) {
        echo 'ERROR !';
    }
);
