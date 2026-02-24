DEPLOYMENT_IP =
DEPLOYMENT_PATH =
DEPLOYMENT_TEST = $(DEPLOYMENT_PATH)-test
DEPLOYMENT_FILES = src vendor script index.php
DEPLOYMENT_USER = $(shell whoami)

COMPOSER = php composer.phar
COMPOSER_VERSION = 2.9.5
PHPSTAN = php phpstan.phar
PHPSTAN_VERSION = 2.1.40

# Start local development server
dev:
	@echo "🌐 Starting development server on http://localhost:8000..."
	php -S localhost:8000

# Install dependencies (development)
install: get_composer
	@echo "📦 Installing dependencies..."
	@$(COMPOSER) install
	@echo "✅ Dependencies installed!"

# Install dependencies (production, no dev)
install_prod: get_composer
	@echo "📦 Installing production dependencies..."
	@$(COMPOSER) install --no-dev --optimize-autoloader
	@echo "✅ Production dependencies installed!"

# Update dependencies
update:
	@echo "🔄 Updating dependencies..."
	@$(COMPOSER) update
	@echo "✅ Dependencies updated!"

# Deploy to production server
push: env_example check install_prod
	@echo "🚀 Deploying to production..."
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_PATH)
	@$(COMPOSER) install
	@echo "✅ Deployed to production!"

# Deploy to test server
push_test: env_example check install_prod
	@echo "🧪 Deploying to test server..."
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_TEST)
	@$(COMPOSER) install
	@echo "✅ Deployed to test!"

# Deploy with migrations to production server
push_migrations: env_example check install_prod
	@echo "🚀 Deploying with migrations to production..."
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) migrations migrations.php $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_PATH)
	@$(COMPOSER) install
	@echo "✅ Deployed with migrations!"

# Download composer.phar if not present
get_composer:
	@if [ ! -f composer.phar ]; then \
		echo "⬇️  Downloading composer.phar v$(COMPOSER_VERSION)..."; \
		php -r "copy('https://getcomposer.org/download/$(COMPOSER_VERSION)/composer.phar', 'composer.phar');"; \
		echo "✅ composer.phar downloaded!"; \
	fi

# Download phpstan.phar if not present
get_phpstan:
	@if [ ! -f phpstan.phar ]; then \
		echo "⬇️  Downloading phpstan.phar v$(PHPSTAN_VERSION)..."; \
		php -r "copy('https://github.com/phpstan/phpstan/releases/download/$(PHPSTAN_VERSION)/phpstan.phar', 'phpstan.phar');"; \
		echo "✅ phpstan.phar downloaded!"; \
	fi

# Sync .env to .env.example (empty values) or create .env from .env.example
env_example:
	@if [ -f .env ] && [ -s .env ]; then \
		sed 's/^\([A-Za-z_][A-Za-z0-9_]*\)=.*/\1=/' .env > .env.example; \
		echo "📄 .env.example updated."; \
	else \
		cp .env.example .env; \
		echo "📄 .env created from .env.example."; \
	fi

# Run syntax and static analysis checks
check: get_phpstan
	@echo "🔍 Checking PHP syntax errors..."
	@find src scripts -type f -name "*.php" -print0 | xargs -0 -n1 php -l || { echo "❌ Syntax errors found! Aborting."; exit 1; }
	@echo "🔍 Running PHPStan for semantic checks..."
	@$(PHPSTAN) analyse src scripts --level 5 || { echo "❌ PHPStan found errors! Aborting."; exit 1; }
	@echo "✅ All checks passed!"

test:
	# TODO: Add tests