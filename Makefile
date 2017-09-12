.PHONY: bump test

VERS := $(shell grep 'VERSION =' -m 1 lib/Gedmo/DoctrineExtensions.php | sed "s/[^0-9\.]*//g")

test:
	bin/phpunit -c tests

bump:
	@if [ -z "$(VERSION)" ]; then echo "Provide version like: 'VERSION=$(VERS) make bump'"; exit 1; fi
	@echo "bumping version from: $(VERS) to $(VERSION)"
	@sed -i.bak 's/$(VERS)/$(VERSION)/g' README.md
	@sed -i.bak 's/$(VERS)/$(VERSION)/g' couscous.yml
	@sed -i.bak 's/$(VERS)/$(VERSION)/g' lib/Gedmo/DoctrineExtensions.php
	@find . -name '*.bak' | xargs rm
