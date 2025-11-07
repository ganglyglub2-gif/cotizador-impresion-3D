const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');

const stlFile = process.argv[2];
if (!stlFile || !fs.existsSync(stlFile)) {
  console.error(JSON.stringify({ error: 'Archivo STL no encontrado' }));
  process.exit(1);
}

const gcodeFile = stlFile.replace(/\.stl$/i, '.gcode');

// Ruta al perfil de PrusaSlicer
const profilePath = '/app/profiles/Ender3_Bltouch.ini';

// Ruta absoluta del binario
const prusaCmd = `/app/squashfs-root/squashfs-root/usr/bin/prusa-slicer --export-gcode -o ${gcodeFile} --load ${profilePath} ${stlFile}`;

exec(prusaCmd, (error, stdout, stderr) => {
  if (error) {
    console.error(JSON.stringify({ error: `Error al generar G-code: ${error.message}`, stderr }));
    return;
  }

  if (!fs.existsSync(gcodeFile)) {
    console.error(JSON.stringify({ error: 'No se generó el archivo G-code' }));
    return;
  }

  const gcodeContent = fs.readFileSync(gcodeFile, 'utf-8');

  const filamentMatchMm  = gcodeContent.match(/filament used \[mm\] = ([0-9.]+)/i);
  const filamentMatchCm3 = gcodeContent.match(/filament used \[cm3\] = ([0-9.]+)/i);
  const timeMatch        = gcodeContent.match(/estimated printing time .* = ([0-9hms ]+)/i);

  const result = {
    gcodeFile,
    filamentUsedMm: filamentMatchMm ? parseFloat(filamentMatchMm[1]) : null,
    filamentUsedCm3: filamentMatchCm3 ? parseFloat(filamentMatchCm3[1]) : null,
    estimatedTime: timeMatch ? timeMatch[1].trim() : null
  };

  console.log(JSON.stringify(result));
});