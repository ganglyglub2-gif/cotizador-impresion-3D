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
            echo '<div class="alert alert-danger">Error al mover el archivo.</div>';
            exit;
        }

        $cmd = "node /var/www/html/runPrusa.js " . escapeshellarg($stlPath);
        exec($cmd, $output, $return_var);

        if ($return_var !== 0 || empty($output)) {
            echo '<div class="alert alert-danger"><b>Error al generar G-code:</b><br>' . implode("<br>", $output) . '</div>';
            exit;
        }

        $result = json_decode($output[0], true);
        if (!$result) {
            echo '<div class="alert alert-danger">No se pudo leer la información del G-code.</div>';
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
        <div class="cotizacion-estimada-container">
            <div class="titulo-seccion-subrayado mb-4">
                <h3>COTIZACIÓN ESTIMADA</h3>
            </div>
            
            <div class="row mb-3">
                
                <div class="col-md-6">
                    <div class="row no-gutters">
                        <div class="col-4">
                            <div class="badge-estimado-titulo">Impresora:</div>
                        </div>
                        <div class="col-8">
                            <div class="badge-estimado-valor">' . htmlspecialchars(ucfirst($impresoraSeleccionada)) . '</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="row no-gutters">
                        <div class="col-4">
                            <div class="badge-estimado-titulo">Material:</div>
                        </div>
                        <div class="col-8">
                            <div class="badge-estimado-valor">' . htmlspecialchars(ucfirst($materialSeleccionado)) . '</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th width="60%">Concepto</th>
                                <th width="40%" class="text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Costo del Material</td>
                                <td class="text-right">$' . number_format($cotizacion['costo_material'], 2) . '</td>
                            </tr>
                            <tr>
                                <td>Costo del Tiempo</td>
                                <td class="text-right">$' . number_format($cotizacion['costo_tiempo'], 2) . '</td>
                            </tr>
                            <tr>
                                <td>Subtotal de Producción</td>
                                <td class="text-right">$' . number_format($cotizacion['subtotal_produccion'], 2) . '</td>
                            </tr>
                            <tr>
                                <td>Consumible de Impresión</td>
                                <td class="text-right">$' . number_format($cotizacion['consumible_impresion'], 2) . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
               <div class="col-md-6">
                    <table class="table table-sm table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th width="60%">Concepto</th>
                                <th width="40%" class="text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Costo de Operación</td>
                                <td class="text-right">$' . number_format($cotizacion['costo_operacion'], 2) . '</td>
                            </tr>
                            <tr>
                                <td>Subtotal de Operación</td>
                                <td class="text-right">$' . number_format($cotizacion['subtotal_operacion'], 2) . '</td>
                            </tr>
                            <tr>
                                <td>Costo Final</td>
                                <td class="text-right">$' . number_format($cotizacion['costo_final_impresion'], 2) . '</td>
                            </tr>
                            <tr>
                                <td>Indirectos</td>
                                <td class="text-right">$' . number_format($cotizacion['indirectos'], 2) . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row text-center mt-3">
                <div class="col-12">
                    <h4 class="font-weight-bold">Precio Final Estimado:</h4>
                    <h3 class="precio-final-grande">$' . number_format($cotizacion['precio_final'], 2) . ' MXN</h3>
                    <a href="uploads/' . htmlspecialchars(basename($gcodePath)) . '" class="btn btn-success" download>
                        <i class="ing-guardar"></i>&nbsp; Descargar G-code
                    </a>
                </div>
            </div>
        <hr>';

        // ========= 2. BLOQUE DE ACORDEONES (REESTRUCTURADO LADO A LADO) =========
        echo '
        <div class="row accordion-custom">
        
            <div class="col-md-6">
                <div id="accordionDatosBase">
                    <div class="card">
                        <div class="card-header" id="headingDatosBase">
                            <h1 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseDatosBase" aria-expanded="false" aria-controls="collapseDatosBase">
                                    <strong>Datos Base (del Slicer)</strong>
                                </button>
                            </h1>
                        </div>
                        <div id="collapseDatosBase" class="collapse" aria-labelledby="headingDatosBase" data-parent="#accordionDatosBase">
                            <div class="card-body">
                                <ul class="list-unstyled accordion-list">
                                    <li><span>Filamento usado:</span> <span>' . number_format($filamento_mm, 2) . ' mm</span></li>
                                    <li><span>Volumen usado:</span> <span>' . number_format($filamento_cm3, 2) . ' cm³</span></li>
                                    <li><span>Tiempo estimado:</span> <span>' . htmlspecialchars($tiempo_estimado) . '</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div id="accordionMargen">
                    <div class="card">
                        <div class="card-header" id="headingMargen">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseMargen" aria-expanded="false" aria-controls="collapseMargen">
                                    Margen de Seguridad (10%)
                                </button>
                            </h5>
                        </div>
                        <div id="collapseMargen" class="collapse" aria-labelledby="headingMargen" data-parent="#accordionMargen">
                            <div class="card-body">
                                <ul class="list-unstyled accordion-list">
                                    <li><span>Filamento (seguridad):</span> <span>' . number_format($filamento_mm_seg, 2) . ' mm</span></li>
                                    <li><span>Volumen (seguridad):</span> <span>' . number_format($filamento_cm3_seg, 2) . ' cm³</span></li>
                                    <li><span>Tiempo (seguridad):</span> <span>' . htmlspecialchars($tiempo_seg) . '</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ';
        // --- FIN DEL BLOQUE REEMPLAZADO ---


        // === VISUALIZADOR 3D ===
        echo '
        <div class="mt-4">
        <div class="titulo-seccion-subrayado mb-4">
        <h3>VISUALIZADOR</h3>
        </div>
        <div id="model" style="width: 100%; height: 500px; background: #f7f7f7 !important; border: 1px solid #dee2e6; border-radius: 8px;"></div>
        </div>


<div class="controles-info" style="width: 100%; background: rgba(0,0,0,0.05); padding: 15px; border-radius: 8px; margin-top: 10px; text-align: left; font-size: 1rem; line-height: 1.6; border: 1px solid #dee2e6;">
    <h4 style="margin: 0 0 10px 0; text-align: center; font-size: 1.25rem;">Controles del Visor 3D</h4>
    <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
        <li><strong>Girar Modelo (Orbitar):</strong> Clic Izquierdo + Arrastrar</li>
        <li><strong>Mover Cámara (Pan):</strong> Clic Derecho + Arrastrar</li>
        <li><strong>Acercar/Alejar (Zoom):</strong> Rueda del Mouse (Scroll)</li>
        <li><strong>Rotar Pieza:</strong> Teclas A / D (o Flecha Izquierda / Derecha)</li>
    </ul>
</div>
';

        // === DIV OCULTO  ===
        $stlUrl = 'uploads/' . htmlspecialchars(basename($stlPath));
        echo '<div id="stl-data-url" data-url="' . $stlUrl . '" style="display: none;"></div>';


    } else {
        echo '<div class="alert alert-danger">No se recibió un archivo STL válido.</div>';
    }
} else {
    echo '<div class="alert alert-warning">Método no permitido.</div>';
}

// --- FUNCIONES AUXILIARES  ---
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