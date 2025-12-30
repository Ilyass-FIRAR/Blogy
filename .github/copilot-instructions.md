# Blogy Codebase Instructions for AI Agents

**Project**: Blog Application (Symfony 7.4 with Doctrine ORM)

## Architecture Overview

This is a Symfony micro-kernel project implementing a blog CRUD system with user authentication, comments, and likes functionality.

**Key Components**:
- **Controllers** (`src/Controller/`): Route handlers for articles, comments, auth, and likes
- **Entities** (`src/Entity/`): Doctrine ORM models for User, Article, Comment, Like
- **Repositories** (`src/Repository/`): Database queries (auto-generated Doctrine repos)
- **Templates** (`templates/`): Twig templating for HTML rendering
- **Assets** (`assets/`): JavaScript (Stimulus controllers), CSS using AssetMapper

**Framework Stack**: Symfony 7.4, Doctrine ORM 3.5, Twig, PostgreSQL (via Docker), Stimulus JS

## Critical Workflows

### Development Environment
- **Database setup**: Uses Docker PostgreSQL - start with `docker compose up`
- **Database migrations**: Run `bin/console doctrine:migrations:migrate` after schema changes
- **Clearing cache**: Use `bin/console cache:clear` when config changes aren't reflected
- **Running tests**: `bin/phpunit` (configured in `phpunit.dist.xml`)

### Creating New Features
1. **New Entity**: Create in `src/Entity/` with Doctrine attributes, then `bin/console make:entity`
2. **New Controller**: Place in `src/Controller/`, use `#[Route]` attributes for routing
3. **New Repository**: Auto-generated when Entity is created; add custom queries as methods
4. **Database changes**: Always create migrations with `bin/console make:migration`, then migrate

### Asset Building
- Uses Symfony AssetMapper (not Webpack/Encore) - see `assets/app.js` entry point
- CSS/JS imported via `importmap('app')` in Twig templates
- Stimulus controllers in `assets/controllers/` auto-register via `stimulus_bootstrap.js`

## Project-Specific Patterns

### Service Autowiring
All services in `src/` are auto-registered and auto-wired (see `config/services.yaml`):
```php
// Controllers automatically receive dependencies in constructor
public function __construct(ArticleRepository $repo, Security $security) { }
```

### Routing
Uses attribute-based routing on Controllers (not `config/routes.yaml`):
```php
#[Route('/articles/{id}', name: 'article_show')]
public function show(Article $article) { }
```

### Authentication
- Simple in-memory provider configured (needs User entity implementation)
- Security firewall in `config/packages/security.yaml` uses `users_in_memory` provider
- When adding real auth: implement `UserInterface` on User entity

### Database
- MySQL is configured in `.env` (`DATABASE_URL`) but Docker uses PostgreSQL
- Doctrine annotations use modern PHP 8.2+ attributes (not old DocBlocks)
- Relations use `#[ORM\ManyToOne]`, `#[ORM\OneToMany]` attributes

## Key Files Reference

| File | Purpose |
|------|---------|
| `config/bundles.php` | Loaded bundles (don't add manually - use `composer require`) |
| `config/services.yaml` | Service container config (App\ autodiscovery is enabled) |
| `config/packages/security.yaml` | Auth/firewall rules |
| `config/packages/doctrine.yaml` | Database/ORM config |
| `assets/stimulus_bootstrap.js` | Stimulus JS controller registration |
| `templates/base.html.twig` | Base layout with `importmap('app')` |
| `phpunit.dist.xml` | Test configuration |

## Development Tips

- **Debug queries**: Enable Doctrine query log in dev environment via Monolog
- **Form validation**: Use Symfony Validator attributes on Entities (e.g., `#[Assert\NotBlank]`)
- **Template globals**: Add custom Twig globals in `config/packages/twig.yaml`
- **Stimulus for interactivity**: Use instead of vanilla JS for DOM manipulation
- **Environment variables**: `.env` is committed; `.env.local` is for local overrides

## Testing Standards

- Unit tests in `tests/` directory (mirrors `src/` structure)
- PHPUnit configured with strict error handling (`failOnDeprecation`, `failOnNotice`)
- Test database isolated via `APP_ENV=test` in phpunit config
- Tests auto-discover Controllers and Entities via PSR-4 (`App\Tests\` namespace)

## Common Commands

```bash
# Start development
docker compose up
bin/console server:run

# Database
bin/console doctrine:migrations:make
bin/console doctrine:migrations:migrate
bin/console doctrine:database:create
bin/console doctrine:database:drop --force

# Code generation
bin/console make:entity
bin/console make:controller
bin/console debug:router

# Testing & debugging
bin/phpunit
bin/console debug:config
```
