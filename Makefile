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
	php -S localhost:8000

# Install dependencies (development)
install: get_composer
	$(COMPOSER) install

# Install dependencies (production, no dev)
install_prod: get_composer
	$(COMPOSER) install --no-dev --optimize-autoloader

# Update dependencies
update:
	$(COMPOSER) update

# Deploy to production server
push: env_example check install_prod
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_PATH)
	$(COMPOSER) install

# Deploy to test server
push_test: env_example check install_prod
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_TEST)
	$(COMPOSER) install

# Deploy with migrations to production server
push_migrations: env_example check install_prod
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) migrations migrations.php $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_PATH)
	$(COMPOSER) install

# Download composer.phar if not present
get_composer:
	if [ ! -f composer.phar ]; then \
		php -r "copy('https://getcomposer.org/download/$(COMPOSER_VERSION)/composer.phar', 'composer.phar');"; \
	fi

# Download phpstan.phar if not present
get_phpstan:
	if [ ! -f phpstan.phar ]; then \
		php -r "copy('https://github.com/phpstan/phpstan/releases/download/$(PHPSTAN_VERSION)/phpstan.phar', 'phpstan.phar');"; \
	fi

# Sync .env to .env.example (empty values) or create .env from .env.example
env_example:
	@if [ -f .env ] && [ -s .env ]; then \
		sed 's/^\([A-Za-z_][A-Za-z0-9_]*\)=.*/\1=/' .env > .env.example; \
		echo ".env.example updated."; \
	else \
		cp .env.example .env; \
		echo ".env created from .env.example."; \
	fi

# Run syntax and static analysis checks
check: get_phpstan
	@echo "$(YELLOW)Checking PHP syntax errors...$(RESET)"
	@find src scripts -type f -name "*.php" -print0 | xargs -0 -n1 php -l || { echo "$(RED)Syntax errors found! Aborting.$(RESET)"; exit 1; }
	@echo "$(YELLOW)Running PHPStan for semantic checks...$(RESET)"
	@$(PHPSTAN) analyse src scripts --level 5 || { echo "$(RED)PHPStan found errors! Aborting.$(RESET)"; exit 1; }
	@echo "$(GREEN)All checks passed!$(RESET)"

test:
	# TODO: Add tests