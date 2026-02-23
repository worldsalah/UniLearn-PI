# UniLearn Project - Team Collaboration Guide

## 🚀 Quick Start for All Team Members

### Prerequisites
- PHP 8.1+ 
- XAMPP (MySQL, Apache)
- Git
- Composer
- VS Code (recommended)

### 1. Initial Setup (One-time)

```bash
# Clone the repository
git clone <repository-url>
cd UniLearn-PI-main

# Install dependencies
composer install

# Create test database in XAMPP MySQL
# Database name: unilearn_test
# User: root, Password: (empty)

# Copy test environment file
cp .env.test .env.local

# Install database
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
php bin/console doctrine:fixtures:load --env=test
```

### 2. Daily Workflow

```bash
# 1. Pull latest changes
git pull origin dev

# 2. Create/update your feature branch
git checkout -b feat/your-feature-name

# 3. Work on your feature
# ... make changes ...

# 4. Run quality checks locally
composer install
vendor/bin/php-cs-fixer fix --diff --verbose
vendor/bin/phpstan analyse
vendor/bin/phpunit

# 5. Commit and push
git add .
git commit -m "feat: add user authentication system"
git push origin feat/your-feature-name

# 6. Create Pull Request on GitHub
```

## 🌿 Branch Strategy

### Protected Branches
- **`main`**: Production-ready code (merge from `dev` only)
- **`dev`**: Development integration branch (merge from feature branches)

### Feature Branches (5 Students)
Each student works on their dedicated feature branch:

1. **Student 1**: `feat/auth` - Authentication & Authorization
2. **Student 2**: `feat/course-module` - Course management system
3. **Student 3**: `feat/quiz` - Quiz functionality
4. **Student 4**: `feat/ui` - User interface & frontend
5. **Student 5**: `feat/backend` - Backend services & APIs

### Branch Naming Conventions
- **Features**: `feat/description`
- **Bug fixes**: `fix/description`
- **Hotfixes**: `hotfix/description`
- **Documentation**: `docs/description`

## 🔄 Pull Request Workflow

### 1. Creating a PR
1. Push your feature branch to GitHub
2. Go to GitHub and click "New Pull Request"
3. Select your feature branch → `dev` branch
4. Fill PR template:
   - Clear title (e.g., "feat: Add user login system")
   - Detailed description of changes
   - Testing performed
   - Screenshots if UI changes

### 2. PR Requirements
- ✅ All CI checks must pass (tests, linting, static analysis)
- ✅ At least 1 code review approval
- ✅ No merge conflicts
- ✅ Documentation updated if needed

### 3. Code Review Process
- Each student reviews at least 1 other PR
- Focus on: code quality, security, performance
- Be constructive and specific in feedback
- Request changes if needed

## 🧪 Local Testing Commands

### Run All Quality Checks
```bash
# Install dependencies
composer install

# Code style check and fix
vendor/bin/php-cs-fixer fix --diff --verbose

# Static analysis
vendor/bin/phpstan analyse

# Run tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --coverage-text --coverage-html=coverage
```

### Individual Commands
```bash
# Database operations
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
php bin/console doctrine:fixtures:load --env=test

# Clear cache
php bin/console cache:clear --env=test

# Create new migration
php bin/console make:migration

# Create new controller
php bin/console make:controller

# Create new entity
php bin/console make:entity
```

## 🛡️ Best Practices

### DO ✅
- **Never commit** `.env.local`, `.env`, secrets, or API keys
- **Always run** local tests before pushing
- **Write descriptive commit messages** (conventional commits)
- **Create PRs** for all changes (no direct pushes to `dev`/`main`)
- **Review code** thoroughly before approving
- **Update documentation** when adding features
- **Use meaningful variable and function names**

### DON'T ❌
- **Don't commit** directly to `dev` or `main` branches
- **Don't push** broken code or failing tests
- **Don't include** sensitive data in commits
- **Don't merge** without CI approval
- **Don't use** `git push --force` on shared branches
- **Don't commit** vendor files or generated content

## 📋 Commit Message Format

Use conventional commits:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, missing semicolons)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(auth): add user login functionality

- Implement login form
- Add session management
- Create authentication service

Fixes #123
```

## 🚨 Troubleshooting

### Common Issues

**1. CI Fails on Tests**
```bash
# Check local test environment
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test
vendor/bin/phpunit
```

**2. PHPStan Errors**
```bash
# Run PHPStan locally to see details
vendor/bin/phpstan analyse --debug
```

**3. Code Style Issues**
```bash
# Auto-fix most issues
vendor/bin/php-cs-fixer fix
# Check remaining issues
vendor/bin/php-cs-fixer fix --dry-run --diff
```

**4. Database Connection Issues**
- Ensure XAMPP MySQL is running
- Check `.env.test` database configuration
- Verify database exists: `unilearn_test`

### Getting Help
1. Check this guide first
2. Look at GitHub Actions CI logs
3. Ask in team chat/Slack
4. Create issue with detailed error description

## 📊 CI/CD Pipeline

### What CI Checks
- **PHP 8.1 & 8.2** matrix testing
- **MySQL 8.0** database setup
- **Composer** dependency installation
- **Database migrations** and fixtures
- **PHPUnit** test execution
- **PHPStan** static analysis (Level 8)
- **PHP CS Fixer** code style checking
- **Code coverage** reporting

### CI Triggers
- **Push** to `dev` or `main` branches
- **Pull Request** to `dev` or `main` branches

### Merge Protection
- **Required status checks**: All CI must pass
- **Required reviews**: At least 1 approval
- **Branch protection**: No force pushes to protected branches

## 🎯 Project Structure

```
UniLearn-PI-main/
├── .github/workflows/ci.yml    # GitHub Actions CI
├── .env.test                    # Test environment config
├── .php_cs.dist                 # PHP CS Fixer rules
├── phpstan.neon                 # PHPStan configuration
├── src/                         # Application source code
├── tests/                       # PHPUnit tests
├── config/                      # Symfony configuration
├── migrations/                  # Doctrine migrations
├── templates/                   # Twig templates
└── public/                      # Web root
```

## 📞 Team Communication

### Daily Standup (15 min)
- What did you complete yesterday?
- What will you work on today?
- Any blockers or issues?

### Weekly Review (30 min)
- Review merged PRs
- Discuss upcoming features
- Address technical debt
- Plan next sprint

### Emergency Contacts
- **Project Lead**: [Contact info]
- **Tech Lead**: [Contact info]
- **DevOps**: [Contact info]

---

**Remember**: Quality over speed. It's better to take extra time for proper testing and code review than to merge broken code. 🚀
