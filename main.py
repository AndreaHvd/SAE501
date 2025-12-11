from typing import Union
from fastapi import FastAPI
from pydantic import BaseModel

import mysql.connector

mydb = mysql.connector.connect(
    host="mariadb",
    user="user",
    password="lannion",
    port=3306,
    database="mariadb"
)

class Produit(BaseModel):
    idPdt: int
    idCat: int
    prixTTC: float
    designation: str
    forfaitLivraison: int
    images: str

app = FastAPI()

@app.get("/produit/")
def list_all_items():
    requete = "SELECT idPdt, idCat, prixTTC, designation, forfaitLivraison, images FROM produit"
    mycursor = mydb.cursor()
    mycursor.execute(requete)
    myresults = mycursor.fetchall()
    
    # Si aucun résultat, retourner une liste vide
    if not myresults:
        return []

    # Récupérer les noms de colonnes
    try:
        # Ceci est important car fetchall() retourne des tuples
        column_names = [i[0] for i in mycursor.description]
    except Exception as e:
        # Gérer l'exception si le curseur n'a pas de description (peu probable après execute)
        print(f"Erreur lors de la récupération des noms de colonnes : {e}")
        return []

    products_list = []
    for row in myresults:
        # Convertir chaque tuple en dictionnaire {colonne: valeur}
        products_list.append(dict(zip(column_names, row)))
        
    return products_list

@app.get("/categorieproduit/{idCat}")
def list_cat(idCat: int):
    requete = f"SELECT * FROM produit WHERE idCat={idCat}"
    mycursor = mydb.cursor()
    mycursor.execute(requete)
    myresult = mycursor.fetchall()
    for e in myresult :
        return (e)

@app.get("/produit/{idPdt}")
def read_item(idPdt: int):
    requete = f"SELECT * FROM produit WHERE idPdt={idPdt}"
    mycursor = mydb.cursor()
    mycursor.execute(requete)
    myresult = mycursor.fetchall()
    for e in myresult :
        return (e)

@app.post("/produit/")    
def create_item(produit: Produit):
    requete = f"INSERT INTO produit VALUES({produit.idPdt},{produit.idCat},{produit.prixTTC},'{produit.designation}',{produit.forfaitLivraison},'{produit.images}')"
    mycursor = mydb.cursor()
    mycursor.execute(requete)
    mydb.commit()
    return "OK"

@app.put("/produit/{item_id}")
def update_item(item_id: int, produit: Produit):
    requete = f"UPDATE produit SET idCat={produit.idCat}, prixTTC={produit.prixTTC}, designation='{produit.designation}', forfaitLivraison={produit.forfaitLivraison}, images='{produit.images}' WHERE idPdt={item_id}"
    mycursor = mydb.cursor()
    mycursor.execute(requete)
    mydb.commit()
    return "OK"

@app.delete("/produit/{item_id}")
def delete_item(item_id: int):
    requete = f"DELETE FROM produit WHERE idPdt={item_id}"
    mycursor = mydb.cursor()
    mycursor.execute(requete)
    mydb.commit()
    return "OK"
