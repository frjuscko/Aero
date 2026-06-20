<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aero Framework — {{ $code }}</title>
    <link rel="stylesheet" href="{{ asset('/css/app.css') }}">
    <style>
        /* Base spatiale immersive */
        body {
            background: radial-gradient(circle at 50% 50%, #0d0614 0%, #030206 80%, #000000 100%) !important;
            color: #f8fafc !important;
            overflow: hidden;
            margin: 0;
            height: 100vh;
        }

        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: 1;
            pointer-events: none;
        }

        .aero-nav {
            position: relative;
            z-index: 10;
            background: transparent !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        }
        .aero-nav a { color: #fff !important; }

        /* Alignement et cartes en verre flouté */
        .error-wrapper {
            position: relative;
            z-index: 5;
            height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .error-card {
            text-align: center;
            max-width: 550px;
            width: 100%;
            background: rgba(10, 5, 15, 0.4) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(239, 68, 68, 0.2) !important; /* Bordure alerte */
            padding: 40px 30px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        /* Effet Glitch Néon sur le Code */
        .error-code {
            font-size: 96px;
            font-weight: 900;
            color: #0077ff;
            line-height: 1;
            margin-bottom: 5px;
            letter-spacing: -0.04em;
            text-shadow: 0 0 20px rgba(239, 68, 68, 0.6);
            animation: glitch 3s infinite;
        }

        .error-badge {
            display: inline-block;
            background: rgba(239, 68, 68, 0.1);
            color: #0077ff;
            padding: 6px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .error-card h2 {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 15px;
        }

        .error-desc {
            color: #9ca3af !important;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .error-desc strong {
            font-family: monospace;
            background: rgba(239, 68, 68, 0.15) !important;
            color: #fca5a5 !important;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-primary {
            background: #ef4444 !important;
            border: none !important;
            color: #fff !important;
            padding: 12px 28px !important;
            border-radius: 12px !important;
            font-weight: 600;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.4);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #dc2626 !important;
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.7);
            transform: translateY(-2px);
        }

        /* Animation de Glitch minimaliste */
        @keyframes glitch {
            0%, 100% { transform: none; opacity: 1; }
            7% { transform: skew(-3deg, 1deg); color: #f87171; }
            10% { transform: none; }
            15% { transform: skew(2deg, -1deg); }
            18% { transform: none; }
        }
    </style>
</head>
<body>

    <div id="canvas-container"></div>

    <nav class="aero-nav">
        <div class="aero-container nav-content">
            <a href="{{route('home')}}" class="logo">Aero<span>.</span></a>
            <div class="nav-links">
                <a href="{{route('home')}}">Accueil</a>
                <a href="{{route('home')}}">Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="error-wrapper">
        <div class="error-card">
            <span class="error-badge">SYSTEM CRASH — ORBITAL DRIFT</span>
            <div class="error-code">{{ $code }}</div>
            
            <h2>
                <?php 
                    echo match($code) {
                        404 => "Détresse Spatiale : Page Introuvable",
                        400 => "Signal Corrompu : Requête Invalide",
                        403 => "Périmètre Sécurisé : Accès Refusé",
                        500 => "Avarie Majeure : Erreur Interne",
                        default => "Exception Noyau Détectée"
                    };
                ?>
            </h2>
            
            <p class="error-desc">
                {!! $message !!}
            </p>
            
            <div class="hero-actions">
                <a href="{{route('home')}}" class="btn btn-primary">Réinitialiser les propulseurs</a>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/EffectComposer.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/RenderPass.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/ShaderPass.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/CopyShader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/LuminosityHighPassShader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/UnrealBloomPass.js"></script>

    <script>
        const container = document.getElementById('canvas-container');
        const scene = new THREE.Scene();
        
        const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 150);
        camera.position.set(0, 0, 25);

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.toneMapping = THREE.ReinhardToneMapping;
        renderer.toneMappingExposure = 1.4;
        container.appendChild(renderer.domElement);

        // CONFIG PIPELINE GLOW (BLOOM)
        const renderPass = new THREE.RenderPass(scene, camera);
        const bloomPass = new THREE.UnrealBloomPass(new THREE.Vector2(window.innerWidth, window.innerHeight), 2.0, 0.7, 0.1);
        const composer = new THREE.EffectComposer(renderer);
        composer.addPass(renderPass);
        composer.addPass(bloomPass);

        // --- GESTION INTERACTIVE SOURIS ---
        let mouse = { x: 0, y: 0, targetX: 0, targetY: 0 };
        window.addEventListener('mousemove', (e) => {
            mouse.targetX = (e.clientX / window.innerWidth) * 2 - 1;
            mouse.targetY = -(e.clientY / window.innerHeight) * 2 + 1;
        });

        // --- 1. FONDS D'ÉTOILES DRIFTANT ---
        const starsCount = 1500;
        const starsGeo = new THREE.BufferGeometry();
        const starsPos = new Float32Array(starsCount * 3);
        for(let i=0; i<starsCount*3; i++) {
            starsPos[i] = (Math.random() - 0.5) * 150;
        }
        starsGeo.setAttribute('position', new THREE.BufferAttribute(starsPos, 3));
        const starsMat = new THREE.PointsMaterial({ color: 0x4b5563, size: 0.15, transparent: true });
        const starField = new THREE.Points(starsGeo, starsMat);
        scene.add(starField);

        // --- 2. ASTÉROÏDE 3D CRASHÉ (GÉNÉRATIF PAR SHADER) ---
        // Icosahedron low-poly pour un effet rocheux tranchant
        const asteroidGeo = new THREE.IcosahedronGeometry(5, 3);
        
        // Customisation des sommets pour rendre la roche asymétrique et accidentée
        const posAttr = asteroidGeo.attributes.position;
        for (let i = 0; i < posAttr.count; i++) {
            let x = posAttr.getX(i);
            let y = posAttr.getY(i);
            let z = posAttr.getZ(i);

            // Simulation de cratères par bruits trigonométriques
            let noise = Math.sin(x*1.2) * cos(y*1.2) * 0.7 + Math.cos(z*2.0)*0.3;
            posAttr.setXYZ(i, x + noise, y + noise, z + noise);
        }
        asteroidGeo.computeVertexNormals();

        // Shaders magmatiques/alerte pour l'astéroïde
        const asteroVertex = `
            varying vec3 vNormal;
            varying vec3 vPosition;
            void main() {
                vNormal = normalize(normalMatrix * normal);
                vPosition = position;
                gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
            }
        `;
        const asteroFragment = `
            varying vec3 vNormal;
            varying vec3 vPosition;
            uniform float uTime;
            void main() {
                // Éclairage de base directionnel
                vec3 lightDir = normalize(vec3(1.0, 1.0, 1.0));
                float prod = max(dot(vNormal, lightDir), 0.1);
                
                // Veines magmatiques d'alerte rouge clignotante
                float pulse = sin(vPosition.x * 2.0 + uTime * 2.0) * cos(vPosition.y * 2.0 + uTime * 2.0);
                vec3 baseColor = vec3(0.12, 0.08, 0.18); // Roche sombre sombre
                vec3 alertColor = vec3(0.93, 0.27, 0.27); // Rouge #ef4444
                
                vec3 finalColor = mix(baseColor, alertColor, smoothstep(0.3, 0.7, pulse) * 0.4);
                gl_FragColor = vec4(finalColor * prod * 1.5, 1.0);
            }
        `;

        const asteroUniforms = { uTime: { value: 0 } };
        const asteroidMat = new THREE.ShaderMaterial({
            vertexShader: asteroVertex,
            fragmentShader: asteroFragment,
            uniforms: asteroUniforms,
            flatShading: true // Force l'effet facetté "low-poly" ultra stylisé
        });

        const asteroidMesh = new THREE.Mesh(asteroidGeo, asteroidMat);
        // Décalé sur le côté gauche pour encadrer la carte
        asteroidMesh.position.set(-10, 2, 0);
        scene.add(asteroidMesh);

        // --- 3. L'ASTRONAUTE EN DÉRIVE COMPLÈTE (PARTICULES FLOATING) ---
        const astroCount = 450;
        const astroGeo = new THREE.BufferGeometry();
        const astroPositions = new Float32Array(astroCount * 3);

        for (let i = 0; i < astroCount; i++) {
            // Silhouette humanoïde en apesanteur
            let x, y, z;
            if (i < 120) { // Casque / Tête
                let theta = Math.random() * Math.PI * 2;
                let phi = Math.acos((Math.random() * 2) - 1);
                x = 0.6 * Math.sin(phi) * Math.cos(theta);
                y = (0.6 * Math.sin(phi) * Math.sin(theta)) + 1.2;
                z = 0.6 * Math.cos(phi);
            } else { // Combinaison et membres ballants
                x = (Math.random() - 0.5) * 1.2;
                y = (Math.random() - 0.5) * 2.2;
                z = (Math.random() - 0.5) * 0.8;
            }
            astroPositions[i * 3] = x;
            astroPositions[i * 3 + 1] = y;
            astroPositions[i * 3 + 2] = z;
        }
        astroGeo.setAttribute('position', new THREE.BufferAttribute(astroPositions, 3));
        const astroMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.18, transparent: true, opacity: 0.85 });
        const astroMesh = new THREE.Points(astroGeo, astroMat);
        
        // Positionné à droite de la carte, légèrement plus proche de l'écran
        astroMesh.position.set(10, -2, 5);
        scene.add(astroMesh);


        // --- BOUCLE D'ANIMATION TICK ---
        const clock = new THREE.Clock();

        function tick() {
            requestAnimationFrame(tick);
            const elapsedTime = clock.getElapsedTime();

            // Inertie fluide de la souris
            mouse.x += (mouse.targetX - mouse.x) * 0.05;
            mouse.y += (mouse.targetY - mouse.y) * 0.05;

            // Mettre à jour le temps pour le shader magmatique
            asteroUniforms.uTime.value = elapsedTime;

            // Rotation lente et chaotique de l'astéroïde
            asteroidMesh.rotation.y = elapsedTime * 0.08;
            asteroidMesh.rotation.x = elapsedTime * 0.04;

            // Dérive et rotation en apesanteur complète de l'astronaute perdu
            astroMesh.position.y = -2 + Math.sin(elapsedTime * 0.8) * 0.4; // Flottement haut/bas
            astroMesh.position.x = 10 + Math.cos(elapsedTime * 0.4) * 0.2; // Légère dérive gauche/droite
            astroMesh.rotation.z = elapsedTime * 0.12; // Tourne lentement sur lui-même
            astroMesh.rotation.x = elapsedTime * 0.05;

            // Parallaxe de l'espace global via la souris
            scene.position.x = mouse.x * 1.5;
            scene.position.y = mouse.y * 1.5;
            starField.rotation.y = -elapsedTime * 0.005;

            composer.render();
        }

        tick();

        // RESIZE RECTIFICATION
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
            composer.setSize(window.innerWidth, window.innerHeight);
        });
    </script>
</body>
</html>