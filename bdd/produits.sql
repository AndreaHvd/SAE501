START TRANSACTION;
CREATE TABLE IF NOT EXISTS `forfaitlivraison` (
	`idForfait`	INTEGER AUTO_INCREMENT,
	`description`	VARCHAR(100) NOT NULL,
	`montant`	int(8) NOT NULL,
	`poids_Min`	float,
	`poids_Max`	float,
	PRIMARY KEY(`idForfait`)
);
CREATE TABLE IF NOT EXISTS `categorieproduit` (
	`idCat`	INTEGER AUTO_INCREMENT,
	`intitule`	VARCHAR(50) NOT NULL,
	`description`	VARCHAR(50) NOT NULL,
	PRIMARY KEY(`idCat`)
);
CREATE TABLE IF NOT EXISTS `produit` (
	`idPdt`	INTEGER AUTO_INCREMENT,
	`idCat`	INTEGER NOT NULL,
	`prixTTC`	FLOAT NOT NULL,
	`designation`	VARCHAR(100) NOT NULL,
	`forfaitLivraison`	int(3) NOT NULL,
	`images`	TEXT,
	CONSTRAINT `fk_Forfait` FOREIGN KEY(`forfaitLivraison`) REFERENCES `forfaitlivraison`(`idForfait`) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY(`idPdt`),
	CONSTRAINT `fk_Cat` FOREIGN KEY(`idCat`) REFERENCES `categorieproduit`(`idCat`) ON DELETE CASCADE ON UPDATE CASCADE
);
INSERT INTO `forfaitlivraison` VALUES (1,'Livraison légère (0-1 kg)',4.99,0.0,2.0);
INSERT INTO `forfaitlivraison` VALUES (2,'Livraison moyenne (1-5 kg)',7.99,2.0,5.0);
INSERT INTO `forfaitlivraison` VALUES (3,'Livraison standard (5-10 kg)',10.99,5.0,10.0);
INSERT INTO `forfaitlivraison` VALUES (4,'Livraison lourde (10-20 kg)',15.99,10.0,20.0);
INSERT INTO `forfaitlivraison` VALUES (5,'Livraison très lourde (20-30 kg)',20.99,20.0,30.0);
INSERT INTO `forfaitlivraison` VALUES (6,'Livraison ultra lourde (30-50 kg)',30.99,30.0,50.0);
INSERT INTO `forfaitlivraison` VALUES (7,'Livraison spéciale (50-150 kg)',60.99,50.0,150.0);
INSERT INTO `forfaitlivraison` VALUES (8,'Sans livraison (contenu numérique)',0,0.0,0.0);
INSERT INTO `categorieproduit` VALUES (1,'Électronique','Appareils & gadgets électronique');
INSERT INTO `categorieproduit` VALUES (2,'Vêtements','Vêtements pour homme, femme et enfants');
INSERT INTO `categorieproduit` VALUES (3,'Électroménager','Tout produits électroménager');
INSERT INTO `categorieproduit` VALUES (4,'Mangas','Mangas en tout genre');
INSERT INTO `categorieproduit` VALUES (5,'Jeux vidéos','Consoles de jeux & jeux ');
INSERT INTO `categorieproduit` VALUES (6,'Contenu digital','Abonnements, cartes cadeaux');
INSERT INTO `categorieproduit` VALUES (7,'Sports & loisirs','Équipements de sport & loisirs');
INSERT INTO `categorieproduit` VALUES (8,'Automobile','Accessoires pour véhicules');
INSERT INTO `categorieproduit` VALUES (9,'Alimentation et boissons','Produits alimentaires & boissons');
INSERT INTO `categorieproduit` VALUES (10,'Informatique','Ordinateurs, logiciels & accessoires');
INSERT INTO `produit` VALUES (1,5,549.99,'PS5 Standard Edition',1,'PS5_standard.png');
INSERT INTO `produit` VALUES (2,5,449.99,'PS5 Digital Edition',2,'PS5_digital.png');
INSERT INTO `produit` VALUES (4,1,549.99,'Enceinte JBL',3,'JBL.png');
INSERT INTO `produit` VALUES (5,2,149.99,'Maillot FFF',1,'Maillot_FFF.png');
INSERT INTO `produit` VALUES (6,3,899.99,'Frigo Américain',7,'Frigo.png');
INSERT INTO `produit` VALUES (7,4,6.99,'One piece Tome 02',1,'One_Piece.png');
INSERT INTO `produit` VALUES (8,7,249.99,'Chaussure de Foot',1,'Chaussures.png');
INSERT INTO `produit` VALUES (9,8,24.99,'Support téléphone ',1,'Voiture.png');
INSERT INTO `produit` VALUES (10,9,37.99,'Kinder Bueno',1,'Kinder.png');
INSERT INTO `produit` VALUES (11,6,50.0,'Carte Google Play',8,'Carte_GP.png');
INSERT INTO `produit` VALUES (13,1,1299.0,'MacBook 13',2,'MacBook.png');
INSERT INTO `produit` VALUES (15,5,64.99,'GTA5',1,'GTA_5.jpg');
INSERT INTO `produit` VALUES (16,8,60000.0,'Mégane 2021',7,'Megane 2021.jpg');
INSERT INTO `produit` VALUES (17,7,49.5,'Résine',1,'Resine.jpg');
INSERT INTO `produit` VALUES (18,7,29.99,'Ballon Euro 2024',2,'Ballon.jpg');
INSERT INTO `produit` VALUES (22,4,6.99,'Naruto - Tome 01',1,'Naruto.jpg');
COMMIT;
