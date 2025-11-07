<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['stl_file']) && $_FILES['stl_file']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = __DIR__ . "/uploads/";
        $stlPath   = $uploadDir . basename($_FILES['stl_file']['name']);
        $gcodePath = $uploadDir . pathinfo($_FILES['stl_file']['name'], PATHINFO_FILENAME) . ".gcode";

        if (!move_uploaded_file($_FILES['stl_file']['tmp_name'], $stlPath)) {
            echo '<div class="card-resultado error">❌ Error al mover el archivo.</div>';
            exit;
        }

        // Ejecutar el slicer (PrusaSlicer o script JS)
        $cmd = "node /var/www/html/runPrusa.js " . escapeshellarg($stlPath);
        exec($cmd, $output, $return_var);

        if ($return_var !== 0 || empty($output)) {
            echo '<div class="card-resultado error">⚠️ Error al generar G-code:<br>' . implode("<br>", $output) . '</div>';
            exit;
        }

        $result = json_decode($output[0], true);
        if (!$result) {
            echo '<div class="card-resultado error">⚠️ No se pudo leer la información del G-code.</div>';
            exit;
        }

        // Datos del archivo
        $margen = 0.10;
        $filamento_mm      = $result['filamentUsedMm'] ?? 0;
        $filamento_cm3     = $result['filamentUsedCm3'] ?? 0;
        $tiempo_estimado   = $result['estimatedTime'] ?? "0h 0m 0s";

        $filamento_mm_seg  = $filamento_mm * (1 + $margen);
        $filamento_cm3_seg = $filamento_cm3 * (1 + $margen);
        $tiempo_seg        = ajustarTiempo($tiempo_estimado, $margen);

        $impresoraSeleccionada = $_POST['marca'] ?? 'markforged';
        $materialSeleccionado  = $_POST['material'] ?? 'pla';

        include 'calculo_precio.php';
        $cotizacion = calcularCotizacionImpresion(
            $filamento_cm3_seg,
            convertirTiempoAHoras($tiempo_seg),
            $materialSeleccionado,
            $impresoraSeleccionada
        );

        // --- MOSTRAR RESULTADOS ---
        echo '
<div class="resultado-dos-columnas">
    <div class="card-resultado">
        <h3>📊 Datos Base</h3>
        <div class="res-item"><strong>Filamento usado:</strong> ' . number_format($filamento_mm, 2) . ' mm</div>
        <div class="res-item"><strong>Volumen usado:</strong> ' . number_format($filamento_cm3, 2) . ' cm³</div>
        <div class="res-item"><strong>Tiempo estimado:</strong> ' . htmlspecialchars($tiempo_estimado) . '</div>

        <hr>
        <h3>🛡️ Con Margen de Seguridad (10%)</h3>
        <div class="res-item"><strong>Filamento usado (seguridad):</strong> ' . number_format($filamento_mm_seg, 2) . ' mm</div>
        <div class="res-item"><strong>Volumen usado (seguridad):</strong> ' . number_format($filamento_cm3_seg, 2) . ' cm³</div>
        <div class="res-item"><strong>Tiempo estimado (seguridad):</strong> ' . htmlspecialchars($tiempo_seg) . '</div>
    </div>

    <div class="card-resultado">
        <h3>💰 Cotización Estimada</h3>
        <div class="res-item"><strong>Impresora:</strong> ' . htmlspecialchars(ucfirst($impresoraSeleccionada)) . '</div>
        <div class="res-item"><strong>Material:</strong> ' . htmlspecialchars(ucfirst($materialSeleccionado)) . '</div>
        <hr>
        <div class="res-item">• Costo Material: <strong>' . number_format($cotizacion['costo_material'], 2) . ' MXN</strong></div>
        <div class="res-item">• Costo Tiempo: <strong>' . number_format($cotizacion['costo_tiempo'], 2) . ' MXN</strong></div>
        <div class="res-item">• Subtotal Producción: ' . number_format($cotizacion['subtotal_produccion'], 2) . ' MXN</div>
        <div class="res-item">• Consumible Impresión: ' . number_format($cotizacion['consumible_impresion'], 2) . ' MXN</div>
        <div class="res-item">• Costo Operación: ' . number_format($cotizacion['costo_operacion'], 2) . ' MXN</div>
        <div class="res-item">• Subtotal Operación: ' . number_format($cotizacion['subtotal_operacion'], 2) . ' MXN</div>
        <div class="res-item">• Costo Final: ' . number_format($cotizacion['costo_final_impresion'], 2) . ' MXN</div>
        <div class="res-item">• Indirectos: ' . number_format($cotizacion['indirectos'], 2) . ' MXN</div>
        <hr>
        <div class="total">💵 Precio Final Estimado: ' . number_format($cotizacion['precio_final'], 2) . ' MXN</div>
        <div class="acciones">
            <a href="uploads/' . htmlspecialchars(basename($gcodePath)) . '" class="boton-descargar" download>⬇️ Descargar G-code</a>
        </div>
    </div>
</div>';

        // === VISUALIZADOR 3D ===
        echo '
<div id="model" style="width: 100%; height: 500px; margin-top: 20px; background: #111;"></div>

<div class="controles-info" style="width: 100%; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-top: 10px; text-align: left; font-size: 0.9em; line-height: 1.6;">
    <h4 style="margin: 0 0 10px 0; text-align: center; font-size: 1.1em;"> Controles del Visor 3D</h4>
    <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
        <li><strong>Girar Modelo (Orbitar):</strong> Clic Izquierdo + Arrastrar</li>
        <li><strong>Mover Cámara (Pan):</strong> Clic Derecho + Arrastrar</li>
        <li><strong>Acercar/Alejar (Zoom):</strong> Rueda del Mouse (Scroll)</li>
        <li><strong>Rotar Pieza:</strong> Teclas A / D (o Flecha Izquierda / Derecha)</li>
    </ul>
</div>
';

       
        $stlUrl = 'uploads/' . htmlspecialchars(basename($stlPath));
        echo '<div id="stl-data-url" data-url="' . $stlUrl . '" style="display: none;"></div>';


        

    } else {
        echo '<div class="card-resultado error">❌ No se recibió un archivo STL válido.</div>';
    }
} else {
    echo '<div class="card-resultado error">⚠️ Método no permitido.</div>';
}

// --- FUNCIONES AUXILIARES ---
function ajustarTiempo($tiempo_str, $porcentaje) {
    preg_match_all("/(\\d+)([hms])/", $tiempo_str, $matches, PREG_SET_ORDER);
    $segundos = 0;
    foreach ($matches as $match) {
        $valor = (int)$match[1];
        switch ($match[2]) {
            case "h": $segundos += $valor * 3600; break;
            case "m": $segundos += $valor * 60; break;
            case "s": $segundos += $valor; break;
        }
    }
    $segundos_ajustados = round($segundos * (1 + $porcentaje));
    $horas = intdiv($segundos_ajustados, 3600);
    $minutos = intdiv($segundos_ajustados % 3600, 60);
    $segundos_final = $segundos_ajustados % 60;
    return "{$horas}h {$minutos}m {$segundos_final}s";
}

function convertirTiempoAHoras($tiempo_str) {
    preg_match_all("/(\\d+)([hms])/", $tiempo_str, $matches, PREG_SET_ORDER);
    $horas = 0;
    foreach ($matches as $match) {
        $valor = (int)$match[1];
        switch ($match[2]) {
            case "h": $horas += $valor; break;
            case "m": $horas += $valor / 60; break;
            case "s": $horas += $valor / 3600; break;
        }
    }
    return $horas;
}
?>