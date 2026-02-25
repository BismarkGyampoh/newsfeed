<?php
// Get credentials from GitHub Secrets
$host   = getenv('DB_HOST');
$db     = getenv('DB_NAME');
$user   = getenv('DB_USER');
$pass   = getenv('DB_PASS');
$apiKey = getenv('NEWS_API_KEY');
$port   = "6543"; // Your Supabase Pooler Port

try {
    // 1. Connect to Supabase using PDO (PostgreSQL)
    // This replaces the "new mysqli" line that was causing the error
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "Connection Successful! ";

    // 2. Fetch News (Using NewsAPI.org)
    $url = "https://newsapi.org/v2/top-headlines?country=us&apiKey=$apiKey";
    
    $opts = [
        "http" => ["header" => "User-Agent: PHP-News-Bot\r\n"]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception("Failed to fetch data from API.");
    }

    $data = json_decode($response, true);

    if (isset($data['articles'])) {
        // 3. Save to Supabase
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
        echo "Successfully updated " . count($data['articles']) . " articles.";
    }

} catch (Exception $e) {
    // This will now show a PostgreSQL error instead of a MySQL error
    echo "Error: " . $e->getMessage();
    exit(1); 
}
?>