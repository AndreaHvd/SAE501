<?php
    // =========================================================================================
    // CONFIGURATION ET FONCTION UTILITAIRE POUR L'API FASTAPI
    // =========================================================================================

    // L'URL de base de votre API FastAPI dans le réseau Docker.
    // Le conteneur s'appelle "api", donc l'hôte est "api".
    $api_url_base = "http://api:8000/produit";
    
    // Configurer la connexion directe à la BDD pour les fonctions restantes (authentification/admin)
    // CORRECTION ICI : On utilise le nom du service "mariadb" et le port INTERNE "3306".
    $DB_HOST = 'mariadb'; 
    $DB_PORT = '3306';    // C'était 3307, corrigé à 3306 pour la comm interne Docker
    $DB_NAME = 'mariadb';
    $DB_USER = 'user';
    $DB_PASS = 'lannion';

    function get_db_connection() {
        global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS;
        try {
            return new PDO("mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME", $DB_USER, $DB_PASS);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Exécute un appel à l'API FastAPI via cURL.
     */
    function call_api($url, $method = 'GET', $data = null) {
        $curl = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5,
        ];

        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            error_log("cURL Error: " . curl_error($curl));
            curl_close($curl);
            return null;
        }

        curl_close($curl);
        
        // Gestion des réponses simples "OK" ou JSON
        if ($response === "OK" && $http_code >= 200 && $http_code < 300) {
             return "OK";
        }

        $decoded_response = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return $decoded_response;
        } else {
            error_log("API Error: HTTP Code " . $http_code . " - Response: " . $response);
            return null; 
        }
    }

    // =========================================================================================
    // FONCTIONS UTILISATEURS (Garde l'accès BDD direct car l'API ne gère pas encore l'auth)
    // =========================================================================================

    function authentification($mail,$pass){
        $DB = get_db_connection();
        if (!$DB) return false;
        
        $mail= $DB->quote($mail);
        $pass = $DB->quote($pass);
        $requete = "SELECT EMAIL, PASS FROM utilisateurs WHERE EMAIL = ".$mail." AND PASS = ".$pass ;
        $resultat = $DB->query($requete);
        if ($resultat) {
            $tableau_assoc = $resultat->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($tableau_assoc)!=0) return true;
        }
        return false;
    }
    
    function isAdmin($mail){
        $DB = get_db_connection();
        if (!$DB) return false;
        
        $mail= $DB->quote($mail);
        $requete = "SELECT STATUT FROM utilisateurs WHERE EMAIL = $mail;" ;
        $resultat = $DB->query($requete) ;
        if ($resultat) {
            $statut = $resultat->fetch(PDO::FETCH_ASSOC) ;
            if (isset($statut["STATUT"]) && $statut["STATUT"] == "admin")    
                return true ;
        }
        return false;   
    }

    // =========================================================================================
    // FONCTIONS PRODUIT (Utilisent l'API FastAPI)
    // =========================================================================================
    
    function listerProduits() {
        global $api_url_base;
        $produits = call_api($api_url_base . "/", 'GET');
        return is_array($produits) ? $produits : [];
    }       
    
    function listerProduitsParCategorie($categorie_id){
        global $api_url_base;
        $url = $api_url_base . "/categorieproduit/" . (int)$categorie_id;
        $produits = call_api($url, 'GET');
        return is_array($produits) ? $produits : [];
    }
    
    function ajouterProduit($categorie, $designation, $forfait, $image, $prix){
        global $api_url_base;
        
        // On garde la récupération de l'ID via SQL car l'API attend un ID manuel pour l'instant
        $DB = get_db_connection();
        $next_id = 1;
        if ($DB) {
            $resultat = $DB->query("SELECT MAX(idPdt) + 1 AS next_id FROM produit;");
            if ($resultat) {
                $data = $resultat->fetch(PDO::FETCH_ASSOC);
                if ($data && $data['next_id']) $next_id = $data['next_id'];
            }
        }

        $data = [
            'idPdt' => (int)$next_id, 
            'idCat' => (int)$categorie,
            'prixTTC' => (float)$prix,
            'designation' => $designation,
            'forfaitLivraison' => (int)$forfait,
            'images' => $image
        ];
        
        $response = call_api($api_url_base . "/", 'POST', $data);
        return ($response == "OK" || is_array($response)) ? 1 : 0;
    }

    function supprimerProduit($produit_id){
        global $api_url_base;
        
        // MODIFICATION : Utilisation de l'API pour récupérer l'info du produit (dont l'image)
        // au lieu de refaire une requête SQL.
        $produit = call_api($api_url_base . "/" . (int)$produit_id, 'GET');
        
        $imagePath = null;
        if ($produit && isset($produit['images'])) {
            $imagePath = 'images/' . $produit['images'];
        }
        
        // Suppression via l'API
        $response = call_api($api_url_base . "/" . (int)$produit_id, 'DELETE');
        
        $retour = ($response == "OK" || is_array($response)) ? 1 : 0;
        
        // Suppression du fichier physique
        if ($retour == 1 && $imagePath && file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        return $retour;
    }

    function modifierProduit($designation_produit, $prix, $forfait){
        global $api_url_base;
        
        // On garde la recherche par "designation" en SQL car c'est spécifique à votre formulaire actuel
        $DB = get_db_connection();
        if (!$DB) return 0;
        
        $designation_q = $DB->quote($designation_produit);
        $requete_get_pdt = "SELECT * FROM produit WHERE designation = $designation_q;";
        $resultat = $DB->query($requete_get_pdt);
        if (!$resultat) return 0;
        
        $produit_actuel = $resultat->fetch(PDO::FETCH_ASSOC);
        if (!$produit_actuel) return 0;

        $idPdt = (int)$produit_actuel['idPdt'];
        
        $data_update = [
            'idPdt' => $idPdt, 
            'idCat' => (int)$produit_actuel['idCat'],
            'prixTTC' => (float)$prix,
            'designation' => $designation_produit,
            'forfaitLivraison' => (int)$forfait,
            'images' => $produit_actuel['images']
        ];
        
        $response = call_api($api_url_base . "/" . $idPdt, 'PUT', $data_update);
        return ($response == "OK" || is_array($response)) ? 1 : 0;
    }
    
    // =========================================================================================
    // FONCTIONS UTILITAIRES D'AFFICHAGE
    // =========================================================================================

    function redirect($url,$tps) {
        $temps = $tps * 1000;
        echo "<script type=\"text/javascript\"> setTimeout(function(){ window.location.href = \"$url\"; }, $temps); </script>";
    }
    
    function afficheTableau($tab) {
        echo '<table>';
        echo '<tr>'; 
        if(isset($tab[0])){
            foreach ($tab[0] as $colonne => $valeur) {
                echo "<th>$colonne</th>";
            }
        }
        echo "</tr>\n";
        foreach ($tab as $ligne) {
            echo '<tr>';
            foreach ($ligne as $colonne => $case) {
                if ($colonne == 'images') {
                    echo '<td><img src="./images/'.$case.'" alt="'.$case.'" style="max-width: 150px; max-height: 150px;"></td>';
                } else {
                    echo "<td>$case</td>";
                }
            }
            echo "</tr>\n";
        }
        echo '</table>';
    }
?>
