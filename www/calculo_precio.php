<?php
/*
==========================================================
REFERENCIA DE CÁLCULOS ORIGINALES DE COSTOS POR IMPRESORA
==========================================================

Estos valores sirvieron de base para calcular los precios globales
usados en el archivo precios.json. Se mantienen aquí como referencia
para futuras actualizaciones de costos, sustituciones de impresoras
o materiales.

function obtenerDatosImpresoras() {
    return [
        'markforged' => [
            'costo_impresora' => 99480.60,  // $4,980 USD × 19.97
            'vida_util_horas' => 36500,
            'materiales' => [
                'onyx' => [
                    'costo_por_cm3' => 6.37  // ($255 USD × 19.97) / 800 cm³
                ]
            ]
        ],
        'elegoo' => [
            'costo_impresora' => 15430.32,  // $13,302 × 1.16 (IVA)
            'vida_util_horas' => 2000,
            'materiales' => [
                'resina_transparente_flexible' => ['costo_por_cm3' => 1.60], // $1,600/L ÷ 1000 cm³/L
                'resina_tipo_abs' => ['costo_por_cm3' => 0.50],              // $500/L ÷ 1000
                'resina_estandar' => ['costo_por_cm3' => 0.40]               // $400/L ÷ 1000
            ]
        ],
        'kingroon' => [
            'costo_impresora' => 8120.00,   // $7,000 × 1.16 (IVA)
            'vida_util_horas' => 78000,
            'materiales' => [
                'pla' => ['costo_por_cm3' => 0.3125],  // $250/kg ÷ 800 cm³/kg (aprox.)
                'abs' => ['costo_por_cm3' => 0.30],  // $300/kg ÷ 1000
                'petg' => ['costo_por_cm3' => 0.30], // $300/kg ÷ 1000
                'tpu' => ['costo_por_cm3' => 0.90]   // $900/kg ÷ 1000
            ]
        ],
        'creality' => [
            'costo_impresora' => 23326.44,  // $20,109 × 1.16 (IVA)
            'vida_util_horas' => 2500,
            'materiales' => [
                'pla' => ['costo_por_cm3' => 0.25],  // $250/kg ÷ 1000 cm³/kg
                'abs' => ['costo_por_cm3' => 0.30],  // $300/kg ÷ 1000
                'petg' => ['costo_por_cm3' => 0.30], // $300/kg ÷ 1000
                'tpu' => ['costo_por_cm3' => 0.90]   // $900/kg ÷ 1000
            ]
        ]
    ];
}

💬 Nota:
Estos datos fueron usados para derivar los precios promedio por material
que ahora se encuentran en precios.json, los cuales son valores globales
por tipo de material independientemente de la impresora.

==========================================================
*/
function calcularCotizacionImpresion($volumen, $tiempoHoras, $materialSeleccionado, $impresoraSeleccionada) {
    $impresorasPath = __DIR__ . '/impresoras.json';
    $preciosPath = __DIR__ . '/precios.json';

    $datosImpresoras = json_decode(file_get_contents($impresorasPath), true);
    $datosPrecios = json_decode(file_get_contents($preciosPath), true);

    if (!$datosImpresoras || !$datosPrecios) {
        return calcularCotizacionDefault();
    }

    $impresora = $datosImpresoras[$impresoraSeleccionada] ?? null;
    if (!$impresora) return calcularCotizacionDefault();

    $vidaUtil = $impresora['vida_util_horas'] ?? 2000;
    $costoImpresora = $impresora['costo_impresora'] ?? 10000;

    $precioPorCm3 = $datosPrecios['materiales'][$materialSeleccionado] ?? 0.25;

    $margenConsumible = $datosPrecios['margenes']['consumible'] ?? 0.015;
    $margenOperacion = $datosPrecios['margenes']['operacion'] ?? 0.02;
    $margenIndirectos = $datosPrecios['margenes']['indirectos'] ?? 0.30;

    // Cálculos
    $costoMaterial = $volumen * $precioPorCm3;
    $costoTiempo = $tiempoHoras * ($costoImpresora / $vidaUtil);

    $subtotalProduccion = $costoMaterial + $costoTiempo;
    $consumible = $subtotalProduccion * $margenConsumible;
    $operacion = $subtotalProduccion * $margenOperacion;
    $subtotalOperacion = $consumible + $operacion;
    $costoFinal = $subtotalProduccion + $subtotalOperacion;
    $indirectos = $costoFinal * $margenIndirectos;
    $precioFinal = $costoFinal + $indirectos;

    return [
        'costo_material' => round($costoMaterial, 2),
        'costo_tiempo' => round($costoTiempo, 2),
        'subtotal_produccion' => round($subtotalProduccion, 2),
        'consumible_impresion' => round($consumible, 2),
        'costo_operacion' => round($operacion, 2),
        'subtotal_operacion' => round($subtotalOperacion, 2),
        'costo_final_impresion' => round($costoFinal, 2),
        'indirectos' => round($indirectos, 2),
        'precio_final' => round($precioFinal, 2)
    ];
}

function calcularCotizacionDefault() {
    return [
        'costo_material' => 0,
        'costo_tiempo' => 0,
        'subtotal_produccion' => 0,
        'consumible_impresion' => 0,
        'costo_operacion' => 0,
        'subtotal_operacion' => 0,
        'costo_final_impresion' => 0,
        'indirectos' => 0,
        'precio_final' => 0
    ];
}
?>