<?php

namespace GregPriday\GitIgnore;

use ErrorException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class GitIgnoreManager
{
    /**
     * The base directory to scan.
     */
    protected string $basePath;

    /**
     * An array of ignore rule sets.
     * Each element is an associative array with keys:
     *  - 'dir': The relative directory where the ignore file was found.
     *  - 'rules': An array of parsed rules.
     */
    protected array $ignoreRules = [];

    /**
     * Whether pattern matching is case‑sensitive.
     */
    protected bool $caseSensitive;

    /**
     * The pattern converter instance.
     */
    protected PatternConverter $patternConverter;

    /**
     * Additional ignore file names to include.
     */
    protected array $additionalIgnoreFiles = [];

    /**
     * Create a new GitIgnoreManager instance.
     *
     * By default, only '.gitignore' files are loaded.
     * You may provide an array of additional filenames (e.g. ['.dockerignore']) to load them as well.
     *
     * @param  string  $basePath  The base directory to scan.
     * @param  bool  $caseSensitive  Whether pattern matching is case‑sensitive.
     * @param  array  $additionalIgnoreFiles  Additional ignore file names to load.
     *
     * @throws RuntimeException if the base path is not a valid directory.
     */
    public function __construct(string $basePath, bool $caseSensitive = true, array $additionalIgnoreFiles = [])
    {
        if (! is_dir($basePath)) {
            throw new RuntimeException("The base path {$basePath} is not a valid directory.");
        }
        $this->basePath = realpath($basePath);
        $this->caseSensitive = $caseSensitive;
        $this->additionalIgnoreFiles = $additionalIgnoreFiles;
        $this->patternConverter = new PatternConverter;
        $this->loadAllGitIgnoreFiles();
    }

    /**
     * Scan for all ignore files under the base path and parse them.
     */
    protected function loadAllGitIgnoreFiles(): void
    {
        $finder = new Finder;
        $finder->ignoreUnreadableDirs(true)
            ->files()
            ->in($this->basePath)
            ->ignoreDotFiles(false)
            ->followLinks(false);

        // By default, only load '.gitignore'.
        $names = array_merge(['.gitignore'], $this->additionalIgnoreFiles ?? []);
        $finder->name($names);

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            $ignoreDir = str_replace($this->basePath, '', $file->getPath());
            $ignoreDir = ltrim($ignoreDir, DIRECTORY_SEPARATOR);
            $rules = $this->loadGitIgnoreFile($file->getRealPath());
            $this->ignoreRules[] = [
                'dir' => $ignoreDir,
                'rules' => $rules,
                'type' => $file->getFilename(),
            ];
        }

        // Order ignore rules by directory depth.
        usort($this->ignoreRules, function ($a, $b) {
            $depthA = $a['dir'] === '' ? 0 : substr_count($a['dir'], DIRECTORY_SEPARATOR) + 1;
            $depthB = $b['dir'] === '' ? 0 : substr_count($b['dir'], DIRECTORY_SEPARATOR) + 1;

            return $depthA <=> $depthB;
        });
    }

    /**
     * Load and parse a single ignore file.
     *
     * Each rule is returned as an associative array with keys:
     * 'pattern', 'isNegation', 'directoryOnly', and 'hasLeadingSlash'.
     */
    protected function loadGitIgnoreFile(string $filePath): array
    {
        $rules = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines or comments (lines actually starting with #)
            if ($line === '' || (strlen($line) > 0 && $line[0] === '#')) {
                continue;
            }

            // Handle quoted patterns - remove the quotes but preserve spaces
            if (strlen($line) >= 2 && $line[0] === '"' && $line[strlen($line) - 1] === '"') {
                $line = substr($line, 1, strlen($line) - 2);
            }

            $line = stripcslashes($line);

            // Handle negation (count number of leading exclamation marks)
            $negationCount = 0;
            while (strpos($line, '!') === 0) {
                $negationCount++;
                $line = substr($line, 1);
            }
            // If negationCount is odd, it's a negation rule
            $isNegation = ($negationCount % 2 === 1);

            $line = ltrim($line);

            $directoryOnly = false;
            if (substr($line, -1) === '/') {
                $directoryOnly = true;
                $line = rtrim($line, '/');
            }
            $hasLeadingSlash = false;
            if (substr($line, 0, 1) === '/') {
                $hasLeadingSlash = true;
                $line = ltrim($line, '/');
            }
            // Use PatternConverter's expandBraces method
            $expandedPatterns = $this->patternConverter->expandBraces($line);
            foreach ($expandedPatterns as $pattern) {
                $pattern = trim($pattern);
                if ($pattern === '') {
                    continue;
                }
                $rules[] = [
                    'pattern' => $pattern,
                    'isNegation' => $isNegation,
                    'directoryOnly' => $directoryOnly,
                    'hasLeadingSlash' => $hasLeadingSlash,
                    'containsSlash' => strpos($pattern, '/') !== false,
                ];
            }
        }

        return $rules;
    }

    /**
     * Determine whether a given file should be accepted based on all ignore rules.
     */
    public function accept(SplFileInfo $file): bool
    {
        $fullPath = $file->getRealPath();
        $relativePath = ltrim(str_replace('\\', '/', substr($fullPath, strlen($this->basePath))), '/');

        $ignored = false;
        foreach ($this->ignoreRules as $ignoreEntry) {
            $ignoreDir = $ignoreEntry['dir'];
            if ($ignoreDir === '') {
                $relative = $relativePath;
            } else {
                $prefix = str_replace('\\', '/', $ignoreDir).'/';
                if (! str_starts_with($relativePath, $prefix)) {
                    continue;
                }
                $relative = substr($relativePath, strlen($prefix));
            }
            foreach ($ignoreEntry['rules'] as $rule) {
                if ($rule['directoryOnly'] && ! $file->isDir()) {
                    continue;
                }
                if ($rule['hasLeadingSlash']) {
                    $subject = $relative;
                } elseif (! $rule['containsSlash']) {
                    $subject = $file->getBasename();
                } else {
                    $subject = $relative;
                }
                if ($this->matchPattern($rule['pattern'], $subject)) {
                    $ignored = ! $rule['isNegation'];
                    if ($ignored && $rule['directoryOnly']) {
                        break;
                    }
                }
            }
            if ($ignored && $file->isDir()) {
                $dirPrefix = $relativePath.'/';
                if ($this->isParentIgnored($dirPrefix)) {
                    return false;
                }
            }
        }
        if ($this->isParentIgnored($relativePath)) {
            return false;
        }

        return ! $ignored;
    }

    protected function isParentIgnored(string $path): bool
    {
        $parts = explode('/', $path);
        $parentPaths = [];
        for ($i = 1; $i < count($parts); $i++) {
            $parentPaths[] = implode('/', array_slice($parts, 0, $i));
        }
        foreach ($this->ignoreRules as $entry) {
            foreach ($entry['rules'] as $rule) {
                if ($rule['directoryOnly'] && ! $rule['isNegation']) {
                    $ignorePath = rtrim(($entry['dir'] ? $entry['dir'].'/' : '').$rule['pattern'], '/');
                    $ignorePath = str_replace('\\', '/', $ignorePath);
                    if ($ignorePath === '') {
                        continue;
                    }
                    foreach ($parentPaths as $parent) {
                        if ($this->matchPattern($ignorePath, $parent)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if a given pattern matches the subject.
     *
     * @param  string  $pattern  The ignore pattern.
     * @param  string  $subject  The string to match against.
     * @return bool Whether the subject matches the pattern.
     */
    public function matchPattern(string $pattern, string $subject): bool
    {
        // If no wildcards exist, use a literal comparison.
        if (! str_contains($pattern, '*') && ! str_contains($pattern, '?')) {
            return $this->caseSensitive
                ? ($pattern === $subject)
                : (strcasecmp($pattern, $subject) === 0);
        }
        // Convert the pattern to a regex and match using PatternConverter
        $regex = $this->patternConverter->patternToRegex($pattern);
        try {
            return preg_match($regex, $subject) === 1;
        } catch (ErrorException $e) {
            return false;
        }
    }

    /**
     * Convert an ignore pattern into a regular expression.
     *
     * This method is now a wrapper around PatternConverter::convertPatternToRegex.
     *
     * @param  string  $pattern  The ignore pattern.
     * @return string The corresponding regular expression.
     *
     * @deprecated Use PatternConverter::convertPatternToRegex directly
     */
    public function convertPatternToRegex(string $pattern): string
    {
        return $this->patternConverter->convertPatternToRegex($pattern);
    }
}
