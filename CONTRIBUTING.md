# Contributing to Filament Studio

Thank you for considering contributing to Filament Studio! We welcome contributions from the community and are grateful for any help you can provide.

## Bug Reports

If you discover a bug, please create an issue on GitHub with the following information:

- A clear, descriptive title
- Steps to reproduce the issue
- Expected vs. actual behavior
- Your environment (PHP version, Laravel version, Filament version)
- Any relevant error messages or stack traces

## Feature Requests

Feature requests are welcome. Please open an issue and describe:

- The problem you're trying to solve
- Your proposed solution
- Any alternatives you've considered

## Pull Requests

### Getting Started

1. Fork the repository
2. Clone your fork locally
3. Install dependencies:

```bash
composer install
```

4. Create a new branch for your feature or fix:

```bash
git checkout -b feature/your-feature-name
```

### Development Workflow

1. **Write tests first.** We use [Pest](https://pestphp.com/) for testing. Place unit tests in `tests/Unit/` and feature tests in `tests/Feature/`.

2. **Run the test suite** to make sure everything passes:

```bash
vendor/bin/pest
```

3. **Follow the existing code style.** We use [Laravel Pint](https://laravel.com/docs/pint) for formatting:

```bash
vendor/bin/pint
```

4. **Write meaningful commit messages** that explain *why* a change was made, not just what changed.

### Pull Request Guidelines

- Keep PRs focused on a single change. Avoid mixing unrelated changes.
- Update or add tests for any new functionality or bug fixes.
- Ensure the full test suite passes before submitting.
- Run Pint to format your code before committing.
- Reference any related issues in your PR description.

### Adding a New Field Type

1. Create your field type class in `src/FieldTypes/Types/`, extending `AbstractFieldType`
2. Register it in `FilamentStudioServiceProvider::packageRegistered()`
3. Add a corresponding test in `tests/Unit/FieldTypes/Types/`

### Adding a New Panel Type

1. Create your panel class in `src/Panels/Types/`, extending `AbstractDmmPanel`
2. Create the associated widget in `src/Widgets/`
3. Create the Blade view in `resources/views/widgets/`
4. Register the panel in `FilamentStudioServiceProvider::packageRegistered()`
5. Add tests for both the panel and widget

## Code of Conduct

Please be respectful and constructive in all interactions. We are committed to providing a welcoming and inclusive experience for everyone.

## Questions?

If you have questions about contributing, feel free to open a discussion on GitHub.
