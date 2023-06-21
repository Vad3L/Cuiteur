<?php
define('BD_SERVER', 'localhost') ;// nom d'hôte ou adresse IP du serveur de base de données
define('BD_NAME' , 'cuiteur'); // fac : vandrepol_cuiteur  maison :cuiteur nom de la base sur le serveur de base de données
define('BD_USER' , 'vandrepol_u') ;// nom de l'utilisateur de la base
define('BD_PASS' , 'vandrepol_p') ;// mot de passe de l'utilisateur de la base


//DEFINE POUR SAVOIR LES LIMITES DE LA BASE DE DONNE ET CELLE QUON SE FIXE POUR LENREGISTREMENT DUNE PERSONNE
define('NB_ABONNEMENTS_SUGGESTIONS', 5);
define('NB_ABONNEMENTS_SUGGESTIONS_MAX', 10);

define('LONGUEUR_MAX_NOM', 60);

define('LONGUEUR_MIN_PSEUDO', 4);
define('LONGUEUR_MAX_PSEUDO', 30);

define('LONGUEUR_MIN_MDP', 4);
define('LONGUEUR_MAX_MDP', 20);

define('LONGUEUR_MAX_EMAIL', 120);

define('LONGUEUR_MAX_VILLE', 50);

define('LONGUEUR_MAX_BIO', 255);

define('NOMBRE_BLABLAS',4);

define('CLE_CRYPTAGE', 'ocKSOzNlBxCu1hsziDoUCQ==');
define('CLE_HACHAGE', 'IFQ64afec21PL5xvnv2WyzfUuiDl7n5vwl5GAvuDENA=');









/*---------------------------------------------------------------------------------------------------------------------------------------------

                                                                Affichage /generation de code html

*---------------------------------------------------------------------------------------------------------------------------------------------*/




//____________________________________________________________________________
/**
 * génère (c'est à dire envoie au navigateur) le début du code HTML.
 * 
 * fais en html de DOCTYPLE le head et ouvre le body
 * @param   string  $titre  le titre de la page html     
 * @param   string  $chemin chemin vers un fichier de feuille de styles
 */
function vl_aff_debut(string $titre,string $chemin):void{

    echo 	'<!DOCTYPE html>',
		    '<html lang="fr">',
		    '<head>',
			    '<meta charset="utf-8">',
			    '<title>', $titre, '</title>';

    if($chemin == ''){
        echo '<link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">',
        '</head>',
        '<body>';
        return ;
    }
    echo
                '<link rel="stylesheet" href="',$chemin,'" type="text/css">',
                '<link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon">',
		    '</head>',
		    '<body>';
                
}

//____________________________________________________________________________
/**
 * génère (c'est à dire envoie au navigateur) l'entete du code HTML.
 * 
 * génère le code HTML du tag d'ouverture de l'élément main,
 * et de l'élément header. Cette fonction est paramétrable
 * car le formulaire n'est pas toujours affiché.
 * 
 * @param   bool  $varForm le choix de l'affichage du form     
 * @param   string $nom    le nom de la personne 
 * @param   string $initTextArea='' pour initialiser le textarea de base s'il est la
 */
function vl_aff_entete(bool $varForm, string $nom,string $initTextArea=''):void{
    echo '<main>';
    if(isset($_SESSION['usID']) || isset($_SESSION['usPseudo'])){
        $cryptageCuiteur = crypteSigneURL(implode('|', array("nb=1")));
        echo
        '<header id="sessionON">',
            '<a href="./deconnexion.php" title="Se déconnecter de cuiteur"></a>',
            '<a href="./cuiteur.php?xyz='.$cryptageCuiteur.'" title="Ma page d\'accueil"></a>',
            '<a href="./recherche.php" title="Rechercher des personnes à suivre"></a>',
            '<a href="./compte.php" title="Modifier mes informations personnelles"></a>';
    }else{
        echo '<header id="sessionOFF">';
    }
    if($varForm){
        $cryptageCuiteur = crypteSigneURL(implode('|', array("nb=1")));
        echo "<form method='post' action='./cuiteur.php?xyz={$cryptageCuiteur}'>",
                "<textarea name='txtMessage'>{$initTextArea}</textarea>",
                "<input type='submit' name='btnPublier' value='' title='Publier mon message'>",
            '</form>',
            '</header>';
    }else{
        echo '<h1>',$nom,'</h1>',
        '</header>';
    }
}            

//____________________________________________________________________________
/**
 * génère (c'est à dire envoie au navigateur) le aside du code HTML.
 * 
 * génère le code HTML de l'élément aside en fonction 
 * 
 * @param mysqli $bd = null pour avoir potentiellement la base de donne
 * @param bool $connecte = true pour savoir si connecter
 */
function vl_aff_infos(mysqli $bd = null,bool $connecte = true):void{
    echo '<aside>';
    if($connecte){
        if(isset($_SESSION['usID'])){

            $S = "SELECT COUNT(*) AS NB, 1 AS TYPE
            FROM blablas
            WHERE blIDAuteur={$_SESSION['usID']}
        UNION
            SELECT COUNT(*) AS NB, 2 AS TYPE
            FROM estabonne
            WHERE eaIDUser={$_SESSION['usID']}
        UNION
            SELECT COUNT(*) AS NB, 3 AS TYPE
            FROM estabonne
            WHERE eaIDAbonne={$_SESSION['usID']};";

            $R = vl_bd_send_request($bd, $S);

            $provisoire = array();
            while($T= mysqli_fetch_assoc($R)){
                $provisoire[]=$T['NB'];
            }
            
            $S = 'SELECT usNom,usAvecPhoto
            FROM users 
            WHERE usID = '.$_SESSION['usID'].';';
            $R = vl_bd_send_request($bd, $S);
            $T= mysqli_fetch_assoc($R);

            $aujourdhui = getdate();
            $annee = $aujourdhui['year'].'0101';

            $SS =  "SELECT taID, COUNT(*) AS NB
                FROM tags INNER JOIN blablas on blID=taIDBlabla 
                WHERE blDate >= {$annee}
                GROUP BY taID
                ORDER BY NB DESC
                LIMIT 0,4;";

            $SSS = "SELECT usID,usPseudo,usNom,eaIDAbonne,eaIDUser,usAvecPhoto
            FROM users LEFT OUTER JOIN estabonne on usID=eaIDAbonne AND eaIDUser={$_SESSION['usID']}
            WHERE usID IN (SELECT eaIDUser
                            FROM estabonne
                            GROUP BY eaIDUser
                            ORDER BY COUNT(*) DESC)
                
            AND usID!={$_SESSION['usID']}
            AND eaIDAbonne IS NULL
            GROUP BY usID
            LIMIT 0,2;";

            $RR = vl_bd_send_request($bd, $SS);
            $RRR = vl_bd_send_request($bd, $SSS);

            echo '<h3>Utilisateur</h3>',
                '<ul>',
                    '<li>';
            $cryptageUtilisatuer = crypteSigneURL($_SESSION['usID']);
            $cryptageBlabla = crypteSigneURL(implode('|', array("nb=1","id={$_SESSION['usID']}")));
            vl_afficher_image_profil_blablas($_SESSION['usID'],$T['usAvecPhoto']);
            echo     '<a href="./utilisateur.php?xyz='.$cryptageUtilisatuer.'" title="Voir mes infos">'.$_SESSION['usPseudo'].'</a> '.$T['usNom'],
                    '</li>',
                    "<li><a href='./blablas.php?xyz={$cryptageBlabla}' title='Voir la liste de mes messages'>".$provisoire[0].' blablas</a></li>',
                    "<li><a href='./abonnements.php?xyz={$cryptageUtilisatuer}' title='Voir les personnes que je suis'>{$provisoire[1]} abonnements</a></li>",
                    "<li><a href='./abonnes.php?xyz={$cryptageUtilisatuer}' title='Voir les personnes qui me suivent'>{$provisoire[2]} abonnés</a></li>",
                '</ul>',
                '<h3>Tendances</h3>',
                '<ul>';
            while($TT= mysqli_fetch_assoc($RR)){
                $cryptageTendance = crypteSigneURL(implode('|', array("tendance={$TT['taID']}",'nb=1')));
                echo "<li>#<a href='./tendance.php?xyz={$cryptageTendance}' title='Voir les blablas contenant ce tag'>{$TT['taID']}</a></li>";
            }
            echo    '<li><a href="./tendance.php">Toutes les tendances</a><li>',
                '</ul>',
                '<h3>Suggestions</h3>',         
                '<ul>';
            while($TTT= mysqli_fetch_assoc($RRR)){
                echo '<li>';
                    vl_afficher_image_profil_blablas($TTT['usID'],$TTT['usAvecPhoto']);
                    $cryptageUtilisatuer2 = crypteSigneURL($TTT['usID']);
                echo    "<a href='./utilisateur.php?xyz={$cryptageUtilisatuer2}' title='Voir mes infos'>{$TTT['usPseudo']}</a> {$TTT['usNom']}",
                         '</li>';
            }
                echo
                    '<li><a href="./suggestions.php">Plus de suggestions</a></li>',
                '</ul>';
        }
    }
                         
    echo '</aside>';
}  


//____________________________________________________________________________
/**
 * génère (c'est à dire envoie au navigateur) le footer du code HTML.
 * 
 * génère le code HTML de l'élément footer
 * 
 */
function vl_aff_pied():void{
    echo '<footer>',
            '<a href="../index.html">A propos</a>',
            '<a href="../index.html">Publicité</a>',
            '<a href="../index.html">Patati</a>',
            '<a href="../index.html">Aide</a>',
            '<a href="../index.html">Patata</a>',
            '<a href="../index.html">Stages</a>',
            '<a href="../index.html">Emplois</a>',
            '<a href="../index.html">Confidentialité</a>',
        '</footer>',
        '</main>';
}  

//____________________________________________________________________________
/**
 * génère la fin du code HTML d'une page
 * 
 * ferme la balise html body et html
 */
function vl_aff_fin():void{
    echo 	'</body>',
            '</html>';
}




//____________________________________________________________________________
/**
* Méthode étant une action qui affiche les blablas
*
* Action parcours le tableau et affiche en html avec le css les blablas dans
* des <li>
*   
* @param mysqli_result      $R le résultat de la requête SQL
* @param mysqli $bd         pour refaire une requete SQL
* @param string $nomScript   pour avoir le nom du script
* @param int $nbBlabla      le nombre de blabla a afficher
* @param array $lien        les potentielle parametre d'entree du script en plus
*/
function vl_afficher_blablas(mysqli_result $R,mysqli $bd,string $nomScript,int $nbBlabla,array $lien): void {

    $T= mysqli_fetch_assoc($R);
    if(isset($T['blTexte'])){
        mysqli_data_seek($R,0);
        

        $nbBlablaAafficher = $nbBlabla*NOMBRE_BLABLAS;
        $nbBlablaEnCour=0;
        
        while (($T= mysqli_fetch_assoc($R)) && $nbBlablaAafficher>$nbBlablaEnCour ) {
            $T = em_html_proteger_sortie($T);
            $groupMentions = array();
            $groupTags = array();

            if(isset($T['groupMention'])){
                $groupMentions = explode(',',$T['groupMention']);
                
                foreach ($groupMentions as $key => $value) {
                    $S = "SELECT usPseudo FROM `users` WHERE usID = {$value};";
                    $RR = vl_bd_send_request($bd, $S);
                    $TT = em_html_proteger_sortie(mysqli_fetch_assoc($RR));
                    $cryptageUtilisatuer = crypteSigneURL($value);
                    $T['blTexte'] = mb_ereg_replace("@{$TT['usPseudo']}","@<strong><a href='./utilisateur.php?xyz={$cryptageUtilisatuer}'>{$TT['usPseudo']}</a></strong>",$T['blTexte']);
                }
            }

            if(isset($T['groupTag'])){
                $groupTags = explode(',',$T['groupTag']);
                foreach ($groupTags as $key => $value) {
                    $cryptageTendance = crypteSigneURL(implode('|', array("tendance={$value}", "nb=1")));
                    $T['blTexte'] = mb_ereg_replace("#{$value}","#<strong><a href='./tendance.php?xyz={$cryptageTendance}'>$value</a></strong>",$T['blTexte']);
                }
            }

            if(isset($T['blIDAuteur'])){
                echo '<li>';
                $cryptageUtilisatuer = crypteSigneURL($T['IDAuteur']);
                //vérifier que l'auteur a une image de profil personalisé
                if($T['Auteur_Originel']!=NULL){   
                    $cryptageUtilisatuerOrig = crypteSigneURL($T['IDAuteurOrig']);
                    vl_afficher_image_profil_blablas($T['IDAuteurOrig'],$T['photo'],'class="imgAuteur"');
                    echo '<a href="utilisateur.php?xyz=',$cryptageUtilisatuerOrig,'" title="Voir mes infos"><strong>',$T['Auteur_Originel'],'</strong></a> ',$T['nomAuteurOriginnel'],
                    ' recuité par ','<a href="utilisateur.php?xyz=',$cryptageUtilisatuer,'" title="Voir mes infos"><strong>',$T['Auteur'],'</strong></a> ';
                }else{
                    vl_afficher_image_profil_blablas($T['IDAuteur'],$T['photo'],'class="imgAuteur"');
                    echo '<a href="utilisateur.php?xyz=',$cryptageUtilisatuer,'" title="Voir mes infos"><strong>',$T['Auteur'],'</strong></a> ',$T['nomAuteur'];
                }
                echo  
                    '<br>',$T['blTexte'],'<br>',
                    '<p class="finMessage">',vl_date_polisseur($T['blDate']),' à ',vl_heure_polisseur($T['blHeure']);
                    if($_SESSION['usID'] != $T['IDAuteur']){
                        $cryptageCuiteurRepondre = crypteSigneURL(implode('|', array("nb=1","mention={$T['Auteur']}")));
                        $cryptageCuiteurRecuiter = crypteSigneURL(implode('|', array("nb=1","recuiter={$T['blID']}")));
                        echo "<a href='./cuiteur.php?xyz={$cryptageCuiteurRepondre}'>Répondre</a>",
                        "<a href='./cuiteur.php?xyz={$cryptageCuiteurRecuiter}'>Recuiter</a>";
                    }else{
                        $cryptageCuiteurSup = crypteSigneURL(implode('|', array("nb=1","supprimer={$T['blID']}")));
                        echo "<a href='./cuiteur.php?xyz={$cryptageCuiteurSup}'>Supprimer</a>";
                    }
                    echo '</p></li>';
            }
            $nbBlablaEnCour=$nbBlablaEnCour+1;
        } 
        $var = $nbBlabla+1;
     

        if(isset($T)){
            $lien[] = "nb={$var}";
            $cryptageTendance = crypteSigneURL(implode('|', $lien));
            echo
                '<li>',
                    "<a href='./{$nomScript}.php?xyz={$cryptageTendance}'><strong>Plus de blablas</strong></a>",
                    '<img src="../images/speaker.png" width="75" height="82" alt="Image du speaker \'Plus de blablas\'">',
                '</li>';
        }else{
            echo    '<li></li>';
        }
        
        
    }else{
        echo '<ul class="bcMessages">',
                '<li>',
                '<a href="https://www.youtube.com/watch?v=iik25wqIuFo" title="Voir mes infos"><strong>Cette utilisateur n\'a pas encore poster de blablas</strong></a> ',
                '</li>';
    }
}

//____________________________________________________________________________
/**
* Méthode étant une action qui affiche l'image de profil
*
* Action qui vérifie si l'id de l'image passer en paramére existe physiquement dans les dossiers
*   
* @param string  $idImage   id de l'image en question 
* @param int $imageBD       usAvecPhoto de la BD
* @param string $var = ''   pour de potentielle id ou class css
*
*/
function vl_afficher_image_profil_blablas(string $idImage,int $imageBD,string $var = '') {

    $anonymousString = "<img src='../images/anonyme.jpg' {$var} alt='photo de lauteur'>";
    echo ( $imageBD == 0 ? $anonymousString: ( file_exists('../upload/'.$idImage.'.jpg') ? "<img src='../upload/{$idImage}.jpg' {$var} alt='photo utilisateur'>" : $anonymousString) );

}




//____________________________________________________________________________
/**
* Méthode étant une action qui affiche les messages d'erreurs 
* en cas de soumission avec erreur(s).
*   
* @param array &$Errs tableau contenant les erreurs que l'utilisateur a fait
*
*/
function vl_aff_erreur(array &$Errs){

    $countErrs = count($Errs);
    if($countErrs){
        echo 
            '<p id="errorIns">',
                'Les erreurs suivantes ont été détectées :<br>';
        foreach ($Errs as $key => $value) {
            echo '- '.$value.'<br>';
        }
        echo '</p>';
    }

}

//____________________________________________________________________________
/**
* Méthode étant une action qui affiche les messages d'erreurs 
* en cas de soumission avec erreur(s).
*   
* @param array &$Errs tableau contenant les erreurs que l'utilisateur a fait
*
*/
function vl_aff_Aucune_erreur(string $string){

        echo 
            '<p id="NoErrorIns">',$string,'</p>';
    
}





//____________________________________________________________________________
/**
* Méthode étant une action qui affiche le formulaire et les messages d'erreurs 
* en cas de soumission avec erreur(s).
*
* En l'absence de soumission (l'utilisateur arrive sur la page pour la première 
* fois), cette fonction affiche un formulaire "vide".
* En cas de soumission avec erreurs, cette fonction afficher le formulaire avec
* les zones remplies par la saisie précédente de l'utilisateur.
*   
* @global array $_POST tableau super globa pour récupérer les info de l'utilisateur
* @param array &$Errs tableau contenant les erreurs que l'utilisateur a fait
*
*/
function vll_aff_formulaire(array &$Errs){
    echo '<div id="contenu">';
    vl_aff_erreur($Errs);

    echo '<form action="./inscription.php" method="post">',
                'Pour vous inscrire, merci de fournir les informations suivantes.',
                '<table>',
                    vl_aff_ligne_input('<label for="pseudo">Votre pseudo :</label>',vl_aff_input('text','pseudo','minlength="4" maxlength="30" placeholder="Minimum 4 caractères alphanumériques" value="'.$_POST['pseudo'].'"')),
                    vl_aff_ligne_input('<label for="passe1">Votre mot de passe :</label>',vl_aff_input('password','passe1','minlength="4" maxlength="20"')),
                    vl_aff_ligne_input('<label for="passe2">Répétez le mot de passe :</label>',vl_aff_input('password','passe2','minlength="4" maxlength="20"')),
                    vl_aff_ligne_input('<label for="nomprenom">Nom et prénom :</label>',vl_aff_input('text','nomprenom','value="'.$_POST['nomprenom'].'"')),
                    vl_aff_ligne_input('<label for="email">Votre adresse email :</label>',vl_aff_input('Email','email','value="'.$_POST['email'].'"')),
                    vl_aff_ligne_input('<label for="naissance">Votre date de naissance :</label>',vl_aff_input('Date','naissance','value="'.$_POST['naissance'].'"')),
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnSInscrire" value="S\'inscrire"/>',
                            '<input type="reset" value="Réinitialiser"/>',
                        '</td>',
                    '</tr>',
                '</table>', 
                '<p>',
                'Déjà inscrit(e), <a href="../index.php">connectez-vous</a>.',
                '</p>',
            '</form>',
            '</div>';
}


/**
 * affiche un li avec l'image de l'utilisateur sont nom prenom et pseudo
 * ainsi que sont nombre d'abonnemets/abonnés/mentions/blablas et si on
 * est abonnés a lui
 * 
 * @param mysqli $bd            la bd pour des requetes SQL
 * @param array $T              pour avoir les infos de l'utilisateur courant
 * @param bool $boutonAbonner   savoir si l'utilisateur courant est abonne a lui ou non
 * @param string $var = ''      pour de potentielle class ou id en css
 */
function vl_li_info_utilisateur(mysqli $bd,array $T,bool $boutonAbonner,string $var = ''){
    $T = em_html_proteger_sortie($T);
    $id = $T['usID'];

    $SS = 'SELECT COUNT(*) AS NB, 1 AS TYPE
    FROM blablas
    WHERE blIDAuteur='.$id.'
    UNION
    SELECT COUNT(*) AS NB, 2 AS TYPE
    FROM estabonne
    WHERE eaIDUser='.$id.'
    UNION
    SELECT COUNT(*) AS NB, 3 AS TYPE
    FROM estabonne
    WHERE eaIDAbonne='.$id.'
    UNION
    SELECT COUNT(*) AS NB, 4 AS TYPE
    FROM mentions
    WHERE meIDUser='.$id.';';
    $RR = vl_bd_send_request($bd, $SS);
    
    $tab = array();
    while($TT= mysqli_fetch_assoc($RR)){
        $tab[] = em_html_proteger_sortie($TT['NB']);
    }
    $cryptageUtilisatuer = crypteSigneURL($id);
    $cryptageBlabla = crypteSigneURL(implode('|', array("nb=1","id={$id}")));
    echo "<li {$var}>",
        vl_afficher_image_profil_blablas($id,$T['usAvecPhoto'],'class="imgAuteur"');
        echo '<a href="utilisateur.php?xyz=',$cryptageUtilisatuer,'" title="Voir mon profil"><strong>',$T['usPseudo'],'</strong></a> ','<strong>',$T['usNom'],'</strong>',
        '<br><a href="./blablas.php?xyz=',$cryptageBlabla,'" title="Voir mes blablas"><strong>'.$tab[0].' blablas</strong></a>',
         ' - <a href="./mentions.php?xyz=',$cryptageBlabla,'" title="Voir mes mentions"><strong>'.$tab[3].' mentions</strong></a>',
         ' - <a href="./abonnes.php?xyz=',$cryptageUtilisatuer,'" title="Voir mes abonnes"><strong>'.$tab[2].' abonnés</strong></a>',
         ' - <a href="./abonnements.php?xyz=',$cryptageUtilisatuer,'" title="Voir mes abonnements"><strong>'.$tab[1].' abonnements</strong></a>';
        

        if($boutonAbonner && $id != $_SESSION['usID']){

            echo '<div style="text-align: end;">',
            vl_aff_input('checkbox',$id, (isset($T['eaIDUser']) ? 'value="0"' : 'value="1"' )),
            '<label for="'.$id.'"><strong>',(isset($T['eaIDUser']) ? 'Se désabonner': 'S\'abonner'),'</strong></label>',
            '</div>';

        }
        echo '</li>';
}



/*---------------------------------------------------------------------------------------------------------------------------------------------

                                                                fonction DATE ET HEURE

*---------------------------------------------------------------------------------------------------------------------------------------------*/
//____________________________________________________________________________
/**
 * transforme une date brute en date lisible
 * 
 * prend une date de type AAAAMMJJ et la transforme en Jour Mois Anné
 * @param   string $dateBrute
 * @return  string  retourne la date "poli"
 */
function vl_date_polisseur(string $dateBrute):string {
    
    $date = new DateTime($dateBrute);

    $moisFr = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin',
   'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
        
    
    return $date->format("j").' '.$moisFr[$date->format("n")-1].' '.$date->format("Y");
}

//____________________________________________________________________________
/**
 * transforme une date brute en date lisible
 * 
 * prend une date de type AAAAMMJJ et la transforme en Jour Mois Anné
 * @param   string $dateBrute
 * @return  string  retourne la date "poli"
 */
function vl_date_polisseur2(string $dateBrute):string {
    
    
    

    return $dateBrute[0].$dateBrute[1].$dateBrute[2].$dateBrute[3].'-'.$dateBrute[4].$dateBrute[5].'-'.$dateBrute[6].$dateBrute[7];
}

//____________________________________________________________________________
/**
 * transforme une heure brute en heure lisible
 * 
 * prend une heure de type HH:MM:SS et la transforme en ex : 17h31mm
 * @param   string $heureBrute
 * @return  string  retourne l'heure "poli"
 */
function vl_heure_polisseur(string $heureBrute):string{
    
    $heure = new DateTime($heureBrute);

    return $heure->format("G").'h'.$heure->format("i").'mn';
}


/*---------------------------------------------------------------------------------------------------------------------------------------------

                                                                fonction BASE DE DONNE, abonnement,desabonnements ....

*---------------------------------------------------------------------------------------------------------------------------------------------*/



//_______________________________________________________________
/**
 * Action permettant de s'abonner a une personne passer en 
 * paramétre
 * 
 * @param mysqli $bd            la base de donner
 * @param int $idPerso          l'id de l'utilisateur
 * @param int $idAbonnerAvirer  l'id de l'abonnement
 */
function vl_sabonner(mysqli $bd,int $idPerso,int $idAbonnerAvirer){

    $var = vl_date_aujourdhui_format_bd();

    $S = "INSERT INTO `estabonne`(`eaIDUser`, `eaIDAbonne`, `eaDate`) VALUES ('$idPerso','$idAbonnerAvirer','$var'); ";

    $R = vl_bd_send_request($bd, $S);
}



//_______________________________________________________________
/**
 * Action permettant de se désabonner a une personne passer en 
 * paramétre
 * 
 * @param mysqli $bd            la base de donner
 * @param int $idPerso          l'id de l'utilisateur
 * @param int $idAbonnerAvirer  l'id de l'abonnement
 */
function vl_desabonner(mysqli $bd,int $idPerso,int $idAbonnerAvirer){
        
    $S = 'DELETE FROM `estabonne` 
    WHERE eaIDUser='.$idPerso.' AND eaIDAbonne='.$idAbonnerAvirer.'; ';

    $R = vl_bd_send_request($bd, $S);
}


/**
 * Méthode qui va supprimer un blabla , ses mentions et tags
 * 
 * @param mysqli $bd    pour avoir la bd
 * @param int    $blID  pour avoir l'id du blablas à supprimer
 */
function vl_delet_blablas(mysqli $bd,int $blID){

    $S = "DELETE FROM mentions WHERE meIDBlabla = {$blID};";
    $SS = "DELETE FROM tags WHERE taIDBlabla = {$blID};";
    $SSS = "DELETE FROM blablas WHERE blID = {$blID};";

    vl_bd_send_request($bd,$S);
    vl_bd_send_request($bd,$SS);
    vl_bd_send_request($bd,$SSS);
}




/*---------------------------------------------------------------------------------------------------------------------------------------------

                                                                verifications validiter formulaire/connections

*---------------------------------------------------------------------------------------------------------------------------------------------*/



//_______________________________________________________________
/**
 * Termine une session et redirige vers inscription
 *
 * détruit la session existante en appelant la fonction session_destroy()
 * efface toutes les variables de session en appelant la fonction session_unset()
 * supprimer le cookie de session 
 * 
 * @param string $tube permet de rediriger
 */
function vl_session_exit(string $tube) {
    session_destroy();
    session_unset();
    $parametreDesCookies = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,$parametreDesCookies['path'], $parametreDesCookies['domain'],$parametreDesCookies['secure'],$parametreDesCookies['httponly']);
    
    header('location: '.$tube.'');
    exit();
}


//_______________________________________________________________
/**
 * permet de savoir si l'utilisateur courant est authentifié
 *
 * teste l'existence de la variable de session mémorisant l'id de 3
 * l'utilisateur
 * 
 */
function vl_verif_authentifie(){
    if(!isset($_SESSION['usID']) || !isset($_SESSION['usPseudo'])) {
        vl_session_exit('../index.php');
    }
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @global array    $_SESSION 
* @return bool     true si l'utilisateur est authentifié, false
*/
function vl_est_authentifie(): bool {
    return  isset($_SESSION['usID']);
}
//_______________________________________________________________
/**
 * permet de vérifier un parametre de formulaire basic 
 *
 * @param string $nomParam  le nom du parametre
 * @param string $string    ce que l'utilisateur a passer dans ce parametre
 * @param int $longueurChar longueur max de ce champs
 * @param array &$Errs      adreses du tableau d'erreur
 * 
 */
function vl_verif_parametre_form_basic(string $nomParam,string $string,int $longueurChar ,array &$Errs){

    $stringVerif = strip_tags($string);
    if ($string != $stringVerif) {
        vl_session_exit('../index.php');
    }
    elseif(! mb_ereg_match('^[[:alnum:] ]{1,'.$longueurChar.'}',$stringVerif)){
        $Errs[] = 'Le champ nom prénom dois n\'est pas valide.';
    } 
    elseif(strlen($stringVerif) > $longueurChar){
        $Errs[] = 'Le champ nom prénom dois étre inférieur à '.$longueurChar.' caractères.';
    }

}

//_______________________________________________________________
/**
 * permet de vérifier le nom et le prenom passer dans un formulaire 
 *
 * @param string $nomPrenom  la chose passer en parametre
 * @param array &$Errs      adreses du tableau d'erreur
 * 
 */
function vl_verif_nomPrenom(string $nomPrenom,array &$Errs){
    
    $nomPrenomNoTags = strip_tags($nomPrenom);
    if ($nomPrenom != $nomPrenomNoTags) {
        vl_session_exit('../index.php');
    }
    elseif($nomPrenom == ''){
        $Errs[] = 'La zone nomprenom ne peut pas étre vide.';
    }
    elseif(! mb_ereg_match('^[[:alnum:] ]{1,}[[:alnum:]]{1,}$',$nomPrenom)){
        $Errs[] = 'Le champ nom prénom dois n\'est pas valide.';
    } 
    elseif(strlen($nomPrenom) > LONGUEUR_MAX_NOM){
        $Errs[] = 'Le champ nom prénom dois étre inférieur à '.LONGUEUR_MAX_NOM.' caractères.';
    }

}

//_______________________________________________________________
/**
 * permet de vérifier l'adresse email passer dans un formulaire 
 *
 * @param string $email     l'adresse email passer en parametre
 * @param array &$Errs      adreses du tableau d'erreur
 * 
 */
function vl_verif_email(string $email,array &$Errs){

    $emailNoTags = strip_tags($email); 
    if($email != $emailNoTags){
        vl_session_exit('../index.php');
    }
    elseif($email == ''){
        $Errs[] = 'L\'adresse email est obligatoire.' ;
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $Errs[] = 'L\'adresse mail n\'est pas valide.';
    }

}


//_______________________________________________________________
/**
 * permet de vérifier l'url passer dans un formulaire 
 *
 * @param string $url       l'url passer en parametre
 * @param array &$Errs      adreses du tableau d'erreur
 * 
 */
function vl_verif_web(string $url,array &$Errs){

    $urlNoTags = strip_tags($url); 
    if($url != $urlNoTags){
        vl_session_exit('../index.php');
    }
    elseif($url == ''){
        $Errs[] = 'Le site internet est obligatoire.' ;
    }
    elseif (!filter_var($url, FILTER_VALIDATE_URL)){
        $Errs[] = 'Le site internet n\'est pas valide.';
    }

}


//_______________________________________________________________
/**
 * permet de vérifier l'adresse email passer dans un formulaire 
 *
 * @param string $date     la date passer en parametre
 * @param array &$Errs      adreses du tableau d'erreur
 * 
 */
function vl_verif_dateNaissance(string $date, array &$Errs){
    $aujourdhui = getdate();
    $dateNoTags = strip_tags($date);
    $dateSansTirer = explode("-",$date);
    $dateValide = checkdate($dateSansTirer[1],$dateSansTirer[2], $dateSansTirer[0]);
    if($date != $dateNoTags){
        vl_session_exit('../index.php');
    }
    elseif($date == ''){
        $Errs[] = 'La Date ne peut pas étre vide.';
    }
    elseif (!$dateValide){
        $Errs[] = "La date de naissance n'est pas valide.";
    }
    elseif ($aujourdhui['year'] - $dateSansTirer[0] < 18){
        $Errs[] = "Vous devez avoir au moins 18 ans pour vous inscrire.";
    }
    elseif ($aujourdhui['year'] - $dateSansTirer[0] == 18 && $aujourdhui['mon'] - $dateSansTirer[1] < 0){
        $Errs[] = "Vous devez avoir au moins 18 ans pour vous inscrire.";
    }
    elseif ($aujourdhui['year'] - $dateSansTirer[0] == 18 && $aujourdhui['mon'] - $dateSansTirer[1] == 0 && $aujourdhui['mday'] - $dateSansTirer[2] < 0){
        $Errs[] = "Vous devez avoir au moins 18 ans pour vous inscrire.";
    }
    elseif ($aujourdhui['year'] - $dateSansTirer[0] > 120){
        $Errs[] = "Vous devez avoir moins de 120 ans pour vous inscrire.";
    }
    elseif ($aujourdhui['year'] - $dateSansTirer[0] == 120 && $aujourdhui['mon'] - $dateSansTirer[1] < 0){
        $Errs[] = "Vous devez avoir moins de 120 ans pour vous inscrire.";
    }
    elseif ($aujourdhui['year'] - $dateSansTirer[0] == 120 && $aujourdhui['mon'] - $dateSansTirer[1] == 0 && $aujourdhui['mday'] - $dateSansTirer[2] < 0){
        $Errs[] = "Vous devez avoir moins de 120 ans pour vous inscrire.";
    }
}


//_______________________________________________________________
/**
 * permet de vérifier les mots de passe passer en parametre
 * leur valider et surtout voir s'il sont égaux
 *
 * @param string $mdp       le mots de passe 1 passer en parametre
 * @param string $mdp2      le mots de passe 2 passer en parametre
 * @param array &$Errs      adreses du tableau d'erreur
 * 
 */
function vl_verif_mdp(string $mdp,string $mdp2, array &$Errs){

    $mdpNoTags = strip_tags($mdp);
    if ($mdp != $mdpNoTags) {
        vl_session_exit('../index.php');
    }
    elseif($mdp == ''){
        $Errs[] = 'La zone du mot de passe est obligatoire.';
    }
    elseif(! mb_ereg_match('^[[:alnum:]]{'.LONGUEUR_MIN_MDP.','.LONGUEUR_MAX_MDP.'}$',$mdp)){
        $Errs[] = 'Le mot de passe doit être constitué de '.LONGUEUR_MIN_MDP.' à '.LONGUEUR_MAX_MDP.' caractères.';
    }else{
        $mdp2s = strip_tags(trim($mdp2));
        if ($mdp2s != $mdp2) {
            vl_session_exit('../index.php');
        }
        if(strcmp($mdp,$mdp2s) != 0){
            $Errs[] = 'Les mots de passe doivent être identiques.';
        }
    }


}

/**
 * Renvoiel a date d'aujourd'hui sous format YYYYMMDD
 * 
 * @return int format du jour
 */
function vl_date_aujourdhui_format_bd():int{

    $aujourdhui = getdate();
    $jour = $aujourdhui['mday'];
    ($jour < 10 ? $jour = '0'.$jour : $jour);

    $mois = $aujourdhui['mon'];
    ($mois < 10 ? $mois = '0'.$mois : $mois);
    $var = $aujourdhui['year'].$mois.$jour;
    return $var;
}




/**
 * Méthode local pour traiter une liste d'abonnements
 * 
 * @param mysqli $bd        pour avoir la base de donnée
 */
function vl_traitement_formulaire_liste(mysqli $bd){
    
    $i=0;
    $max = count($_POST)-1;
    foreach ($_POST as $key => $value) {
        if($i < $max){
            ( $value == 0 ? vl_desabonner($bd,$_SESSION['usID'],$key) : vl_sabonner($bd,$_SESSION['usID'],(int)$key));
        }
        $i++;
    }


}




?>
