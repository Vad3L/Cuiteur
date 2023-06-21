<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifier les parametre
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/
ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

vl_verif_authentifie();
$bd = vl_bd_connect();

/*------------------------- Etape 1 --------------------------------------------
- vérifier les parametre 
------------------------------------------------------------------------------*/
$stringInfoPage=' ';
$taID ='';
$nombreBlabla = 0;
$tab = array();
$tab[] = &$taID;
$tab[] = &$nombreBlabla;
if(count($_GET) > 0){

    afficherReception($tab);

    $temp1 = explode('=',$taID);
    $keyTaID =$temp1[0];
    $valueTAID = $temp1[1];

    $temp2 = explode('=',$nombreBlabla);
    $keyNombreBlalba = $temp2[0];
    $valueNombreBlabla = $temp2[1];

    if(strcmp($keyTaID,"tendance") == 0){
        $taID = $valueTAID;
        $nombreBlabla = $valueNombreBlabla;
    }else{
        $taID = $valueNombreBlabla;
        $nombreBlabla = $valueTAID;
    }

    $stringInfoPage=$taID;
}

/*------------------------- Etape intermédiare --------------------------------------------
- Récupération des données de la personne demander 
------------------------------------------------------------------------------*/



/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

vl_aff_debut('Tendance | Cuiteur','../styles/cuiteur.css');
vl_aff_entete(false,$stringInfoPage);

vl_aff_infos($bd);

(count($_GET) ? vll_aff_tags_specifier($bd,$taID,$nombreBlabla) : vll_aff_tags_tendance($bd));

vl_aff_pied();
vl_aff_fin();

// libération des ressources
mysqli_close($bd);

// facultatif car fait automatiquement par PHP
ob_end_flush();

/**
 * Permet d'afficher la page tendance quand un tag est specifier
 * 
 * @param mysqli $bd            pour faire une requete SQL
 * @param string $taID          pour avoir le tag en questions
 * @param int $nombreBlabla     pour savoir le nombre de blalbas on va afficher
 * 
 */
function vll_aff_tags_specifier(mysqli $bd,string $taID,int $nombreBlabla){

    $S = "SELECT  DISTINCT UA.usID AS IDAuteur, UA.usPseudo AS Auteur, UA.usNom AS nomAuteur, UA.usAvecPhoto AS photo, 
    blTexte, blDate, blHeure,blID,blIDAuteur,
    UO.usID AS IDAuteurOrig, UO.usPseudo AS Auteur_Originel, UO.usNom AS nomAuteurOriginnel, UO.usAvecPhoto AS oriPhoto,
    GROUP_CONCAT(DISTINCT meIDUser) AS groupMention,
    GROUP_CONCAT(DISTINCT taID) AS groupTag
    FROM ((((((users AS UA
    INNER JOIN blablas ON blIDAuteur = usID)
    LEFT OUTER JOIN users AS UO ON UO.usID = blIDAutOrig)
    LEFT OUTER JOIN estabonne ON UA.usID = eaIDAbonne)
    LEFT OUTER JOIN mentions ON blID = meIDBlabla)
    LEFT OUTER JOIN tags ON blID = taIDBlabla)
    LEFT OUTER JOIN users AS UaBONNEMENTS ON meIDUser = UaBONNEMENTS.usID)
    WHERE blID IN (SELECT blID
                    FROM blablas LEFT OUTER JOIN tags ON blID=taIDBlabla
                    WHERE taID='{$taID}')
    GROUP BY blID
    ORDER BY blID DESC;";

    $R = vl_bd_send_request($bd, $S);

    echo '<div  id="contenu">',    
                  '<ul class="bcMessages">';

    vl_afficher_blablas($R,$bd,"tendance",$nombreBlabla,array("tendance={$taID}"));
    echo      '</ul>',
            '</div>';

}

/**
 * Permet d'afficher la page tendance quand aucun tag n'a etais specifier
 * affiche differrents top 10 de tags 
 * 
 * @param mysqli $bd        pour faire des requetes SQL
 * 
 */
function vll_aff_tags_tendance(mysqli $bd){

    $aujourdhui = getdate();
    $jour = ($aujourdhui['mday'] < 10 ? "0{$aujourdhui['mday']}": $aujourdhui['mday']);
    $mois = ($aujourdhui['mon'] < 10 ? "0{$aujourdhui['mon']}": $aujourdhui['mon']);

    $date = date_create("{$aujourdhui['year']}-{$mois}-{$jour}");
    $jourAenlever = ($aujourdhui['wday'] == 0 ? 6:$aujourdhui['wday'] );
    date_sub($date, date_interval_create_from_date_string("{$jourAenlever} days"));
    $dateExplodeArray = explode('-',date_format($date, 'Y-m-d'));

    $dateSemaine = "{$dateExplodeArray[0]}{$dateExplodeArray[1]}{$dateExplodeArray[2]}";
    $dateJour = "{$aujourdhui['year']}{$mois}{$jour}";
    $dateMois = "{$aujourdhui['year']}{$mois}01";
    $annee = $aujourdhui['year'].'0101';

    echo    '<div id="contenu">';
    vll_aff_tags_liste_tendance($bd,vll_requete_sql($dateJour),'Top 10 du jour',0);
    vll_aff_tags_liste_tendance($bd,vll_requete_sql($dateSemaine),'Top 10 de la semaine',0);
    vll_aff_tags_liste_tendance($bd,vll_requete_sql($dateMois),'Top 10 du mois',0);
    vll_aff_tags_liste_tendance($bd,vll_requete_sql($annee),"Top 10 de l'année",0);
    echo    '</div>';
    

}

/**
 * Affiche les listes des tags tout bien dans un ul avec des li
 * 
 * @param mysqli $bd        pour faire des requetes SQL
 * @param string $S         la requete a envoyer pour ce top
 * @param string $h1        le nom du top
 * @param int $date         la date 
 * 
 */
function vll_aff_tags_liste_tendance(mysqli $bd,string $S,string $h1,int $date){

    $R = vl_bd_send_request($bd, $S);

    echo "<h1><strong>{$h1}</strong></h1>";
    $T= mysqli_fetch_assoc($R);
    if(isset($T)){
        mysqli_data_seek($R,0);
        echo '<ul class="ulTendance">';
        $nb = 1;
        while($T= mysqli_fetch_assoc($R)){
            $tags = em_html_proteger_sortie($T['taID']);
            $nbtags = em_html_proteger_sortie($T['NB']);
            $cryptageTendance = crypteSigneURL(implode('|', array($tags, "1")));
            echo "<li><strong>{$nb}. <a href='./tendance.php?xyz={$cryptageTendance}'>{$tags} ({$nbtags})</a></strong></li>";
            $nb++;
        }
        echo '</ul>';
        return;
    }
    echo 'Aucune tendance ...';
    
}

/**
 * renvoie une requete SQL en fonction d'une date qui change en parametre
 * 
 * @param string $var       date qui changent
 * @return string           la requete former
 * 
 */
function vll_requete_sql(string $var):string{
    return "SELECT taID, COUNT(*) AS NB
    FROM tags INNER JOIN blablas on blID=taIDBlabla 
    WHERE blDate >={$var}
    GROUP BY taID
    ORDER BY NB DESC
    LIMIT 0,10;";
}


?>

