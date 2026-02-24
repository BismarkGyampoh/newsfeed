<?php
// 1. Connection settings
$host = "sql308.infinityfree.com";
$user = "if0_41238435";
$pass = "fra123ncella";
$dbname = "news_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed");

// 2. Fetch from API
$apiKey = "pub_27bbc6510d114cd5a7aef849e6510ec8";
$url = "https://newsdata.io/api/1/news?apikey=$apiKey&language=en&category=technology";

$response = file_get_contents($url);
$data = json_decode($response, true);

// 3. Loop and Store
if (isset($data['results'])) {
    foreach ($data['results'] as $news) {
        $title = $conn->real_escape_string($news['title']);
        $link = $conn->real_escape_string($news['link']);
        $desc = $conn->real_escape_string($news['description'] ?? '');
        $img = $conn->real_escape_string($news['image_url'] ?? '');
        $pubDate = date("Y-m-d H:i:s", strtotime($news['pubDate']));

        // INSERT IGNORE prevents duplicate links
        $sql = "INSERT IGNORE INTO news_articles (title, description, link, image_url, pub_date) 
                VALUES ('$title', '$desc', '$link', '$img', '$pubDate')";
        $conn->query($sql);
    }
    echo "Update Successful!";
}
$conn->close();
?>