<?php
	//******************************************************************************
	function FormulaireAuthentification(){//fourni
	?>
	<form id="form1" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return checkPassword()">
		<fieldset>
			<legend>Formulaire d'authentification</legend>	
			<label for="id_mail">Adresse Mail : </label><input type="email" name="login" id="id_mail" placeholder="@mail" required size="20" /><br />
			<label for="id_pass">Mot de passe : </label><input type="password" name="pass" id="id_pass" required size="10" /><br />
			<input type="submit" name="connect" value="Connexion" />
		</fieldset>
	</form>
	<?php
	}
	
	//******************************************************************************
	function Menu() {
		?>
		<nav class="navbar navbar-expand-lg navbar-light bg-light">
			<a class="navbar-brand" href="index.php"><img src="images/Amazon_logo.svg" alt="image_am" style="max-width: 100px; max-height: 100px;"></a>
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav ml-auto">
					<li class="nav-item">
						<a class="nav-link" href="index.php?action=accueil" title="accueil">Accueil</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="index.php?action=liste_produits" title="Lister les produits par catégorie">Lister les produits par catégorie</a>
					</li>
					<?php
					if (isset($_SESSION["statut"]) && $_SESSION["statut"] == true) { // Si il est admin
					?>
						<li class="nav-item">
							<a class="nav-link" href="insertion.php?action=inserer_produit" title="Insérer un produit">Insérer un produit</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="suppression.php?action=supprimer_produit" title="Supprimer un produit">Supprimer un produit</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="modification.php?action=modifier_produit" title="Modifier un produit">Modifier un produit</a>
						</li>
					<?php
					}
					?>
					<li class="nav-item">
						<p><a href="index.php?action=logout" id="deco" title="Déconnexion" style ="color : red;">Se déconnecter</a></p>
					</li>
				</ul>
				
			</div>
		</nav>
		<?php
	}
	//******************************************************************************
	function FormulaireProduitParCategorie(){
		echo "<br/>";
		// Modification : Utilisation de get_db_connection() (MariaDB) au lieu de SQLite
		$madb = get_db_connection();
		
		// Note: On suppose que les tables 'produit' et 'categorieproduit' existent dans MariaDB
		// On garde la logique SQL pour les catégories car l'API semble orientée "Produit"
		if ($madb) {
			$requete = "SELECT DISTINCT c.idCat, intitule FROM produit as p INNER JOIN categorieproduit as c ON p.idCat = c.idCat ;" ;
			$resultat = $madb->query($requete) ;
			$categories = $resultat ? $resultat->fetchAll(PDO::FETCH_ASSOC) : [];
		} else {
			$categories = [];
			echo "<p style='color:red'>Erreur de connexion BDD (Catégories)</p>";
		}
	?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<fieldset> 
			<label for="id_Cat">Catégories :</label> 
			<select id="id_Cat" name="categorieproduit" size="1" onchange="listeFiltreProduits(this)">
			<option value="0">Rechercher une catégorie</option>
				<?php
					foreach($categories as $categorieproduit){
						echo '<option value="'.$categorieproduit['idCat'].'">'.$categorieproduit['intitule'].'</option>';
					}
				?>
			</select>
		</fieldset>
	</form>
	<?php
		echo "<br/>";
	}
	//******************************************************************************
	function FormulaireAjoutProduit(){
		// Modification : Connexion BDD MariaDB via get_db_connection()
		$madb = get_db_connection(); 
		$categories = [];
		$forfaits = [];

		if ($madb) {
			// Récupération des catégories
			$requete1 = 'SELECT DISTINCT c.idCat, intitule FROM produit as p INNER JOIN categorieproduit as c ON p.idCat = c.idCat ;';
			// Note : Idéalement, pour un ajout, on devrait lister toutes les catégories (SELECT * FROM categorieproduit), 
			// mais je garde la logique originale du fichier fourni.
			$resultat1 = $madb->query($requete1);
			if($resultat1) $categories = $resultat1->fetchAll(PDO::FETCH_ASSOC);

			// Récupération des forfaits
			$requete2 = 'SELECT DISTINCT forfaitlivraison.idForfait, forfaitlivraison.description, forfaitlivraison.montant FROM forfaitlivraison ;';
			$resultat2 = $madb->query($requete2);
			if($resultat2) $forfaits = $resultat2->fetchAll(PDO::FETCH_ASSOC);
		}
	?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit = "return checkproduit()" enctype="multipart/form-data">
		<fieldset>
			</br>
			<label for="id_Cat">Catégorie : </label>
			<select id="id_Cat" name="categorieproduit" size="1">
			<option value="0">Rechercher une catégorie</option>
				<?php
					foreach($categories as $categorieproduit){
						echo '<option value="'.$categorieproduit['idCat'].'">'.$categorieproduit['intitule'].'</option>';
					}
				?>
			</select>
			<br></br>
			<label for="designation">Nom complet du produit : </label>
			<input type="text" name="designation" id="designation" placeholder="Nom" required size="20" /><br />
			</br>
			<label for="id_forfait">forfait de livraison :</label> 
			<select id="id_forfait" name="forfait" size="1">
			<option value="0">Forfait de livraison adéquat</option>
				<?php
					foreach($forfaits as $forfaitlivraison ){
						echo '<option value="'.$forfaitlivraison['idForfait'].'">'.$forfaitlivraison['description'].' '.$forfaitlivraison['montant'].'</option>';
					}
				?>
			</select>
			<br></br>
			<label for="image">Choisissez une image à télécharger : </label>
			<input type="file" name="images" id="image">
			</br>	
			</br>
			<label for="prix">Prix TTC du produit : </label>
				<input type="text" name="prix" id="prix" placeholder="Prix TTC" required size="20"/><br />
				</br>
				<input type="submit" value="Ajouter le nouveau produit"/>
		</fieldset>
	</form>
	<?php
		echo "<br/>";
	}
	//***********************************************************************************************
	function FormulaireSuppressionBDD(){
		// Modification : Utilisation de l'API pour lister les produits
		// On utilise la fonction définie dans fonctions.php
		$BDD = listerProduits(); 
	?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<fieldset> 
			<select id="produit_supp" name="produit_supp" size="1">
			<option value="0">Choisissez le produit à supprimer</option>
				<?php
					if(is_array($BDD)){
						foreach($BDD as $bases ){
							// Attention : vérifiez si l'API renvoie 'idPdt' ou 'id' selon votre modèle FastAPI
							echo '<option value="'.$bases['idPdt'].'">'.$bases['designation'].'</option>';
						}
					}
				?>
			</select>
			</br></br>
			<img src="image.php" onclick="this.src='image.php?' + Math.random();" alt="captcha" style="cursor:pointer;">
			<input type="text" name="captcha"/>
			</br></br>
			<input type="submit" value="Supprimer"/>
		</fieldset>
	</form>
	<?php
		echo "<br/>";
	}// fin affiche$_SESSION["admin"]==true
	//****************************************************************************************************************
	function FormulaireChoixProduit($choix){
        // Modification : Utilisation de l'API pour lister les produits
        $produits = listerProduits();
    ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <fieldset> 
            <select id="id_produit" name="produit" size="1">
                <?php
                    if(is_array($produits)){
                        foreach($produits as $produit ){
                            echo '<option value="'.$produit['idPdt'].'">'.$produit['designation'].'</option>';
                        }
                    }
                ?>
            </select>
            <input type="submit" value=" <?php echo $choix;?> "/>
        </fieldset>
    </form>
    <?php
        echo "<br/>";
    }
	//****************************************************************************************************************
	function FormulaireModificationProduit($objet){ 
        global $api_url_base; // Nécessaire pour faire l'appel API direct
        $madb = get_db_connection(); // Connexion BDD pour les forfaits (table auxiliaire)

        // 1. Récupération du produit via l'API (et non plus SQL direct)
        // $objet contient l'ID du produit
        $produitDetails = call_api($api_url_base . "/" . (int)$objet, 'GET');
        
        // Si l'API échoue ou produit vide
        if (!$produitDetails) {
            echo "<p style='color:red'>Erreur: Impossible de récupérer les informations du produit depuis l'API.</p>";
            return;
        }

        // 2. Récupération des forfaits via MariaDB (Table auxiliaire non gérée par l'API Produit)
        $forfaits = [];
        if ($madb) {
            $requete3 = 'SELECT * FROM forfaitlivraison';
            $resultat3 = $madb->query($requete3);
            if ($resultat3) $forfaits = $resultat3->fetchall(PDO::FETCH_ASSOC);
        }
    ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
         
        <fieldset> 
            <label for="id_produit">Nom produit : </label>
            <input type="text" name="produit" id="id_produit" value="<?php echo htmlspecialchars($produitDetails["designation"]); ?>" readonly required size="20" /><br />

            <label for="id_prix">Prix : </label>
            <input name="prix" id="id_prix" value="<?php echo htmlspecialchars($produitDetails['prixTTC']); ?>" required size="20" /><br />
            
            <label for="id_forfait">Forfait de Livraison :</label> 
            <select id="id_forfait" name="forfait" size="1">
                
                <?php 
                    foreach($forfaits as $forfait ){
                        // On pré-sélectionne le forfait actuel si possible (optionnel, logique améliorée)
                        $selected = (isset($produitDetails['forfaitLivraison']) && $produitDetails['forfaitLivraison'] == $forfait['idForfait']) ? 'selected' : '';
                        
                        echo '<option value="'.$forfait['idForfait'].'" '.$selected.'>'.$forfait['description'].'</option>';
                    }
                ?>
            </select>   
            <br/><br/>
			<img src="image.php" onclick="this.src='image.php?' + Math.random();" alt="captcha" style="cursor:pointer;">
			<input type="text" name="captcha"/>
			</br></br>
            <input type="submit" value="Valider"/>
        </fieldset>
    </form>
    <?php
        echo "<br/>";
    }// fin 
?>