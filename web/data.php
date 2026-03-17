<?php
// Database connectie-instellingen
$host = "localhost";  // of de server waar je MySQL draait
$dbname = "temperatures";  // je database naam
$username = "logger";  // je database username
$password = "paswoord";  // je database password

// Maak een connectie met MySQL
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// SQL query om de data op te halen
$query = "SELECT dateandtime, temperature, humidity FROM temperaturedata ORDER BY dateandtime ASC";

// Voer de query uit en haal de data op
$stmt = $conn->prepare($query);
$stmt->execute();

// Haal de resultaten op in een array
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Zet de data om naar JSON en stuur het terug naar de browser
header('Content-Type: application/json');
echo json_encode($results);

?>
