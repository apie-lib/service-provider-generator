<?php
namespace Apie\ServiceProviderGenerator;

final class CodeUtils 
{
    private function __construct()
    {
    }

    public static function indent(string $input, int $indentation): string
    {
        $indentString = str_repeat(' ', $indentation);

        // this is the quickest to write, but certainly not the fastest.
        $lines = array_map(function (string $line) use ($indentString) {
            return $indentString . $line;
        }, explode("\n", $input));
        return implode("\n", $lines);
    }

    public static function renderString(string $input): string
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9]*(\\\\[A-Za-z][A-Za-z0-9]*)+$/', $input)) {
            return '\\' . $input . '::class';
        }
        return var_export($input, true);
    }
}