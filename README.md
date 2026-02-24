<div align="center">

# PHP API Template 🚀

Start building your APIs in PHP effortlessly with this robust and feature-rich template! 🎉
Designed to save time while encouraging best practices, this template includes a static code analyzer, helpful utility classes, and a clean architecture to keep your code organized and maintainable. 🌟

</div>

## ✨ Features

- **Best Practices**: Follows PHP standards and clean coding principles.
- **Static Code Analysis**: Integrated with PHPStan (standalone `.phar`) for detecting code issues early.
- **Hexagonal Architecture**: Promotes scalability and maintainability.
- **SOLID Principles**: Ensures strong, reliable design patterns.
- **Environment Validation**: Automatic validation of required env vars and boolean types via `Envs` class.
- **Security**: CORS origin control, secure cookies, JWT authentication middleware.
- **Utility Classes**: Packed with reusable and helpful classes to jumpstart development.

## 🛠️ Technologies Used

- [PHPStan](https://phpstan.org/) – Static code analyzer (standalone `.phar`, not a Composer dependency).
- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) – Environment variable management.
- [firebase/php-jwt](https://github.com/firebase/php-jwt) – JSON Web Token encoding/decoding.

## ⚙️ Requirements

- **PHP 8.0** or higher.

## 🚀 Installation

Clone the repository and run:

```shell
make install
```

This will automatically download `composer.phar` if not present and install all dependencies.

## 🧑‍💻 Available Commands

All available commands are defined in the `Makefile`. Check it for deployment, static analysis, dependency management and more. 📦

## 🔧 Configuration

Copy `.env.example` to `.env` and fill in the required values:

```shell
cp .env.example .env
```

Or simply run `make env_example` which will create the `.env` from `.env.example` if it doesn't exist.

Required environment variables: `DEV_MODE`, `COOKIE_SECURE`, `CORS_ORIGIN`. Check `.env.example` for all available options.

## 🤝 Contributing

Contributions are welcome! If you'd like to improve this template or suggest new features, feel free to open an issue or submit a pull request. 🙌

Before contributing, ensure your code follows PHP coding standards and passes `make check`.

## 📜 License

This project is licensed under the MIT License. Feel free to use, modify, and distribute it as you see fit. 😊