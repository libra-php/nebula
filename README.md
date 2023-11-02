# ![image](https://avatars.githubusercontent.com/u/99982570?s=28&v=4) Nebula: A High-Performance PHP Framework

[![Discord Community](https://discordapp.com/api/guilds/1139362100821626890/widget.png?style=shield)](https://discord.gg/RMhUmHmNak)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Composer](https://github.com/libra-php/nebula/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/libra-php/nebula/actions/workflows/php.yml)


## Introduction

Welcome to Nebula, an advanced PHP framework designed to elevate your web development projects to new heights 🚀

Tailored for engineers who ❤️  php + htmx


## Features

- 🔥 **Blazing Speed**: Leverage Nebula's **htmx** capabilities for lightning-fast user experiences.
- 🥷 **Flexibility**: Craft custom web applications effortlessly with Nebula's extensible architecture.
- 👷 **Under Development**: Nebula is actively evolving to offer the best in class features.
- ❌ **Not for Production**: Please note that Nebula is currently not recommended for production use.


## Getting Started

Follow these steps to kickstart your Nebula journey:

1. **Installation**: Clone the repository and install dependencies using Composer:
   ```bash
   git clone https://github.com/libra-php/nebula.git
   cd nebula
   composer install
   ```

2. **Configuration**: Customize your project by editing configuration files, including database settings. Copy the example environment configuration:
   ```bash
   cp .env.example .env
   ```

3. **Dependencies**: Nebula integrates essential dependencies such as Redis (caching, rate limiting) and MySQL (database).

4. **Development**: Begin building your application by creating controllers, views, and models within the `app` directory. Feel free to modify the framework source code located in the  `src` directory. Start a local development server with:
   ```bash
   ./nebula -s
   ```


## Docker Integration

Simplify your deployment process with Docker:

1. Launch the Nebula stack:
   ```bash
   docker-compose up --build -d
   ```

2. Shut down the stack:
   ```bash
   docker-compose down
   ```

3. Access the application:
   ```bash
   docker-compose exec nebula-app bash
   # Try launching the cli tool
   ./nebula -h
   ```

   `/shared/httpd` = Project base directory

4. Access the database:
   ```bash
   docker-compose exec nebula-mysql bash
   # Next, start mysql using your .env db user + password credentials
   mysql -u root -p
   ```


## Documentation

Note: documentation will be released in v0.0.1


## Benchmarks

We are committed to optimizing Nebula for top-tier performance. Stay tuned for benchmark updates.


## Contributing

Contributions to Nebula are appreciated. If you encounter issues or have enhancement suggestions, open an issue or submit a pull request.


## License

This project is licensed under the [MIT License](https://github.com/libra-php/nebula/blob/main/LICENSE).


## Acknowledgements

Nebula draws inspiration from leading open-source projects including Symfony, Slim Framework, and Laravel.
