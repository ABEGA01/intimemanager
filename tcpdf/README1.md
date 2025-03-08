# Installation de TCPDF

Pour installer TCPDF manuellement :

1. Téléchargez la dernière version de TCPDF depuis : https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip
2. Extrayez le contenu du fichier ZIP
3. Copiez tous les fichiers du dossier `tcpdf` dans le dossier `tcpdf` de votre projet
4. Assurez-vous que le fichier `tcpdf.php` est présent dans le dossier

La structure finale devrait être :
```
votre_projet/
  ├── tcpdf/
  │   ├── tcpdf.php
  │   ├── fonts/
  │   ├── images/
  │   └── ... (autres fichiers)
  └── ... (autres fichiers de votre projet)
```

Une fois l'installation terminée, vous pourrez utiliser les fichiers `ImprimerFacture.php` et `ImprimerEtat.php` pour générer des PDF. 