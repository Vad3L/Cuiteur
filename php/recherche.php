<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifier quel fomrulaire a étais soumis et traiter le bon
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/
ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

vl_verif_authentifie();
$bd = vl_bd_connect();

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
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

vl_aff_debut('Recherche | Cuiteur','../styles/cuiteur.css');
vl_aff_entete(false,'Rechercher des utilisateurs');

vl_aff_infos($bd);


vll_afficher_formulaire_recherche($bd);

vl_aff_pied();
vl_aff_fin();

// libération des ressources
mysqli_close($bd);

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Permet d'afficher le formulaire de cette page
 * 
 *  @param mysqli $bd        pour avoir la base de donnée
 */
function vll_afficher_formulaire_recherche(mysqli $bd){
    $Errs = array();

    echo '<div id="contenu">',
        '<form method="post" >',
            '<table>',
                vl_aff_ligne_input(vl_aff_input('text','search', (isset($_POST['btnValideSearch']) ? 'value="'.$_POST['search'].'"' : '')),'<input type="submit" name="btnValideSearch" value="Rechercher">'),
            '</table>',
        '</form>';

    if(count($Errs) != 0){
        vl_aff_erreur($Errs);
    }

    if(isset($_POST['btnValideSearch'])){

        if($_POST['search']==''){
            $Errs[] = 'La zone de recherche ne peut pas être vide.';
        }

        $recherche = strip_tags(trim($_POST['search']));

        if($recherche != $_POST['search']){
            vl_session_exit("./deconnexion.php");
        }

        

        if(count($Errs) == 0){
            $S ="SELECT usID,usPseudo,usNom,eaIDAbonne,eaIDUser,usAvecPhoto
            FROM users LEFT OUTER JOIN estabonne on usID=eaIDAbonne AND eaIDUser = {$_SESSION["usID"]}
            WHERE usNom LIKE '%{$recherche}%' OR usPseudo LIKE '%{$recherche}%'
            GROUP BY usPseudo;" ;

            $R = vl_bd_send_request($bd, $S);
            $T= mysqli_fetch_assoc($R);

            if(isset($T['usID'])){
                mysqli_data_seek($R,0);
                echo '<form method="post" action="./recherche.php">',
                '<h1>Résultats de la recherche</h1>',
                '<ul class="bcMessages">';


                while (($T= mysqli_fetch_assoc($R)) ) {
                    vl_li_info_utilisateur($bd,$T,true);
                }

                
            }else{echo '<p id="errorIns">Aucun résultat</p>';}
            mysqli_data_seek($R,0);
            echo '<li></li></ul>',
                ( mysqli_fetch_assoc($R) ? '<table><tr><td><input type="submit" name="btnValider" value="Valider"></td></tr></table></form>' : ' ');        
        }  
    }
   echo '</div>';
}




?>