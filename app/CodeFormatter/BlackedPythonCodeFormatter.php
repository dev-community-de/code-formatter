<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class BlackedPythonCodeFormatter
 *
 * @package DevCommunityDE\CodeFormatter\CodeFormatter
 */
class BlackedPythonCodeFormatter extends CodeFormatter
{

    /**
     * @var array
     */
    protected static $supported_languages = [
        'python',
    ];

    /**
     * @param string $file
     * @return string
     */
    protected function getShellCommand(string $file) : string
    {
        return 'black --quiet --config ' . __DIR__ . '/../../.black-format.toml \'' . $file . '\'';
    }
}
