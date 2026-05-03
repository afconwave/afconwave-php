# PHP SDK Deployment & Publishing Guide

This guide explains how to publish the `afconwave/php` package to Packagist (the default Composer package repository).

## Prerequisites
1. Ensure this `php` folder is extracted into its own dedicated GitHub repository (e.g., `github.com/afconwave/afconwave-php`). Packagist strictly requires packages to live at the root of a Git repository.
2. You must have an account on [Packagist.org](https://packagist.org).
3. Update `composer.json` with the correct `version` and ensure dependencies are correct.

## Pre-Flight Checklist
- [ ] Run `composer install` to verify dependencies.
- [ ] Run `php test.php` to ensure the core classes load without syntax errors.
- [ ] Ensure `composer.json` has the correct `name` set to `afconwave/php`.

## Publishing Steps

### Initial Setup (One-Time)
1. Go to [Packagist Submit Page](https://packagist.org/packages/submit).
2. Paste the URL of your public GitHub repository (`https://github.com/afconwave/afconwave-php`).
3. Click **Check** and then **Submit**.

### Releasing New Versions
Packagist integrates directly with GitHub releases and tags. You do NOT upload files manually.
To release a new version (e.g., v1.0.1):

1. **Commit your changes:**
   ```bash
   git add .
   git commit -m "Release v1.0.1: Added new crypto features"
   ```

2. **Create a Git Tag:**
   Composer looks for Git tags that follow semantic versioning.
   ```bash
   git tag v1.0.1
   git push origin main --tags
   ```

3. **Verify:**
   Packagist will automatically detect the new tag within a few minutes via its GitHub Webhook. Verify the new version is available by running `composer require afconwave/php` in a test project.
