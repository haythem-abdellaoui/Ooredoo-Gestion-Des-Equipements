import pandas as pd
import joblib
import os
import sys
import json

# Charger le modèle
import os
script_dir = os.path.dirname(os.path.abspath(__file__))
model_path = os.path.join(script_dir, '../public/model/model.pkl')
model_path = os.path.normpath(model_path)
model, encoders, feature_names = joblib.load(model_path)

# Lire les données envoyées en JSON via l'entrée standard (stdin)
input_data = json.loads(sys.stdin.read())

# Convertir en DataFrame
df = pd.DataFrame([input_data])

# Encoder les colonnes catégorielles
for col in df.select_dtypes(include="object").columns:
    le = encoders[col]
    if df[col][0] not in le.classes_:
        df[col] = le.transform([le.classes_[0]])
    else:
        df[col] = le.transform(df[col])

# Prédire la probabilité de panne
proba = model.predict_proba(df[feature_names])[0][1]

# Afficher la sortie (pour que PHP la récupère)
print(proba)
