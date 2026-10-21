<?php
session_start();
require_once '../config/db.php';

// SÉCURITÉ : Vérification d'accès pour le rôle Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_message = "";
$user_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['new_name']);
    $new_email = trim($_POST['new_email']);
    $new_password = $_POST['new_password'];
    $new_role = $_POST['new_role'];
    $matricule = trim($_POST['matricule']);
    
    // CORRECTION : Harmonisation du nom de la variable avec le formulaire HTML
    $numero_telephone = trim($_POST['numero_telephone']);
    
    if (!empty($new_name) && !empty($new_email) && !empty($new_password) && !empty($new_role) && !empty($matricule) && !empty($numero_telephone)) {
        try {
            $pdo = Database::getInstance();
            
            // Vérifier si l'email ou le matricule existe déjà
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR matricule = ?");
            $check->execute([$new_email, $matricule]);
            
            if ($check->fetch()) {
                $user_error = "Cette adresse email ou ce matricule est déjà utilisé.";
            } else {
                // SÉCURITÉ : Il est fortement conseillé de hacher le mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                
                // CORRECTION Ligne 36 : Utilisation de la bonne variable $numero_telephone
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, matricule, telephone) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$new_name, $new_email, $hashed_password, $new_role, $matricule, $numero_telephone]);
                
                $user_message = "Le compte de " . htmlspecialchars($new_name) . " (" . strtoupper($new_role) . ") a été créé avec succès !";
            }
        } catch (Exception $e) {
            $user_error = "Erreur lors de la création : " . $e->getMessage();
        }
    } else {
        $user_error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazaki Signage — Créer un Utilisateur</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; color: #ffffff; margin: 0; padding: 30px; display: flex; justify-content: center; align-items: center; min-height: 100vh; box-sizing: border-box; }
        .panel { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; padding: 40px; backdrop-filter: blur(16px); width: 100%; max-width: 500px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); }
        h2 { font-size: 22px; font-weight: 600; margin-top: 0; margin-bottom: 25px; border-left: 4px solid #e60000; padding-left: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px; color: rgba(255, 255, 255, 0.8); }
        .form-control { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; color: white; padding: 12px 16px; font-size: 14px; outline: none; width: 100%; box-sizing: border-box; transition: all 0.3s ease; }
        .form-control:focus { border-color: rgba(255, 255, 255, 0.5); background: rgba(255,255,255,0.1); }
        .select-control { background: #1e293b; color: white; }
        .btn-submit { width: 100%; padding: 14px; background-color: #ffffff; color: #0f172a; border: none; border-radius: 30px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 15px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .btn-submit:hover { background-color: rgba(255, 255, 255, 0.9); transform: translateY(-1px); }
        .btn-back { display: inline-block; color: rgba(255,255,255,0.5); text-decoration: none; margin-bottom: 20px; font-size: 14px; transition: color 0.2s; }
        .btn-back:hover { color: #ffffff; }
        .alert { padding: 14px; border-radius: 12px; font-size: 14px; margin-bottom: 25px; text-align: center; }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
    </style>
</head>
<body>

    <div class="panel">
        <a href="dashboard.php" class="btn-back">← Retour au Tableau de bord</a>
        <h2>Créer un nouvel utilisateur</h2>
        
        <?php if(!empty($user_message)): ?> <div class="alert alert-success"><?php echo $user_message; ?></div> <?php endif; ?>
        <?php if(!empty($user_error)): ?> <div class="alert alert-error"><?php echo $user_error; ?></div> <?php endif; ?>

        <form action="create_account.php" method="POST">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" name="new_name" placeholder="Ex: Ahmed Alami" required class="form-control">
            </div>

             <div class="form-group">
                <label>Matricule</label>
                <!-- CORRECTION : Suppression de la balise <option> isolée en plein milieu -->
                <input type="text" name="matricule" placeholder="Ex: YAK-084" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>Adresse Email</label>
                <input type="email" name="new_email" placeholder="nom@yazaki.com" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="new_password" placeholder="••••••••" required class="form-control">
            </div>

            <div class="form-group">
                <label>Numéro de téléphone</label>
                <!-- CORRECTION : Remplacement du type invalide "Numero" par "text" -->
                <input type="text" name="numero_telephone" placeholder="+212 XXX XXX XXX" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>Rôle d'accès</label>
                <select name="new_role" required class="form-control select-control">
                    <option value="" disabled selected hidden>Choisir le rôle</option>
                    <option value="rh">Ressources Humaines (RH)</option>
                    <option value="admin">Administrateur IT</option>
                </select>
            </div>
            
            <button type="submit" class="btn-submit">Créer le compte</button>
        </form>
    </div>

</body>
</html>
