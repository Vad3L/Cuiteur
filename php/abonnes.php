<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifier les parametre et les decryptes
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
//verif envoie formulaire
if(isset($_POST['btnValider'])){
    vl_traitement_formulaire_liste($bd);
    $cryptageCuiteur = crypteSigneURL(implode('|', array("nb=1")));
    header ("location: ./cuiteur.php?xyz={$cryptageCuiteur}");
    exit(); 
}


//vérification parametre dentree du script decryptage
$id = -1;
$oui = array();
$oui[] = &$id;
if (count($_GET)> 0) {
	afficherReception($oui);
}

/*------------------------- Etape intermédiare --------------------------------------------
- Récupération des données de la personne demander 
------------------------------------------------------------------------------*/

$S = "SELECT usID,usPseudo,usNom,eaIDAbonne,eaIDUser,usDateNaissance,usDateInscription,usVille,usBio,usWeb,usAvecPhoto
FROM users LEFT OUTER JOIN estabonne on usID=eaIDAbonne AND eaIDUser = {$_SESSION['usID']}
WHERE usID={$id};";

$R = vl_bd_send_request($bd, $S);
$T= mysqli_fetch_assoc($R);

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

vl_aff_debut('Abonnés | Cuiteur','../styles/cuiteur.css');
vl_aff_entete(false,'Les abonnés de '.em_html_proteger_sortie($T['usPseudo']));

vl_aff_infos($bd);

vll_afficher_page($bd,$T,$id);

vl_aff_pied();
vl_aff_fin();

// libération des ressources
mysqli_close($bd);

// facultatif car fait automatiquement par PHP
ob_end_flush();






/**
 * Affichage de la page et des blablas
 * 
 * @param mysqli $bd la base de donner
 * @param array $T pour l'utilisateur en question 
 * @param int $id pour avoir l'id de la personne choisis
 */
function vll_afficher_page(mysqli $bd,array $T,int $id){

    $S = "SELECT usID,usPseudo,usNom,eaIDUser,usAvecPhoto,eaIDAbonne
    FROM (users LEFT OUTER JOIN estabonne on usID=eaIDAbonne AND eaIDUser = {$_SESSION['usID']})
    WHERE usID IN (SELECT usID
                        FROM `estabonne` INNER JOIN users on usID = eaIDUser
                        WHERE eaIDAbonne = {$id});";



    

    $R = vl_bd_send_request($bd, $S);
    $TT;
      
    echo '<div  id="contenu">',
        '<form method="post" >',    
                  '<ul class="bcMessages">';
    vl_li_info_utilisateur($bd,$T,($_SESSION['usID'] == $T['usID'] ? false : true),'id="blablaSansBG"');
    while ($TT= mysqli_fetch_assoc($R)) {
        vl_li_info_utilisateur($bd,$TT,true);
    }
    echo      '<li></li></ul>',
        '<table><tr><td><input type="submit" name="btnValider" value="Valider"></td></tr></table></form>',  
    '</div>';
    
}
?>

