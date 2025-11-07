<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cotizador 3D</title>
  <link rel="stylesheet" href="estilos.css">

  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

  <script>
  let datosImpresoras = {};

  async function cargarImpresoras() {
    const res = await fetch('impresoras.json');
    datosImpresoras = await res.json();

    const selectMarca = document.getElementById('marca');
    selectMarca.innerHTML = '';

    for (const [clave, data] of Object.entries(datosImpresoras)) {
      const option = document.createElement('option');
      option.value = clave;
      option.textContent = data.nombre;
      selectMarca.appendChild(option);
    }

    actualizarMateriales();
  }

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
  
  // Variables para el teclado y el modelo
  let mesh; 
  const keyState = {}; 

  // Listeners para el teclado
  window.addEventListener('keydown', (e) => {
        const key = e.key.toLowerCase();
        if (key === 'a' || key === 'arrowleft') {
            keyState['a'] = true; 
        }
        if (key === 'd' || key === 'arrowright') {
            keyState['d'] = true; 
        }
    });
    window.addEventListener('keyup', (e) => {
        const key = e.key.toLowerCase();
        if (key === 'a' || key === 'arrowleft') {
            keyState['a'] = false;
        }
        if (key === 'd' || key === 'arrowright') {
            keyState['d'] = false;
        }
    });

// --- Función del visualizador 3D ---
  function cargarVisualizador(stlUrl) {
    
    const modelContainer = document.getElementById("model");
    if (!modelContainer) {
      console.error("El contenedor #model no se encontró.");
      return;
    }

    // Limpiar contenedor
    while (modelContainer.firstChild) {
        modelContainer.removeChild(modelContainer.firstChild);
    }

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, modelContainer.clientWidth / 500, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(modelContainer.clientWidth, 500); 
    modelContainer.appendChild(renderer.domElement);

    const controls = new THREE.OrbitControls(camera, renderer.domElement); 
    
    // Controles del Mouse
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

        // Centrar modelo
        const box = new THREE.Box3().setFromObject(mesh);
        const center = box.getCenter(new THREE.Vector3());
        mesh.position.sub(center);

        // --- Ajuste de la cámara ---
        const size = box.getSize(new THREE.Vector3()).length();
        
        camera.position.x = 0; 
        camera.position.z = size * 1.5; 
        camera.position.y = size * 0.4; 
        controls.target.set(0, 0, 0); 
        controls.update(); 
        

        // Iniciar la animación
        animate(); 
        
    }, undefined, function (error) { 
        console.error("Error al cargar el STL:", error); 
        modelContainer.innerHTML = "<p style='color:red; padding: 20px;'>❌ No se pudo cargar el modelo 3D.</p>";
    });

    // --- Bucle de Animación ---
    function animate() {
        requestAnimationFrame(animate);

        // Lógica de Teclado
        if (mesh) { 
            const rotationSpeed = 0.02; 
            
            
            if (keyState['a']) {
                mesh.rotation.z -= rotationSpeed; 
            }
            if (keyState['d']) {
                mesh.rotation.z += rotationSpeed;
            }
        }
        
        controls.update(); 
        renderer.render(scene, camera);
    }

    // Ajustar el tamaño si el contenedor cambia
    function onWindowResize() {
        if (modelContainer) {
            const width = modelContainer.clientWidth;
            const height = 500; // Altura fija
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        }
    }
    
    new ResizeObserver(onWindowResize).observe(modelContainer);
    onWindowResize(); // Llamada inicial
  }


  window.addEventListener('DOMContentLoaded', () => {
    cargarImpresoras();

    document.getElementById('marca').addEventListener('change', actualizarMateriales);

    const form = document.querySelector('form');
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
  });
  </script>
</head>
<body>
  <div class="contenedor">
    <h1>Cotizador de Impresión 3D</h1>
    <form action="procesar.php" method="POST" enctype="multipart/form-data" class="formulario">
      <div class="campo">
        <label for="marca">Selecciona la impresora:</label>
        <select name="marca" id="marca" required></select>
      </div>
      <div class="campo">
        <label for="material">Selecciona el material:</label>
        <select name="material" id="material" required></select>
      </div>
      <div class="campo">
        <label for="stl_file">Sube tu archivo STL:</label>
        <input type="file" name="stl_file" id="stl_file" accept=".stl" required>
      </div>
      <button type="submit" class="boton">Calcular cotización</button>
    </form>
    <div class="resultado"></div>
  </div>

  <div id="loader-overlay">
    <div class="loader-container">
      <div class="loader-spinner"></div>
      <p class="loader-text">Procesando archivo...</p>
      <div class="barra-contenedor">
        <div class="barra-progreso"></div>
      </div>
    </div>
  </div>
</body>
</html>