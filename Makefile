DEPLOYMENT_IP = 
DEPLOYMENT_PATH = 
DEPLOYMENT_TEST = $(DEPLOYMENT_PATH)-test
DEPLOYMENT_FILES = src vendor script index.php
DEPLOYMENT_USER = $(shell whoami)

ifeq ($(shell command -v composer 2> /dev/null),)
	ifeq ($(wildcard composer.phar),)
		call :get_composer
	endif
	COMPOSER := php composer.phar
else
	COMPOSER := composer
endif

dev:
	php -S localhost:8000

install:
	$(COMPOSER) install --ignore-platform-reqs

install_prod:
	$(COMPOSER) install --no-dev --optimize-autoloader

update:
	$(COMPOSER) update

push: env_example check install_prod
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_PATH)
	$(COMPOSER) install --ignore-platform-reqs

push_test: env_example check install_prod
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_TEST)
	$(COMPOSER) install --ignore-platform-reqs

push_migrations: env_example check install_prod
	rsync -zrPLp --chmod=ug=rwX,o=rX --delete $(DEPLOYMENT_FILES) migrations migrations.php $(DEPLOYMENT_USER)@$(DEPLOYMENT_IP):$(DEPLOYMENT_PATH)
	$(COMPOSER) install --ignore-platform-reqs

get_composer:
	php -r "copy('https://getcomposer.org/download/latest-stable/composer.phar', 'composer.phar');"
	$(COMPOSER) install --ignore-platform-reqs

env_example:
	@cp .env .env.example && sed -i 's/=.*/=/g' .env.example

check:
	@echo "$(YELLOW)Checking PHP syntax errors...$(RESET)"
	@find src scripts -type f -name "*.php" -print0 | xargs -0 -n1 php -l || { echo "$(RED)Syntax errors found! Aborting.$(RESET)"; exit 1; }
	@echo "$(YELLOW)Running PHPStan for semantic checks...$(RESET)"
	@vendor/bin/phpstan analyse src scripts --level 5 || { echo "$(RED)PHPStan found errors! Aborting.$(RESET)"; exit 1; }
	@echo "$(GREEN)All checks passed!$(RESET)"

test:
	# TODO: Add tests