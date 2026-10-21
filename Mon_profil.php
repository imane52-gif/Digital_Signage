<?php
session_start();
require_once '../config/db.php'; // Vérifiez le chemin vers votre db.php

// SÉCURITÉ : Bloquer l'accès si l'utilisateur n'est pas un admin connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_message = "";
$user_error = "";

try {
    $pdo = Database::getInstance();
    
    // Traitement de la modification du téléphone et de l'email
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_email = trim($_POST['email']);
        $new_telephone = trim($_POST['telephone']);
        
        if (!empty($new_email) && !empty($new_telephone)) {
            $update_stmt = $pdo->prepare("UPDATE users SET email = ?, telephone = ? WHERE id = ?");
            $update_stmt->execute([$new_email, $new_telephone, $user_id]);
            $user_message = "Vos informations d'administrateur ont été mises à jour !";
        } else {
            $user_error = "Veuillez remplir tous les champs.";
        }
    }

    // Récupération des données de l'admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $nom_complet = $user['name'];
    $email       = $user['email'];
    $telephone   = $user['telephone'];
    $matricule   = $user['matricule'];

    // Données de l'onglet Logs
    $system_logs = [
        ["detail" => "Connexion au panneau d'administration", "date" => date('d/10/2026 H:i'), "badge" => "Succès"],
        ["detail" => "Mise à jour des accès écrans", "date" => "19/10/2026 23:00", "badge" => "Succès"]
    ];

} catch (Exception $e) {
    $user_error = "Erreur système : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil Admin — Yazaki</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; color: #ffffff; margin: 0; padding: 30px; display: flex; justify-content: center; align-items: center; min-height: 100vh; box-sizing: border-box; }
        .profile-container { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; padding: 40px; width: 100%; max-width: 650px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); }
        .profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .avatar-circle { background-color: #38bdf8; color: #0f172a; width: 55px; height: 55px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: 700; font-size: 20px; }
        .tabs { display: flex; border-bottom: 2px solid rgba(255, 255, 255, 0.08); margin-bottom: 25px; gap: 15px; }
        .tab-btn { background: none; border: none; color: rgba(255, 255, 255, 0.5); padding: 10px 15px; font-size: 15px; font-weight: 600; cursor: pointer; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: #38bdf8; border-bottom-color: #38bdf8; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; color: rgba(255, 255, 255, 0.6); margin-bottom: 8px; }
        .form-control { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; color: white; padding: 12px 16px; font-size: 14px; width: 100%; box-sizing: border-box; }
        .form-control:disabled { background: rgba(255, 255, 255, 0.02); color: rgba(255, 255, 255, 0.4); cursor: not-allowed; }
        .activity-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .activity-table th, .activity-table td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .btn-group { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 12px 24px; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; flex: 1; border: none; text-decoration: none; text-align: center; }
        .btn-edit { background-color: #38bdf8; color: #0f172a; }
        .btn-save { background-color: #22c55e; color: #ffffff; display: none; }
        .btn-back { background-color: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.15); }
        .alert { padding: 12px; border-radius: 12px; font-size: 14px; margin-bottom: 20px; text-align: center; background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
    </style>
</head>
<body>
<div class="profile-container">
    <div class="profile-header">
        <div class="avatar-circle"><?php echo strtoupper(substr($nom_complet, 0, 2)); ?></div>
        <div>
            <h2><?php echo htmlspecialchars($nom_complet); ?></h2>
            <span style="color: #38bdf8; font-size: 14px;">Administrateur IT / Système</span>
        </div>
    </div>
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('infos')">Mes Informations</button>
        <button class="tab-btn" onclick="switchTab('logs')">Logs Système</button>
    </div>
    <?php if(!empty($user_message)): ?> <div class="alert"><?php echo $user_message; ?></div> <?php endif; ?>
    <form method="POST">
        <div id="infos-tab" class="tab-content active">
            <div class="form-group"><label>Matricule</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($matricule); ?>" disabled></div>
            <div class="form-group"><label>Nom complet</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($nom_complet); ?>" disabled></div>
            <div class="form-group"><label>Email de Sécurité</label><input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled required></div>
            <div class="form-group"><label>Téléphone d'urgence</label><input type="text" name="telephone" id="telephone" class="form-control" value="<?php echo htmlspecialchars($telephone); ?>" disabled required></div>
        </div>
        <div id="logs-tab" class="tab-content">
            <table class="activity-table">
                <thead><tr><th>Action</th><th>Date</th><th>Statut</th></tr></thead>
                <tbody>
                    <?php foreach ($system_logs as $log): ?>
                    <tr><td><?php echo $log['detail']; ?></td><td><?php echo $log['date']; ?></td><td><span class="badge"><?php echo $log['badge']; ?></span></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="btn-group">
            <button type="button" id="editBtn" class="btn btn-edit" onclick="enableEditing()">Modifier mes informations</button>
            <button type="submit" id="saveBtn" class="btn btn-save">Enregistrer</button>
            <a href="dashboard.php" class="btn btn-back">Retour</a>
        </div>
    </form>
</div>
<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName + '-tab').classList.add('active');
    event.currentTarget.classList.add('active');
    document.getElementById('editBtn').style.visibility = (tabName === 'logs') ? 'hidden' : 'visible';
}
function enableEditing() {
    document.getElementById('email').disabled = false;
    document.getElementById('telephone').disabled = false;
    document.getElementById('email').focus();
    document.getElementById('editBtn').style.display = 'none';
    document.getElementById('saveBtn').style.display = 'block';
}
</script>
</body>
</html>
