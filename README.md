# HistoPad

Mini application web permettant d'historiser des pads (etherpad) et les archiver dans un dépôt git.

## License

Logiciel libre sous license AGPL V3

## Installation

### Debian / Ubuntu

Installation des dépendances :

```
sudo aptitude install php git
```

Récupération des sources :

```
git clone https://github.com/24eme/histopad.git
```

Pour tester l'application :

```
php -S localhost:8000
```

### Déploiement avec Apache

Droits apache sur les dossiers `cache`, `queue` et `pads` :

```
sudo chown www-data:www-data cache queue pads
```

Configuration Apache :

```
DocumentRoot /path/to/histopad

<Directory /path/to/histopad>
    Require all granted
</Directory
```

### Mise à jour des modifications des pads

L'application permet de suivre les modifications de pads.

À chaque tentative de mise à jour la date de prochaine tentative de mise à jour est calculé en ajoutant la même durée d'ancienneté de la dernière modifications à la date du jour.

Ainsi un pad qui a eu une modification il y a un mois et demi sera à mettre à jour dans un mois et demi.

La routine `run.php` va permettre d'aller vérifier si des pads sont à mettre à jour.

Cette routine est lancé automatiquement à chaque appel de page, donc si la page est consulté souvent les modifications d'un pad seront suivi.

Si l'on souhaite tracker les modifications de façon plus fiable il est possible de faire cette vérification via `crontab`.

Vérifie toutes les 5 minutes si des pads sont à mettre à jour via `curl` :

```
*/5  *  *  *  * curl -s http://url_de_l_appli/run.php
```
