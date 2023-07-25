<?xml version="1.0" ?>
<rss version="2.0">
    <channel>
        <title><?= e($site_title) ?></title>
        <link><?= e($site_url) ?></link>
        <description><?= e($site_description) ?></description>

        <?php foreach($articles as $article): ?>
           <item>
               <title><?= e($article->title) ?></title>
               <description><?= e($article->description) ?></description>
               <link><?= e($article->url) ?></link>
           </item>
       <?php endforeach; ?>
   </channel>
</rss>
