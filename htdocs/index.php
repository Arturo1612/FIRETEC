<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIRECTEC - Mediciones y Gráficas</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Estilos base */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            width: 850px;
            background-color: white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            display: flex;
            align-items: center;
            background-color: #ff7043;
            color: white;
            padding: 15px 20px;
            gap: 15px;
        }
        .header img {
            width: 100px;
            height: 100px;
        }
        .header h1 {
            font-size: 1.5em;
            margin: 0;
        }
        .section-title {
            font-size: 1.2em;
            color: #333;
            margin: 20px;
        }
        .main-info {
            background-color: #d32f2f;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .data {
            display: flex;
            justify-content: space-around;
            gap: 15px;
        }
        .data div {
            text-align: center;
        }
        .data .highlight {
            font-size: 2.5em;
            font-weight: bold;
        }
        .map {
            margin: 20px;
            height: 400px;
            width: 100%;
        }
        .table-container {
            margin: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #ff7043;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <img src="logo.png" alt="Logo de FIRECTEC">
            <h1>FIRECTEC</h1>
        </div>
        
        <!-- Información principal -->
        <div class="section-title">Incendios detectados y su ubicación</div>
        <div class="main-info">
            <div class="data">
                <div>
                    <div class="highlight">
                        <?php
                        // Conexión a la base de datos
                        $servername = "localhost";
                        $username = "root";
                        $password = ""; // Cambia según tu configuración
                        $dbname = "sensores";
                        $conn = new mysqli($servername, $username, $password, $dbname);

                        if ($conn->connect_error) {
                            die("Conexión fallida: " . $conn->connect_error);
                        }

                        // Consulta para obtener el último registro
                        $sql = "SELECT * FROM lecturas ORDER BY fecha DESC LIMIT 1";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            echo $row["temperatura"] . "°C";
                        } else {
                            echo "Sin datos";
                        }
                        ?>
                    </div>
                    <div>Temperatura</div>
                </div>
                <div>
                    <div class="highlight">
                        <?php echo isset($row) ? $row["humedad"] . "%" : "Sin datos"; ?>
                    </div>
                    <div>Humedad</div>
                </div>
            </div>
        </div>

        <!-- Mapa dinámico -->
        <div id="map" class="map"></div>

        <!-- Tabla de coordenadas -->
        <div class="table-container">
            <div class="section-title">Historial de Coordenadas</div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Temperatura</th>
                        <th>Humedad</th>
                        <th>CO Value</th>
                        <th>Latitud</th>
                        <th>Longitud</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Variable n: número de marcadores a mostrar
                    $n = 5; // Cambia este valor según tus necesidades

                    // Consulta para obtener los últimos n registros
                    $sql = "SELECT * FROM lecturas ORDER BY fecha DESC LIMIT $n";
                    $result = $conn->query($sql);

                    $coordenadas = []; // Arreglo para almacenar las coordenadas
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["fecha"] . "</td>";
                            echo "<td>" . $row["temperatura"] . "°C</td>";
                            echo "<td>" . $row["humedad"] . "%</td>";
                            echo "<td>" . $row["co_value"] . "</td>";
                            echo "<td>" . $row["latitud"] . "</td>";
                            echo "<td>" . $row["longitud"] . "</td>";
                            echo "</tr>";

                            // Agregar coordenadas al arreglo
                            $coordenadas[] = [
                                'lat' => $row["latitud"],
                                'lng' => $row["longitud"],
                                'popup' => "Fecha: " . $row["fecha"] . "<br>Temperatura: " . $row["temperatura"] . "°C<br>Humedad: " . $row["humedad"] . "%" . "<br>CO: " . $row["co_value"] 
                            ];
                        }
                    } else {
                        echo "<tr><td colspan='6'>No hay datos disponibles</td></tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Inicializar el mapa
        const map = L.map('map').setView([19.432608, -99.133209], 12);

        // Agregar capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Agregar marcadores dinámicos desde PHP
        const coordenadas = <?php echo json_encode($coordenadas); ?>;
        coordenadas.forEach(coord => {
            L.marker([coord.lat, coord.lng]).addTo(map)
                .bindPopup(coord.popup);
        });
    </script>
</body>
</html>
