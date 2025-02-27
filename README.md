# GitIgnore PHP

GitIgnore PHP is a lightweight PHP library for parsing and managing gitignore files. It provides a powerful engine to interpret .gitignore patterns—including advanced features like brace expansion, recursive wildcard matching, and configurable case sensitivity. Use GitIgnore PHP to filter files in a repository according to its ignore rules, integrate with file processing systems, or build custom file parsers.

## Features

- **Gitignore Parsing:**  
  Automatically loads `.gitignore` files from your project directory and parses the ignore rules.

- **Brace Expansion:**  
  Supports advanced brace expansion (e.g. `*.{js,css,html}` expands to `*.js`, `*.css`, and `*.html`), allowing you to write compact and flexible ignore rules.

- **Wildcard Matching:**  
  Supports both single asterisk (`*`) and double asterisk (`**`) wildcards for file and directory matching.

- **Case Sensitivity Options:**  
  Choose between case-sensitive and case-insensitive matching to fit your project needs.

- **Multiple Ignore Files:**  
  By default, only `.gitignore` files are loaded. However, you can pass additional filenames (e.g. `['.ctreeignore']`) in the constructor if you want to load extra ignore rules.

- **Flexible API:**  
  Easily integrate into your projects to filter file lists based on gitignore rules.

## Installation

Install GitIgnore PHP via Composer:

```bash
composer require gregpriday/gitignore-php
```

## Usage

### Basic Usage

Load the gitignore rules from a project directory and check whether a file should be ignored:

```php
<?php

require 'vendor/autoload.php';

use GregPriday\GitIgnore\GitIgnoreManager;
use Symfony\Component\Finder\SplFileInfo;

// Create a GitIgnoreManager instance for your project directory
$manager = new GitIgnoreManager('/path/to/your/project');

// Check a specific file
$file = new SplFileInfo('/path/to/your/project/src/Example.php', 'src', 'src/Example.php');

if ($manager->accept($file)) {
    echo "File is not ignored.\n";
} else {
    echo "File is ignored.\n";
}
```

### Using Additional Ignore Files

By default, only `.gitignore` files are loaded. To load additional ignore files (for example, `.ctreeignore`), pass an array of filenames as the third argument:

```php
<?php

use GregPriday\GitIgnore\GitIgnoreManager;

// Load .gitignore by default and add .ctreeignore as an extra ignore file
$additionalFiles = ['.ctreeignore'];
$manager = new GitIgnoreManager('/path/to/your/project', true, $additionalFiles);
```

### Brace Expansion

One of the unique features of GitIgnore PHP is its support for brace expansion. For example, the pattern:

```
*.{js,css,html}
```

automatically expands to:

- `*.js`
- `*.css`
- `*.html`

You can test brace expansion directly using the `PatternConverter` class:

```php
<?php

use GregPriday\GitIgnore\PatternConverter;

$converter = new PatternConverter();
$expanded = $converter->expandBraces('*.{js,css,html}');
print_r($expanded);
// Output:
// Array
// (
//     [0] => *.js
//     [1] => *.css
//     [2] => *.html
// )
```

### Filtering a Git Repository

GitIgnore PHP can be used to filter files from a Git repository based on its ignore rules. For example, to list all files that are not ignored:

```php
<?php

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

This script uses Symfony's Finder component to gather all files in the repository and then filters out the ones ignored by your `.gitignore` (or any additional ignore files you specify).

## API Reference

### GitIgnoreManager

#### __construct(string $basePath, bool $caseSensitive = true, array $additionalIgnoreFiles = [])
Creates a new instance of GitIgnoreManager.
- **$basePath:** The directory in which to scan for ignore files.
- **$caseSensitive:** If true, matching is case sensitive.
- **$additionalIgnoreFiles:** An array of additional ignore file names to load (default is only `.gitignore`).

#### accept(SplFileInfo $file): bool
Determines whether the given file should be accepted (i.e. is not ignored) based on the loaded ignore rules.

#### matchPattern(string $pattern, string $subject): bool
Checks if the specified pattern matches the given subject string.

### PatternConverter

#### convertPatternToRegex(string $pattern): string
Converts a gitignore-style pattern into a regular expression.

#### expandBraces(string $pattern): array
Recursively expands brace expressions in a pattern.

#### patternToRegex(string $pattern): string
Converts a gitignore pattern into a complete regular expression with delimiters.

## Code Formatting

This project uses [Laravel Pint](https://github.com/laravel/pint) for code formatting. To format your code, run:

```bash
composer format
```

This will ensure your code adheres to the Laravel coding style.

## Testing

To run the tests, execute:

```bash
composer test
```

or

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Make sure your code follows the Laravel coding standards.
4. Run tests and format your code (`composer format`).
5. Submit a pull request with a clear explanation of your changes.

## License

This project is licensed under the MIT License.