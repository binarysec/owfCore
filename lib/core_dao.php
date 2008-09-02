<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2008) / www.binarysec.com         *
 * Author: Olivier Pascal <op@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de "reverse engineering".                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Data Access Object pour le BinarySEC Framework
 *
 * Abstraction pour l'accès à un objet en BDD
 * @author Olivier Pascal <op@binarysec.com>
 */
abstract class core_dao {

	// Attributs privées

	private $wf;           /**< L'objet web_framework */
	private $name;         /**< Le nom du DAO */
	private $description;  /**< Description */
	private $struct;       /**< Structure */
	private $data;         /**< Données */
	private $primary_keys; /**< Clés primaires */


	// Méthodes abstraites

	/**
	 * Retourne le nom
	 *
	 * @return Le nom
	 */
	abstract public function get_name();

	/**
	 * Retourne la description
	 *
	 * @return La description
	 */
	abstract public function get_description();

	/**
	 * Retourne la structure
	 *
	 * @return La structure
	 */
	abstract protected function get_struct();


	// Construction / Destruction

	/**
	 * Constructeur
	 *
	 * @param $wf L'objet web_framework
	 */
	public function __construct($wf) {
		$this->wf = $wf;
		$this->name = $this->get_name();
		$this->description = $this->get_description();
		$this->struct = $this->get_struct();
		$this->primary_keys = array_keys($this->struct, WF_PRI);

		$this->data = array();

		/* enregistre la zone en BDD */
		$this->wf->db->register_zone(
			$this->name,
			$this->description,
			$this->struct
		);
	}


	// Getters / Setters

	/**
	 * Récupère une propriété du DAO
	 *
	 * @param $prop Le nom de la propriété
	 *
	 * @return La valeur de la propriété, ou null si elle n'existe pas
	 */
	public function __get($prop) {
		if(array_key_exists($prop, $this->data))
			return($this->data[$prop]);

		throw new wf_exception(
			$this,
			WF_EXC_PRIVATE,
			'Propertie <strong>'.$this->name.'::'.$prop.'</strong> not found.'
		);
		return(null);
	}

	/**
	 * Définit une propriété du DAO
	 *
	 * @param $prop Le nom de la propriété
	 * @param $val La valeur de la propriété
	 */
	public function __set($prop, $val) {
		if(array_key_exists($prop, $this->struct)) {
			$this->data[$prop] = $val;
			return;
		}

		throw new wf_exception(
			$this,
			WF_EXC_PRIVATE,
			'Propertie <strong>'.$this->name.'::'.$prop.'</strong> not found.'
		);
	}

	/**
	 * Enregistre l'état de l'objet en BDD
	 */
	public function save() {
		$pkeys = array();
		foreach($this->primary_keys as $pkey)
			if(!is_null($this->data[$pkey]))
				$pkeys[$pkey] = $this->data[$pkey];

		/* des clés primaires de l'objet sont définies */
		if($pkeys) {
			/* toutes les clés primaires de l'objet sont définies */
			if(count($pkeys) == count($this->primary_keys))
				return($this->update($pkeys));
		}
		/* aucune clé primaire n'est définie */
		else
			return($this->create());

		/* il manque des clés primaires */
		return(false);
	}

	/**
	 * Crée l'objet dans la BDD
	 */
	private function create() {
		$q = new core_db_insert($this->name, $this->data);
		$this->wf->db->query($q);

		/* définit les données auto-incrémentées */
		foreach($this->primary_keys as $pkey) {
			/* le nom de la séquence est indispensable
			   pour PostgreSQL et est de la forme
			   <table>_<clé>_seq */
			$this->$pkey = $this->wf->db->get_last_insert_id(
				$this->name.'_'.$pkey.'_seq'
			);
		}

		return(true);
	}

	/**
	 * Met à jour l'objet dans la BDD
	 */
	private function update($pkeys) {
		$data = array_diff_assoc($this->data, $pkeys);

		$q = new core_db_update($this->name);
		$q->where($pkeys);
		$q->insert($data);
		$this->wf->db->query($q);
		return(true);
	}

	/**
	 * Supprime l'objet de la BDD
	 *
	 * @return L'une des valeurs suivantes
	 * @retval true si l'objet a pu être supprimé
	 * @retval false si il manque une ou plusieurs clés primaires
	 */
	public function delete() {
		$pkeys = array();
		foreach($this->primary_keys as $pkey)
			if(!is_null($this->data[$pkey]))
				$pkeys[$pkey] = $this->data[$pkey];

		/* toutes les clés primaires de l'objet sont définies */
		if(count($pkeys) == count($this->primary_keys)) {
			$q = new core_db_delete($this->name, $pkeys);
			$this->wf->db->query($q);
			return(true);
		}

		/* il manque des clés primaires */
		return(false);

		
	}

	/**
	 * Récupère un objet depuis la BDD
	 *
	 * @param $fields Tableau associatif des champs conditionnels
	 *
	 * @return Un tableau d'objets
	 */
	public function get($fields=array()) {
		/* vérifie que les champs sont valides */
		foreach($fields as $key => $value)
			if(!array_key_exists($key, $this->struct)) {
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					'Propertie <strong>'.$this->name.'::'.$key.'</strong> not found.'
				);
				return(false);
			}

		$q = new core_db_select($this->name);
		$q->where($fields);
		$this->wf->db->query($q);
		$res = $q->get_result();

		/* crée un tableau de DAOs */
		$objects = array();
		foreach($res as $i => $infos) {
			$me = get_class($this);
			$object = new $me($this->wf);
			$object->set_data($infos);
			$objects[] = $object;
		}
		return($objects);
	}

	/**
	 * Charge des données dans l'objet
	 *
 	 * @param $data Les données à charger
	 *
	 * @return true
	 */
	public function set_data($data) {
		foreach($data as $key => $value) {
			if(array_key_exists($key, $this->struct))
				$this->data[$key] = $value;
			else
				throw new wf_exception(
					$this,
					WF_EXC_PRIVATE,
					'Propertie <strong>'.$this->name.'::'.$key.'</strong> not found.'
				);
		}
		return(true);
	}

	/**
	 * Retourne les données de l'objet
	 *
	 * @return Les données
	 */
	public function get_data() {
		$keys = array_keys($this->struct);
		$vals = array_fill(0, count($this->struct), null);
		$empty_struct = array_combine($keys, $vals);
		return(array_merge($empty_struct, $this->data));
	}

}
