const { execFile } = require('child_process');
const fs = require('fs');
const path = require('path');

// Argumentos
const inputFile = process.argv[2];
const profileName = process.argv[3];

// Validaciones iniciales
if (!inputFile || !fs.existsSync(inputFile)) {
  console.error(JSON.stringify({ error: 'Archivo no encontrado' }));
  process.exit(1);
}

// Ruta al binario de PrusaSlicer
const prusaPath = '/app/squashfs-root/squashfs-root/usr/bin/prusa-slicer';

// Función principal asíncrona para manejar el flujo paso a paso
async function procesarArchivo() {
    try {
        let stlFile = inputFile;
        const ext = path.extname(inputFile).toLowerCase();
        
        // 1. SI NO ES STL, LO CONVERTIMOS
        if (ext !== '.stl') {
            // Creamos un nombre para el nuevo STL (ej: archivo.step -> archivo.stl)
            const convertedStl = inputFile.substring(0, inputFile.lastIndexOf('.')) + '.stl';
            
            // Comando de conversión: prusa-slicer --export-stl -o archivo.stl archivo.step
            await new Promise((resolve, reject) => {
                execFile(prusaPath, ['--export-stl', '-o', convertedStl, inputFile], (error, stdout, stderr) => {
                    if (error) reject(`Error convirtiendo a STL: ${stderr || error.message}`);
                    else resolve();
                });
            });
            
            // Ahora usamos el nuevo archivo convertido para todo lo demás
            stlFile = convertedStl;
        }

        // 2. PREPARAMOS EL G-CODE
        const gcodeFile = stlFile.replace(/\.stl$/i, '.gcode');
        
        // Perfil de impresora (Fallback si no se envía)
        const profilePath = profileName 
            ? `/app/profiles/${profileName}` 
            : '/app/profiles/Ender3_Bltouch.ini';

        if (!fs.existsSync(profilePath)) {
             throw new Error(`Perfil no encontrado: ${profilePath}`);
        }

        // 3. GENERAMOS EL G-CODE (Usando el STL, sea el original o el convertido)
        await new Promise((resolve, reject) => {
            const prusaArgs = ['--export-gcode', '-o', gcodeFile, '--load', profilePath, stlFile];
            execFile(prusaPath, prusaArgs, (error, stdout, stderr) => {
                if (error) reject(`Error generando G-code: ${stderr || error.message}`);
                else resolve();
            });
        });

        if (!fs.existsSync(gcodeFile)) {
            throw new Error('No se generó el archivo G-code');
        }

        // 4. LEEMOS RESULTADOS
        const gcodeContent = fs.readFileSync(gcodeFile, 'utf-8');
        const filamentMatchMm  = gcodeContent.match(/filament used \[mm\] = ([0-9.]+)/i);
        const filamentMatchCm3 = gcodeContent.match(/filament used \[cm3\] = ([0-9.]+)/i);
        const timeMatch        = gcodeContent.match(/estimated printing time .* = ([0-9hms ]+)/i);

        // 5. ENVIAMOS LA RESPUESTA
        // Importante: Devolvemos 'finalStl' para que PHP sepa qué archivo mostrar en el visor
        console.log(JSON.stringify({
            gcodeFile: gcodeFile,
            finalStl: path.basename(stlFile), // <--- ESTO ES CLAVE PARA EL VISOR
            filamentUsedMm: filamentMatchMm ? parseFloat(filamentMatchMm[1]) : 0,
            filamentUsedCm3: filamentMatchCm3 ? parseFloat(filamentMatchCm3[1]) : 0,
            estimatedTime: timeMatch ? timeMatch[1].trim() : "0h 0m 0s"
        }));

    } catch (err) {
        console.error(JSON.stringify({ error: err.message }));
        process.exit(1);
    }
}

procesarArchivo();