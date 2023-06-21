<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

$Errs = array() ;
// traitement si soumission du formulaire d'inscription
(isset($_POST['btnSInscrire']) ? vll_traitement_inscription($Errs) : vll_initialisation_formulaire()) ;
    
// si utilisateur déjà authentifié, on le redirige vers la page cuiteur.php ou si le formulaire n'a aucune erreur
if ((isset($_POST['btnSInscrire']) && count($Errs) == 0) || vl_est_authentifie()) {
    header ('location: ./cuiteur.php?nombreBlabla=1');
    exit();  
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

vl_aff_debut('Inscription','../styles/cuiteur.css');

vl_aff_entete(false,'Inscription');
vl_aff_infos(null,false);

vll_aff_formulaire($Errs);

vl_aff_pied();
vl_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Permet d'initialiser le formulaire
 */
function vll_initialisation_formulaire(){
    $_POST['pseudo'] = '';
    $_POST['nomprenom'] = '';
    $_POST['email'] = '';
    $_POST['naissance'] = "2000-01-01";
}

//______________________________________________________________
/**
 * Vérifie le formulaire d'inscription 
 * 
* Fonction qui fait la vérification des données reçues, et 
* l'enregistrement du nouvel utilisateur dans la base de données 
* si aucune erreur n'est détectée
*
* @global array $_POST pour accéder au zone de saisies
* @return array le tableau contenant les erreurs
*/
function vll_traitement_inscription(array &$Errs){

    $obligatoiore = array('pseudo','passe1','passe2','nomprenom','email','naissance','btnSInscrire');
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
        $Errs[] = 'Le pseudo ne doit contenir que des caractères alphanumériques.';
    }  



    $mdp = trim($_POST['passe1']);
    $mdp2 = trim($_POST['passe2']);
    vl_verif_mdp($mdp,$mdp2,$Errs);


    $nomPrenom = trim($_POST['nomprenom']);
    vl_verif_nomPrenom($nomPrenom,$Errs);


    $email = trim($_POST['email']);
    vl_verif_email($email,$Errs);

    $date = trim($_POST['naissance']);
    $dateSansTirer = explode("-",$date);
    vl_verif_dateNaissance($date,$Errs);



    if(count($Errs) == 0){
        $bd = vl_bd_connect();

        $S = "SELECT usID, usPseudo
        FROM users
        WHERE usPseudo = '{$_POST['pseudo']}';";

        $R = vl_bd_send_request($bd, $S);
        $T= mysqli_fetch_assoc($R);

        if(isset($T['usID'])){
            $Errs['pseudo'] = 'Le pseudo '.$_POST['pseudo'].' n\'est pas disponible, il est déjà prit.';
        }else{
            $mdpPASSWORDhash = password_hash($mdp, PASSWORD_DEFAULT);
            $inscription = date('Ymd');
            $dateNaissance = $dateSansTirer[0]*10000+$dateSansTirer[1]*100+$dateSansTirer[2];
            $S = "INSERT INTO users SET
                    usPseudo = '$pseudo',
                    usNom = '$nomPrenom',
                    usPasse = '$mdpPASSWORDhash',
                    usMail = '$email',
                    usDateInscription = $inscription,
                    usDateNaissance = $dateNaissance,
                    usBio = '',
                    usVille = '',
                    usWeb = ''";

            $R = vl_bd_send_request($bd, $S);
            $id = mysqli_insert_id($bd);
            $_SESSION['usID'] = $id;
            $_SESSION['usPseudo'] = $pseudo;
            
        }

        // libération des ressources
        mysqli_close($bd);
        
    }
    return $Errs;
}


?>