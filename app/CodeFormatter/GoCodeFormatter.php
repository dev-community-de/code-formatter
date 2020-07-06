<?php

/*
 * This file is part of the dev-community code formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

/**
 * Class GoCodeFormatter.
 */
class GoCodeFormatter extends CodeFormatter
{
    protected const LANGUAGES = [
        'go',
    ];

    /**
     * @param string $file
     *
     * @return string
     */
    protected function getShellCommand(string $file): string
    {
        $fileArg = escapeshellarg($file);
        return 'gofmt -w ' . $fileArg;
    }
}
