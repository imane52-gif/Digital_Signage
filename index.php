<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazaki Écran TV — Diffusion Active</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #000000;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: sans-serif;
        }
        video {
            width: 100vw;
            height: 100vh;
            object-fit: cover; /* La vidéo prend tout l'écran de la TV sans bandes noires */
        }
        .no-video {
            color: #ffffff;
            font-size: 24px;
            letter-spacing: 2px;
            opacity: 0.5;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <!-- Lecteur vidéo HTML5 en plein écran -->
    <video id="yazaki-player" autoplay muted loop playsinline></video>
    <div id="message" class="no-video" style="display: none;">En attente de contenu validé...</div>

    <script>
    let currentVideoId = null;
    const player = document.getElementById('yazaki-player');
    const messageDiv = document.getElementById('message');

    function checkScreenVideo() {
        fetch('api.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.filename) {
                    if (data.id !== currentVideoId) {
                        currentVideoId = data.id;
                        player.style.display = 'block';
                        messageDiv.style.display = 'none';
                        
                        // Chargement du fichier mp4 depuis le dossier uploads
                        player.src = '../uploads/' + data.filename;
                        player.load();
                        player.play().catch(error => {
                            console.log("Lecture automatique bloquée par le navigateur, attente d'un clic.");
                        });
                    }
                } else {
                    currentVideoId = null;
                    player.style.display = 'none';
                    messageDiv.style.display = 'block';
                    player.src = "";
                }
            })
            .catch(err => console.error("Erreur API Écran", err));
    }

    // Vérification toutes les 5 secondes pour plus de réactivité lors des tests
    setInterval(checkScreenVideo, 5000);
    checkScreenVideo();
    </script>

</body>

</html>
