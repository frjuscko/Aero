<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aero Engine — Hyper-Space</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 1;
            pointer-events: none;
        }
        main, header, footer { position: relative; z-index: 2; }
        header, footer { background: transparent !important; border-color: rgba(255, 255, 255, 0.05) !important; }
        .stat-card, .feature-card {
            background: rgba(10, 15, 30, 0.4) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        body { 
    /* Dégradé radial simulant une nébuleuse lointaine violette/bleue très sombre */
    background: radial-gradient(circle at 50% 50%, #090d22 0%, #020617 70%, #010208 100%) !important; 
    color: #f8fafc !important; 
    overflow-x: hidden; 
}
        .hero h1 { font-size: 4rem; letter-spacing: -0.04em; }
        .hero p, .stat-lbl, .feature-card p, footer p { color: #8b949e !important; }
    </style>
</head>
<body>

    <div id="canvas-container"></div>

    <header>
        <div class="logo-container">
            <span class="logo-text" style="color: #fff;">Aero.</span>
            <span class="badge-status" style="background: rgba(139, 92, 246, 0.2); color: #a78bfa;">Quantum Core</span>
        </div>
    </header>

    <main>
        <section class="hero">
            <h1 style="color: #fff;">Architectured for <span class="gradient-text">Absolute Velocity</span></h1>
            <p>L'expérience cinématique rencontre la performance brute. Aero redéfinit le cycle de vie d'une application PHP.</p>
            <div class="terminal" style="background: rgba(1, 4, 9, 0.7); border: 1px solid rgba(139, 92, 246, 0.2);">
                <span>$ composer create-project desinova/aero</span>
            </div>
        </section>

        <section class="diagnostics">
            <div class="stat-card">
                <div class="stat-val" style="color: #a78bfa; font-family: monospace;">
                    <?= number_format((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2) . ' ms'; ?>
                </div>
                <div class="stat-lbl">Engine Latency</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: #34d399; font-family: monospace;">
                    <?= number_format(memory_get_usage() / 1024 / 1024, 2) . ' MB'; ?>
                </div>
                <div class="stat-lbl">Core Weight</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: #60a5fa; font-family: monospace;">
                    <?= count(get_included_files()); ?>
                </div>
                <div class="stat-lbl">Loaded Structs</div>
            </div>
        </section>
    </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/EffectComposer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/RenderPass.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/ShaderPass.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/CopyShader.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/LuminosityHighPassShader.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/UnrealBloomPass.js"></script>

<script>
    // --- SCÈNE ET RENDERER ---
    const container = document.getElementById('canvas-container');
    const scene = new THREE.Scene();
    
    const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 150);
    camera.position.set(0, 0, 20);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.toneMapping = THREE.ReinhardToneMapping;
    renderer.toneMappingExposure = 1.6;
    container.appendChild(renderer.domElement);

    // --- PIPELINE DE POST-PROCESSING ---
    const renderPass = new THREE.RenderPass(scene, camera);
    const bloomPass = new THREE.UnrealBloomPass(new THREE.Vector2(window.innerWidth, window.innerHeight), 2.2, 0.65, 0.1);
    
    const composer = new THREE.EffectComposer(renderer);
    composer.addPass(renderPass);
    composer.addPass(bloomPass);

    // --- GESTION INTERACTIVE DE LA SOURIS ---
    let mouse = { x: 0, y: 0, targetX: 0, targetY: 0, worldX: 0, worldY: 0 };
    
    window.addEventListener('mousemove', (e) => {
        mouse.targetX = (e.clientX / window.innerWidth) * 2 - 1;
        mouse.targetY = -(e.clientY / window.innerHeight) * 2 + 1;

        // Convertir les coordonnées écran en coordonnées 3D pour la nuée suiveuse
        const vector = new THREE.Vector3(mouse.targetX, mouse.targetY, 0.5);
        vector.unproject(camera);
        const dir = vector.sub(camera.position).normalize();
        const distance = -camera.position.z / dir.z;
        const pos3D = camera.position.clone().add(dir.multiplyScalar(distance));
        
        mouse.worldX = pos3D.x;
        mouse.worldY = pos3D.y;
    });

    // --- 0. SYSTÈME DE FOND : STARFIELD EN PARALLAXE ---
    const starsCount = 2000;
    const starsGeometry = new THREE.BufferGeometry();
    const starsPositions = new Float32Array(starsCount * 3);
    const starsBrightness = new Float32Array(starsCount);

    for (let i = 0; i < starsCount; i++) {
        starsPositions[i * 3] = (Math.random() - 0.5) * 160;
        starsPositions[i * 3 + 1] = (Math.random() - 0.5) * 160;
        starsPositions[i * 3 + 2] = (Math.random() - 0.5) * 80 - 30; // Distribuées à l'arrière-plan
        starsBrightness[i] = Math.random();
    }

    starsGeometry.setAttribute('position', new THREE.BufferAttribute(starsPositions, 3));
    starsGeometry.setAttribute('aBrightness', new THREE.BufferAttribute(starsBrightness, 1));

    const starsVertex = `
        attribute float aBrightness;
        uniform float uTime;
        varying float vAlpha;
        void main() {
            vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
            gl_Position = projectionMatrix * mvPosition;
            gl_PointSize = 1.3 * (20.0 / -mvPosition.z);
            vAlpha = 0.2 + sin(uTime * 2.0 + aBrightness * 12.0) * 0.5;
        }
    `;

    const starsFragment = `
        varying float vAlpha;
        void main() {
            float dist = distance(gl_PointCoord, vec2(0.5));
            if (dist > 0.5) discard;
            gl_FragColor = vec4(1.0, 1.0, 1.0, vAlpha);
        }
    `;

    const starsUniforms = { uTime: { value: 0 } };
    const starsMaterial = new THREE.ShaderMaterial({
        vertexShader: starsVertex,
        fragmentShader: starsFragment,
        uniforms: starsUniforms,
        transparent: true,
        depthWrite: false
    });

    const starField = new THREE.Points(starsGeometry, starsMaterial);
    scene.add(starField);

    // --- 1. SYSTÈME : LA NÉBULEUSE DE FOND (SHADERS GLSL) ---
    const particleCount = 20000;
    const geometry = new THREE.BufferGeometry();
    const positions = new Float32Array(particleCount * 3);
    const randoms = new Float32Array(particleCount);

    for (let i = 0; i < particleCount; i++) {
        const r = 4 + Math.random() * 6;
        const theta = Math.random() * Math.PI * 2;
        const phi = Math.acos((Math.random() * 2) - 1);
        positions[i * 3] = r * Math.sin(phi) * Math.cos(theta);
        positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
        positions[i * 3 + 2] = r * Math.cos(phi);
        randoms[i] = Math.random();
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('aRandom', new THREE.BufferAttribute(randoms, 1));

    const vertexShader = `
        uniform float uTime;
        uniform vec2 uMouse;
        attribute float aRandom;
        varying float vGlow;
        void main() {
            vec3 pos = position;
            float wave = sin(pos.x * 0.4 + uTime * 1.5) * cos(pos.y * 0.4 + uTime * 1.5) * 1.2;
            pos.x += wave * sin(aRandom * 6.28);
            pos.y += wave * cos(aRandom * 6.28);
            pos.z += sin(pos.x * 0.2 + uTime) * 2.0;

            float distToMouse = distance(pos.xy, uMouse * 12.0);
            if(distToMouse < 6.0) {
                float force = (1.0 - (distToMouse / 6.0)) * 2.5;
                pos.xy += (uMouse * 12.0 - pos.xy) * force * 0.2;
            }
            vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
            gl_Position = projectionMatrix * mvPosition;
            gl_PointSize = (3.0 * (1.0 + aRandom)) * (15.0 / -mvPosition.z);
            vGlow = wave;
        }
    `;

    const fragmentShader = `
        varying float vGlow;
        void main() {
            float dist = distance(gl_PointCoord, vec2(0.5));
            if (dist > 0.5) discard;
            vec3 colorA = vec3(0.54, 0.36, 0.96); // Violet
            vec3 colorB = vec3(0.22, 0.74, 0.97); // Turquoise
            vec3 finalColor = mix(colorA, colorB, (vGlow + 1.2) / 2.4);
            float alpha = smoothstep(0.5, 0.1, dist) * 0.6;
            gl_FragColor = vec4(finalColor, alpha);
        }
    `;

    const customUniforms = {
        uTime: { value: 0 },
        uMouse: { value: new THREE.Vector2(0, 0) }
    };

    const material = new THREE.ShaderMaterial({
        vertexShader: vertexShader,
        fragmentShader: fragmentShader,
        uniforms: customUniforms,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending
    });

    const particleSystem = new THREE.Points(geometry, material);
    scene.add(particleSystem);

    // --- 2. NOUVEAU SYSTÈME : LA NUÉE SUIVEUSE (POUSSIÈRE DE CURSEUR) ---
    const trailCount = 1000;
    const trailGeometry = new THREE.BufferGeometry();
    const trailPositions = new Float32Array(trailCount * 3);
    const trailVels = [];
    const trailLifes = new Float32Array(trailCount);

    for(let i=0; i < trailCount; i++) {
        trailPositions[i*3] = 0;
        trailPositions[i*3+1] = 0;
        trailPositions[i*3+2] = 0;
        trailLifes[i] = 0.0;
        trailVels.push(new THREE.Vector3(
            (Math.random() - 0.5) * 0.1,
            (Math.random() - 0.5) * 0.1,
            (Math.random() - 0.5) * 0.1
        ));
    }

    trailGeometry.setAttribute('position', new THREE.BufferAttribute(trailPositions, 3));
    trailGeometry.setAttribute('aLife', new THREE.BufferAttribute(trailLifes, 1));

    const trailVertex = `
        attribute float aLife;
        varying float vLife;
        void main() {
            vLife = aLife;
            vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
            gl_Position = projectionMatrix * mvPosition;
            gl_PointSize = (6.0 * aLife) * (15.0 / -mvPosition.z);
        }
    `;

    const trailFragment = `
        varying float vLife;
        void main() {
            float dist = distance(gl_PointCoord, vec2(0.5));
            if (dist > 0.5) discard;
            vec3 color = vec3(1.0, 0.85, 0.3); 
            float alpha = smoothstep(0.5, 0.0, dist) * vLife;
            gl_FragColor = vec4(color, alpha);
        }
    `;

    const trailMaterial = new THREE.ShaderMaterial({
        vertexShader: trailVertex,
        fragmentShader: trailFragment,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending
    });

    const trailSystem = new THREE.Points(trailGeometry, trailMaterial);
    scene.add(trailSystem);

    let currentTrailIndex = 0;

    // --- BOUCLE TEMPS RÉEL ---
    const clock = new THREE.Clock();

    function tick() {
        requestAnimationFrame(tick);
        const elapsedTime = clock.getElapsedTime();

        // Lissage de l'inertie de la souris
        mouse.x += (mouse.targetX - mouse.x) * 0.05;
        mouse.y += (mouse.targetY - mouse.y) * 0.05;

        // Synchronisation des horloges uniformes GPU
        customUniforms.uTime.value = elapsedTime;
        customUniforms.uMouse.value.set(mouse.x, mouse.y);
        starsUniforms.uTime.value = elapsedTime;

        // Rotations lentes globales pour simuler la dérive orbitale
        particleSystem.rotation.y = elapsedTime * 0.03;
        starField.rotation.z = elapsedTime * 0.005;

        // Effet de parallaxe inversé sur les étoiles lointaines
        starField.position.x = -mouse.x * 2.5;
        starField.position.y = -mouse.y * 2.5;

        // --- ANIMATION ET RECRUTEMENT DE LA TRAÎNÉE ---
        const pArray = trailGeometry.attributes.position.array;
        const lArray = trailGeometry.attributes.aLife.array;

        // Injection continue de poussière sous la souris
        for(let k=0; k<5; k++) {
            let intIdx = currentTrailIndex * 3;
            pArray[intIdx] = mouse.worldX + (Math.random() - 0.5) * 0.2;
            pArray[intIdx+1] = mouse.worldY + (Math.random() - 0.5) * 0.2;
            pArray[intIdx+2] = (Math.random() - 0.5) * 0.5;
            
            lArray[currentTrailIndex] = 1.0;
            
            trailVels[currentTrailIndex].set(
                (Math.random() - 0.5) * 0.06,
                (Math.random() - 0.5) * 0.06,
                (Math.random() - 0.5) * 0.06
            );

            currentTrailIndex = (currentTrailIndex + 1) % trailCount;
        }

        // Cycle de vie physique des éclats de la traînée
        for(let i=0; i<trailCount; i++) {
            if(lArray[i] > 0) {
                lArray[i] -= 0.015;
                pArray[i*3] += trailVels[i].x;
                pArray[i*3+1] += trailVels[i].y;
                pArray[i*3+2] += trailVels[i].z;
            } else {
                lArray[i] = 0.0;
            }
        }

        trailGeometry.attributes.position.needsUpdate = true;
        trailGeometry.attributes.aLife.needsUpdate = true;

        // Exécution du rendu post-traitement complet (Nébuleuse + Traînée + Étoiles Scintillantes + Bloom)
        composer.render();
    }

    tick();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        composer.setSize(window.innerWidth, window.innerHeight);
    });
</script>
</body>
</html>