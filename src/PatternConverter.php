<?php

namespace GregPriday\GitIgnore;

/**
 * PatternConverter
 *
 * A class to convert gitignore-style glob patterns to regular expressions.
 */
class PatternConverter
{
    /**
     * Convert a gitignore-style pattern to a regular expression pattern.
     *
     * @param  string  $pattern  The gitignore pattern to convert
     * @return string The regular expression pattern (without delimiters)
     */
    public function convertPatternToRegex(string $pattern): string
    {
        // Expand braces before any regex conversion
        $expandedPatterns = $this->expandBraces($pattern);

        // If we have multiple expanded patterns from braces, handle each one
        if (count($expandedPatterns) > 1) {
            $regexParts = [];
            foreach ($expandedPatterns as $expandedPattern) {
                $regexParts[] = $this->convertSinglePatternToRegex($expandedPattern);
            }

            return '(?:'.implode('|', $regexParts).')';
        }

        // Otherwise, convert the single pattern
        return $this->convertSinglePatternToRegex($expandedPatterns[0]);
    }

    /**
     * Convert a single gitignore pattern (without braces) to a regular expression.
     *
     * @param  string  $pattern  The pattern to convert
     * @return string The regular expression pattern (without delimiters)
     */
    protected function convertSinglePatternToRegex(string $pattern): string
    {
        // First identify if we're dealing with a pattern that starts with **/
        $startsWithDoubleAsterisk = false;
        if (str_starts_with($pattern, '**/')) {
            $startsWithDoubleAsterisk = true;
            $pattern = substr($pattern, 3); // Remove the **/ prefix for now
        }

        // Process the pattern character by character
        $result = '';
        $inBracket = false;
        $bracketContent = '';
        $escaping = false;

        for ($i = 0; $i < strlen($pattern); $i++) {
            $char = $pattern[$i];

            if ($escaping) {
                // This character is escaped
                if ($inBracket) {
                    // Inside a bracket, handle special characters that need escaping
                    if (in_array($char, ['^', ']', '-', '\\'])) {
                        // These need to be escaped in a character class
                        $bracketContent .= '\\'.$char;
                    } else {
                        // Normal character, no escaping needed in character class
                        $bracketContent .= $char;
                    }
                } else {
                    // Outside a bracket, add as escaped
                    $result .= preg_quote($char, '#');
                }
                $escaping = false;
            } elseif ($char === '\\') {
                // Start of escape sequence
                $escaping = true;
            } elseif ($char === '[' && ! $inBracket) {
                // Start of a character class
                $inBracket = true;
                $bracketContent = '';
            } elseif ($char === ']' && $inBracket) {
                // End of a character class
                $inBracket = false;

                // Handle special cases for character classes
                if ($bracketContent === '^') {
                    // Special case: [^] should match a literal ^ character, not act as negation
                    $result .= '\\^';
                } elseif (preg_match('/^\[.*\]$/', $bracketContent)) {
                    // Special case: [[a-z]] should match the literal string [a-z]
                    $innerContent = substr($bracketContent, 1, strlen($bracketContent) - 2);
                    $result .= '\\['.$innerContent.'\\]';
                } elseif (strlen($bracketContent) > 1 && ($bracketContent[0] === '!' || $bracketContent[0] === '^')) {
                    // Negated character class
                    $result .= '[^'.substr($bracketContent, 1).']';
                } else {
                    // Regular character class
                    $result .= '['.$bracketContent.']';
                }
            } elseif ($inBracket) {
                // Inside a character class
                $bracketContent .= $char;
            } elseif ($char === '*') {
                // Wildcard
                if ($i + 1 < strlen($pattern) && $pattern[$i + 1] === '*') {
                    // Double asterisk
                    if ($i + 2 < strlen($pattern) && $pattern[$i + 2] === '/') {
                        // **/ pattern
                        $result .= '(?:.*?/)?';
                        $i += 2; // Skip the next two characters
                    } else {
                        // Just ** (no slash)
                        $result .= '.*';
                        $i++; // Skip the next character
                    }
                } else {
                    // Single asterisk
                    $result .= '[^/]*';
                }
            } elseif ($char === '?') {
                // Question mark wildcard
                $result .= '.';
            } else {
                // Regular character
                if (! $inBracket) {
                    $result .= preg_quote($char, '#');
                } else {
                    $bracketContent .= $char;
                }
            }
        }

        // If we're still in a bracket at the end, treat it as literal characters
        if ($inBracket) {
            $result .= '\\['.preg_quote($bracketContent, '#');
        }

        // If we ended with an escape character, add it as a literal
        if ($escaping) {
            $result .= '\\\\';
        }

        // If the pattern started with **/, add the appropriate prefix
        if ($startsWithDoubleAsterisk) {
            return '(?:.*/)?'.$result;
        }

        return $result;
    }

    /**
     * Recursively expand brace expressions in a pattern.
     *
     * @param  string  $pattern  The pattern containing brace expressions
     * @return string[] An array of expanded patterns
     */
    public function expandBraces(string $pattern): array
    {
        // If there are no braces, return the pattern as-is
        if (! str_contains($pattern, '{')) {
            return [$pattern];
        }

        // Find the first opening brace
        $posOpen = strpos($pattern, '{');

        // Find the matching closing brace
        $posClose = $this->findMatchingCloseBrace($pattern, $posOpen);

        // If no matching closing brace, return the pattern as-is
        if ($posClose === false) {
            return [$pattern];
        }

        // Extract parts before and after the braces, and the options inside
        $prefix = substr($pattern, 0, $posOpen);
        $suffix = substr($pattern, $posClose + 1);
        $inside = substr($pattern, $posOpen + 1, $posClose - $posOpen - 1);

        // Split inside by commas that aren't inside nested braces
        $options = $this->splitOptionsRespectingNesting($inside);

        // Combine each option with the prefix and suffix, and expand any nested braces
        $results = [];
        foreach ($options as $option) {
            $expanded = $prefix.$option.$suffix;
            $results = array_merge($results, $this->expandBraces($expanded));
        }

        return $results;
    }

    /**
     * Find the position of the matching closing brace.
     *
     * @param  string  $pattern  The pattern to search in
     * @param  int  $posOpen  The position of the opening brace
     * @return int|false The position of the matching closing brace, or false if not found
     */
    protected function findMatchingCloseBrace(string $pattern, int $posOpen): int|false
    {
        $length = strlen($pattern);
        $nesting = 0;

        for ($i = $posOpen; $i < $length; $i++) {
            if ($pattern[$i] === '{') {
                $nesting++;
            } elseif ($pattern[$i] === '}') {
                $nesting--;
                if ($nesting === 0) {
                    return $i;
                }
            }
        }

        return false;
    }

    /**
     * Split options at commas, respecting nested braces.
     *
     * @param  string  $inside  The content inside a set of braces
     * @return string[] An array of options
     */
    protected function splitOptionsRespectingNesting(string $inside): array
    {
        $options = [];
        $start = 0;
        $nesting = 0;
        $length = strlen($inside);

        for ($i = 0; $i < $length; $i++) {
            if ($inside[$i] === '{') {
                $nesting++;
            } elseif ($inside[$i] === '}') {
                $nesting--;
            } elseif ($inside[$i] === ',' && $nesting === 0) {
                $options[] = substr($inside, $start, $i - $start);
                $start = $i + 1;
            }
        }

        // Add the last option
        $options[] = substr($inside, $start);

        return $options;
    }

    /**
     * Convert a pattern to a regular expression with delimiters and anchors.
     *
     * @param  string  $pattern  The gitignore pattern
     * @return string The complete regular expression
     */
    public function patternToRegex(string $pattern): string
    {
        $regex = $this->convertPatternToRegex($pattern);

        // Escape the '#' character since we're using it as our delimiter
        $regex = str_replace('#', '\#', $regex);

        return '#^'.$regex.'$#';
    }
}
