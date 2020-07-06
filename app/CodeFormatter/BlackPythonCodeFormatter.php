<?php

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class BlackPythonCodeFormatter.
 */
class BlackPythonCodeFormatter extends CodeFormatter
{
    protected const LANGUAGES = [
        'python',
        'py',
    ];

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getShellCommand(string $file): string
    {
        $fileArg = escapeshellarg($file);
        return 'black --quiet --config ' . __DIR__ . '/../../.black-format.toml ' . $fileArg;
    }
}
