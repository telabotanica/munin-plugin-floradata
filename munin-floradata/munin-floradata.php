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

// récupération des données
$dsn = 'mysql:host=' . $config['bd']['hote'] . ';dbname=' . $config['bd']['base'] . ';port=' . $config['bd']['port'];
$bd = new PDO($dsn, $config['bd']['login'], $config['bd']['mdp']);

// boucle sur les séries
foreach ($indic['series'] as $nomSerie => $serie) {
	$q = $serie["requete"];
	$res = $bd->query($q);
	$ligne = $res->fetch();
	echo $nomSerie . '.data ' . $ligne['val'] . PHP_EOL;
}

/** écrit un message d'erreur sur la sortie d'erreur et quitte le programme
	avec un code d'erreur -1 (255) */
function erreur($str) {
	fwrite(STDERR, $str . PHP_EOL);
	exit(-1);
}