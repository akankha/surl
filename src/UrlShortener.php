<?php

namespace UrlShortener;

use PDO;

class UrlShortener
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function shortenUrl($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException("No URL provided");
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL");
        }

        $shortCode = substr(md5($url . time()), 0, 6);

        $stmt = $this->pdo->prepare("INSERT INTO urls (original_url, short_code) VALUES (:url, :code)");
        $stmt->bindParam(':url', $url, PDO::PARAM_STR);
        $stmt->bindParam(':code', $shortCode, PDO::PARAM_STR);
        $stmt->execute();

        return $shortCode;
    }

    public function getTotalUrls()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM urls");
        return $stmt->fetchColumn();
    }

    public function getUrlStats($page, $perPage)
    {
        $start = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("SELECT original_url, short_code, COUNT(url_clicks.id) AS total_clicks, COUNT(DISTINCT clicker_ip) AS unique_clicks FROM urls LEFT JOIN url_clicks ON urls.id = url_clicks.url_id GROUP BY urls.id LIMIT :start, :perPage");
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
    protected function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {

            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {

            return $_SERVER['REMOTE_ADDR'];
        }
    }

    public function redirectUrl($code)
    {
        $stmt = $this->pdo->prepare("SELECT id, original_url FROM urls WHERE short_code = :code LIMIT 1");
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch();
        if ($result) {
            $this->logClick($result['id'], $this->getClientIp());
            var_dump($_SERVER['HTTP_CLIENT_IP']);
            header("Location: " . $result['original_url']);
            exit;
        }

        header("HTTP/1.1 404 Not Found");
        exit('404, URL Not Found');
    }



    protected function logClick($urlId, $clickerIp)
    {
        $stmt = $this->pdo->prepare("INSERT INTO url_clicks (url_id, clicker_ip) VALUES (:urlId, :clickerIp)");
        $stmt->bindParam(':urlId', $urlId, PDO::PARAM_INT);
        $stmt->bindParam(':clickerIp', $clickerIp, PDO::PARAM_STR);
        $stmt->execute();
    }
}
