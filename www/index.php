<?php
/*error_reporting(E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);*/
//require_once './include/bd_pdo.php';
require_once './include/util.php';
// Iniciamos la sesión aquí para que el header.php la tenga disponible
session_start(); 
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <title>Cotizador 3D | Facultad de ingeniería</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        
        <link rel="stylesheet" href="./css/bootstrap-ulsa.min.css" type="text/css">
        <link rel="stylesheet" href="./css/jquery-ui.css" type="text/css">
        <link rel="stylesheet" href="./css/indivisa.css" type="text/css">
        <link rel="stylesheet" href="./css/style.css" type="text/css">
        <link rel="stylesheet" href="./css/fa_all.css" type="text/css">
        
        <link rel="stylesheet" href="estilos.css">
        
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

        <script>
            let datosImpresoras = {};
            let mesh; 
            const keyState = {}; 

            // Listener para teclado (A/D/Flechas)
            window.addEventListener('keydown', (e) => {
                const key = e.key.toLowerCase();
                if (key === 'a' || key === 'arrowleft') { keyState['a'] = true; }
                if (key === 'd' || key === 'arrowright') { keyState['d'] = true; }
            });
            window.addEventListener('keyup', (e) => {
                const key = e.key.toLowerCase();
                if (key === 'a' || key === 'arrowleft') { keyState['a'] = false; }
                if (key === 'd' || key === 'arrowright') { keyState['d'] = false; }
            });

            // Cargar Impresoras
            async function cargarImpresoras() {
                // Hacemos una pausa de 10ms para asegurar que el DOM esté listo
                await new Promise(resolve => setTimeout(resolve, 10));

                const selectMarca = document.getElementById('marca');
                if (!selectMarca) {
                    console.error("No se encontró el <select> #marca");
                    return;
                }

                const res = await fetch('impresoras.json');
                datosImpresoras = await res.json();
                
                selectMarca.innerHTML = '';
                for (const [clave, data] of Object.entries(datosImpresoras)) {
                    const option = document.createElement('option');
                    option.value = clave;
                    option.textContent = data.nombre;
                    selectMarca.appendChild(option);
                }
                actualizarMateriales();
            }

            // Actualizar Materiales
            function actualizarMateriales() {
                const marca = document.getElementById('marca').value;
                const select = document.getElementById('material');
                select.innerHTML = '';
                if (!datosImpresoras[marca]) return;
                const materiales = datosImpresoras[marca].materiales;
                for (const [clave, nombre] of Object.entries(materiales)) {
                    const option = document.createElement('option');
                    option.value = clave;
                    option.textContent = nombre;
                    select.appendChild(option);
                }
            }

            // Mostrar Loader
            function mostrarLoader() {
                const overlay = document.getElementById('loader-overlay');
                const barra = document.querySelector('.barra-progreso');
                const texto = document.querySelector('.loader-text');
                overlay.style.display = 'flex';
                barra.style.width = '0%';
                texto.textContent = 'Procesando archivo...';
                let progresoSimulado = 0;
                let velocidad = 0.5 + Math.random() * 1;
                let intervalo = setInterval(() => {
                if (progresoSimulado < 97) {
                    progresoSimulado += velocidad;
                    barra.style.width = progresoSimulado + '%';
                }
                }, 30);
                return () => {
                    clearInterval(intervalo);
                    let progresoFinal = progresoSimulado;
                    const animarFinal = setInterval(() => {
                        progresoFinal += 1;
                        if (progresoFinal >= 100) {
                        progresoFinal = 100;
                        clearInterval(animarFinal);
                        texto.textContent = 'Completado';
                        setTimeout(() => overlay.style.display = 'none', 400);
                        }
                        barra.style.width = progresoFinal + '%';
                    }, 25);
                };
            }

            // Cargar Visualizador 3D
            function cargarVisualizador(stlUrl) {
                const modelContainer = document.getElementById("model");
                if (!modelContainer) {
                    console.error("El contenedor #model no se encontró.");
                    return;
                }
                while (modelContainer.firstChild) {
                    modelContainer.removeChild(modelContainer.firstChild);
                }
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(75, modelContainer.clientWidth / 500, 0.1, 1000);
                const renderer = new THREE.WebGLRenderer({ antialias: true });
                renderer.setSize(modelContainer.clientWidth, 500); 
                modelContainer.appendChild(renderer.domElement);
                const controls = new THREE.OrbitControls(camera, renderer.domElement); 
                controls.enablePan = true; 
                controls.enableZoom = true;
                controls.enableDamping = true;
                controls.dampingFactor = 0.05; 
                const hemiLight = new THREE.HemisphereLight(0xffffff, 0x444444, 1.2);
                scene.add(hemiLight);
                const dirLight = new THREE.DirectionalLight(0xffffff, 1.0);
                dirLight.position.set(5, 10, 7.5);
                scene.add(dirLight);
                const loader = new THREE.STLLoader(); 
                loader.load(stlUrl, function (geometry) {
                    const material = new THREE.MeshPhongMaterial({ color: 0x0077ff });
                    mesh = new THREE.Mesh(geometry, material); 
                    scene.add(mesh);
                    const box = new THREE.Box3().setFromObject(mesh);
                    const center = box.getCenter(new THREE.Vector3());
                    mesh.position.sub(center);
                    const size = box.getSize(new THREE.Vector3()).length();
                    camera.position.x = 0; 
                    camera.position.z = size * 1.5; 
                    camera.position.y = size * 0.4; 
                    controls.target.set(0, 0, 0); 
                    controls.update(); 
                    animate(); 
                }, undefined, function (error) { 
                    console.error("Error al cargar el STL:", error);
                    modelContainer.innerHTML = "<p style='color:red; padding: 20px;'>❌ No se pudo cargar el modelo 3D.</p>";
                });
                function animate() {
                    requestAnimationFrame(animate);
                    if (mesh) { 
                        const rotationSpeed = 0.02; 
                        if (keyState['a']) { mesh.rotation.z -= rotationSpeed; }
                        if (keyState['d']) { mesh.rotation.z += rotationSpeed; }
                    }
                    controls.update(); 
                    renderer.render(scene, camera);
                }
                function onWindowResize() {
                    if (modelContainer) {
                        const width = modelContainer.clientWidth;
                        const height = 500;
                        camera.aspect = width / height;
                        camera.updateProjectionMatrix();
                        renderer.setSize(width, height);
                    }
                }
                new ResizeObserver(onWindowResize).observe(modelContainer);
                onWindowResize();
            }

            // Función Principal (Ejecutar al cargar la página)
            window.addEventListener('DOMContentLoaded', () => {
                // Inicia la carga de impresoras
                cargarImpresoras();
                
                // Asigna el listener al selector (aunque esté vacío al inicio)
                const selectMarca = document.getElementById('marca');
                if (selectMarca) {
                    selectMarca.addEventListener('change', actualizarMateriales);
                }

                // Asigna el listener al formulario
                const form = document.querySelector('form.formulario-cotizador'); 
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        const finalizarLoader = mostrarLoader();
                        const formData = new FormData(form);
                        fetch(form.action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            finalizarLoader();
                            setTimeout(() => {
                            document.querySelector('.resultado').innerHTML = html;
                            const stlData = document.getElementById('stl-data-url');
                            if (stlData && stlData.dataset.url) {
                                cargarVisualizador(stlData.dataset.url);
                            } else {
                                console.error("No se encontró la URL del STL en la respuesta.");
                                const modelContainer = document.getElementById("model");
                                if(modelContainer) modelContainer.innerHTML = "<p style='color:red; padding: 20px;'>Error: No se recibió la ruta del modelo 3D.</p>";
                            }
                            }, 500); 
                        })
                        .catch(() => {
                            alert('Error al procesar el archivo.');
                            document.getElementById('loader-overlay').style.display = 'none';
                        });
                    });
                }
            });
        </script>
        
        <script src="./js/util.js"></script>
    </head>
    <body style="display: block;">
        
        <?php
        include("./include/header.php");
        ?>
        
        <main class="container content marco">
            
            
           <p class="subtitulo-cotizador">Sube tu archivo (STL, STEP, OBJ, 3MF), selecciona la impresora y el material para obtener una cotización estimada.</p>
            <hr>
            
            <form action="procesar.php" method="POST" enctype="multipart/form-data" class="formulario-cotizador">
                
                <div class="form-group">
                    <label for="marca" class="font-weight-bold">Selecciona la impresora:</label>
                    <select name="marca" id="marca" class="form-control" required>
                        <option>Cargando impresoras...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="material" class="font-weight-bold">Selecciona el material:</label>
                    <select name="material" id="material" class="form-control" required></select>
                </div>
                
                <div class="form-group">
                        <label for="stl_file" class="font-weight-bold">Sube tu archivo (STL, STEP, OBJ, 3MF):</label>
                        <input type="file" name="stl_file" id="stl_file" class="form-control-file" accept=".stl,.step,.stp,.obj,.3mf" required>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-success" style="margin-top: 20px; padding: 10px 30px; font-size: 1.1em;">
                        <i class="ing-listado-menus"></i>&nbsp; Calcular cotización
                    </button>
                </div>
            </form>
            
            <hr style="margin-top: 30px; margin-bottom: 30px;">
            
            <div class="resultado">
                </div>
            
        </main><?php
        include("./include/footer.php");
        ?>
        
        <div id="loader-overlay">
            <div class="loader-container">
                <div class="loader-spinner"></div>
                <p class="loader-text">Procesando archivo...</p>
                <div class="barra-contenedor">
                    <div class="barra-progreso"></div>
                </div>
            </div>
        </div>
                
        <script src="./js/jquery.min.js"></script>
        <script src="./js/bootstrap/popper.min.js"></script>
        <script src="./js/bootstrap/bootstrap.min.js"></script>
        <script src="./js/util.js"></script>
        <script src="./js/sidebarmenu.js"></script>

    </body>
</html>