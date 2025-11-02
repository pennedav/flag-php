
.PHONY: analyze
analyze:
	./vendor/bin/phpstan analyse -l 10 ./demo.php ./src/*.php
