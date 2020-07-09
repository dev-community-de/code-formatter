<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class PhpCodeFormatter.
 */
class PhpCodeFormatter extends CodeFormatter
{
    /**
     * @var array
     */
    protected static $supported_languages = [
        'php',
    ];

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getShellCommand(string $file): string
    {
        return 'php-cs-fixer fix ' . $file;
    }
}
