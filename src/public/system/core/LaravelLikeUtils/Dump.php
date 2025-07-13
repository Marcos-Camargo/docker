<?php


namespace LaravelLikeUtils;

require_once 'var-dumper/Cloner/VarCloner.php';
require_once 'var-dumper/Caster/ReflectionCaster.php';
require_once 'var-dumper/Cloner/VarCloner.php';
require_once 'var-dumper/Dumper/CliDumper.php';
require_once 'var-dumper/Dumper/ContextProvider/SourceContextProvider.php';
require_once 'var-dumper/Dumper/ContextualizedDumper.php';
require_once 'var-dumper/Dumper/HtmlDumper.php';
require_once 'var-dumper/Dumper/ServerDumper.php';


use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Dumper\ContextualizedDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\ServerDumper;

class Dump
{
    private static $handler;

    public static function dump($var)
    {
        if (null === self::$handler) {
            self::register();
        }

        return (self::$handler)($var);
    }

    private static function register(): void
    {
        $cloner = new VarCloner();
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $format = $_SERVER['VAR_DUMPER_FORMAT'] ?? null;
        switch (true) {
            case 'html' === $format:
                $dumper = new HtmlDumper();
                break;
            case 'cli' === $format:
                $dumper = new CliDumper();
                break;
//            case 'server' === $format:
//            case 'tcp' === parse_url($format, \PHP_URL_SCHEME):
//                $host = 'server' === $format ? $_SERVER['VAR_DUMPER_SERVER'] ?? '127.0.0.1:9912' : $format;
//                $dumper = \in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) ? new CliDumper() : new HtmlDumper();
//                $dumper = new ServerDumper($host, $dumper, self::getDefaultContextProviders());
//                break;
            default:
                $dumper = \in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) ? new CliDumper() : new HtmlDumper();
        }

        if (!$dumper instanceof ServerDumper) {
            $dumper = new ContextualizedDumper($dumper, [new SourceContextProvider()]);
        }

        self::$handler = function ($var) use ($cloner, $dumper) {
            $dumper->dump($cloner->cloneVar($var));
        };
    }

}