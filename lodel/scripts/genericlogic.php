<?php
/**
 * Fichier de la classe Genericlogic
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajout� depuis la version 0.8
 * @version CVS:$Id:
 */


/**
 * Classe des logiques m�tiers g�n�rique.
 * 
 * <p>Cette classe d�finit la logique par d�faut pour les objets dynamiques de l'interface :
 * entr�es, personnes par exemple</p>
 *
 * @package lodel
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @since Classe ajout�e depuis la version 0.8
 * @see logic.php
 */

class GenericLogic extends Logic
{
	var $_publicfields_array;
	/** 
	 * Constructeur de la classe
	 *
	 * D�finit le nom de la table type pour l'objet ainsi que le nom du champ identifiant unique.
	 *
	 * @param string $classtype le type d'objet generique, parmis : entities, entries et persons.
	 */
	function GenericLogic($classtype)
	{
		switch ($classtype) {
		case 'entities' :
			$this->_typetable = "types";
			$this->_idfield = "identity";
			break;
		case 'entries' :
			$this->_typetable = "entrytypes";
			$this->_idfield = "identry";
			break;
		case 'persons' :
			$this->_typetable = "persontypes";
			$this->_idfield = "idperson";
		}
		$this->Logic($classtype);
	}

	/**
	 * Impl�mentation pour les objets g�n�rique de l'action permettant d'appeler l'affichage d'un objet.
	 *
	 * Cette fonction r�cup�re les donn�es de l'objet <em>via</em> la DAO de l'objet. Ensuite elle
	 * met ces donn�es dans le context (utilisation de la fonction priv�e _populateContext())
	 * 
	 * view an object Action
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 * @return string les diff�rentes valeurs possibles de retour d'une action (_ok, _back, _error ou xxxx).
	 */
	function viewAction(&$context, &$error)
	{
		// define some loop functions
		require_once 'lang.php';
		if(!function_exists('loop_edition_fields')) {
			function loop_edition_fields($context, $funcname)
			{
				global $db, $home;
				require_once 'validfunc.php';
				if ($context['class']) {
					validfield($context['class'], 'class', '', '','data');
					$class = $context['class'];
				}	elseif ($context['type']['class'])	{
					validfield($context['type']['class'], 'class', '', '', 'data');
					$class = $context['type']['class'];
				}	else {
					die("ERROR: internal error in loop_edition_fields");
				}
				if ($context['classtype'] == "persons")	{
					$criteria = "class='".$class."'";
					// degree is defined only when the persons is related to a document. Is it a hack ? A little no more...
					if ($context['identifier']) {
						$criteria .= " OR class='entities_".$class."'";
					}
				}	elseif ($context['classtype'] == "entries")	{
					$criteria = "class='".$class."'";
				}	else {
					$criteria = "idgroup='". $context['id']."'";
					$context['idgroup'] = $context['id'];
				}
				$result = $db->execute(lq("SELECT * FROM #_TP_tablefields WHERE ".$criteria." AND status>0 AND edition!='' AND edition!='none'  AND edition!='importable' ORDER BY rank")) or dberror();
	
				$haveresult = !empty ($result->fields);
				if ($haveresult) {
					call_user_func("code_before_$funcname", $context);
				}
				while (!$result->EOF)	{
					$localcontext = array_merge($context, $result->fields);
					$name = $result->fields['name'];
					$localcontext['value'] = $result->fields['edition'] != "display" && is_string($context['data'][$name]) ? htmlspecialchars($context['data'][$name]) : $context['data'][$name];
					call_user_func("code_do_$funcname", $localcontext);
					$result->MoveNext();
				}
				if ($haveresult) {
					call_user_func("code_after_$funcname", $context);
				}
			} //function }}}
		}
		$id = $context['id'];
		if ($id && !$error) {
			$dao = $this->_getMainTableDAO();
			$vo = $dao->getById($id);
			if (!$vo) {
				die("ERROR: can't find object $id in the table ".$this->maintable);
			}
			$this->_populateContext($vo, $context);
		}

		$daotype = &getDAO($this->_typetable);
		$votype = $daotype->getById($context['idtype']);
		if (!$votype) {
			die("ERROR: idtype must me known in GenericLogic::viewAction");
		}
		$this->_populateContext($votype, $context['type']);

		if ($id && !$error)	{
			$gdao = &getGenericDAO($votype->class, $this->_idfield);
			$gvo = $gdao->getById($id);
			if (!$gvo) {
				die("ERROR: can't find object $id in the associated table. Please report this bug");
			}
			$this->_populateContext($gvo, $context['data']);
			$ret = $this->_populateContextRelatedTables($vo, $context);
		}

		return $ret ? $ret : "_ok";
	}

	

	/**
	 * Validated the public fields and the unicity as usual and in addition the typescompatibility
	 *
	 * Validation des champs publics et de l'unicit� comme dans la fonction de logic.php. Mais v�rifie
	 * la compatibilit� des types d'objet en plus.
	 *
	 * @param array $context le tableau des donn�es pass� par r�f�rence.
	 * @param array $error le tableau des erreurs rencontr�es pass� par r�f�rence.
	 */
	function validateFields(&$context, &$error)
	{
		global $home;

		// get the fields of class
		require_once "validfunc.php";
		if ($context['class']) {
			validfield($context['class'], 'class', '', '', 'data');
			$class = $context['class'];
		}	elseif ($context['type']['class']) {
			validfield($context['type']['class'], "class", '', '', 'data');
			$class = $context['type']['class'];
		}	else {
			die("ERROR: internal error in loop_edition_fields");
		}

		$daotablefields = &getDAO("tablefields");
		$fields = $daotablefields->findMany("(class='". $class. "' OR class='entities_". $class. "') AND status>0 ", "", "name,type,class,cond,defaultvalue,allowedtags,edition,g_name");

		// file to move once the document id is know.
		$this->files_to_move = array ();
		$this->_publicfields = array ();
		require_once "fieldfunc.php";

		foreach ($fields as $field)	{
			if ($field->g_name) {
				$this->addGenericEquivalent($class, $field->g_name, $field->name); // save the generic field
			}
			$type = $field->type;
			$name = $field->name;

			// check if the field is required or not, and rise an error if any problem.
			$value = &$context['data'][$name];
			if (!is_array($value)) {
				$value = trim($value);
			}
			if ($value) {
				$value = lodel_strip_tags($value, $field->allowedtags);
			}

			// is empty ?
			$empty = $type != "boolean" && (// boolean are always true or false
							!isset ($context['data'][$name]) || // not set
							$context['data'][$name] === ""); // or empty string

			if (($context['do'] == "edit" && ($field->edition == "importable" || 
					$field->edition == "none" || $field->edition == "display"))) {

				// in edition interface and field is not editable in the interface
				if ($field->cond != "+") { // the field is not required.
					unset ($value);
					continue;
				}	else {
					$value = lodel_strip_tags($field->default, $field->allowedtags); // default value
						$empty = false;
				}
			}
			if ($context['id'] > 0 && (($field->cond == "permanent") ||
					($field->cond == "defaultnew" && $empty))) {
				// or a permanent field
				// or field is empty and the default value must not be used
				unset ($value);
				continue;
			}

			if ($type != "persons" && $type != "entries" && $type != "entities") {
				
				$this->_publicfields[$field->class][$name] = true; // this field is public
			}
			if ($field->edition == "none") {
				unset ($value);
			}
			if ($empty) {
				$value = lodel_strip_tags($field->default, $field->allowedtags); // default value
			}
			if ($field->cond == "+" && $empty) {
				$error[$name] = "+"; // required
				continue;
			}
			// clean automatically the fields when required.
			if (!is_array($value) && $GLOBALS['lodelfieldtypes'][$type]['autostriptags']) {
				$value = trim(strip_tags($value));
			}

			$valid = validfield($value, $type, $field->default, $name, 'data');
			if ($valid === true)	{
				// good, nothing to do.
				if ($type == "file" || $type == "image") {
					if (preg_match("/\/tmpdir-\d+\/[^\/]+$/", $value)) {
						// add this file to the file to move.
						$this->files_to_move[$name] = array ('filename' => $value, 'type' => $type, 'name' => $name);
					}
				}
			}	elseif (is_string($valid))	{
				$error[$name] = $valid; // error
			}	else	{
				$name = $name;
				// not validated... let's try other type
				switch ($type) {
				case 'persons' :
				case 'entries' :
					// get the type
					if ($type == "persons") {
						$dao = &getDAO("persontypes");
					}	else	{
						$dao = &getDAO("entrytypes");
					}
					$vo = $dao->find("type='".$name."'", "class,id");
					$idtype = $vo->id;

					$localcontext = &$context[$type][$idtype];
					if (!$localcontext) {
						break;
					}
					if ($type == "entries" && !is_array($localcontext))	{
						$keys = explode(",", $localcontext);
						$localcontext = array ();
						foreach ($keys as $key)	{
							$localcontext[] = array ("g_name" => $key);
						}
					}
					$logic = &getLogic($type); // the logic is used to validate
					if (!is_array($localcontext)) {
						die("ERROR: internal error in GenericLogic::validateFields");
					}

					foreach (array_keys($localcontext) as $k)	{
						if (!is_numeric($k) || !$localcontext[$k]) {
							continue;
						}
						$localcontext[$k]['class'] = $vo->class;
						$localcontext[$k]['idtype'] = $idtype;
						$err = array ();
						$logic->validateFields($localcontext[$k], $err);
						if ($err) {
							$error[$type][$idtype][$k] = $err;
						}
					}
					break;
				case 'entities' :
					$value = &$context['entities'][$name];
					if (!$value) {
						unset ($context['entities'][$name]);
						break;
					}
					$ids = array ();
					foreach (explode(",", $value) as $id) {
						if ($id > 0) {
							$ids[] = intval($id);
						}
					}
					$value = $ids;
					$count = $GLOBALS['db']->getOne(lq("SELECT count(*) FROM #_TP_entities WHERE status>-64 AND id ".sql_in_array($value)));
					if ($GLOBALS['db']->errorno()) {
						dberror();
					}
					if ($count != count($value)) {
						die("ERROR: some entities in $name are invalid. Please report the bug");
					}
					// don't check they exists, the interface ensure it ! (... hum)
					break;
				default :
					die("ERROR: unable to check the validity of the field ".$name." of type ".$type);
				} // switch
			} // if valid
		} // foreach files
		return empty ($error);
	}
	/**#@+
	 * @access private
	 */
	/**
	 * D�placement des fichiers associ�s � l'objet dans le bon r�pertoire
	 *
	 * @param integer $id l'identifiant num�rique de l'objet
	 * @param array $files_to_move un tableau contenant les informations de tous les fichiers (nom et type)
	 * @param object &$vo l'objet virtuel correspondant � l'objet pass� par r�f�rence
	 */
	function _moveFiles($id, $files_to_move, &$vo)
	{
		foreach ($files_to_move as $file)	{
			$src = SITEROOT.$file['filename'];
			$dest = basename($file['filename']); // basename
			if (!$dest) {
				die("ERROR: error in move_files");
			}
			// new path to the file
			$dirdest = "docannexe/". $file['type']. "/". $id;
			checkdocannexedir($dirdest);
			$dest = $dirdest. "/". $dest;
			$vo->$file['name'] = addslashes($dest);
			if ($src == SITEROOT. $dest) {
				continue;
			}
			rename($src, SITEROOT. $dest);
			chmod(SITEROOT. $dest, 0666 &octdec($GLOBALS['filemask']));
			@rmdir(dirname($src)); // do not complain, the directory may not be empty
		}
	}

	/**
	 * D�finition de l'�quivalent g�n�rique permanent.
	 * 
	 * <p> Cette fonction utilise un cache statique (tableau global). Elle d�finit l'�quivalent
	 * g�n�rique suivant la classe et le nom de l'objet.
	 * </p>
	 * <p> Info :These functions simulate a static cache by using a global array
	 * PHP5 would solve the problem</p>
	 *
	 * @param string $class le nom de la classe de l'objet.
	 * @param string $name le nom du champ.
	 * @param string $value la valeur associ�e au champ.
	 */
	function addGenericEquivalent($class, $name, $value)
	{
		global $genericlogic_g_name;
		$genericlogic_g_name[$class][$name] = $value;
	}

	/**
	 * Retourne un �quivalent g�n�rique pour une classe et un champ donn�
	 *
	 * @param string $class le nom de la classe de l'objet.
	 * @param string $name le nom du champ.
	 */
	function getGenericEquivalent($class, $name)
	{
		global $genericlogic_g_name;
		return $genericlogic_g_name[$class][$name];
	}

	/**
	 * Impl�mentation par d�faut de _populateContextRelatedTables
	 *
	 */
	function _populateContextRelatedTables($vo, $context)
	{
	}

	/**
	 * Populate the object from the context. Only the public fields are inputted.
	 * GenericLogic can deal with related table by detecting the class of $vo
	 *
	 * @param object &$vo L'objet virtuel � remplir.
	 * @param array &$context Le tableau contenant les donn�es.
	 */
	function _populateObject(&$vo, &$context)
	{
		//print_r($context);
		$class = strtolower(substr(get_class($vo), 0, -2)); // remove the VO from the class name
		$publicfields = $this->_publicfields();
		
		if ($publicfields[$class]) {
			foreach ($publicfields[$class] as $field => $fielddescr) {
				$vo->$field = isset($context[$field]) ? $context[$field] : '';
			}
		}
	}
	

	// begin{publicfields} automatic generation  //
	function _publicfields()
	{
		if (!isset ($this->_publicfields))
			trigger_error("ERROR: publicfield has not be created in ". get_class($this). "::_publicfields", E_USER_ERROR);
		return $this->_publicfields;
	}
	// end{publicfields} automatic generation  //

	// begin{uniquefields} automatic generation  //

	// end{uniquefields} automatic generation  //
	/**#@-*/
} // class 


/**
 *	Fonction de nettoyage des tags XHTML
 *
 * <p>Cette fonction nettoie une chaine de ses balises XHTML. Ce nettoyage tiens compte d'une liste
 * de balises autoris� (attribut allowedtags)</p>
 *
 * @param string $text le texte � nettoyer
 * @param array $allowedtags un tableau contenant la liste des balises autoris�es.
 * @param integer $k par d�faut � -1. ???
 * @return $text le texte nettoy�
 */
function lodel_strip_tags($text, $allowedtags, $k = -1)
{
	if (is_array($text)) { //si text est un array alors applique le nettoyage � chaque partie du tableau
		array_walk($text, "lodel_strip_tags", $allowedtags);
		return $text;
	}
	if (is_numeric($allowedtags) && !is_numeric($k)) {
		$allowedtags = $k;
	} // for call via array_walk

	global $home;
	require_once "balises.php";
	static $accepted; // cache the accepted balise;
	global $multiplelevel, $xhtmlgroups;

	// simple case.
	if (!$allowedtags) {
		return strip_tags($text);
	}

	if (!$accepted[$allowedtags])	{ // not cached ?
		$accepted[$allowedtags] = array ();

		// split the groupe of balises
		$groups = preg_split("/\s*;\s*/", $allowedtags);
		array_push($groups, ""); // balises speciales
		// feed the accepted string with accepted tags.
		foreach ($groups as $group) {
			// xhtml groups
			if ($xhtmlgroups[$group]) {
				foreach ($xhtmlgroups[$group] as $k => $v) {
					if (is_numeric($k))	{
						$accepted[$allowedtags][$v] = true; // accept the tag with any attributs
					}	else {
						// accept the tag with attributs matching unless it is already fully accepted
						if (!$accepted[$allowedtags][$k]) {
							$accepted[$allowedtags][$k][] = $v; // add a regexp
						}
					}
				}
			} // that was a xhtml group
		} // foreach group
	} // not cached.

	$acceptedtags = $accepted[$allowedtags];

	// the simpliest case.
	if (!$accepted) {
		return strip_tags($text);
	}

	$arr = preg_split("/(<\/?)(\w+:?\w*)\b([^>]*>)/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);

	$stack = array ();
	$count = count($arr);
	for ($i = 1; $i < $count; $i += 4) {
		if ($arr[$i] == "</")	{ // closing tag
			if (!array_pop($stack)) {
				$arr[$i] = $arr[$i +1] = $arr[$i +2] = "";
			}
		}	else { // opening tag
			$tag = $arr[$i +1];
			$keep = false;
			if (isset ($acceptedtags[$tag]))	{
				// simple case.
				if ($acceptedtags[$tag] === true)	{ // simple
					$keep = true;
				}	else	{ // must valid the regexp
					foreach ($acceptedtags[$tag] as $re)	{
						#echo $re," ",$arr[$i+2]," ",preg_match("/(^|\s)$re(\s|>|$)/",$arr[$i+2]),"<br/>";
						if (preg_match("/(^|\s)$re(\s|>|$)/", $arr[$i +2]))	{
							$keep = true;
							break;
						}
					}
				}
				#	echo "keep:$keep<br/>";
			}
			#echo ":",$arr[$i],$arr[$i+1],$arr[$i+2]," ",htmlentities(substr($arr[$i+2],-2)),"<br/>";
			if (substr($arr[$i +2], -2) != "/>") {// not an opening closing.
				array_push($stack, $keep); // whether to keep the closing tag or not.
			}
			if (!$keep)	{
				$arr[$i] = $arr[$i +1] = $arr[$i +2] = "";
			}
		}
	}
	// now, we know the accepted tags
	return join("", $arr);
}

/*-----------------------------------*/
/* loops                             */
?>