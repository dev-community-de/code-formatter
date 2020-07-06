<?php

/*
 * This file is part of the dev-community code formatter
 * (c) The dev-community.de authors
 */

namespace DevCommunityDE\CodeFormatter\CodeFormatter;

use DevCommunityDE\CodeFormatter\Exceptions\Exception;

/**
 * Class CodeFormatter.
 */
abstract class CodeFormatter
{
    private const FORMATTERS = [
        BlackPythonCodeFormatter::class,
        ClangCodeFormatter::class,
        GoCodeFormatter::class,
        PrettierCodeFormatter::class,
    ];

    protected const LANGUAGES = [];

    /**
     * @param string $language
     *
     * @return self|null
     */
    public static function create(string $language): ?self
    {
        foreach (self::FORMATTERS as $formatter) {
            if (\in_array($language, $formatter::LANGUAGES)) {
                return new $formatter($language);
            }
        }

        return null;
    }

    /**
     * @param string $file
     */
    public function exec(string $file)
    {
        $shell = $this->getShellCommand($file);
        echo '> ', $shell, "\n";

        // execute code formatting
        echo exec($shell, $output, $return_var), "\n";
        echo implode("\n", $output), "\n";

        if (0 !== $return_var) {
            throw new Exception('code formatting failed');
        }
    }

    /**
     * @param string $file
     *
     * @return string
     */
    abstract protected function getShellCommand(string $file);
}
