# GitIgnore PHP Library Guide

This document provides an in‑depth overview of Greg Priday's GitIgnore PHP library, detailing its design, features, and usage examples. It is written to guide an LLM (large language model) in understanding how to integrate and extend the library for feature development and file filtering tasks.

---

## Overview

**GitIgnore PHP** is a PHP library designed to parse, interpret, and manage ignore rules defined in `.gitignore` files (and optionally in additional ignore files). It offers a robust set of features including:

- **Parsing .gitignore Files:**  
  Automatically scans a project directory for ignore files (by default, `.gitignore`) and loads their rules.

- **Advanced Glob Pattern Support:**  
  Supports single asterisks (`*`) for matching any sequence of characters except directory separators, double asterisks (`**`) for matching across directories, and the question mark (`?`) for single-character matches.

- **Brace Expansion:**  
  Allows patterns such as `*.{js,css,html}` to be automatically expanded into multiple patterns (`*.js`, `*.css`, and `*.html`). This feature even supports nested brace expressions.

- **Negation and Rule Precedence:**  
  Processes negation rules using exclamation marks (`!`) and supports complex negation chains. The library carefully applies rules in order—taking into account directory depth and specific rule types—to decide whether a file should be ignored.

- **Directory-only Rules:**  
  Recognizes patterns ending with a slash (e.g. `build/`) to mark directories (and their contents) as ignored.

- **Unicode and Special Character Handling:**  
  Correctly parses files and patterns that include Unicode characters, whitespace, and escaped special characters.

- **Configurable Case Sensitivity:**  
  Allows you to toggle between case‑sensitive and case‑insensitive matching depending on your project needs.

- **Integration with Symfony Finder:**  
  Leverages Symfony's Finder component to seamlessly scan directories and integrate file system operations with ignore rule evaluation.

- **Support for Multiple Ignore Files:**  
  In addition to the standard `.gitignore`, you can specify extra ignore files (e.g. `.ctreeignore`, `.customignore`) to load additional rules.

---

## Installation

Install GitIgnore PHP via Composer:

```bash
composer require gregpriday/gitignore-php
```

---

## Core Features and Usage Examples

### 1. Basic Usage

To load a project's `.gitignore` rules and determine whether a file should be ignored, instantiate the manager and pass it a file wrapped in Symfony’s `SplFileInfo`:

```php
use GregPriday\GitIgnore\GitIgnoreManager;
use Symfony\Component\Finder\SplFileInfo;

$manager = new GitIgnoreManager('/path/to/your/project');
$file = new SplFileInfo('/path/to/your/project/src/Example.php', 'src', 'src/Example.php');

if ($manager->accept($file)) {
    // File is accepted (not ignored)
} else {
    // File is ignored
}
```

### 2. Loading Additional Ignore Files

By default, GitIgnore PHP scans only for `.gitignore` files. You can also include additional ignore files by passing an array of filenames as the third parameter:

```php
use GregPriday\GitIgnore\GitIgnoreManager;

$manager = new GitIgnoreManager('/path/to/your/project', true, ['.ctreeignore']);
```

### 3. Brace Expansion

Brace expansion enables you to define multiple patterns in a single, compact rule. For example, the pattern:

```
*.{js,css,html}
```

expands to:

- `*.js`
- `*.css`
- `*.html`

You can test brace expansion directly using the `PatternConverter` class:

```php
use GregPriday\GitIgnore\PatternConverter;

$converter = new PatternConverter();
$expanded = $converter->expandBraces('*.{js,css,html}');
// Expected output: ['*.js', '*.css', '*.html']
print_r($expanded);
```

### 4. Wildcard Matching

GitIgnore PHP supports a variety of wildcard patterns:
- **`*`** – Matches any sequence of characters except directory separators.
- **`**`** – Matches across directory boundaries.
- **`?`** – Matches any single character.

**Example:**  
The pattern `src/**/*.php` matches:
- `src/File.php`
- `src/Controllers/HomeController.php`
- `src/Models/User.php`

### 5. Filtering a Git Repository

You can combine GitIgnore PHP with Symfony Finder to scan and filter a repository’s files:

```php
use GregPriday\GitIgnore\GitIgnoreManager;
use Symfony\Component\Finder\Finder;

$projectPath = '/path/to/your/git/repo';
$manager = new GitIgnoreManager($projectPath);

$finder = new Finder();
$finder->files()->in($projectPath);

$acceptedFiles = [];
foreach ($finder as $file) {
    if ($manager->accept($file)) {
        $acceptedFiles[] = $file->getRelativePathname();
    }
}

echo "Accepted files:\n";
print_r($acceptedFiles);
```

---

## Advanced Features and Considerations

### Negation Rules and Precedence

- **Negation Rules:**  
  Patterns beginning with one or more exclamation marks (`!`) indicate that files matching the pattern should be re‑included even if an earlier rule would ignore them. The library supports complex negation chains (for example, using `!`, `!!`, and `!!!`) to allow fine‑tuned control over which files are ultimately accepted.

- **Rule Precedence:**  
  When multiple rules match a file, the library determines the outcome based on the order of evaluation and the directory depth at which the rules were loaded. Rules in deeper subdirectories are applied after higher‑level rules, allowing for localized exceptions.

### Directory-only Rules

A pattern ending with a slash (e.g. `build/`) tells GitIgnore PHP to treat the rule as directory‑only. This means that not only is the directory itself ignored, but so are all files and subdirectories contained within it.

### Handling Special Characters and Unicode

GitIgnore PHP correctly processes patterns with:
- Escaped characters (e.g. `\#not_a_comment.txt` to match filenames that begin with a literal `#`).
- Files containing whitespace or unusual characters.
- Unicode characters, ensuring that international filenames are handled reliably.

### Case Sensitivity

The matching behavior is configurable:
- **Case‑Sensitive:**  
  When enabled (default), pattern matching respects the case of filenames.
- **Case‑Insensitive:**  
  You can disable case sensitivity by setting the second parameter of the `GitIgnoreManager` constructor to `false`.

### Integration with Multiple Ignore Files

The library allows you to load rules from multiple files. For example, you might want to load both a global `.gitignore` and a project‑specific ignore file (such as `.ctreeignore`). The manager merges and orders these rules based on directory depth, ensuring that more specific rules take precedence.

---

## API Reference

### GitIgnoreManager

- **`__construct(string $basePath, bool $caseSensitive = true, array $additionalIgnoreFiles = [])`**  
  Initializes the manager with the base directory to scan. It loads `.gitignore` files by default and any additional ignore files specified.  
  _Example:_
  ```php
  $manager = new GitIgnoreManager('/path/to/project', true, ['.ctreeignore']);
  ```

- **`accept(SplFileInfo $file): bool`**  
  Returns `true` if the given file is not ignored based on the loaded rules; otherwise, it returns `false`.

- **`matchPattern(string $pattern, string $subject): bool`**  
  Checks if a given ignore pattern matches a subject string. This method is used internally to compare file paths against the regex generated from a glob pattern.

### PatternConverter

- **`convertPatternToRegex(string $pattern): string`**  
  Converts a gitignore‑style glob pattern into a regular expression (without delimiters). This method handles wildcards, escapes, and character classes.

- **`expandBraces(string $pattern): array`**  
  Recursively expands any brace expressions in the pattern (e.g. `*.{js,css,html}`) into an array of individual patterns.

- **`patternToRegex(string $pattern): string`**  
  Converts a gitignore pattern into a complete regular expression, including delimiters and anchors, making it ready for use with `preg_match()`.

---

## Unique Implementation Details

- **Rule Loading and Ordering:**  
  The `GitIgnoreManager` uses Symfony's Finder component to search for ignore files. It then parses each file, stores its rules along with the relative directory where it was found, and orders them by directory depth. This ensures that ignore rules are applied in the correct hierarchy.

- **Advanced Pattern Conversion:**  
  The `PatternConverter` is at the heart of transforming user‑friendly glob patterns into efficient regular expressions. It processes each character, correctly handles escapes, bracket expressions, and nested brace expansions. This detailed conversion ensures that even complex patterns (such as those involving negated character classes or multiple wildcards) behave as expected.

- **Extensive Test Coverage:**  
  The project includes a suite of tests (e.g. `AdvancedGitIgnoreTest`, `AdvancedPatternConverterTest`, `GitIgnoreManagerTest`, and `PatternConverterTest`) covering a broad range of scenarios. These tests validate support for:
  - Deeply nested directory structures.
  - Complex brace expansion and wildcard combinations.
  - Handling of Unicode and special characters.
  - Overlapping rules and negation chains.
  - Case sensitivity configurations.

---

## Summary

GitIgnore PHP provides a comprehensive, powerful API for parsing and applying gitignore‑style rules to file systems. Its support for advanced features like recursive brace expansion, flexible wildcard matching, complex negation, and directory‑only rules make it an ideal tool for projects requiring precise file filtering. By integrating with Symfony Finder and offering configurable matching options, GitIgnore PHP can be seamlessly incorporated into various workflows—from simple file validations to complex repository scanning tasks.

Use the examples and API details above as a reference for integrating and extending the library within your feature development pipelines.