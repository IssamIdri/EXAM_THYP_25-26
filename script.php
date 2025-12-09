<?php
echo "Bienvenue sur la page PHP !\n";
echo "Examen THYP du 9 décembre 2025\n";
echo "AISSAOUI IDRISSI ISSAM\n";

function afficherMessage($nom) {
    return "Bonjour, " . $nom . " !\n";
}

$message = afficherMessage("ISSAM");
echo $message;

$date = "9 décembre 2025";
echo "Date de l'examen : " . $date . "\n";
?>

