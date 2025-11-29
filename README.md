# 🌍 Travel Clicker

A **marketplace simulation idle game** built as a learning project for Symfony backend development.

## About

Travel Clicker is a Cookie Clicker-style idle game with a travel/marketplace theme. This project serves as a hands-on learning experience for building enterprise-grade game architecture with modern web technologies.

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Symfony 7.4 (PHP 8.4) |
| **Database** | PostgreSQL |
| **Frontend** | Svelte 5 (Runes) |
| **Styling** | TailwindCSS 4 |
| **Real-Time** | Mercure (SSE) |
| **Build Tool** | Vite |

## Prerequisites

- PHP 8.4+
- Composer
- Node.js 20+
- PostgreSQL (for database)
- Symfony CLI (optional, for local development server)

## Getting Started

```bash
# Clone the repository
git clone <repository-url>
cd travel_clicker

# Install dependencies
make install
# Or manually:
# composer install
# npm install

# Configure environment
cp .env .env.local
# Edit .env.local with your database credentials

# Create database
php bin/console doctrine:database:create

# Build frontend assets
npm run build

# Start development servers (in separate terminals)
symfony serve        # Terminal 1: Symfony server (port 8000)
npm run dev          # Terminal 2: Vite dev server (port 5173)
```

## Development Commands

```bash
# Development
make dev              # Show dev server instructions
npm run dev           # Start Vite dev server with HMR
npm run build         # Build production assets

# Code Quality
make fix              # Run CS-Fixer & PHPStan
make stan             # Run PHPStan only (level 8)
make cs               # Run PHP-CS-Fixer only
make cs-check         # Check code style without fixing

# Testing
make tests            # Run PHPUnit tests

# Database
make db-create        # Create database
make db-migrate       # Run migrations
make db-diff          # Generate migration from entities

# Utilities
make clean            # Clear cache and build files
make help             # Show all available commands
```

## Project Structure

```
src/
├── Domain/           # Pure business logic (Entities, Value Objects, Services)
├── Application/      # Use cases (Commands, Handlers, DTOs)
├── Infrastructure/   # Technical implementations (Repositories, Mercure)
└── UserInterface/    # Controllers & API endpoints

assets/
├── app.js            # Main entry point
├── bootstrap.js      # Stimulus & Svelte initialization
├── styles/
│   └── app.css       # TailwindCSS styles
├── svelte/
│   └── controllers/  # Svelte components
└── controllers/      # Stimulus controllers
```

## Architecture

This project follows **Domain-Driven Design** with a server-authoritative game model. Key principles:

- **Server Authority**: Backend holds the absolute truth for game state
- **Optimistic UI**: Frontend reacts instantly while syncing with server
- **Real-Time**: Mercure (SSE) for pushing updates to clients
- **CQRS**: Commands for writes, DTOs for reads

For detailed technical guidelines and coding standards, see [.github/copilot-instructions.md](.github/copilot-instructions.md).

## License

MIT
