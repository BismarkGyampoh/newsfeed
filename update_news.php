<?php
// Get credentials from GitHub Secrets
$host   = getenv('DB_HOST');
$db     = getenv('DB_NAME');
$user   = getenv('DB_USER');
$pass   = getenv('DB_PASS');
$apiKey = getenv('NEWS_API_KEY');
$port   = "6543"; // As you confirmed earlier

try {
    // 1. Connect to Supabase using PDO (The "PostgreSQL" Key)
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "Connection Successful! ";

    // 2. Fetch News (Example API call)
    $url = "https://newsapi.org/v2/top-headlines?country=us&apiKey=$apiKey";
    
    // Set up a simple request
    $context = stream_context_create([
        "http" => ["header" => "User-Agent: PHP-News-Bot\r\n"]
    ]);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if (isset($data['articles'])) {
        // 3. Save to the database
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
    // If the connection fails, this will tell us why
    echo "Database Error: " . $e->getMessage();
    exit(1);
}
?>