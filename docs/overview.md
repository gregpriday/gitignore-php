# GitIgnore PHP Library Guide

This document provides a concise overview of Greg Priday's GitIgnore PHP and its features, along with detailed examples. It is intended to guide an LLM (large language model) in understanding how to use the library for feature development.

---

## Overview

**GitIgnore PHP** is a PHP library that:
- Parses and interprets `.gitignore` files.
- Supports advanced glob patterns including wildcards (`*`, `**`, `?`) and brace expansion (e.g. `*.{js,css,html}`).
- Offers configurable case sensitivity.
- Allows optional loading of additional ignore files (e.g. `.ctreeignore`).
- Integrates seamlessly with file systems using Symfony's Finder component.

---

## Installation

Install via Composer:

```bash
composer require gregpriday/gitignore-php
```

---

## Core Features and Usage Examples

### 1. Basic Usage

Load a project’s `.gitignore` rules and test if a file is ignored:

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

---

### 2. Loading Additional Ignore Files

By default, only `.gitignore` files are loaded. To include extra ignore files (for example, `.ctreeignore`), pass an array as the third parameter:

```php
use GregPriday\GitIgnore\GitIgnoreManager;

$manager = new GitIgnoreManager('/path/to/your/project', true, ['.ctreeignore']);
```

---

### 3. Brace Expansion

Brace expansion lets you define multiple patterns in one compact rule.

**Example Pattern:**
```
*.{js,css,html}
```

**Expands to:**
- `*.js`
- `*.css`
- `*.html`

Test this using the PatternConverter:

```php
use GregPriday\GitIgnore\PatternConverter;

$converter = new PatternConverter();
$expanded = $converter->expandBraces('*.{js,css,html}');
// Expected output: ['*.js', '*.css', '*.html']
print_r($expanded);
```

---

### 4. Wildcard Matching

GitIgnore PHP supports:
- `*` – matches any sequence of characters except directory separators.
- `**` – matches any sequence of characters across directory boundaries.
- `?` – matches a single character.

**Example:**  
The pattern `src/**/*.php` matches:
- `src/File.php`
- `src/Controllers/HomeController.php`
- `src/Models/User.php`

---

### 5. Filtering a Git Repository

Combine GitIgnore PHP with Symfony Finder to filter files:

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

## API Reference

### GitIgnoreManager

- **__construct(string $basePath, bool $caseSensitive = true, array $additionalIgnoreFiles = [])**  
  Initializes the manager. By default, loads `.gitignore` files; additional ignore files may be specified.

- **accept(SplFileInfo $file): bool**  
  Returns `true` if the file is not ignored according to the loaded rules.

- **matchPattern(string $pattern, string $subject): bool**  
  Checks if a given pattern matches the subject string.

### PatternConverter

- **convertPatternToRegex(string $pattern): string**  
  Converts a gitignore-style pattern into a regex (without delimiters).

- **expandBraces(string $pattern): array**  
  Recursively expands brace expressions into an array of patterns.

- **patternToRegex(string $pattern): string**  
  Converts a gitignore pattern into a complete regular expression with anchors.

---

## Noteworthy Features

- **Advanced Brace Expansion:**  
  Write compact patterns like `*.{js,css,html}` to cover multiple file types.

- **Flexible Wildcard Handling:**  
  Supports both single (`*`) and double asterisks (`**`) for matching across directories.

- **Optional Additional Ignore Files:**  
  Load extra ignore files (e.g. `.ctreeignore`) by passing them into the constructor.

- **Integration with Symfony Finder:**  
  Easily filter entire file sets from a project or repository.

---

## Summary

GitIgnore PHP provides a concise, powerful API to determine which files should be ignored based on gitignore-style rules. Its support for advanced features like brace expansion and flexible wildcard matching makes it a robust tool for file filtering in complex projects. Use the examples above as a quick reference for integrating this library into your feature development workflows.