<?xml version="1.0" ?>
<rss version="2.0">
    <channel>
        <title><?= $page_title ?></title>
        <link><?= $page_url ?></link>
        <description><?= $page_description ?></description>

        <?php foreach($articles as $article): ?>
           <item>
               <title><?= $article->title ?></title>
               <description><?= $article->description ?></description>
               <link><?= $article->url ?></link>
           </item>
       <?php endforeach; ?>
   </channel>
</rss>
