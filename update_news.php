<?php
// Get credentials from GitHub Secrets
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$apiKey = getenv('NEWS_API_KEY');
$port = "6543"; // Supabase default

try {
    // 1. Connect to Supabase using PDO
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // 2. Fetch News from the API
    // Using NewsAPI.org as an example (adjust URL as needed)
    $url = "https://newsapi.org/v2/top-headlines?country=us&apiKey=$apiKey";
    
    // We use curl for better error handling than file_get_contents
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'GitHub-Action-News-Bot');
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['articles']) && is_array($data['articles'])) {
        // 3. Prepare the SQL Statement
        // "ON CONFLICT (url) DO NOTHING" prevents duplicate news articles
        $stmt = $pdo->prepare("
            INSERT INTO news_articles (title, description, url, published_at, source_name) 
            VALUES (?, ?, ?, ?, ?) 
            ON CONFLICT (url) DO NOTHING
        ");

        $count = 0;
        foreach ($data['articles'] as $article) {
            $stmt->execute([
                $article['title'],
                $article['description'],
                $article['url'],
                date('Y-m-d H:i:s', strtotime($article['published_at'])),
                $article['source']['name'] ?? 'Unknown'
            ]);
            $count += $stmt->rowCount();
        }
        echo "Successfully added $count new articles to Supabase.";
    } else {
        echo "No articles found or API error: " . ($data['message'] ?? 'Unknown error');
    }

} catch (Exception $e) {
    // This will show up in your GitHub Action logs if it fails
    error_log("Error: " . $e->getMessage());
    exit(1); 
}
?>