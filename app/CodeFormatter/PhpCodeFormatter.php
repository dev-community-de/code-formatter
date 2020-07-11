<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class PhpCodeFormatter.
 */
class PhpCodeFormatter extends CodeFormatter
{
    protected const LANGUAGES = [
        'php',
    ];

    /**
     * @return string
     */
    protected function getShellCommand(): string
    {
        return 'php-cs-fixer fix --rules @PSR2 -';
    }
}
