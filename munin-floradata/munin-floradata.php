#!/usr/bin/env php
<?php

// lecture de la configuration
$fichierConfig = __DIR__ . "/config.json";
if (!file_exists($fichierConfig)) {
	erreur("fichier config manquant");
}
$config = json_decode(file_get_contents($fichierConfig), true);
//var_dump($config);
$indicateurs = $config['indicateurs'];

// décodage du nom de l'indicateur depuis le lien
$nomScript = $argv[0];
$nomScriptDecoupe = explode('_', $nomScript);
if (empty($nomScriptDecoupe[1])) {
	erreur("mauvais lien");
}
$nomIndicateur = $nomScriptDecoupe[1];
//echo $indicateur . PHP_EOL;

// lecture de l'indicateur dans la config
if (!array_key_exists($nomIndicateur, $indicateurs)) {
	erreur("mauvais indicateur [$nomIndicateur]");
}
$indic = $indicateurs[$nomIndicateur];
//var_dump($indic);

// config à donner à Munin
if ($argc > 1 && $argv[1] == 'config') {
	$configIndic = $indic['config'];
	array_unshift($configIndic, "graph_category floradata");
	$configIndic[] = ''; // pour ajouter un EOL à la fin
	echo implode(PHP_EOL, $configIndic);
	exit(0);
}

// dossier de cache pour la temporisation
$dossierCache = $config['cache'];
if (!is_dir($dossierCache) || !is_writable($dossierCache)) {
	erreur("dossier cache inexistant ou pas le droit d'écrire dedans");
}
// intervalle plus grand que les 300 secondes de Munin < 2.0 ?
$intervalle = false;
if (! empty($indic['intervalle'])) {
	$intervalle = $indic['intervalle'];
}

// si un intervalle a été défini, on vérifie la date de dernière écriture du cache
$recalculer = ($intervalle === false || cacheObsolete($nomIndicateur, $intervalle));

$valeurs = "";
if ($recalculer) {
	// connexion à la base de données
	$dsn = 'mysql:host=' . $config['bd']['hote'] . ';dbname=' . $config['bd']['base'] . ';port=' . $config['bd']['port'];
	$bd = new PDO($dsn, $config['bd']['login'], $config['bd']['mdp']);
	// boucle sur les séries
	foreach ($indic['series'] as $nomSerie => $serie) {
		// exécution de la requête
		// @TODO remplir le cache tout de même en cas d'échec, pour ne pas
		// réessayer trop souvent
		$q = $serie["requete"];
		$res = $bd->query($q);
		$ligne = $res->fetch();
		$val = $ligne['val'];
		// ajout de la série aux valeurs de l'indicateur
		$valeurs .= $nomSerie . '.data ' . $val . PHP_EOL;
	}
	// écriture du cache
	ecrireCache($nomIndicateur, $valeurs);
} else {
	// lecture dans le cache
	$valeurs = lireCache($nomIndicateur);
}

// sortie de valeurs
echo $valeurs;


/** retourne true si la date de dernière écriture du fichier de cache pour
	l'indicateur donné est plus vieille que l'intervalle demandé */
function cacheObsolete($nomIndicateur, $intervalle) {
	$fichierCache = calculerNomFichierCache($nomIndicateur);
	$dateModif = 0;
	if (file_exists($fichierCache)) {
		$dateModif = filemtime($fichierCache);
	}
	$maintenant = time();
	$diff = $maintenant - $dateModif;
	//echo "fichier modifié il y a [$diff]\n";
	$obsolete = ($diff > $intervalle);
	//echo "obsolete : "; var_dump($obsolete); echo "\n";
	return $obsolete;
}

/** écrit dans le cache les valeurs de l'indicateur donné */
function ecrireCache($nomIndicateur, $valeurs) {
	$fichierCache = calculerNomFichierCache($nomIndicateur);
	file_put_contents($fichierCache, $valeurs);
}

/** lit dans le cache les valeurs de l'indicateur donné */
function lireCache($nomIndicateur) {
	$fichierCache = calculerNomFichierCache($nomIndicateur);
	return file_get_contents($fichierCache);
}

/** renvoie l'emplacement du fichier de cache correspondant à l'indicateur donné */
function calculerNomFichierCache($nomIndicateur) {
	global $dossierCache;
	$fichier = $dossierCache . '/munin-floradata-' . $nomIndicateur . '.cache';
	//echo "fichier cache: [$fichier]\n";
	return $fichier;
}

/** écrit un message d'erreur sur la sortie d'erreur et quitte le programme
	avec un code d'erreur -1 (255) */
function erreur($str) {
	fwrite(STDERR, $str . PHP_EOL);
	exit(-1);
}