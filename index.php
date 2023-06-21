<?php

/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/
ob_start(); //démarre la bufferisation
session_start();

require_once './php/bibli_generale.php';
require_once './php/bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/


// traitement aprés soumissions du formulaire
$Errs = array() ;
(isset($_POST['btnConnexion']) ? vll_traitement_connexion($Errs) : $_POST['pseudo'] = '');

// vérifications aprés soumissions du formulaire de la validité des champs saisies et si l'utilisateur est connecter redirige vers cuiteur
if((isset($_POST['btnConnexion']) && count($Errs) == 0) || vl_est_authentifie()){
    $cryptageCuiteur = crypteSigneURL(implode('|', array("nb=1")));
    header ("location: ./php/cuiteur.php?xyz={$cryptageCuiteur}");
    exit();
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

vl_aff_debut('index','./styles/cuiteur.css');
vl_aff_entete(false,'Connectez-vous');

vl_aff_infos(null,false);
vll_aff_connexion($Errs);

vl_aff_pied();
vl_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

//______________________________________________________________
/**
 * Vérifie le formulaire de connexion 
 * 
* Fonction qui fait la vérification des données reçues, et 
* l'enregistrement du nouvel utilisateur dans la base de données 
* si aucune erreur n'est détectée
*
* @global array $_POST pour accéder au zone de saisies
* @return array le tableau contenant les erreurs
*/
function vll_traitement_connexion(array &$Errs){

    $obligatoiore = array('pseudo','passe','btnConnexion');
    if(!vl_parametres_controle('post',$obligatoiore)){
        echo 'ERROR 404' ;
        vl_aff_fin();
        exit(1) ;
    }


    $pseudo = trim($_POST['pseudo']);
    $pseudoNoTags = strip_tags($pseudo);
    if ($pseudo != $pseudoNoTags) {
        vl_session_exit('index.php');
    }
    elseif($pseudo == ''){
        $Errs[] = 'La zone pseudo ne peut pas étre vide.';
    }
    elseif(! mb_ereg_match('^[[:alnum:]]{'.LONGUEUR_MIN_PSEUDO.','.LONGUEUR_MAX_PSEUDO.'}$',$pseudo)){
        $Errs[] = 'Le pseudo ne contient que des caractères alphanumériques.';
    }  



    $mdp = trim($_POST['passe']);
    $mdpNoTags = strip_tags($mdp);
    if ($mdp != $mdpNoTags) {
        vl_session_exit('index.php');
    }
    elseif($mdp == ''){
        $Errs[] = 'La zone du mot de passe est obligatoire.';
    }
    elseif(! mb_ereg_match('^[[:alnum:]]{'.LONGUEUR_MIN_MDP.','.LONGUEUR_MAX_MDP.'}$',$mdp)){
        $Errs[] = 'Le mot de passe doit être constitué de '.LONGUEUR_MIN_MDP.' à '.LONGUEUR_MAX_MDP.' caractères.';
    }
    

    if(count($Errs) == 0){
        
        $bd = vl_bd_connect();
        $pseudoEchapper = em_bd_proteger_entree($bd,$_POST['pseudo']);
        $S = "SELECT usID, usPseudo, usPasse
        FROM users
        WHERE usPseudo = '{$pseudoEchapper}';";

        $R = vl_bd_send_request($bd, $S);
        $T= mysqli_fetch_assoc($R);

        if(!isset($T['usPseudo'])){
            $Errs[] = 'Le pseudo '.$pseudo.' n\'existe pas.';
        }elseif(!password_verify($mdp,$T['usPasse'])){
            $Errs[] = 'Le mot de passe n\'est pas le bon.';
        }else{
            $_SESSION['usID'] = em_html_proteger_sortie($T['usID']);
            $_SESSION['usPseudo'] = em_html_proteger_sortie($pseudo);
        }
        // libération des ressources
        mysqli_close($bd);

    }
    return $Errs;
}


//____________________________________________________________________________
/**
* Affichage du contenu de la page (formulaire de connexions)
*   
* @global array $_POST tableau super globa pour récupérer les info de l'utilisateur
* @param array &$Errs tableau contenant les erreurs que l'utilisateur a fait
*/
function vll_aff_connexion(array &$Errs){
    echo'<div id="contenu">';
    vl_aff_erreur($Errs);

    echo '<form action="./index.php" method="post">',
                'Pour vous connecter à cuiteur, il faut vous authentifier :',
                '<table>',
                vl_aff_ligne_input('<label for="pseudo">Pseudo :</label>',vl_aff_input('Text','pseudo','value="'.$_POST['pseudo'].'"')),
                vl_aff_ligne_input('<label for="passe">Mot de passe :</label>',vl_aff_input('password','passe','')),
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnConnexion" value="Connexion"/>',
                    '</td>',
                '</tr>',    
            '</table>', 
            '<p>',
                'Pas encore de compte ? <a href="./php/inscription.php"> Inscrivez-vous </a>sans tarder !<br>',
                'Vous hésitez à vous inscrire ? Laissez-vous séduire par une <a href="./html/presentation.html">présentation </a>des possibilitées de Cuiteur.',
            '</p>',
        '</form>',
        '</div>';

}

?>