<?php
$servername = "localhost";
$username = "root";
$password = ""; // Cambia según tu configuración de MySQL
$dbname = "sensores";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si se enviaron todos los datos
if (isset($_POST['temperatura'], $_POST['humedad'], $_POST['coValue'], $_POST['coLimit'], $_POST['latitud'], $_POST['longitud'])) {
    $temperatura = $_POST['temperatura'];
    $humedad = $_POST['humedad'];
    $coValue = $_POST['coValue'];
    $coLimit = $_POST['coLimit'];
    $latitud = $_POST['latitud'];
    $longitud = $_POST['longitud'];

    // Insertar datos en la tabla
    $sql = "INSERT INTO lecturas (temperatura, humedad, co_value, co_limit, latitud, longitud)
            VALUES ('$temperatura', '$humedad', '$coValue', '$coLimit', '$latitud', '$longitud')";

    if ($conn->query($sql) === TRUE) {
        echo "Datos guardados correctamente.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Datos incompletos.";
}

$conn->close();
?>
