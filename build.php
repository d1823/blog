#!/usr/bin/env php
<?php

$is_served_locally = $argc > 1 && $argv[1] === '--local';

/**
 * BASIC CONFIGURATION VARIABLES
 * -----------------------------
 * This section allows to set the title and description of the blog, as well
 * as its url in both local and live environments, or contact handles.
 */
$site_title = "d1823.pl";
$site_description = "d1823's programming ramblings";
$site_url = $is_served_locally ? 'http://localhost:8080' : "https://1823.pl/";
$email_address = "ramblings@1823.pl";
$twitter_username = "_d1823";

/**
 * BEGINNING THE BUILD PROCEDURE
 * --------------------------------
 * Here's the start of the building procedure. It runs the same for both local
 * and live environments - the only difference is in the output directory.
 */
$build_dir = $is_served_locally ? __DIR__ . "/local-docs" : __DIR__ . "/docs";
$src_dir = __DIR__ . "/src";
$content_dir = __DIR__ . "/content";
$feed = "feed.xml";

/**
 * 1. CLEARING THE OLD BUILDS
 */
system("rm -fr $build_dir");
system("mkdir -p $build_dir");

/**
 * 2. CONCATENATING STYLESHEETS TOGETHER
 * -------------------------------------
 * Each stylesheet is defined in src/assets as a regular CSS file.
 * Since all files are concatenated into a one big stylesheet,
 * I'm using numbered prefixes to control their order.
 */
$styles = array_reduce(files_from_dir("$src_dir/assets"), function (string $styles, string $asset_pathname): string {
    return $styles . file_get_contents($asset_pathname);
}, '');

/**
 * @var $it array<string, SplFileInfo>
 */
$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($content_dir, FilesystemIterator::SKIP_DOTS),
        fn(SplFileInfo $file) => $file->isDir() || $file->getExtension() === 'md'
    )
);

/**
 * 3. GENERATING THE CONTENT
 * -------------------------
 * Articles are written in Markdown and converted to HTML with Pandoc.
 *
 * Each article needs to be annotated with three special markers allowing
 * to specify the title, description, creation date and update date.
 *
 * Markers are metadata that shouldn't be included in the generated
 * HTML output. They're defined using a neat trick utilizing the
 * reference-style links supported by a wide range of Markdown
 * parsers.
 *
 * By passing an invalid link id ("//"), and a valid url ("#"),
 * I can write anything I want in an anchor's title spot:
 *
 *     [//]: # (TITLE: First Article)
 *     [//]: # (DESCRIPTION: My first article about starting up this beautiful blogging journey!)
 *     [//]: # (DATE: 2019-07-31)
 *
 * I personally feel all of this is really neat.
 */
$pages = [];

foreach ($it as $file) {
    /*
     * Processing a post bundle and a standalone post needs to happen a bit differently.
     *
     * When a post bundle is processed, the $path is an absolutely rebased real-path of the parent directory:
     *    /home/john/my-blog/content/articles/juggling/index.md => /articles/juggling
     * The $id is the name of the parent directory, and the directory processing is required.
     *
     * When a standalone post is processed, the $path is an absolutely rebased real-path of the file itself,
     * without its extension:
     *    /home/john/my-blog/content/articles/juggling.md => /articles/juggling
     * The $id is its name without the extension, and the directory processing is not required, because
     * no accompanying files are allowed.
     */
    [$url, $id, $should_process_directory] = match ($basename = $file->getBasename(".{$file->getExtension()}")) {
        "index" => [
            str_replace(
                $content_dir,
                "",
                $file->getPathInfo()->getRealPath(),
            ),
            $file->getPathInfo()->getBasename(),
            true
        ],
        default => [
            str_replace(
                $content_dir,
                "",
                str_replace($file->getFilename(), $basename, $file->getRealPath())
            ),
            $basename,
            false
        ]
    };

    $template_path = $file->getRealPath();

    $title = parse_title($file->getRealPath());
    $description = parse_description($file->getRealPath());

    $creation_date = parse_date($file->getRealPath());
    $update_date = parse_update_date($file->getRealPath());

    $pages[] = (object)compact(
        'template_path',
        'id',
        'title',
        'description',
        'creation_date',
        'update_date',
        'url'
    );
}

$articles = array_filter($pages, fn(stdClass $page) => str_starts_with($page->url, '/articles'));

/**
 * 4. SORTING ARTICLES USING THEIR DATES
 */
usort($articles, function (object $article, object $other_article) {
    return $other_article->creation_date->getTimestamp() <=> $article->creation_date->getTimestamp();
});

/**
 * 5. CONFIGURING THE CNAME OF THE BLOG
 * ------------------------------------
 * My blog is hosted using GitHub Pages with a custom domain.
 * This file lets me publish it under a domain of my choice.
 */
file_put_contents("$build_dir/CNAME", parse_url($site_url, PHP_URL_HOST));

/**
 * 6. RENDERING THE ARTICLES PAGE
 * ------------------------------
 * PHP started as a templating language and that's exactly how
 * I'm rendering this blog. You can find all the templates in
 * the /src directory in *.html.php and *.xml.php files.
 */
render_php_to_path(
    "$build_dir/index.html",
    "$src_dir/base.html.php",
    compact('site_description', 'site_url', 'styles', 'feed') + [
        'site_title' => "Blog - $site_title",
        'twitter_username' => $twitter_username,
        'page_url' => '/',
        'content' => render_php_to_string(
            "$src_dir/_articles.html.php",
            compact('articles')
        )
    ]
);

/**
 * 7. RENDERING PAGES
 */
foreach ($pages as $page) {
    render_php_to_path(
        join_path($build_dir, $page->url) . ".html",
        "$src_dir/base.html.php",
        [
            'site_title' => sprintf("%s - %s", $page->title, $site_title),
            'site_description' => $page->description,
            'site_url' => $site_url,
            'page_url' => $page->url,
            'styles' => $styles,
            'feed' => $feed,
            'twitter_username' => $twitter_username,
            'content' => render_php_to_string(
                "$src_dir/_page.html.php",
                [
                    'title' => $page->title,
                    'creation_date' => $page->creation_date,
                    'update_date' => $page->update_date,
                    'content' => render_md_to_string(
                        $page->template_path,
                        compact('email_address', 'twitter_username')
                    )
                ]
            )
        ]
    );
}

/**
 * 8. THE FEED
 * -----------
 * Did I mentioned I'm generating my own RSS feed?
 * It's a shame it's not that common anymore.
 */
render_php_to_path(
    "$build_dir/$feed",
    "$src_dir/feed.xml.php",
    compact('site_title', 'site_description', 'site_url', 'articles')
);

function join_path(string ...$parts): string
{
    return array_reduce(
        array_slice($parts, 1),
        static fn($path, string $part): string => $path . DIRECTORY_SEPARATOR . ltrim($part, DIRECTORY_SEPARATOR),
        $parts[0]
    );
}

function files_from_dir(string $path, string $extension = null): array
{
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

function render_php_to_path(string $path, string $template_path, array $content = []): void
{
    if (!is_dir($dir = dirname($path))) {
        mkdir($dir, recursive: true);
    }

    file_put_contents(
        $path,
        render_php_to_string($template_path, $content),
    );
}

function render_php_to_string(string $template_path, array $content = []): string
{
    extract($content);

    ob_start();
    include $template_path;
    return ob_get_clean();
}

function render_md_to_string(string $template_path, array $content = []): string
{
    /**
     * As the first thing, there's a chance a page written in Markdown will need some sort of
     * dynamic content that needs to be injected during rendering. We'll try to replace
     * all occurrences of {{key}} with the proper value.
     * There's no error-checking here... yet.
     */
    $result = str_replace(
        array_map(fn(string $value) => sprintf("{{%s}}", $value), array_keys($content)),
        array_values($content),
        file_get_contents($template_path)
    );

    $spec = [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['file', '/dev/null', 'a']
    ];

    /**
     * Converting the Markdown to HTML as "self-contained" will inline all images.
     * The resulting HTML will be fully standalone - wrapped in an <html> and <body> tags.
     */
    $process = proc_open("pandoc --self-contained -f markdown -t html", $spec, $pipes, dirname($template_path));

    if (!is_resource($process)) {
        throw new \RuntimeException("Converting markdown to html has failed. Make sure pandoc is installed.");
    }

    fwrite($pipes[0], $result);
    fclose($pipes[0]);

    $result = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    if (!$result) {
        throw new \RuntimeException("Converting markdown to html has failed. Make sure pandoc is installed.");
    }

    /**
     * We're obviously only interested in the contents of the <body> element.
     * This elaborate piece of code gives us exactly that.
     */
    $doc = new DOMDocument();
    @$doc->loadHTML($result);
    $elements = $doc->getElementsByTagName('body');
    $body = $elements[0] ?? null;
    $newDoc = new DOMDocument();
    foreach ($body->childNodes as $child) {
        $node = $newDoc->importNode($child, true);
        $newDoc->appendChild($node);
    }
    $newDoc->appendChild($node);
    $result = $newDoc->saveHTML();

    if (!$result) {
        throw new \RuntimeException("Converting markdown to html has failed. Make sure pandoc is installed.");
    }

    return $result;
}

function parse_title(string $pathname): string
{
    $title = parse_token("TITLE", $pathname);

    if (!$title) {
        throw new \RuntimeException("$pathname: missing a title");
    }

    return $title;
}

function parse_description(string $pathname): string
{
    $description = parse_token("DESCRIPTION", $pathname);

    if (!$description) {
        throw new \RuntimeException("$pathname: missing a description");
    }

    return $description;
}

function parse_date(string $pathname): ?DateTimeInterface
{
    try {
        $date_format = 'Y-m-d';
        $date_string = parse_token("DATE", $pathname);
    } catch (RuntimeException) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat(
        $date_format,
        $date_string
    );

    $errors = DateTimeImmutable::getLastErrors();

    if (!empty($errors['warning_count']) || $date === false) {
        throw new \RuntimeException("$pathname: date $date_string isn't formatted as $date_format");
    }

    return $date;
}

function parse_update_date(string $pathname): ?DateTimeInterface
{
    try {
        $date_format = 'Y-m-d';
        $date_string = parse_token("UPDATE DATE", $pathname);
    } catch (RuntimeException) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat(
        $date_format,
        $date_string
    );

    $errors = DateTimeImmutable::getLastErrors();

    if (!empty($errors['warning_count']) || $date === false) {
        throw new \RuntimeException("$pathname: date $date_string isn't formatted as $date_format");
    }

    return $date;
}

function parse_token(string $marker, string $pathname): string
{
    $token_prefix = "[//]: #";
    $token = "$token_prefix ($marker:";
    $file_handle = fopen($pathname, 'r+');

    while ($line = fgets($file_handle)) {
        if (strpos($line, $token_prefix) === false) {
            fclose($file_handle);

            break;
        }

        if (strpos($line, $token) === false) {
            continue;
        }

        fclose($file_handle);

        return trim(substr($line, strlen($token), -2));
    }

    throw new \RuntimeException("$pathname: missing a $marker");
}
