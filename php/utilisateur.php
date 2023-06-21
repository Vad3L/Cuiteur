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

$id = -1;
$oui = array();
$oui[] = &$id;
if (count($_GET)> 0) {
	afficherReception($oui);
}

$estAbonner = vll_traiterAbonnement($bd,$id);
vll_traitementFormulairePost($bd,$id,$estAbonner);

if(isset($_POST['btnDesabonner']) || isset($_POST['btnAbonner'])){
    $cryptageCuiteur = crypteSigneURL(implode('|', array("nb=1")));
    header ("location: ./cuiteur.php?xyz={$cryptageCuiteur}");
    exit(); 
}


/*------------------------- Etape intermédiare --------------------------------------------
- Récupération des données de la personne demander 
------------------------------------------------------------------------------*/

$S = "SELECT usID,usNom,usPseudo,usDateNaissance,usDateInscription,usVille,usBio,usWeb,usAvecPhoto
FROM `users` 
WHERE usID={$id};";

$R = vl_bd_send_request($bd, $S);
$T= mysqli_fetch_assoc($R);

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/


vl_aff_debut('utilisateur','../styles/cuiteur.css');
vl_aff_entete(false,'Le profil de '.em_html_proteger_sortie($T['usPseudo']));
vl_aff_infos($bd);

vll_afficher_formulaire($bd,$T,$id,$estAbonner);

vl_aff_pied();
vl_aff_fin();

// libération des ressources
mysqli_close($bd);

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Peremt d'afficher le formulaire de cette page
 * 
 * @param mysqli $bd pour avoir la bd
 * @param array $T pour avoir les attributs de la persone choisis
 * @param int $id l'id passer en parametre du script 
 * @param bool $estAbonner est une variable bool savoir s'il est abonner
 */
function vll_afficher_formulaire(mysqli $bd, array $T,int $id,bool $estAbonner){
    if($T == NULL){
        vl_session_exit("./deconnexion.php");
    }
    $T = em_html_proteger_sortie($T);
    $cryptageUtilisateur = crypteSigneURL(implode('|', array($id)));
    echo '<div  id="contenu">',    
        '<form method="post" action="./utilisateur.php?xyz='.$cryptageUtilisateur.'">',   
        '<ul class="bcMessages">';
    vl_li_info_utilisateur($bd,$T,false,'id="blablaSansBG"');
    echo
        '<li></li>',
             '</ul>',
            '<table>',
                vl_aff_ligne_input('<label><strong>Date de naissance :</strong></label>',vl_date_polisseur($T['usDateNaissance'])),
                vl_aff_ligne_input('<label><strong>Date d\'inscription :</strong></label>',vl_date_polisseur($T['usDateInscription']));
    vll_verifIsNull('Ville de résidence :',$T['usVille']);
                
                
    echo        '<tr>',
                    '<td style="vertical-align: middle;">',
                    '<label><strong>Mini-Bio :</strong></label>',
                    '</td>',
                    '<td style="width: 360px;">';
    if(strcmp('',$T['usBio']) != 0){
       echo $T['usBio'];
    }else{
        echo "Non renseigné(e)";
    }          
    echo         
                    '</td>',
                '</tr>';
    vll_verifIsNull('Site Web :',$T['usWeb']);
    echo       '<tr>',
                    '<td colspan="2">';
    if($id != $_SESSION['usID']){
        if(!$estAbonner){
            echo '<input type="submit" name="btnAbonner" value="S\'abonner" style="width: 140px;">';
        }else{
            echo '<input type="submit" name="btnDesabonner" value="Désabonner" style="width: 140px;">';
        }
    }
    
            echo    '</td>',
                '</tr>',
            '</table>',
            '</form>',
        '</div>';
}

/**
 * verifie si un champ de la bd est null ou non
 * et affiche la ligne avec son label
 * 
 * @param string $label pour mettre ce qu'il y a dans le label 
 * @param string $arg pour mettre le champs a gauche
 */
function vll_verifIsNull(string $label,string $arg){
    if(strcmp('',$arg)){
        echo vl_aff_ligne_input("<label><strong>{$label}</strong></label>",$arg);
    }else{
        echo vl_aff_ligne_input("<label><strong>{$label}</strong></label>","Non renseigné(e)");
    }
}

/**
 * Verifie si l'utilisateur est abonner à l'id de la personne 
 * demander et renvoie true si vraie sinon false
 * 
 * @param mysqli $bd pour avoir la bd et faire une requete
 * @param int îd pour avoir l'id
 */
function vll_traiterAbonnement(mysqli $bd,int $id):bool{

    $S = 'SELECT eaDate FROM estabonne WHERE eaIDUser='.$_SESSION['usID'].' AND eaIDAbonne='.$id.';';

    $R = vl_bd_send_request($bd, $S);
    $T= mysqli_fetch_assoc($R);
    
    if(isset($T['eaDate'])){
        return true;
    }

    return false;
}

/**
 * Permet de faire le traitement du formulaire
 *
 * @param mysqli $bd pour avoir la bd et faire une requete
 * @param int îd pour avoir l'id
 * @param bool $estAbonner pour savoir s'il est abonner ou non a la personne demander
 */
function vll_traitementFormulairePost(mysqli $bd,int $id,bool $estAbonner){

    if(isset($_POST['btnAbonner']) && !$estAbonner){
        

        vl_sabonner($bd,$_SESSION['usID'],$id);
    }

    if(isset($_POST['btnDesabonner']) && $estAbonner){
        
        vl_desabonner($bd,$_SESSION['usID'],$id);

    }

    
}

?>
