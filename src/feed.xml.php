<?xml version="1.0" ?>
<rss version="2.0">
    <channel>
        <title><?= $site_title ?></title>
        <link><?= $site_url ?></link>
        <description><?= $site_description ?></description>

        <?php foreach($articles as $article): ?>
           <item>
               <title><?= $article->title ?></title>
               <description><?= $article->description ?></description>
               <link><?= $article->url ?></link>
           </item>
       <?php endforeach; ?>
   </channel>
</rss>
