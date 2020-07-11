<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class GoCodeFormatter.
 */
class GoCodeFormatter extends CodeFormatter
{
    /**
     * @var array
     */
    protected const LANGUAGES = [
        'go',
    ];

    /**
     * @return string
     */
    protected function getShellCommand(): string
    {
        return 'gofmt';
    }
}
