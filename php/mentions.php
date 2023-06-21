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
if(isset($_POST['btnValider'])){
    vl_traitement_formulaire_liste($bd);
    $cryptageCuiteur = crypteSigneURL(implode('|', array("nb=1")));
    header ("location: ./cuiteur.php?xyz={$cryptageCuiteur}");
    exit(); 
}


$id;
$nombreBlabla = 0;
$tab[] = &$id;
$tab[] = &$nombreBlabla;
if(count($_GET) > 0){

    afficherReception($tab);

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

$S = "SELECT usID,usPseudo,usNom,eaIDAbonne,eaIDUser,usDateNaissance,usDateInscription,usVille,usBio,usWeb,usAvecPhoto
FROM users LEFT OUTER JOIN estabonne on usID=eaIDAbonne AND eaIDUser = {$_SESSION['usID']}
WHERE usID={$id};";

$R = vl_bd_send_request($bd, $S);
$T= mysqli_fetch_assoc($R);

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

vl_aff_debut('Abonnés | Cuiteur','../styles/cuiteur.css');
vl_aff_entete(false,($id == $_SESSION['usID'] ? 'Vos mentions': 'Les mentions de '.em_html_proteger_sortie($T['usPseudo'])));

vl_aff_infos($bd);

vll_afficher_page($bd,$T,$nombreBlabla,$id);

vl_aff_pied();
vl_aff_fin();

// libération des ressources
mysqli_close($bd);

// facultatif car fait automatiquement par PHP
ob_end_flush();






/**
 * Affichage de la page et des blablas
 * 
 * @param   mysqli $bd          la base de donner
 * @param   array $T            pour l'utilisateur en question 
 * @param  int $nombreBlabla    le nombre de blabla a afficher
 * @param  int $id              l'id de l'utilisateur en cour
 */
function vll_afficher_page(mysqli $bd,array $T,int $nombreBlabla,int $id){

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
                    FROM blablas LEFT OUTER JOIN mentions ON blID=meIDBlabla
                    WHERE meIDUser={$id})
    GROUP BY blID
    ORDER BY blID DESC;";

    $R = vl_bd_send_request($bd, $S);
    $TT;

    echo '<div  id="contenu">',
                  '<ul class="bcMessages">';
    vl_li_info_utilisateur($bd,$T,false,'id="blablaSansBG"');
    vl_afficher_blablas($R,$bd,"mentions",$nombreBlabla,array("id={$T['usID']}"));
    echo      '</ul>',
    '</div>';
    
}
?>

