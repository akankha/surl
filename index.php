<?php

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use UrlShortener\Database;
use UrlShortener\UrlShortener;

$pdo = Database::getConnection();
$urlShortener = new UrlShortener($pdo);

$message = '';
$shortenedUrl = '';
$host = 'http://localhost/surl';

if (isset($_GET['code'])) {
    $urlShortener->redirectUrl($_GET['code']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['url'])) {
    try {
        $originalUrl = $_POST['url'];
        $shortCode = $urlShortener->shortenUrl($originalUrl);
        $shortenedUrl = $host . '/?code=' . $shortCode;
    } catch (Exception $e) {
        $message = 'An error occurred: ' . $e->getMessage();
    }
}


$perPage = 7;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$totalRecords = $urlShortener->getTotalUrls();
$totalPages = ceil($totalRecords / $perPage);

$urlData = $urlShortener->getUrlStats($page, $perPage);

?>


<!DOCTYPE html>
<html>

<head>
    <title>URL Shortener</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <div class="container">
        <h1>Awesome Motive URL Shortener</h1>

        <?php if ($message) : ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="url" placeholder="Enter URL">
            <button type="submit">Shorten URL</button>
        </form>

        <?php if ($shortenedUrl) : ?>
            <p>Short URL: <a href="<?php echo $shortenedUrl; ?>" target="_blank"><?php echo $shortenedUrl; ?></a></p>
        <?php endif; ?>

        <table>
            <tr>
                <th>Original URL</th>
                <th>Short URL</th>
                <th>Number of Clicks</th>
                <th>Number of Unique Clicks</th>
            </tr>
            <?php foreach ($urlData as $data) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($data['original_url']); ?></td>
                    <td><a href="?code=<?php echo htmlspecialchars($data['short_code']); ?>" target="_blank"><?php echo $host . '/?code=' . htmlspecialchars($data['short_code']); ?></a></td>
                    <td><?php echo htmlspecialchars($data['total_clicks']); ?></td>
                    <td><?php echo htmlspecialchars($data['unique_clicks']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                <a href="?page=<?php echo $i; ?>" class="<?php if ($i === $page) echo 'active'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</body>

</html>