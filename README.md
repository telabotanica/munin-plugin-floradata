# munin-plugin-floradata
Une sonde pour Munin qui mesure des trucs dans Flora Data

## Principe de fonctionnement
Le fichier `indicateurs.json` contient la liste des **indicateurs** disponibles, chaque **indicateur** donnant lieu à un graphe sur Munin (en fait 4 graphes : daily, weekly, monthly et yearly).

Chaque **indicateur** peut afficher plusieurs valeurs sur le même graphe : ce sont les **séries**.

Chaque **indicateur** a un titre, un label pour l'axe des ordonnées, et un intervalle de rafraîchissement, en secondes. Munin interroge les sondes toutes les 300s (5mn), mais un système de cache permet d'augmenter le délai, pour ne pas exécuter trop souvent des requêtes coûteuses en temps et en ressources.

Le cache est configuré dans `config.json`; s'assurer que le dossier renseigné existe et que l'utilisateur Munin a le droit d'écrire dedans.

Chaque **série** possède une configuration propre (nom court, nom détaillé, type de ligne à tracer), et une **requête**.

Une **série** dont le nom (clef JSON) commence par `_` (underscore) sera ignorée.

Chaque **requête** doit retourner un nombre sous l'intitulé `val`, du genre `SELECT count(*) AS val (...) `

Pour lire un indicateur, créer un **lien symbolique** vers `munin-floradata.php` portant le nom `floradata_nomdelindicateur`. Des exemples sont livrés.

Pour installer ce "plugin" dans Munin, créer les liens symboliques dans `/etc/munin/plugins`, un par indicateur. Vérifier qu'ils fonctionnent en les exécutant avec `munin-run`, et en essayant le paramètre `config` :
```
# munin-run floradata_champsmanquants
# munin-run floradata_champsmanquants config
```

Lorsque ça fonctionne, relancer le nœud Munin :
```
# service munin-node restart
```
et attendre patiemment que ça graphe !

## Temps d'exécution
Par défaut Munin interrompt une sonde au bout de 10 secondes.
Si les requêtes choisies prennent plus de 10 secondes à s'exécuter, ce qui est fort probable, ajouter un fichier de configuration dans `/etc/munin/plugin-conf.d`, par exemple `floradata.conf`, et y placer le contenu suivant :
```
[floradata_*]
timeout 60
```
