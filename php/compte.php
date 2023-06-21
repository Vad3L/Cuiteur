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
- vérifier quel fomrulaire a étais soumis et traiter le bon
------------------------------------------------------------------------------*/

//Vérifie quel formulaire & étais envoyer te appel en conséquent la bonne méthode de vérification
$Errs = array() ;
if(isset($_POST['btnValider1'])){
    vll_traitement_formulaire1($bd,$Errs);
}elseif(isset($_POST['btnValider2'])){
    vll_traitement_formulaire2($bd,$Errs);
}elseif(isset($_POST['btnValider3'])){
    vll_traitement_formulaire3($bd,$Errs);
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/


vl_aff_debut('Mon compte | Cuiteur','../styles/cuiteur.css');
vl_aff_entete(false,'Paramètres de mon compte');

vl_aff_infos($bd);

vll_afficher_page($bd,$Errs);

vl_aff_pied();
vl_aff_fin();

// libération des ressources
mysqli_close($bd);

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Affichage de la page et de ses 3 formulaires
 * 
 * @param mysqli $bd    la connexion a la base de donne
 * @param array &$Errs  le tableau d'erreur pour en rajouter
 */
function vll_afficher_page(mysqli $bd,array &$Errs){
    $S = "SELECT * FROM `users` WHERE usID = {$_SESSION['usID']}; ";

    $R = vl_bd_send_request($bd, $S);

    $T= mysqli_fetch_assoc($R);



    echo '<div id="contenu">', 
        '<form method="post" action="./compte.php">',
            'Cette page vous permet de modifier les informations relatives à votre compte.',
            '<h1>Information personnelles</h1>';
    if(isset($_POST['btnValider1']) && count($Errs) == 0){
            vl_aff_Aucune_erreur('La mise à jour des informations personelles sur votre compte a bien été effectuée.');
    }elseif(isset($_POST['btnValider1']) && count($Errs) != 0){
        vl_aff_erreur($Errs);
    }        

    echo    '<table>',
                vl_aff_ligne_input('<label for="nom">Nom </label>',vl_aff_input('text','nom','minlength="4" maxlength="30" value="'.em_html_proteger_sortie($T['usNom']).'"')),
                vl_aff_ligne_input('<label for="dateN">Date de naissance </label>',vl_aff_input('date','dateN','value="'.vl_date_polisseur2(em_html_proteger_sortie($T['usDateNaissance'])).'"')),
                vl_aff_ligne_input('<label for="ville">Ville </label>',vl_aff_input('text','ville','maxlength="30" value="'.em_html_proteger_sortie($T['usVille']).'"')),
                vl_aff_ligne_input('<label for="TextAreaCompte">Mini-bio </label>','<textarea id="TextAreaCompte" name="bio" cols="30" rows="6" style="vertical-align: middle;">'.em_html_proteger_sortie($T['usBio']).'</textarea>'),
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnValider1" value="Valider">',
                    '</td>',
                '</tr>',
            '</table>',
            '</form>',

            '<form method="post">',
                '<h1>Informations sur votre compte Cuiteur</h1>';
    if(isset($_POST['btnValider2']) && count($Errs) == 0){
            vl_aff_Aucune_erreur('La mise à jour des Informations sur votre compte a bien été effectuée.');
    }elseif(isset($_POST['btnValider2']) && count($Errs) != 0){
        vl_aff_erreur($Errs);
    }     
    echo        '<table>',
                vl_aff_ligne_input('<label for="email">Adresse mail </label>',vl_aff_input('email','email','value="'.em_html_proteger_sortie($T['usMail']).'"')),
                vl_aff_ligne_input('<label for="web">Site web </label>',vl_aff_input('text','web','maxlength="30" value="'.em_html_proteger_sortie($T['usWeb']).'"')),
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnValider2" value="Valider">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>',

            '<form method="post" enctype="multipart/form-data">',
                '<h1>Paramètres de votre compte Cuiteur</h1>';
    if(isset($_POST['btnValider3']) && count($Errs) == 0){
        vl_aff_Aucune_erreur('La mise à jour des Paramètres de votre compte a bien été effectuée.');
    }elseif(isset($_POST['btnValider3']) && count($Errs) != 0){
        vl_aff_erreur($Errs);
    }     
    echo        '<table>',
                vl_aff_ligne_input('<label for="pwd">Changer le mot de passe : </label>',vl_aff_input('password','pwd','')),
                vl_aff_ligne_input('<label for="pwdBis">Répétez le mot de passe : </label>',vl_aff_input('password','pwdBis','')),
                '<tr>',
                '<td>',
                    'Votre photo actuelle ',
                '</td>',
                '<td>';
                vl_afficher_image_profil_blablas($_SESSION['usID'],$T['usAvecPhoto'],'id="imgCompte"');
                echo '<br>',
                    'Taille 20ko maximum',
                    '<br>',
                    'Image JPG carrée (mini 50x50px)',
                    '<br>',
                    '<input type="file" name="btnParcourir" >',
                '</td>',
            '</tr>',
            '<tr>',
                '<td>',
                    'Utiliser votre photo :',//mettre les deux boutons sur la même ligne
                '</td>',
                '<td class="tdRadio">',
                    '<input id="photoUse1" type="radio" class="photoRadio" name="contact" value="0" checked/>',
                    '<label for="photoUse1">non</label>',
                    '<input id="photoUse2" type="radio" class="photoRadio" name="contact" value="1" />',
                    '<label for="photoUse2">oui</label>',
                '</td>',
            '</tr>',
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnValider3" value="Valider">',
                    '</td>',
                '</tr>',
            '</table>',
        '</form>',
    '</div>';
}

//______________________________________________________________
/**
 * Vérifie le formulaire d'inscription 1
 * 
* Fonction qui fait la vérification des données reçues, et 
* l'enregistrement du nouvel utilisateur dans la base de données 
* si aucune erreur n'est détectée
*
* @global array $_POST pour accéder au zone de saisies
* @return array le tableau contenant les erreurs
*/
function vll_traitement_formulaire1(mysqli $bd,array &$Errs){

    $obligatoiore = array('nom','dateN','ville','bio','btnValider1');
    if(!vl_parametres_controle('post',$obligatoiore)){
        echo 'ERROR 404' ;
        vl_aff_fin();
        exit(1) ;
    }

    
    
    $nom = em_bd_proteger_entree($bd,$_POST['nom']);
    vl_verif_nomPrenom($nom,$Errs);

    $date = em_bd_proteger_entree($bd,$_POST['dateN']);
    $dateSansTirer = explode("-",$date);
    vl_verif_dateNaissance($date,$Errs);

    $ville=strip_tags(em_bd_proteger_entree($bd,$_POST['ville']));
    if(strcmp($ville,'') != 0){
        vl_verif_parametre_form_basic('ville',$_POST['ville'],LONGUEUR_MAX_VILLE,$Errs);
    }
    $bio=strip_tags(em_bd_proteger_entree($bd,$_POST['bio']));

    if(strcmp($bio,'') != 0){
        vl_verif_parametre_form_basic('Mini-Bio',$_POST['bio'],LONGUEUR_MAX_BIO,$Errs);
    }
    
    if(count($Errs) == 0){

        $dateNaissance = $dateSansTirer[0]*10000+$dateSansTirer[1]*100+$dateSansTirer[2];
        $S = "UPDATE `users` SET `usNom`='{$nom}',`usVille`='{$ville}',`usBio`='{$bio}',`usDateNaissance`='{$dateNaissance}' 
        WHERE usID={$_SESSION['usID']};";

        vl_bd_send_request($bd, $S);
    }
       

}


//______________________________________________________________
/**
 * Vérifie le formulaire 2 
 * 
* Fonction qui fait la vérification des données reçues, et 
* l'envoie pour la modification dans la base de données 
* si aucune erreur n'est détectée
*
* @global array $_POST pour accéder au zone de saisies
* @return array le tableau contenant les erreurs
*/
function vll_traitement_formulaire2(mysqli $bd, array &$Errs){

    $obligatoiore = array('email','web','btnValider2');
    if(!vl_parametres_controle('post',$obligatoiore)){
        echo 'ERROR 404' ;
        vl_aff_fin();
        exit(1);
    }

    $email = em_bd_proteger_entree($bd,$_POST['email']);
    vl_verif_email($email,$Errs);

    $url = em_bd_proteger_entree($bd,$_POST['web']);
    if(strcmp($url,'') != 0){
        vl_verif_web($url,$Errs);
    }

    
    

    if(count($Errs) == 0){
        $S = "UPDATE `users` SET `usWeb`='{$url}',`usMail`='{$email}' 
        WHERE usID ={$_SESSION['usID']};";

        vl_bd_send_request($bd, $S);
    }

}

//______________________________________________________________
/**
 * Vérifie le formulaire 3 
 * 
* Fonction qui fait la vérification des données reçues, et 
* l'envoie pour la modification dans la base de données 
* si aucune erreur n'est détectée
*
* @global array $_POST pour accéder au zone de saisies
* @return array le tableau contenant les erreurs
*/
function vll_traitement_formulaire3(mysqli $bd, array &$Errs){

    $obligatoiore = array('pwd','pwdBis','contact','btnValider3');
    if(!vl_parametres_controle('post',$obligatoiore)){
        echo var_dump($_POST);
        echo 'ERROR 404' ;
        vl_aff_fin();
        exit(1);
    }
    $S;

    if($_POST['pwd'] != '' || $_POST['pwdBis'] != ''){
        vl_verif_mdp($_POST['pwd'],$_POST['pwdBis'],$Errs);
        if(count($Errs) == 0){
            $mdpPASSWORDhash = password_hash(em_bd_proteger_entree($bd,$_POST['pwd']), PASSWORD_DEFAULT);
            $S = "UPDATE `users` SET `usAvecPhoto`='{$_POST['contact']}',`usPasse`='{$mdpPASSWORDhash}' WHERE usID ={$_SESSION['usID']};";
        }
    }else{
        $S = "UPDATE `users` SET `usAvecPhoto`='{$_POST['contact']}' WHERE usID ={$_SESSION['usID']};";
    }

    if(count($Errs) == 0){
        


        if($_POST['contact'] == 1 && strcmp($_FILES['btnParcourir']['name'],'') != 0 ){
            $oks = array('image/jpeg','image/jpg');

            $type = mime_content_type($_FILES['btnParcourir']['tmp_name']);
echo '<pre>';
echo($type);
echo("<br>");
            print_r($_FILES['btnParcourir']);
            echo  '</pre>';
            if (! in_array($type, $oks)) {
                $Errs[]='L\'extension de l\'image n\'est pas la bonne';
                return;
            }
            $Dest = "../upload/{$_SESSION['usID']}.jpg";
            $taille = getimagesize($_FILES['btnParcourir']['tmp_name']);

            if($taille[0] < 50 && $taille[1] < 50){
                $Errs[]='Erreur lors de l\'upload l\'image doit faire au mininmum 50x50px';
                return;
            }

            if($taille[0] > 50 || $taille[1] > 50){
                $image2 = imagecreatetruecolor(50,50);
                imagefilter($image2, IMG_FILTER_NEGATE);
                $source2 = imagecreatefromjpeg($_FILES['btnParcourir']['tmp_name']);
                imagecopyresized($image2,$source2,0,0,0,0,50,50,$taille[0],$taille[1]);

                imagejpeg($image2,$_FILES['btnParcourir']['tmp_name']);
            }
            if($_FILES['btnParcourir']['size']>20000){
                $Errs[]='Erreur lors de l\'upload le fichier est trop volumineux la taille Maximal est de 20ko';
                return;
            }
            if ($_FILES['btnParcourir']['error'] === 0 && @is_uploaded_file($_FILES['btnParcourir']['tmp_name'])&& @move_uploaded_file($_FILES['btnParcourir']['tmp_name'], $Dest)) {
            } else {
                $Errs[]='Erreur lors de l\'upload ';
                return;
            }
        }

        vl_bd_send_request($bd, $S);
        
    }

}



?>
