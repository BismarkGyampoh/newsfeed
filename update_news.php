<?php
// Get credentials from GitHub Secrets
$host   = getenv('DB_HOST');
$db     = getenv('DB_NAME');
$user   = getenv('DB_USER');
$pass   = getenv('DB_PASS');
$apiKey = getenv('NEWS_API_KEY');
$port   = "6543"; // Your Supabase Pooler Port

try {
    // Connect using PDO (PostgreSQL)
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "Connection Successful! ";

    // Fetch News
    $url = "https://newsapi.org/v2/top-headlines?country=us&apiKey=$apiKey";
    $context = stream_context_create(["http" => ["header" => "User-Agent: PHP-News-Bot\r\n"]]);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if (isset($data['articles'])) {
        $stmt = $pdo->prepare("INSERT INTO news_articles (title, description, url, published_at, source_name) 
                               VALUES (?, ?, ?, ?, ?) ON CONFLICT (url) DO NOTHING");

        foreach ($data['articles'] as $article) {
            $stmt->execute([
                $article['title'],
                $article['description'],
                $article['url'],
                date('Y-m-d H:i:s', strtotime($article['published_at'])),
                $article['source']['name'] ?? 'Unknown'
            ]);
        }
        echo "Database updated with " . count($data['articles']) . " articles.";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
    exit(1);
}
?>
