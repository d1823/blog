.PHONY: open serve watch

open:
	xdg-open http://localhost:8080

serve:
	php -S localhost:8080 -t local-docs/ router.php

watch:
	while true; do php build.php --local; sleep 2; done
