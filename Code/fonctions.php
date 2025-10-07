<?php
    // Nouveau PDO qui nous permet de nous connecter a la base mariadb
	$DB = new PDO('mysql:host=db;port=3306;dbname=mariadb','user','lannion');
	
	//****************Fonctions utilisées*****************************************************************
	function authentification($mail,$pass){
		global $DB;
		$retour = false ;
		
		$mail= $DB->quote($mail);
		$pass = $DB->quote($pass);
		$requete = "SELECT EMAIL, PASS FROM utilisateurs WHERE EMAIL = ".$mail." AND PASS = ".$pass ;
		//var_dump($requete);echo "<br/>";  	
		$resultat = $DB->query($requete);
		$tableau_assoc = $resultat->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($tableau_assoc)!=0) $retour = true;	
		return $retour;
	}
	
	//***************************************************************************************************
	
	function isAdmin($mail){
		global $DB;
		$retour = false ;
		//se connecter à la bdd

		$mail= $DB->quote($mail);
		$requete = "SELECT STATUT FROM utilisateurs WHERE EMAIL = $mail;" ;
		// var_dump($requete) ;
		$resultat = $DB->query($requete) ;
		$statut = $resultat->fetch(PDO::FETCH_ASSOC) ;		// fetch -> quand on a un seul résultat attendu
		if ($statut["STATUT"] == "admin")	  // décision !
			$retour = true ;

		return $retour;	
		
	}
	//***************************************************************************************************
	function listerProduits()	{
		global $DB;
		$retour = false ;	

		$requete = "SELECT images, designation, prixTTC, forfaitlivraison FROM produit ;" ;
		$resultat = $DB->query($requete) ;
		$retour = $resultat->fetchAll(PDO::FETCH_ASSOC) ;

		return $retour;
	}		
	
	//***************************************************************************************************
	function listerProduitsParCategorie($categorie){
		global $DB;
		$retour = false ;
		
		$categoriep = $DB->quote($categorie);
		//var_dump($categorie) ;
		$requete = "SELECT images, designation, prixTTC, forfaitlivraison FROM produit as p INNER JOIN categorieproduit as c ON p.idCat = c.idCat WHERE c.idCat = $categoriep;" ;
		$resultat = $DB->query($requete);
		$retour = $resultat->fetchAll(PDO::FETCH_ASSOC);
		return $retour;
	}
	//*****************************************************************************************************
	function ajouterProduit($categorie,$designation,$forfait,$image,$prix){
		global $DB;
		$retour=0;
		try {
	
			$categorie=$DB->quote($categorie);
			$designation=$DB->quote($designation);
			$forfait=$DB->quote($forfait);
			$prix=$DB->quote($prix);
			$image=$DB->quote($image);
			$requete = "INSERT INTO produit (idCat, designation, forfaitlivraison, images, prixTTC) VALUES ($categorie,$designation,$forfait,$image, $prix);" ;
			$retour = $DB->exec($requete) ;

		}
		catch (Exception $e) {		
			echo "Erreur " . $e->getMessage();
		}
		return $retour;

	}
	//*****************************************************************************************************
	function supprimerProduit($produit){
		global $DB;
		$retour=0;
		try {
			
			$requete = "SELECT images FROM produit WHERE idPdt = $produit ;";
			$resultat = $DB->query($requete);
			$image = $resultat->fetch(PDO::FETCH_ASSOC);
			
			if ($image) {
				//var_dump($image['images']);
				$requete2 = "DELETE FROM produit WHERE idPdt = $produit ;";
				$retour = $DB->exec($requete2);
				
				// Supprimer l'image associée (code trouver grâce à mes recherches personnelles)
				$imagePath = 'images/' . $image['images'];
				//var_dump($imagePath);
				$extension = pathinfo($imagePath, PATHINFO_EXTENSION);
				switch ($extension) {
					case 'jpg':
					case 'jpeg':
					case 'png':
					case 'gif':
					case 'svg':
						if (file_exists($imagePath)) {
							unlink($imagePath);
						}
						break;
				}
			} else {
				echo "Image non trouvée pour le produit avec ID : $produit";
			}
		} catch (Exception $e) {
			echo "Erreur " . $e->getMessage();
		}
		return $retour;
	}
	//******************************************************************************************************
	function modifierProduit($produit,$prix,$forfait){
		global $DB;
        $retour=0;
        try{
            
            $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) ;
            $produit=$DB->quote($produit);
            $prix=$DB->quote($prix);
            $forfait=$DB->quote($forfait);
            
            $requete = "UPDATE produit SET prixTTC = $prix WHERE  designation = $produit ; " ;
            
            $requete2 = "UPDATE produit SET forfaitlivraison = $forfait  WHERE  designation = $produit ; " ;
            
            //var_dump($requete) ;
            $retour = $DB->exec($requete) ;
            $retour2 = $DB->exec($requete2) ;
        }
        catch (Exception $e) {      
            echo "Erreur " . $e->getMessage();      
        }
        return $retour+$retour2;
    }
	//*********************************************************************************************************
	//Nom : redirect()
	//Role : Permet une redirection en javascript
	//Parametre : URL de redirection et Délais avant la redirection
	//Retour : Aucun
	//*******************
	function redirect($url,$tps)
	{
		$temps = $tps * 1000;
		
		echo "<script type=\"text/javascript\">\n"
		. "<!--\n"
		. "\n"
		. "function redirect() {\n"
		. "window.location='" . $url . "'\n"
		. "}\n"
		. "setTimeout('redirect()','" . $temps ."');\n"
		. "\n"
		. "// -->\n"
		. "</script>\n";
		
	}
	//********************************************************************************************************
	function afficheTableau($tab) {
		echo '<table>';
		echo '<tr>'; // Les entêtes des colonnes qu'on lit dans le premier tableau par exemple
		foreach ($tab[0] as $colonne => $valeur) {
			echo "<th>$colonne</th>";
		}
		echo "</tr>\n";
		// Le corps de la table
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
