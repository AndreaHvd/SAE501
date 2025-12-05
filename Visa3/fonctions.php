<?php
    // =========================================================================================
    // CONFIGURATION ET FONCTION UTILITAIRE POUR L'API FASTAPI
    // =========================================================================================

    // L'URL de base de votre API FastAPI.
    // **ASSUREZ-VOUS QUE C'EST L'ADRESSE CORRECTE DE VOTRE CONTAINER/SERVICE FASTAPI**
    $api_url_base = "http://api:8000/produit";
    
    // Configurer la connexion directe à la BDD pour les fonctions restantes (authentification/admin)
    // NOTE: Il serait plus sûr d'avoir une API pour ces fonctions également.
    $DB_HOST = 'mariadb'; // Mettre 'db' si vous utilisez un docker-compose standard
    $DB_PORT = '3306'; 
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
     * @param string $url URL complète de l'endpoint
     * @param string $method GET, POST, PUT, DELETE
     * @param array|null $data Données à envoyer (pour POST/PUT)
     * @return mixed La réponse décodée (array/string) ou null en cas d'échec
     */
    function call_api($url, $method = 'GET', $data = null) {
        $curl = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5, // Timeout en secondes
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
        
        // La réponse "OK" de FastAPI pour POST/PUT/DELETE est une chaîne simple, pas du JSON
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
    // FONCTIONS UTILISATEURS (Garde l'accès BDD pour la simplicité)
    // =========================================================================================

	function authentification($mail,$pass){
        $DB = get_db_connection();
        if (!$DB) return false;
		$retour = false ;
		$mail= $DB->quote($mail);
		$pass = $DB->quote($pass);
		$requete = "SELECT EMAIL, PASS FROM utilisateurs WHERE EMAIL = ".$mail." AND PASS = ".$pass ;
		$resultat = $DB->query($requete);
		$tableau_assoc = $resultat->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($tableau_assoc)!=0) $retour = true;	
		return $retour;
	}
	
	function isAdmin($mail){
        $DB = get_db_connection();
        if (!$DB) return false;
		$retour = false ;
		$mail= $DB->quote($mail);
		$requete = "SELECT STATUT FROM utilisateurs WHERE EMAIL = $mail;" ;
		$resultat = $DB->query($requete) ;
		$statut = $resultat->fetch(PDO::FETCH_ASSOC) ;
		if (isset($statut["STATUT"]) && $statut["STATUT"] == "admin")	 
			$retour = true ;
		return $retour;	
	}

    // =========================================================================================
    // FONCTIONS PRODUIT (Utilisent l'API FastAPI)
    // =========================================================================================
	
    // Utilise l'endpoint GET /produit/
	function listerProduits()	{
        global $api_url_base;
        $produits = call_api($api_url_base . "/", 'GET');
        return is_array($produits) ? $produits : [];
	}		
	
    // Utilise l'endpoint GET /produit/categorie/{idCat}
	function listerProduitsParCategorie($categorie_id){
        global $api_url_base;
        // La fonction reçoit l'ID de catégorie
        $url = $api_url_base . "/categorie/" . (int)$categorie_id;
        $produits = call_api($url, 'GET');
        return is_array($produits) ? $produits : [];
    }
    
    // Utilise l'endpoint POST /produit/
	function ajouterProduit($categorie, $designation, $forfait, $image, $prix){
        global $api_url_base;
        
        // NOTE : Votre fonction PHP n'envoie pas l'idPdt, mais le modèle FastAPI l'exige. 
        // L'ID du produit doit être soit déterminé dans l'API, soit récupéré ici. 
        // Pour l'instant, je vais utiliser une valeur arbitraire (0) et espérer que l'API/BDD le gère. 
        // Si cela cause des erreurs, vous devrez modifier le modèle Pydantic ou l'API pour auto-incrémenter l'ID.
        
        // Tentative de récupérer l'ID max (solution BDD temporaire si l'API ne le gère pas)
        $DB = get_db_connection();
        $next_id = 0;
        if ($DB) {
            $resultat = $DB->query("SELECT MAX(idPdt) + 1 AS next_id FROM produit;");
            $data = $resultat->fetch(PDO::FETCH_ASSOC);
            $next_id = $data['next_id'] ?? 1;
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
        
        // Retourne 1 (comme PDO::exec) si "OK", 0 sinon
        return ($response == "OK") ? 1 : 0;
	}

    // Utilise l'endpoint DELETE /produit/{item_id}
	function supprimerProduit($produit_id){
        global $api_url_base;
        
        // NOTE: La suppression du fichier image doit toujours se faire CÔTÉ PHP ou via un autre service API
        // car le code PHP est responsable de la gestion des fichiers sur le système de fichiers du serveur.
        
        // 1. Récupérer le nom de l'image (Nécessite une connexion BDD ou un endpoint API GET /produit/{id})
        $DB = get_db_connection();
        $imagePath = null;
        if ($DB) {
            $produit_id_q = $DB->quote($produit_id);
            $requete = "SELECT images FROM produit WHERE idPdt = $produit_id_q;";
            $resultat = $DB->query($requete);
            $image = $resultat->fetch(PDO::FETCH_ASSOC);
            if ($image) {
                $imagePath = 'images/' . $image['images'];
            }
        }
        
        // 2. Supprimer de l'API (de la BDD)
        $response = call_api($api_url_base . "/" . (int)$produit_id, 'DELETE');
        
        $retour = ($response == "OK") ? 1 : 0;
        
        // 3. Supprimer le fichier image si la suppression BDD a réussi
        if ($retour == 1 && $imagePath && file_exists($imagePath)) {
            // Unlink est nécessaire pour supprimer le fichier du système de fichiers
            unlink($imagePath);
        }
        
        return $retour;
	}

    // Utilise l'endpoint PUT /produit/{item_id}
	function modifierProduit($designation_produit, $prix, $forfait){
        global $api_url_base;
        $retour = 0;
        
        // ÉTAPE 1: Récupérer l'ID et les données complètes du produit actuel via BDD (car l'API PUT exige tout)
        $DB = get_db_connection();
        if (!$DB) return 0;
        
        $designation_q = $DB->quote($designation_produit);
        $requete_get_pdt = "SELECT * FROM produit WHERE designation = $designation_q;";
        $resultat = $DB->query($requete_get_pdt);
        $produit_actuel = $resultat->fetch(PDO::FETCH_ASSOC);

        if (!$produit_actuel) {
            return 0; // Produit non trouvé
        }

        $idPdt = (int)$produit_actuel['idPdt'];
        
        // ÉTAPE 2: Préparer les données pour la mise à jour
        $data_update = [
            'idPdt' => $idPdt, // L'ID pour le modèle Pydantic
            'idCat' => (int)$produit_actuel['idCat'],
            'prixTTC' => (float)$prix,
            'designation' => $designation_produit,
            'forfaitLivraison' => (int)$forfait,
            'images' => $produit_actuel['images']
        ];
        
        // ÉTAPE 3: Appel à l'API PUT /produit/{item_id}
        $response = call_api($api_url_base . "/" . $idPdt, 'PUT', $data_update);
        
        // L'API PUT retourne "OK" en cas de succès
        return ($response == "OK") ? 1 : 0;
    }
	
    // =========================================================================================
    // FONCTIONS UTILITAIRES (Inchangées)
    // =========================================================================================

	function redirect($url,$tps)
	{
		$temps = $tps * 1000;
		echo "<script type=\"text/javascript\">\n"
		. "\n"
		. "</script>\n";
	}
	
	function afficheTableau($tab) {
        // Le code reste le même, il affiche juste le tableau PHP
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
