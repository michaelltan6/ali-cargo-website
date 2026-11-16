<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('database.php');
require_once('library.php');

// Check authentication
if (!function_exists('isUser') || !isUser()) {
    error_log("User not authenticated in footer.php");
    header("Location: login.php");
    exit;
}

try {
    $stmt = dbQuery("SELECT * FROM company LIMIT 1");
    $company = dbFetchAssoc($stmt);
    if (!$company) {
        $company = [
            'footer_website' => 'https://anekalintasindcargo.com',
            'cname' => 'Aneka Lintasindo Cargo',
            'caddress' => '123 Jakarta St, Indonesia',
            'cphone' => '08123456789',
            'cemail' => 'contact@anekalintasindcargo.com',
            'currency' => 'IDR'
        ];
        error_log("No data found in company table, using default values.");
    }
} catch (PDOException $e) {
    error_log("Footer PDO Error: " . $e->getMessage());
    $company = [
        'footer_website' => 'https://anekalintasindcargo.com',
        'cname' => 'Aneka Lintasindo Cargo',
        'caddress' => '123 Jakarta St, Indonesia',
        'cphone' => '08123456789',
        'cemail' => 'contact@anekalintasindcargo.com',
        'currency' => 'IDR'
    ];
}
?>

<footer class="footer text-right">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <a href="<?php echo htmlspecialchars($company['footer_website']); ?>" target="_blank">
                    <?php echo htmlspecialchars($company['footer_website']); ?>
                </a>
            </div>
        </div>
    </div>
</footer>