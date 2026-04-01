<?php
//http://localhost/sensor/insert_data.php?temperature=25.5&humidity=101325
// Adatbázis kapcsolat beállításai
$servername = "localhost";
$username   = "subiczm";
$password   = "Jelszo123!";
$dbname     = "subiczm_db";

// Kapcsolódás az adatbázishoz
$conn = new mysqli($servername, $username, $password, $dbname);

// Kapcsolódási hiba ellenőrzése
if ($conn->connect_error) {
    die("Kapcsolódási hiba: " . $conn->connect_error);
}

// Paraméterek ellenőrzése és mentése
if (isset($_GET['temperature']) && isset($_GET['humidity'])) {
    $temperature = filter_var($_GET['temperature'], FILTER_VALIDATE_FLOAT);
    $humidity = filter_var($_GET['humidity'], FILTER_VALIDATE_FLOAT);

    if ($temperature === false || $humidity === false) {
        echo "Érvénytelen paraméterek!";
    } else {
        $stmt = $conn->prepare("INSERT INTO measurements (temperature, humidity, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("dd", $temperature, $humidity);

        if ($stmt->execute()) {
            echo "Adatok sikeresen rögzítve.";
        } else {
            echo "Hiba történt: " . $stmt->error;
        }

        $stmt->close();
    }
} else {
    echo "Hiányzó paraméterek!";
}

$conn->close();
?>
