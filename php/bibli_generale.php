<?php
define('IS_DEV' , true); //true en phase de développement, false en phase de production 

//____________________________________________________________________________
/**
 * Arrêt du script si erreur de base de données 
 *
 * Affichage d'un message d'erreur, puis arrêt du script
 * Fonction appelée quand une erreur 'base de données' se produit :
 *      - lors de la phase de connexion au serveur MySQL
 *      - ou lorsque l'envoi d'une requête échoue
 *
 * @param array    $err    Informations utiles pour le débogage
 */
function vl_bd_erreur_exit(array $err):void {
    ob_end_clean();  // Suppression de tout ce qui a pu être déja généré

    echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">',
            '<title>Erreur',  
            IS_DEV ? ' base de données': '', '</title>',
            '</head><body>';
    if (IS_DEV){
        // Affichage de toutes les infos contenues dans $err
        echo    '<h4>', $err['titre'], '</h4>',
                '<pre>', 
                    '<strong>Erreur mysqli</strong> : ',  $err['code'], "\n",
                    utf8_encode($err['message']), "\n";
                    //$err['message'] est une chaîne encodée en ISO-8859-1
        if (isset($err['autres'])){
            echo "\n";
            foreach($err['autres'] as $cle => $valeur){
                echo    '<strong>', $cle, '</strong> :', "\n", $valeur, "\n";
            }
        }
        echo    "\n",'<strong>Pile des appels de fonctions :</strong>', "\n", $err['appels'], 
                '</pre>';
    }
    else {
        echo 'Une erreur s\'est produite';
    }
    
    echo    '</body></html>';
    
    if (! IS_DEV){
        // Mémorisation des erreurs dans un fichier de log
        $fichier = fopen('error.log', 'a');
        if($fichier){
            fwrite($fichier, '['.date('d/m/Y').' '.date('H:i:s')."]\n");
            fwrite($fichier, $err['titre']."\n");
            fwrite($fichier, "Erreur mysqli : {$err['code']}\n");
            fwrite($fichier, utf8_encode($err['message'])."\n");
            if (isset($err['autres'])){
                foreach($err['autres'] as $cle => $valeur){
                    fwrite($fichier,"{$cle} :\n{$valeur}\n");
                }
            }
            fwrite($fichier,"Pile des appels de fonctions :\n");
            fwrite($fichier, "{$err['appels']}\n\n");
            fclose($fichier);
        }
    }
    exit(1);        // ==> ARRET DU SCRIPT
}


//____________________________________________________________________________
/** 
 *  Ouverture de la connexion à la base de données en gérant les erreurs.
 *
 *  En cas d'erreur de connexion, une page "propre" avec un message d'erreur
 *  adéquat est affiché ET le script est arrêté.
 *
 *  @return mysqli  objet connecteur à la base de données
 */
function vl_bd_connect(): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try{
        $conn = mysqli_connect(BD_SERVER, BD_USER, BD_PASS, BD_NAME);
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de connexion';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString(); //Pile d'appels
        $err['autres'] = array('Paramètres' =>   'BD_SERVER : '. BD_SERVER
                                                    ."\n".'BD_USER : '. BD_USER
                                                    ."\n".'BD_PASS : '. BD_PASS
                                                    ."\n".'BD_NAME : '. BD_NAME);
        vl_bd_erreur_exit($err); // ==> ARRET DU SCRIPT
    }
    try{
        //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
        //de données depuis et vers le serveur de base de données.
        mysqli_set_charset($conn, 'utf8');
        return $conn;     // ===> Sortie connexion OK
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur lors de la définition du charset';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString();
        vl_bd_erreur_exit($err); // ==> ARRET DU SCRIPT
    }
}

//____________________________________________________________________________
/**
 * Envoie une requête SQL au serveur de BdD en gérant les erreurs.
 *
 * En cas d'erreur, une page propre avec un message d'erreur est affichée et le
 * script est arrêté. Si l'envoi de la requête réussit, cette fonction renvoie :
 *      - un objet de type mysqli_result dans le cas d'une requête SELECT
 *      - true dans le cas d'une requête INSERT, DELETE ou UPDATE
 *
 * @param   mysqli              $bd     Objet connecteur sur la base de données
 * @param   string              $sql    Requête SQL
 * @return  mysqli_result|bool  Résultat de la requête
 */
function vl_bd_send_request(mysqli $bd, string $sql): mysqli_result|bool {
    try{
        return mysqli_query($bd, $sql);
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de requête';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString();
        $err['autres'] = array('Requête' => $sql);
        vl_bd_erreur_exit($err);    // ==> ARRET DU SCRIPT
    }
}

//____________________________________________________________________________
/**
* Vérification d'un paramètre GET
*
* Vérification que des paramètres passés dans un GET (url)
* sont numériques et d'une valeur minimum. 
* En cas d'erreur, la fonction retourne FALSE
*   
* @param string  $nom Nom du paramètre à vérifier
* @param int $mini Valeur minimum du paramètre
* @global array $_GET
*
* @return int si la valeur est négative alors il y a une erreur sinon renvoie l'id valide
*/
function vl_verifGET(string $nom, int $mini = -1): int|string {

    $var=false;

    foreach ($_GET as $key => $value) {
        if(strcmp($nom,$key) == 0 ){
            $var = true;
        }
    }
    if(!$var){
        return -2;
    }
    
    if($mini != -1){
        if($_GET[$nom] < $mini){
            return -3; 
        }
    }   
    return $_GET[$nom];
    
}


//____________________________________________________________________________
/** 
* Contrôle des clés contenus dans $_POST ou $_GET

* Soit $x l’ensemble des clés du tableau superglobal à tester
* Renvoie true  $obligatoires est inclus dans $x ET 
*  $x est inclus dans {$obligatoires U $facultatives}
*/
function vl_parametres_controle(string $tab, array $obligatoires, array $facultatives = array()) : bool {

    $x = strtolower($tab) == 'post' ? $_POST : $_GET;
    $x = array_keys($x);
    if (count(array_diff($obligatoires, $x)) > 0){ 
        return false;
    }
    if (count(array_diff($x, array_merge($obligatoires,$facultatives))) > 0) {
        return false;
    }

    return true;
}


//_______________________________________________________________
/**
 * affiche une ligne d'un tableau permettant la saisie d'un champ 
 * input de type 'text', 'password', 'date' ou 'email'.
 *
 * La ligne est constituée de 2 cellules :
 * la 1ère cellule contient un label permettant un contrôle étiqueté de l'input
 * la 2ème cellule contient l'input 
 * 
 * @param string $premiereCellule explicite
 * @param string $deuxiemeCellule explicite
 * 
 * @return string le resultat
 */
function vl_aff_ligne_input(string $premiereCellule ,string $deuxiemeCellule): string{
    return "<tr><td>{$premiereCellule}</td><td>{$deuxiemeCellule}</td></tr>";
}


//_______________________________________________________________
/**
* afficher un input
*
* @param string $type le type de l'input
* @param string $nom le nom de sa zone
* @param int $autre  potentielle autre attributs
*
* @return string la ligne de l'input en HTML
*/
function vl_aff_input($type, $nom, $autre):string {
    return "<input id='$nom' type='$type' name='$nom' $autre />";
}


//_______________________________________________________________
/** 
 *  Protection des sorties (code HTML généré à destination du client).
 *
 *  Fonction à appeler pour toutes les chaines provenant de :
 *      - de saisies de l'utilisateur (formulaires)
 *      - de la bdD
 *  Permet de se protéger contre les attaques XSS (Cross site scripting)
 *  Convertit tous les caractères éligibles en entités HTML, notamment :
 *      - les caractères ayant une signification spéciales en HTML (<, >, ", ', ...)
 *      - les caractères accentués
 * 
 *  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
 *  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées. 
 *
 *  @param  array|string  $content   la chaine à protéger ou un tableau contenant des chaines à protéger 
 *  @return array|string             la chaîne protégée ou le tableau
 */
function em_html_proteger_sortie(array|string $content): array|string {
    if (is_array($content)) {
        foreach ($content as &$value) {
            if (is_array($value) || is_string($value)){
                $value = em_html_proteger_sortie($value);
            }
        }
        unset ($value); // à ne pas oublier (de façon générale)
        return $content;
    }
    if (is_string($content)){
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
    return $content;
}

/**
*  Protection des entrées (chaînes envoyées au serveur MySQL)
* 
* Avant insertion dans une requête SQL, certains caractères spéciaux doivent être échappés (", ', ...).
* Toutes les chaines de caractères provenant de saisies de l'utilisateur doivent être protégées 
* en utilisant la fonction mysqli_real_escape_string() (si elle est disponible)
* Cette dernière fonction :
* - protège les caractères spéciaux d'une chaîne (en particulier les guillemets)
* - permet de se protéger contre les attaques de type injections SQL. 
*
*  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
*  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées.  
*   
*  @param    objet          $bd         l'objet représentant la connexion au serveur MySQL
*  @param    array|string   $content    la chaine à protéger ou un tableau contenant des chaines à protéger 
*  @return   array|string               la chaîne protégée ou le tableau
*/  
function em_bd_proteger_entree(mysqli $bd, array|string $content): array|string {
    if (is_array($content)) {
        foreach ($content as &$value) {
            if (is_array($value) || is_string($value)){
                $value = em_bd_proteger_entree($bd,$value);
            }
        }
        unset ($value); // à ne pas oublier (de façon générale)
        return $content;
    }
    if (is_string($content)){
        if (function_exists('mysqli_real_escape_string')) {
            return mysqli_real_escape_string($bd, $content);
        }
        if (function_exists('mysqli_escape_string')) {
            return mysqli_escape_string($bd, $content);
        }
        return addslashes($content);
        
    }
    return $content;
}

/**
 * Affichage des données reçues.
 * 
 * Contrôle de la validité :
 * - du nombre de couple nom=valeur reçus
 * - du nom du couple attendu
 * Vérifie que les données reçues sont égales à celles
 * qui ont été émises
 * 
 * @param array &$list pour avoir le nb de parametre qu'on veux 
 */
function afficherReception(array &$list) {
	if (count($_GET) != 1){
		header ("location: https://www.youtube.com/watch?v=iik25wqIuFo");
        exit(); 
	}
	if (!isset($_GET['xyz'])){
		header ("location: https://www.youtube.com/watch?v=iik25wqIuFo");
        exit(); 
	}
	$xyz = decrypteSigneURL($_GET['xyz']);
	if ($xyz === FALSE){
		header ("location: https://www.youtube.com/watch?v=iik25wqIuFo");
        exit(); 
	}
	$xyz = explode('|', $xyz);
	// on n'est jamais trop prudent !
	if (count($xyz) > count($list)){
		header ("location: https://www.youtube.com/watch?v=iik25wqIuFo");
        exit(); 
	}
	foreach ($xyz as $key => $value) {
        $list[$key] = $value;
    }
		
}


/**
 * Crypte une valeur pour la passer dans une URL.
 *
 * @param mixed		$val	La valeur à crypter
 * @return string	La valeur cryptée encodée url
 */
function crypteSigneURL($val){
	// -- longueur du vecteur d'initialisation
	$ivlen = openssl_cipher_iv_length($cipher='AES-128-CBC');
	// -- génération du vecteur d'initialisation
	$iv = openssl_random_pseudo_bytes($ivlen);
	// -- cryptage de $val
	$x = openssl_encrypt($val, $cipher, base64_decode(CLE_CRYPTAGE), OPENSSL_RAW_DATA, $iv);
	// -- calcul de la signature de la valeur cryptée
	$hmac = hash_hmac('sha256', $x, base64_decode(CLE_HACHAGE), true);
	$sha2len=32;
	$x = substr($hmac, 0, $sha2len/2).$iv.$x.substr($hmac, $sha2len/2);
	$x = base64_encode($x);
	return urlencode($x);
}

/**
 * Décrypte une valeur cryptée avec la fonction crypteSigneURL()
 *
 * @param string	$x	La valeur à décrypter
 * @return mixed	La valeur décryptée ou FALSE si erreur
 */
function decrypteSigneURL($x){
	$ivlen = openssl_cipher_iv_length($cipher='AES-128-CBC');
	$x = base64_decode($x);
	$sha2len=32;
	$hmac = substr($x, 0, $sha2len/2).substr($x, -$sha2len/2);
	$iv = substr($x, $sha2len/2, $ivlen);
	$x = substr($x, $sha2len/2 + $ivlen, -$sha2len/2);
	// calcul de  la signature de la chaine cryptée reçue
	$hmacCalc = hash_hmac('sha256', $x, base64_decode(CLE_HACHAGE), true);
	if (! hash_equals($hmac, $hmacCalc)){
		return FALSE;
	}
	return openssl_decrypt($x, $cipher, base64_decode(CLE_CRYPTAGE), OPENSSL_RAW_DATA, $iv);
}
?>