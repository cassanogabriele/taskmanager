<?php
// Fichier de sauvegarde des tâches
$dataFile = __DIR__ . '/../data/data.json';

// Charger le contenu du fichier JSON et le convertir en tableau PHP
// Si le fichier est vide ou invalide, initialiser un tableau avec une clé 'users' vide
$data = json_decode(file_get_contents($dataFile), true) ?: ['users' => []];

// Stockage les messages d'erreurs de connexion
$error = '';

// Vérifier si le formulaire a été soumis via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // Récupérer les informations du formulaire encodées par l'utilsateur
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Vérifier si l'utilisateur n'existe pas déjà dans le tableau de données
    if (!isset($data['users'][$username])) 
    {
        $data['users'][$username] = [
            // Ajouter un nouvel utilisateur avec son mot de passe haché 
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ];
        
        // Enregistrer le tableau de données mis à jour dans le fichier JSON
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        // Définir l'utilisateur comme connecté dans la session
        $_SESSION['user'] = $username;
        // Rediriger l'utilisateur vers la page principale (index.php)
        header("Location: ../index.php");
        exit;
    } else {
        // Sinon, on affiche un message d'erreur
        $error = "<div class='alert alert-danger' role='alert'>L'utilisateur existe déjà</div>.";
    }
}
?>

<!DOCTYPE html>
    <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Inscription</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="../css/style.css" rel="stylesheet">
        </head>

        <body class="d-flex justify-content-center align-items-center vh-100">
            <div class="w-50">
                <h2 class="text-white">Inscription</h2>

                <?php if ($error) echo "<p class='text-danger mt-3'>$error</p>"; ?>

                <form method="post" class="mt-3 mb-3 text-white">
                    <div class="mb-3">
                        <label>Nom d'utilisateur</label>
                        <input type="text" name="username" class="form-control mt-3" required>
                    </div>

                    <div class="mb-3">
                        <label>Mot de passe</label>
                        <input type="password" name="password" class="form-control mt-3" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success">S'inscrire</button>
                </form>

                <p class="text-white">Vous avez déjà un compte ? <a href="../index.php" class="text-decoration-none"><span class="text-info">Connectez-vous</span></a></p>
            </div>
        </body>
</html>
