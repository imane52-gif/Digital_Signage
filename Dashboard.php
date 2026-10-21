<?php
session_start();
require_once '../config/db.php';

// SÉCURITÉ : Vérification d'accès pour le rôle Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// ⚡ TRAITEMENT ASYNCHRONE DES ACTIONS DE MODÉRATION (AJAX VIA FETCH)
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    $video_id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    try {
        $pdo = Database::getInstance();
        if ($action === 'approve') {
            // Passe la vidéo au statut 'approved'
            $stmt = $pdo->prepare("UPDATE videos SET status = 'approved' WHERE id = ?");
            $stmt->execute([$video_id]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE videos SET status = 'rejected', rejection_reason = ? WHERE id = ?");
            $stmt->execute([$reason, $video_id]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'stop') {
            // Coupe la diffusion en passant au statut 'stopped'
            $stmt = $pdo->prepare("UPDATE videos SET status = 'stopped' WHERE id = ?");
            $stmt->execute([$video_id]);
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

$pending_videos = [];
$stats = ['total' => 0, 'pending_count' => 0, 'approved_count' => 0, 'total_views' => 0];
$active_video = null;

try {
    $pdo = Database::getInstance();

    // 1. Récupération des statistiques globales
    $stats_query = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN LOWER(status) = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(views_count) as total_views
    FROM videos");
    
    $stats = $stats_query->fetch(PDO::FETCH_ASSOC);

    if (is_null($stats['total_views'])) { $stats['total_views'] = 0; }
    if (is_null($stats['approved_count'])) { $stats['approved_count'] = 0; }
    if (is_null($stats['pending_count'])) { $stats['pending_count'] = 0; }

    // 2. Sélection des contenus en attente de modération
    $stmt = $pdo->query("SELECT v.*, u.name as rh_name 
                         FROM videos v 
                         LEFT JOIN users u ON v.created_by = u.id 
                         WHERE LOWER(v.status) = 'pending' 
                         ORDER BY v.created_at ASC");
    $pending_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. VÉRIFICATION PERSISTANTE : Y a-t-il une vidéo déjà active en cours de diffusion ?
    $active_query = $pdo->query("SELECT id, filename FROM videos WHERE LOWER(status) = 'approved' ORDER BY id DESC LIMIT 1");
    $active_video = $active_query->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Mode de secours local (si déconnexion de la BDD pendant vos tests)
    $upload_dir = '../uploads/';
    if (is_dir($upload_dir)) {
        $files = array_diff(scandir($upload_dir), array('.', '..'));
        $mock_id = 1;
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'mp4') {
                $pending_videos[] = [
                    'id' => $mock_id++,
                    'rh_name' => 'Ressources Humaines',
                    'original_name' => $file,
                    'scheduled_at' => date('Y-m-d H:i:s'),
                    'filename' => $file
                ];
            }
        }
        $stats['pending_count'] = count($pending_videos);
        $stats['total'] = count($pending_videos);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazaki Signage — Espace Admin</title>
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; color: #ffffff; margin: 0; padding: 30px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 20px; margin-bottom: 30px; }
        h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .user-tag { color: rgba(255, 255, 255, 0.6); font-size: 14px; margin-top: 5px; }
        .btn-secondary { padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; color: #fca5a5; border: 1px solid rgba(252, 165, 165, 0.3); transition: all 0.2s; }
        .btn-secondary:hover { background-color: rgba(252, 165, 165, 0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; text-align: center; backdrop-filter: blur(10px); }
        .stat-number { font-size: 30px; font-weight: 700; margin-top: 8px; }
        .stat-card.pending { border-color: rgba(234, 179, 8, 0.4); color: #fde047; }
        .panel { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 25px; backdrop-filter: blur(10px); margin-bottom: 30px; }
        h2 { font-size: 18px; font-weight: 600; margin-top: 0; margin-bottom: 20px; border-left: 4px solid #e60000; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; }
        th { color: rgba(255, 255, 255, 0.4); font-weight: 500; }
        .actions-form { display: flex; gap: 10px; align-items: center; }
        .input-reason { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: white; padding: 8px 12px; font-size: 13px; width: 180px; outline: none; }
        .btn-action { padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: opacity 0.2s; }
        .btn-approve { background-color: #22c55e; color: white; }
        .btn-reject { background-color: #ef4444; color: white; }
        .btn-action:hover { opacity: 0.9; }

        /* Style pour la zone live monitoring */
        .live-panel { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 20px; padding: 25px; margin-top: 20px; }
        .live-monitor-container { display: flex; align-items: center; justify-content: space-between; gap: 30px; }
        .btn-stop { padding: 12px 28px; background-color: #ef4444; color: white; border: none; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }
        .btn-stop:hover { background-color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <div>
                <h1>Yazaki Digital Signage — Espace Administrateur</h1>
                <div class="user-tag">Session active : <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin IT'); ?></strong></div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="create_account.php" style="padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; background-color: #ffffff; color: #0f172a; border: none; transition: background 0.2s; display: inline-block;">+ Créer un utilisateur</a>
                <a href="Mon_profil.php" style="padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; background-color: #ffffff; color: #0f172a; border: none; transition: background 0.2s; display: inline-flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://w3.org" viewBox="0 0 24 24" fill="currentColor" style="width: 16px; height: 16px;">
                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
                    </svg>
                    Mon profil
                </a>
                <a href="../screen/index.php" target="_blank" style="padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; background-color: #38bdf8; color: #0f172a; border: none; display: inline-block;">📺 Voir l'écran</a>
                <a href="../auth/logout.php" class="btn-secondary">Déconnexion</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div>Total Contenus</div><div class="stat-number"><?php echo (int)$stats['total']; ?></div></div>
            <div class="stat-card pending"><div>En attente</div><div class="stat-number"><?php echo (int)$stats['pending_count']; ?></div></div>
            <div class="stat-card"><div>Vidéos Validées</div><div class="stat-number"><?php echo (int)$stats['approved_count']; ?></div></div>
            <div class="stat-card"><div>Vues Totales</div><div class="stat-number"><?php echo (int)$stats['total_views']; ?></div></div>
        </div>

        <div class="panel">

            <h2>Demandes de diffusion en attente de modération</h2>
            <?php if (empty($pending_videos)): ?>
                <p style="color: rgba(255,255,255,0.4); text-align: center; padding: 30px 0; margin: 0;">Aucune demande de validation en attente.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Auteur (RH)</th>
                            <th>Nom du contenu vidéo</th>
                            <th>Date prévue de diffusion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_videos as $video): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($video['rh_name'] ?? 'Ressources Humaines'); ?></strong></td>
                                <td><?php echo htmlspecialchars($video['original_name'] ?? $video['filename']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($video['scheduled_at'] ?? 'now')); ?></td>
                                <td>
                                    <form action="action.php" method="POST" class="actions-form">
                                        <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($video['filename'] ?? ''); ?>">
                                        
                                        <button type="submit" name="decision" value="approve" class="btn-action btn-approve">Approuver</button>
                                        
                                        <input type="text" name="reason" placeholder="Raison du rejet (Optionnel)..." class="input-reason">
                                        <button type="submit" name="decision" value="reject" class="btn-action btn-reject">Rejeter</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                     
                    </tbody>
                </table>
            <?php endif; ?> <!-- ✅ CORRECTION : On ferme le IF du tableau ICI (Ligne 200) -->
        </div> <!-- Fermeture de la div class="panel" du tableau -->

        <!-- 📺 Le Mini-Lecteur est sorti et s'affiche indépendamment du tableau -->
        <div id="liveControlPanel" style="margin-top: 35px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 25px; <?php echo !empty($active_video) ? 'display: block;' : 'display: none;'; ?>">
            <h3 style="font-size: 18px; font-weight: 600; margin-top: 0; margin-bottom: 15px; border-left: 4px solid #38bdf8; padding-left: 10px;">
                Video en cours de lecture
            </h3>
            
            <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <video id="liveMonitor" muted autoplay loop style="width: 100%; max-width: 480px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                    <?php if (!empty($active_video)): ?>
                        <source src="../uploads/<?php echo htmlspecialchars($active_video['filename']); ?>" type="video/mp4">
                    <?php endif; ?>
                </video>
                
                <p id="liveVideoName" style="font-size: 13px; font-family: monospace; color: #38bdf8; margin: 0;">
                    <?php echo !empty($active_video) ? htmlspecialchars($active_video['filename']) : ''; ?>
                </p>

                <button onclick="stopActiveDiffusion()" style="padding: 12px 30px; background-color: #ef4444; color: white; border: none; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);">
                    🛑 Stop
                </button>
            </div>
        </div>

    </div> <!-- Fermeture de la div class="container" globale -->


        </div>
    </div>
    
</body>


</html>

