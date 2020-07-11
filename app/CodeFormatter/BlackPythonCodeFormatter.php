<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class BlackPythonCodeFormatter.
 */
class BlackPythonCodeFormatter extends CodeFormatter
{
    /**
     * @var array
     */
    protected const LANGUAGES = [
        'python',
    ];

    /**
     * @return string
     */
    protected function getShellCommand(): string
    {
        return 'black --quiet --config ' . __DIR__ . '/../../.black-format.toml -';
    }
}
