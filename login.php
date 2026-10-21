<?php
// 1. LA LOGIQUE PHP DE SECOURS ET CONNEXION
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// On essaie de charger la BDD, mais on ne bloque pas le projet si elle a un problème
try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    // Échec silencieux pour le secours
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        
        // ⚡ SYSTEME DE SECOURS DOUBLE : Si les comptes statiques de test sont utilisés
        if ($email === 'rh@yazaki.com' && $password === '123456') {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_name'] = 'Responsable RH (Secours)';
            $_SESSION['user_role'] = 'rh';
            
            header("Location: ../rh/dashboard.php");
            exit();
        } 
        elseif ($email === 'admin@yazaki.com' && $password === '123456') {
            $_SESSION['user_id'] = 2;
            $_SESSION['user_name'] = 'Admin IT (Secours)';
            $_SESSION['user_role'] = 'admin';
            
            header("Location: ../admin/dashboard.php");
            exit();
        }
        else {
            // Si c'est un nouvel utilisateur de la BDD (comme le nouvel administrateur créé)
            try {
                if (isset($pdo)) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();

                    // CORRECTION ICI : Utilisation de password_verify() pour les mots de passe sécurisés
                    if ($user !== false && password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role']; 

                        if ($user['role'] === 'rh') {
                            header("Location: ../rh/dashboard.php");
                            exit();
                        } elseif ($user['role'] === 'admin') {
                            header("Location: ../admin/dashboard.php");
                            exit();
                        }
                    }
                }
            } catch (PDOException $e) {
                // Erreur BDD ignorée pour le secours
            }
            
            $error = "Identifiants incorrects.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazaki Signage - Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('/yazaki_projet/assets/images/bg-usine.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.08); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2); 
            padding: 45px 40px;
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-sizing: border-box;
            z-index: 5;
        }

        .logo-yazaki {
            max-width: 140px;
            margin-bottom: 25px;
            display: inline-block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        h2 {
            font-size: 26px;
            color: #ffffff; 
            margin: 0 0 6px 0;
            font-weight: 600;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.75); 
            font-size: 14px;
            margin-bottom: 35px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9); 
            margin-bottom: 8px;
        }

        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.07); 
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 12px; 
            font-size: 14px;
            color: #ffffff; 
            box-sizing: border-box;
            transition: all 0.3s ease;
            outline: none;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        input:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.6);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.05);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #ffffff; 
            color: #0f172a; 
            border: none;
            border-radius: 30px; 
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-submit:hover {
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .error-message {
            color: #fca5a5;
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body> 

    <div class="login-container">
        <img src="/yazaki_projet/assets/images/1ksp_yazaki.png" alt="Yazaki Logo" class="logo-yazaki">
        <h2>Digital Signage</h2>
        <div class="subtitle">Connectez-vous à votre espace</div>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Ajout des champs manquants dans votre formulaire HTML -->
        <form action="" method="POST">
            <div class="form-group">
                <label>Adresse Email</label>
                <input type="email" name="email" placeholder="nom@yazaki.com" required>
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-submit">Se connecter</button>
        </form>
    </div>

</body>
</html>
