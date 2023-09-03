.PHONY: open serve watch

open:
	xdg-open http://localhost:9980

serve:
	php -S localhost:9980 -t local-docs/ router.php

watch:
	while true; do php build.php --local; sleep 2; done
