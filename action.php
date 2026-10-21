<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    // On s'assure de bien récupérer l'ID envoyé par le formulaire
    $video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;
    $decision = $_POST['decision'];
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if ($video_id > 0) {
        try {
            $pdo = Database::getInstance();
            
            if ($decision === 'approve') {
                // Met à jour le statut en "approved" dans MySQL
                $stmt = $pdo->prepare("UPDATE videos SET status = 'approved', rejection_reason = NULL WHERE id = ?");
                $stmt->execute([$video_id]);
            } elseif ($decision === 'reject') {
                // Met à jour le statut en "rejected" dans MySQL
                $stmt = $pdo->prepare("UPDATE videos SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                $stmt->execute([$reason, $video_id]);
            }
        } catch (Exception $e) {
            die("Erreur de mise à jour MySQL : " . $e->getMessage());
        }
    } else {
        die("Erreur : ID de vidéo invalide détecté.");
    }
}

// Redirection instantanée pour mettre à jour les compteurs KPI
header("Location: dashboard.php");
exit();
?>
