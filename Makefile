all:
	@echo "Please choose a task."
.PHONY: all

lint: lint-composer lint-yaml lint-xml
.PHONY: lint

lint-composer:
	composer-normalize --dry-run
	composer validate
.PHONY: lint-composer

lint-xml:
	find './tests/.' \( -name '*.xml' \) \
		| while read xmlFile; \
	do \
		XMLLINT_INDENT='    ' xmllint --encode UTF-8 --format "$$xmlFile"|diff - "$$xmlFile"; \
		if [ $$? -ne 0 ]; then echo "$$xmlFile" && exit 1; fi; \
	done

.PHONY: lint-xml

lint-doctrine-xml-schema:
	find './tests/Gedmo/Mapping/Driver/Xml/.' \( -name '*.xml' \) \
		| while read xmlFile; \
	do \
		xmllint --encode UTF-8 --format "$$xmlFile" --schema "./doctrine-mapping.xsd"; \
		if [ $$? -ne 0 ]; then echo "$$xmlFile" && exit 1; fi; \
	done

.PHONY: lint-doctrine-xml-schema

cs-fix-doctrine-xml:
	find './tests/Gedmo/Mapping/Driver/Xml/.' \( -name '*.xml' \) \
		| while read xmlFile; \
	do \
		XMLLINT_INDENT='    ' xmllint --encode UTF-8 --format "$$xmlFile" --output "$$xmlFile"; \
	done
.PHONY: cs-fix-doctrine-xml

lint-yaml:
	yamllint .

.PHONY: lint-yaml
