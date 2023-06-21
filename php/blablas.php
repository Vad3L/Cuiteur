<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifier les parametre crypter
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

//decrypte les parametres
$id;
$nombreBlabla = 0;
$tab[] = &$id;
$tab[] = &$nombreBlabla;
if(count($_GET) > 0){

    afficherReception($tab);
    //les met dans les bonnes variables pour pas qu'il y ai besoin d'ordre dnas l'url pour les parametre
    $temp1 = explode('=',$id);
    $keyID =$temp1[0];
    $valueID = $temp1[1];

    $temp2 = explode('=',$nombreBlabla);
    $keyNombreBlalba = $temp2[0];
    $valueNombreBlabla = $temp2[1];

    if(strcmp($keyID,"id") == 0){
        $id = $valueID;
        $nombreBlabla = $valueNombreBlabla;
    }else{
        $id = $valueNombreBlabla;
        $nombreBlabla = $valueID;
    }

}

/*------------------------- Etape intermédiare --------------------------------------------
- Récupération des données de la personne demander 
------------------------------------------------------------------------------*/

$S = "SELECT usID,usNom,usPseudo,usDateNaissance,usDateInscription,usVille,usBio,usWeb,usAvecPhoto
FROM `users` 
WHERE usID={$id};";

$R = vl_bd_send_request($bd, $S);
$T= em_html_proteger_sortie(mysqli_fetch_assoc($R));

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

vl_aff_debut('Blablas | Cuiteur','../styles/cuiteur.css');
vl_aff_entete(false,'Les blablas de '.em_html_proteger_sortie($T['usPseudo']));

vl_aff_infos($bd);

vll_afficher_page($bd,$T,$nombreBlabla);

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
 */
function vll_afficher_page(mysqli $bd,array $T,int $nombreBlabla){

      $S = "SELECT  DISTINCT UA.usID AS IDAuteur, UA.usPseudo AS Auteur, UA.usNom AS nomAuteur, UA.usAvecPhoto AS photo, 
      blTexte, blDate, blHeure,blID,blIDAuteur,
      UO.usID AS IDAuteurOrig, UO.usPseudo AS Auteur_Originel, UO.usNom AS nomAuteurOriginnel, UO.usAvecPhoto AS oriPhoto,
      GROUP_CONCAT(DISTINCT meIDUser) AS groupMention,
      GROUP_CONCAT(DISTINCT taID) AS groupTag,
      GROUP_CONCAT(DISTINCT UaBONNEMENTS.usPseudo) AS groupAbonnement
      FROM ((((((users AS UA
      INNER JOIN blablas ON blIDAuteur = usID)
      LEFT OUTER JOIN users AS UO ON UO.usID = blIDAutOrig)
      LEFT OUTER JOIN estabonne ON UA.usID = eaIDAbonne)
      LEFT OUTER JOIN mentions ON blID = meIDBlabla)
      LEFT OUTER JOIN tags ON blID = taIDBlabla)
      LEFT OUTER JOIN users AS UaBONNEMENTS ON meIDUser = UaBONNEMENTS.usID)
      WHERE UA.usID = {$T['usID']}
      GROUP BY blID
      ORDER BY blID DESC;";

      $R = vl_bd_send_request($bd, $S);
      
      echo '<div  id="contenu">',    
                  '<ul class="bcMessages">';
      vl_li_info_utilisateur($bd,$T,false,'id="blablaSansBG"');
      vl_afficher_blablas($R,$bd,"blablas",$nombreBlabla,array("id={$T['usID']}"));
      echo      '</ul>',
            '</div>';
}
?>

