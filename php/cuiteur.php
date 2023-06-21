<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifier les parametre d'entree si un formulaire a etais envoyer...
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/
ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

vl_verif_authentifie();
$bd = vl_bd_connect();

/*------------------------- Etape 1 --------------------------------------------
- vérifier quel fomrulaire a étais soumis et traiter le bon
------------------------------------------------------------------------------*/
//decrypte les parametre de l'url
$nombreBlabla;

$mention='';

$tab[] = &$nombreBlabla;
$tab[] = &$param;
if(count($_GET) > 0 ){

    afficherReception($tab);
    
    $nombreBlabla = explode('=',$nombreBlabla);
    $nombreBlabla = $nombreBlabla[count($nombreBlabla)-1];

    if(isset($param)){
        $temp = explode('=',$param);
        $keyParam = $temp[0];
        $valueParam = $temp[1];
        if(strcmp($keyParam,'recuiter') == 0){
            vll_recuit_blabla($bd,$_SESSION['usID'],$valueParam);
        }elseif(strcmp($keyParam,'supprimer') == 0){
            vl_delet_blablas($bd,$valueParam);
        }elseif(strcmp($keyParam,'mention') == 0){
            $mention = '@'.$valueParam;
        }
    }  
}
//si un formulaire a etais envoyer alors on envoie un blabla
if(isset($_POST['txtMessage'])){
    vll_cuit_blabla($bd,$_SESSION['usID'],$_POST['txtMessage']);
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

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
WHERE UA.usID = {$_SESSION['usID']} OR eaIDUser = {$_SESSION['usID']} OR meIDUser = {$_SESSION['usID']}
GROUP BY blID
ORDER BY blID DESC;";



$R = vl_bd_send_request($bd, $S);


$T= mysqli_fetch_assoc($R);

vl_aff_debut('cuiteur','../styles/cuiteur.css');

vl_aff_entete(true,'',$mention);

vl_aff_infos($bd);
mysqli_data_seek($R,0);

echo '<div id="contenu">',
    '<ul class="bcMessages">';
vl_afficher_blablas($R,$bd,"cuiteur",$nombreBlabla,array());
echo '</ul></div>';

vl_aff_pied();
vl_aff_fin();

/**
 * permet de trouver les tags et les mentions contenue dans une string
 * 
 * @param mysqli $bd            pour faire des requetes ala bd
 * @param string $str           la string a verifier
 * @param array &$groupTag      l'adresse vers le tab qui contiendras les tag
 * @param array &$groupMentio   l'adresse vers le tab qui contiendras les mentions
 * 
 */
function vll_trouver_tags_mentions(mysqli $bd,string $str,array &$groupTag,array &$groupMentio){
    $strExplode = explode(' ',$str);
    if(count($strExplode) > 1){
        $strExplode = explode(' ',$str);
    

        foreach ($strExplode as $key => $value) {
            if($value[0] == '#' ){
                $groupTag[] = mb_ereg_replace('#','',$value);
            }
            if($value[0] == '@'){
                $groupMentio[] = mb_ereg_replace('@','',$value);
            }
        }
        return;
    }
    

   

    if($str[0] == '#' ){
        $groupTag[] = mb_ereg_replace('#','',$str);
    }
    if($str[0] == '@'){
        $groupMentio[] = mb_ereg_replace('@','',$str);
    }
}


/**
 * Envoie un blalbla en verifiant que tout est bien
 * 
 * @param mysqli $bd            la bd pour requete SQL
 * @param int $id               l'id de la personne qui va publier ce blabla
 * @param string $blTexte       le texte du blabla
 * 
 */
function vll_cuit_blabla(mysqli $bd,int $id,string $blTexte){

    if(strlen($blTexte) > LONGUEUR_MAX_BIO){
        return 2;
    }

    $groupTag = array();
    $groupMentio = array();
    vll_trouver_tags_mentions($bd,$_POST['txtMessage'],$groupTag,$groupMentio);

    $text = addslashes($blTexte);

    $aujourdhui = getdate();
    $heure = "{$aujourdhui['hours']}:{$aujourdhui['minutes']}:{$aujourdhui['seconds']}";
    $date = vl_date_aujourdhui_format_bd();

    $SS = "INSERT INTO `blablas`(`blIDAuteur`, `blDate`, `blHeure`, `blTexte`) 
                        VALUES ('{$id}','{$date}','{$heure}','{$text}')";
    
    vl_bd_send_request($bd,$SS);
    $blID = mysqli_insert_id($bd);

    if(isset($groupTag)){
        foreach ($groupTag as $key => $value) {
            if(strlen($value) > 0){
                $SSS ="INSERT INTO `tags`(`taID`, `taIDBlabla`) 
                            VALUES ('{$value}','{$blID}')";
                vl_bd_send_request($bd,$SSS); 
            }
                     
        }
    }


    if(isset($groupMentio)){
        foreach ($groupMentio as $key => $value) {
            if(strlen($value) > 0){
                $POURrecupUSid = "SELECT usID FROM `users` WHERE usPseudo = '{$value}';";
                $RR = vl_bd_send_request($bd,$POURrecupUSid); 
                $T= mysqli_fetch_assoc($RR);

                if(isset($T['usID'])){
                    $SSS ="INSERT INTO `mentions`(`meIDUser`, `meIDBlabla`) 
                                VALUES ('{$T['usID']}','{$blID}')";
                    vl_bd_send_request($bd,$SSS); 
                }  
            }       
        }
    }
}

/**
 * Permet de recuite un blablas
 * 
 * @param mysqli $bd            la bd pour requete SQL
 * @param int $id               l'id de la personne qui va publier ce blabla
 * @param string $blID          l'id de la personne a qui appartient le blablas qu'on veut recuite
 */
function vll_recuit_blabla(mysqli $bd,int $id,int $blID){

    $S = "SELECT DISTINCT blIDAuteur,blTexte,GROUP_CONCAT(DISTINCT taID) AS groupTag,GROUP_CONCAT(DISTINCT meIDUser) AS groupMention 
    FROM ((`blablas` LEFT OUTER JOIN mentions ON blID = meIDBlabla)
    LEFT OUTER JOIN tags ON taIDBlabla = blID)
    WHERE blID={$blID};";

    $R = vl_bd_send_request($bd,$S);
    $T= mysqli_fetch_assoc($R);

    $text = addslashes($T['blTexte']);

    $aujourdhui = getdate();
    $heure = "{$aujourdhui['hours']}:{$aujourdhui['minutes']}:{$aujourdhui['seconds']}";
    $date = vl_date_aujourdhui_format_bd();

    $SS = "INSERT INTO `blablas`(`blIDAuteur`, `blDate`, `blHeure`, `blTexte`, `blIDAutOrig`) 
                        VALUES ('{$id}','{$date}','{$heure}','{$text}','{$T['blIDAuteur']}')";
    
    vl_bd_send_request($bd,$SS);
    $blID = mysqli_insert_id($bd);
    $groupTag;
    if(isset($T['groupTag'])){
        $groupTag = explode(',',$T['groupTag']);
        
        foreach ($groupTag as $key => $value) {
            $SSS ="INSERT INTO `tags`(`taID`, `taIDBlabla`) 
                            VALUES ('{$value}','{$blID}')";
            vl_bd_send_request($bd,$SSS);          
        }
    }

    $groupMention;
    if(isset($T['groupMention'])){
        $groupMention = explode(',',$T['groupMention']);
        foreach ($groupMention as $key => $value) {
            $SSS ="INSERT INTO `mentions`(`meIDUser`, `meIDBlabla`) 
                            VALUES ('{$value}','{$blID}')";
            vl_bd_send_request($bd,$SSS);          
        }
    }
}
?>