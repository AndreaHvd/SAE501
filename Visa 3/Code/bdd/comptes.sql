START TRANSACTION;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
	`EMAIL`	VARCHAR(255) NOT NULL,
	`PASS`	VARCHAR(255) NOT NULL,
	`STATUT`	VARCHAR(50) NOT NULL DEFAULT 'Etudiant',
	PRIMARY KEY(`EMAIL`)
);
INSERT INTO `utilisateurs` VALUES ('superuser@test.fr','L@nnion','admin');
INSERT INTO `utilisateurs` VALUES ('simpleuser@test.fr','L@nnion','user');
COMMIT;
