#!/usr/bin/env php
<?php

$is_served_locally = $argc > 1 && $argv[1] === '--local';

$build_dir = $is_served_locally ? __DIR__ . "/local-docs" : __DIR__ . "/docs";
$src_dir = __DIR__ . "/src";
$articles_dir = __DIR__ . "/articles";

$page_title = "d1823's programming ramblings";
$page_description = "d1823's programming ramblings";
$page_url = $is_served_locally ? 'http://localhost:8080' : "https://1823.pl/";
$email_address = "ramblings@1823.pl";
$twitter_username = "_d1823";

system("rm -fr $build_dir");
system("mkdir -p $build_dir");

$styles = array_reduce(files_from_dir("$src_dir/assets"), function (string $styles, string $asset_pathname): string {
    return $styles . file_get_contents($asset_pathname);
}, '');

$feed = "feed.xml";

$articles_images = files_from_dir($articles_dir, "png");

$articles = array_map(function (string $pathname) use ($articles_dir, $articles_images, $build_dir, $page_url): stdClass {
    $id = pathinfo($pathname, PATHINFO_FILENAME);

    $title = parse_title($pathname);
    $description = parse_description($pathname);
    $content = shell_exec("pandoc -f markdown -t html $pathname 2> /dev/null");

    if (!$content) {
        throw new \RuntimeException("Converting markdown to html has failed. Make sure pandoc is installed.");
    }

    foreach ($articles_images as $image_pathname) {
        if (!stristr($image_pathname, $id)) {
            continue;
        }

        $image_id = pathinfo($image_pathname, PATHINFO_FILENAME);
        $image_basename = pathinfo($image_pathname, PATHINFO_BASENAME);

        $content = str_ireplace($image_id, "$page_url$image_basename", $content);

        copy($image_pathname, "$build_dir/$image_basename");
    }

    $date = parse_date($pathname);
    $time = $date->format('Y-m-d');
    $human_time = $date->format('F d, Y');

    $url = "$page_url#$id";

    return (object) compact('id', 'title', 'description', 'date', 'time', 'human_time', 'content', 'url');
}, files_from_dir($articles_dir, "md"));

usort($articles, function (object $article, object $other_article) {
    return $other_article->date->getTimestamp() <=> $article->date->getTimestamp();
});

file_put_contents("$build_dir/CNAME", parse_url($page_url, PHP_URL_HOST));

file_put_contents(
    "$build_dir/index.html",
    render_to_string(
        "$src_dir/base.html.php",
        compact('page_title', 'page_description', 'page_url', 'styles', 'feed') + ['content' => render_to_string("$src_dir/articles.html.php", compact('articles'))]
    )
);

file_put_contents(
    "$build_dir/contact.html",
    render_to_string(
        "$src_dir/base.html.php",
        compact('page_title', 'page_description', 'page_url', 'styles', 'feed') + ['content' => render_to_string("$src_dir/contact.html.php", compact('email_address', 'twitter_username'))]
    )
);

file_put_contents(
    "$build_dir/feed.xml",
    render_to_string("$src_dir/feed.xml.php", compact('page_title', 'page_description', 'page_url', 'articles'))
);

function files_from_dir(string $path, string $extension = null): array {
    $files = new FilesystemIterator($path);

    $files = array_filter(iterator_to_array($files), function (\SplFileInfo $file) use ($extension) {
        return $extension === null || $extension === strtolower($file->getExtension());
    });

    $paths = array_map(function (\SplFileInfo $file) {
        return $file->getPathname();
    }, $files);

    sort($paths);

    return $paths;
}

function render_to_string(string $template_path, array $content = []): string {
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
