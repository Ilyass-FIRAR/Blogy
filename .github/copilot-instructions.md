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
- **Database setup**: Uses Docker PostgreSQL (configured in `compose.yaml`, image `postgres:16-alpine`) - start with `docker compose up`
- **Database setup**: After containers start, run `bin/console doctrine:database:create` and `bin/console doctrine:migrations:migrate`
- **Database migrations**: Run `bin/console doctrine:migrations:migrate` after schema changes
- **Clearing cache**: Use `bin/console cache:clear` when config changes aren't reflected
- **Running tests**: `bin/phpunit` (configured in `phpunit.dist.xml`, uses `APP_ENV=test` database)

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

### Data Model (Entity Relationships)
- **User** → owns many **Articles**, **Comments**, **Likes** (one-to-many with orphan removal)
- **Article** → has many **Comments** and **Likes** (one-to-many with orphan removal)
- **Comment** → belongs to one **User** and one **Article** (many-to-one, non-nullable)
- **Like** → belongs to one **User** and one **Article** (many-to-one, non-nullable); unique constraint prevents duplicate likes
- All entities have `createdAt` DateTime field auto-set in constructor

### Service Autowiring
All services in `src/` are auto-registered and auto-wired (see `config/services.yaml` with `App\:` resource):
```php
// Controllers automatically receive dependencies in constructor
public function __construct(ArticleRepository $repo, Security $security) { }
```

### Routing
Uses attribute-based routing on Controllers (not `config/routes.yaml`):
```php
#[Route('/articles', name: 'app_articles')]
public function list(ArticleRepository $articleRepository): Response { }
```
Current routes: `/home` (HomeController), `/articles` (ArticleController list)

### Authentication
- **User entity fully implements** `UserInterface` and `PasswordAuthenticatedUserInterface` (see `src/Entity/User.php`)
- Security firewall in `config/packages/security.yaml` uses `users_in_memory` provider (placeholder config)
- To enable real authentication: replace `users_in_memory` provider with entity provider pointing to User class (e.g., `entity: { class: App\Entity\User, property: email }`)
- Password hashing configured to auto-detect based on `PasswordAuthenticatedUserInterface`
- User roles stored in `roles` JSON array field; timestamps auto-set in constructor

### Database & ORM
- **PostgreSQL 16** configured in `compose.yaml` (credentials in `.env`)
- Doctrine ORM configured for attribute-based mapping (modern PHP 8.2+ `#[ORM\...]` attributes, not DocBlocks)
- Entity relations configured with `#[ORM\ManyToOne]`, `#[ORM\OneToMany]` and cascade rules (e.g., `orphanRemoval: true` on collections)
- Naming strategy uses `underscore_number_aware` (see `config/packages/doctrine.yaml`)
- Unique constraints defined at entity level (e.g., `Like` uses `UNIQ_USER_ARTICLE` composite constraint)

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

- **Debug queries**: Enable Doctrine query log in dev environment via Monolog (see `config/packages/monolog.yaml`)
- **Form validation**: Use Symfony Validator attributes on Entities (e.g., `#[Assert\NotBlank]`) - remember to validate in controllers
- **Template globals**: Add custom Twig globals in `config/packages/twig.yaml` to expose app-wide data
- **Stimulus for interactivity**: Preferred over vanilla JS; register controllers in `assets/controllers/`; auto-load via `stimulus_bootstrap.js`
- **Environment variables**: `.env` is committed with defaults; `.env.local` for local overrides (not committed)
- **Repository queries**: Custom queries live in `src/Repository/*Repository.php` classes; inject into controllers for reuse
- **Timestamps**: All entities auto-set `createdAt` in constructor; consider adding `updatedAt` field and event listener for UPDATE tracking

## Current Implementation Status

**Complete**:
- All 4 core entities (User, Article, Comment, Like) with full relationships and cascade rules
- Doctrine migrations infrastructure (initial migration in `migrations/`)
- Service container auto-wiring and Controller dependency injection
- PostgreSQL database with Docker orchestration

**Minimal/Placeholder**:
- Controllers: Only 2 endpoints exist (`HomeController`, `ArticleController::list`); need CRUD operations and comment/like endpoints
- Authentication: User entity ready but firewall uses `users_in_memory`; needs provider switch to real User entity
- Templates: Only `home.html.twig` and `articles/list.html.twig` exist; need detail pages, forms, auth views
- Validation: No validator attributes on entities yet
- Tests: Only `bootstrap.php` exists; no test cases yet

**Next Development Priorities**:
1. Switch security to entity provider (replace `users_in_memory` in `config/packages/security.yaml`)
2. Add login/registration endpoints and forms
3. Add article CRUD endpoints (show, create, update, delete)
4. Add comment/like functionality endpoints
5. Build detail and form templates with Twig

## Testing Standards

- Unit tests in `te (PostgreSQL container + Symfony dev server)
docker compose up
bin/console server:run  # or 'symfony server:start'

# Database initialization (first time)
bin/console doctrine:database:create
bin/console doctrine:migrations:migrate

# Database changes
bin/console doctrine:migrations:make    # After modifying entities
bin/console doctrine:migrations:migrate

# Database cleanup
bin/console doctrine:database:drop --force

# Code generation
bin/console make:entity
bin/console make:controller
bin/console debug:router                # List all routes
bin/console debug:autowiring            # Verify service dependencies

# Testing & debugging
bin/phpunit                             # Run all tests
bin/phpunit --testdox tests/            # List test names
bin/console debug:config doctrine       # Show Doctrine config
bin/console cache:clear                 # Clear app cache
# Code generation
bin/console make:entity
bin/console make:controller
bin/console debug:router

# Testing & debugging
bin/phpunit
bin/console debug:config
```
