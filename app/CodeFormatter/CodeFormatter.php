<?php

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

    /** @var string */
    protected $language;

    /**
     * constructs a new formatter.
     *
     * @param string $language
     */
    protected function __construct(string $language)
    {
        $this->language = $language;
    }

    /**
     * @param string $language
     *
     * @return self|null
     */
    public static function create(string $language): ?self
    {
        foreach (self::FORMATTERS as $formatter) {
            if (\in_array($language, $formatter::LANGUAGES, true)) {
                return new $formatter($language);
            }
        }

        return null;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public function exec(string $content): string
    {
        $shell = $this->getShellCommand();
        $specs = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($shell, $specs, $pipes);

        if (false === $proc) {
            throw new Exception("unable to open process: {$shell}");
        }

        fwrite($pipes[0], $content);
        fclose($pipes[0]);

        $result = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $return = proc_close($proc);

        if (0 !== $return || false === $result) {
            throw new Exception("code formatting failed! stderr: {$errors}");
        }

        return $result;
    }

    /**
     * @return string
     */
    abstract protected function getShellCommand(): string;
}
