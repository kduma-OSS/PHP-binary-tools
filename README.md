# Binary Tools

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kduma/binary-tools.svg?style=flat-square)](https://packagist.org/packages/kduma/binary-tools)
[![Tests](https://img.shields.io/github/actions/workflow/status/kduma-OSS/PHP-binary-tools/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kduma-OSS/PHP-binary-tools/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/kduma/binary-tools.svg?style=flat-square)](https://packagist.org/packages/kduma/binary-tools)

A PHP library for binary data manipulation and encoding/decoding operations. This library provides safe, efficient tools for working with binary data, including UTF-8 validation and secure string comparisons.

Check full documentation: [opensource.duma.sh/libraries/php/binary-tools](https://opensource.duma.sh/libraries/php/binary-tools)

## Installation

```bash
composer require kduma/binary-tools
```

## Requirements

- PHP 8.4+
- `ext-mbstring` - For UTF-8 validation
- `ext-hash` - For secure string comparisons

## Features

- **Safe binary data manipulation** with bounds checking
- **UTF-8 string validation** for text data
- **Multiple encoding formats** (hex, base64)
- **Secure string comparison** using `hash_equals()`
- **Big-endian integer support** for network protocols
- **Position tracking** for streaming operations

## Core Classes

### BinaryString

Immutable wrapper for binary data with conversion and comparison methods.

### BinaryWriter

Stream-like writer for building binary data structures.

### BinaryReader

Stream-like reader for parsing binary data with position tracking.

## License

This library is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
