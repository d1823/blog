.PHONY: open serve watch

open:
	xdg-open http://localhost:8080

serve:
	php -S localhost:8080 -t docs/

watch:
	while true; do php build.php http://localhost:8080/; sleep 2; done
