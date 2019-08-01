#!/usr/bin/env php
<?php

$build_dir = __DIR__ . "/docs";
$src_dir = __DIR__ . "/src";
$articles_dir = __DIR__ . "/articles";

$page_title = "1823's programming ramblings";
$page_description = "1823's programming ramblings";
$page_url = "https://d1823.github.io/blog/";

$styles = array_reduce(files_from_dir("$src_dir/assets"), function (string $styles, string $asset_pathname): string {
    return $styles . file_get_contents($asset_pathname);
}, '');

$feed = "feed.xml";

$articles = array_map(function (string $pathname) use ($page_url): stdClass {
    $id = pathinfo($pathname, PATHINFO_FILENAME);

    $title = parse_title($pathname);
    $description = parse_description($pathname);
    $content = shell_exec("/usr/bin/pandoc -f markdown -t html $pathname 2> /dev/null");

    $date = parse_date($pathname);
    $time = $date->format('Y-m-d');
    $human_time = $date->format('F d, Y');

    $url = "$page_url#$id";

    return (object) compact('id', 'title', 'description', 'time', 'human_time', 'content', 'url');
}, files_from_dir($articles_dir));

system("rm -fr $build_dir");
system("mkdir $build_dir");

file_put_contents(
	"$build_dir/index.html",
    render_to_string("$src_dir/index.html.php", compact('page_title', 'page_description', 'page_url', 'styles', 'feed', 'articles'))
);

file_put_contents(
    "$build_dir/feed.xml",
    render_to_string("$src_dir/feed.xml.php", compact('page_title', 'page_description', 'page_url', 'articles'))
);

function files_from_dir(string $path): array {
    $files = new FilesystemIterator($path);

    return array_map(function (\SplFileInfo $file) {
        return $file->getPathname();
    }, iterator_to_array($files));
}

function render_to_string(string $template_path, array $content): string {
	extract($content);

	ob_start();
	include $template_path;
	return ob_get_clean();
}

function parse_title(string $pathname) {
	$title = parse_token("TITLE", $pathname);

	if (!$title) {
		throw new \RuntimeException("$pathname: missing a title");
	}

	return $title;
}

function parse_description(string $pathname) {
    $description = parse_token("DESCRIPTION", $pathname);

    if (!$description) {
        throw new \RuntimeException("$pathname: missing a description");
    }

    return $description;
}

function parse_date(string $pathname): \DateTime {
	$date_format = 'Y-m-d';
	$date_string = parse_token("DATE", $pathname);

    $date = DateTime::createFromFormat(
    	$date_format,
    	$date_string
	);

    $errors = DateTime::getLastErrors();

    if (!empty($errors['warning_count']) || $date === false) {
		throw new \RuntimeException("$pathname: date $date_string isn't formatted as $date_format");
    }

    return $date;
}

function parse_token(string $marker, string $pathname) {
	$token = "[//]: # ($marker:";
	$file_handle = fopen($pathname, 'r+');

	while($line = fgets($file_handle)){
		if (strpos($line, $token) === false) {
			continue;
		}

		fclose($file_handle);

		return trim(substr($line, strlen($token), -2));
	}

	throw new \RuntimeException("$pathname: missing a $marker");
}