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


/*------------------------- Etape intermédiare ---------------------------------
- Récupération des données de la personne demander 
------------------------------------------------------------------------------*/
if(isset($_POST['btnValider'])){
    vl_traitement_formulaire_liste($bd);
    $cryptageCuiteur = crypteSigneURL(implode('|', array("nb=1")));
    header ("location: ./cuiteur.php?xyz={$cryptageCuiteur}");
    exit(); 
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/



vl_aff_debut('Suggestions | Cuiteur','../styles/cuiteur.css');
vl_aff_entete(false,'Suggestions');

vl_aff_infos($bd);

vll_afficher_page($bd);

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
function vll_afficher_page(mysqli $bd){
    $nbSuggestions = 0;
    $nbSug_Abo_abo = NB_ABONNEMENTS_SUGGESTIONS;
    $MaxSugestions = NB_ABONNEMENTS_SUGGESTIONS_MAX;

    $S= "SELECT usID,usPseudo,usNom,eaIDAbonne,eaIDUser,usAvecPhoto
    FROM users LEFT OUTER JOIN estabonne on usID=eaIDAbonne AND eaIDUser = {$_SESSION['usID']}
    WHERE usID IN (SELECT eaIDUser
                    FROM estabonne
                    WHERE eaIDAbonne IN (SELECT eaIDAbonne
                                    FROM estabonne
                                    WHERE eaIDUser={$_SESSION['usID']}
                                    )
                    )
    AND usID!={$_SESSION['usID']}
    AND eaIDUser IS NULL
    ORDER BY RAND()
    LIMIT {$nbSug_Abo_abo};";

    $SS="SELECT usID, usPseudo, COUNT(usID), dejaAbonne.eaIDUser,usAvecPhoto,usNom
    FROM (users INNER JOIN estabonne ON usID=eaIDUser) LEFT OUTER JOIN estabonne AS dejaAbonne ON usID=dejaAbonne.eaIDAbonne AND dejaAbonne.eaIDUser={$_SESSION['usID']}
    WHERE usID!={$_SESSION['usID']}
    ";

    $R = vl_bd_send_request($bd, $S);
    echo '<div  id="contenu">',
        '<form method="post" >',    
                  '<ul class="bcMessages">';

    while (($T= mysqli_fetch_assoc($R)) && $nbSuggestions < $nbSug_Abo_abo) {
        $SS = "{$SS} AND usID!={$T['usID']}";
        vl_li_info_utilisateur($bd,$T,true);
        $nbSuggestions++;
    }
    $SS = "{$SS} 
    AND dejaAbonne.eaIDAbonne IS NULL
    GROUP BY usID
    ORDER BY RAND()
    LIMIT {$MaxSugestions};";

    if($nbSuggestions < $nbSug_Abo_abo){
        $RR = vl_bd_send_request($bd,$SS); 
        while(($TT= mysqli_fetch_assoc($RR))  && $nbSuggestions < $nbSug_Abo_abo ) {
            vl_li_info_utilisateur($bd,$TT,true);
            $nbSuggestions++;
        }
    }
    echo      '<li></li></ul>', ($nbSuggestions == 0 ? vl_aff_Aucune_erreur("Vous n'avez aucune suggestions") : '<table><tr><td><input type="submit" name="btnValider" value="Valider"></td></tr></table></form>'),  
    '</div>';
    
}
?>