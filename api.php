<?php
header('Content-Type: application/json');
require_once '../config/db.php'; // Vérifiez le chemin vers votre db.php

try {
    $pdo = Database::getInstance();
    
    // CORRECTION : "ORDER BY id DESC LIMIT 1" trie par ID décroissant 
    // pour récupérer le tout dernier fichier approuvé en BDD
   $stmt = $pdo->prepare("SELECT id, filename FROM videos WHERE status = 'Approved' ORDER BY id DESC LIMIT 1");

    // (Note : Ajustez 'users_videos' et 'statut' selon les vrais noms de votre table et colonne)
    
    $stmt->execute();
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($video) {
        echo json_encode($video);
    } else {
        echo json_encode(null);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
