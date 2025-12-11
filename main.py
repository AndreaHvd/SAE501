from typing import Union, List, Dict, Any
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import mysql.connector

# Configuration de la connexion
db_config = {
    "host": "mariadb",
    "user": "user",
    "password": "lannion",
    "port": 3306,
    "database": "mariadb"
}

# Fonction pour obtenir une nouvelle connexion à chaque requête
def get_db_connection():
    try:
        conn = mysql.connector.connect(**db_config)
        return conn
    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"Erreur connexion BDD: {err}")

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
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True) # dictionary=True permet d'avoir les noms de colonnes
    try:
        requete = "SELECT idPdt, idCat, prixTTC, designation, forfaitLivraison, images FROM produit"
        cursor.execute(requete)
        myresults = cursor.fetchall()
        return myresults
    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"Erreur SQL: {err}")
    finally:
        cursor.close()
        conn.close()

@app.get("/categorieproduit/{idCat}")
def list_cat(idCat: int):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        # Utilisation de paramètre %s pour la sécurité
        requete = "SELECT * FROM produit WHERE idCat=%s"
        cursor.execute(requete, (idCat,))
        myresult = cursor.fetchall()
        return myresult
    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"Erreur SQL: {err}")
    finally:
        cursor.close()
        conn.close()

@app.get("/produit/{idPdt}")
def read_item(idPdt: int):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        requete = "SELECT * FROM produit WHERE idPdt=%s"
        cursor.execute(requete, (idPdt,))
        myresult = cursor.fetchone()
        if myresult:
            return myresult
        return {}
    except mysql.connector.Error as err:
        raise HTTPException(status_code=500, detail=f"Erreur SQL: {err}")
    finally:
        cursor.close()
        conn.close()

@app.post("/produit/")    
def create_item(produit: Produit):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        # Utilisation des paramètres %s pour gérer correctement les types et les guillemets
        requete = "INSERT INTO produit (idPdt, idCat, prixTTC, designation, forfaitLivraison, images) VALUES (%s, %s, %s, %s, %s, %s)"
        valeurs = (produit.idPdt, produit.idCat, produit.prixTTC, produit.designation, produit.forfaitLivraison, produit.images)
        cursor.execute(requete, valeurs)
        conn.commit()
        return "OK"
    except mysql.connector.Error as err:
        print(f"Erreur insertion: {err}") # Affiche l'erreur dans les logs Docker
        raise HTTPException(status_code=500, detail=f"Erreur insertion: {err}")
    finally:
        cursor.close()
        conn.close()

@app.put("/produit/{item_id}")
def update_item(item_id: int, produit: Produit):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        requete = "UPDATE produit SET idCat=%s, prixTTC=%s, designation=%s, forfaitLivraison=%s, images=%s WHERE idPdt=%s"
        valeurs = (produit.idCat, produit.prixTTC, produit.designation, produit.forfaitLivraison, produit.images, item_id)
        cursor.execute(requete, valeurs)
        conn.commit()
        return "OK"
    except mysql.connector.Error as err:
        print(f"Erreur modification: {err}")
        raise HTTPException(status_code=500, detail=f"Erreur modification: {err}")
    finally:
        cursor.close()
        conn.close()

@app.delete("/produit/{item_id}")
def delete_item(item_id: int):
    conn = get_db_connection()
    cursor = conn.cursor()
    try:
        requete = "DELETE FROM produit WHERE idPdt=%s"
        cursor.execute(requete, (item_id,))
        conn.commit()
        return "OK"
    except mysql.connector.Error as err:
        print(f"Erreur suppression: {err}")
        raise HTTPException(status_code=500, detail=f"Erreur suppression: {err}")
    finally:
        cursor.close()
        conn.close()