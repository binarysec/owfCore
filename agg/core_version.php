<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Web Framework 1                                       *
 * BinarySEC (c) (2000-2012) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
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

class core_version extends wf_agg {
	
	public function loader($wf) {
		$this->wf = $wf;

		$this->_core_cacher = $wf->core_cacher();

		$this->struct = array(
			"form" => array(),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
				),
				"name" => array(
					"type" => WF_VARCHAR
				)
			),
		);
		
		$this->dao_section = new core_dao_form_db(
			$this->wf,
			"core_version_section",
			0,
			$this->struct,
			"core_version_section",
			"OWF Core version section"
		);
		
		$this->struct = array(
			"form" => array(),
			"data" => array(
				"id" => array(
					"type" => WF_PRI,
				),
				"section_id" => array(
					"type" => WF_INT
				),
				"ext_id" => array(
					"type" => WF_INT
				),
				"prev_version" => array(
					"type" => WF_INT
				),
				"next_version" => array(
					"type" => WF_INT
				),
			),
		);
		
		$this->dao_version = new core_dao_form_db(
			$this->wf,
			"core_version",
			0,
			$this->struct,
			"core_version",
			"OWF Core version"
		);

	}

	
	public function change($section, $eid=0) {
		/* get section */
		$d_section = $this->get_section($section);
		if(count($d_section) <= 0) {
			$insert = array(
				"name" => $section
			);
			$this->dao_section->add($insert);
			$d_section = $this->get_section($section);
		}
		$s = &$d_section[0];

		/* get version */
		$cvar = "core_version:ver:".$s["id"].":".$eid;
		$d_version = $this->get_version($s["id"], $eid);
		if(count($d_version) <= 0) {
			$insert = array(
				"section_id" => $s["id"],
				"ext_id" => $eid,
				"prev_version" => rand(),
				"next_version" => rand(),
			);
			$this->dao_version->add($insert);
			$d_version = $this->get_version($s["id"], $eid);

			$this->_core_cacher->store(
				$cvar,
				$d_version
			);

			return($d_version);
		}
		$v = &$d_version[0];

		/* update version */
		$update = array(
			"next_version" => rand(),
		);
			
		$this->dao_version->modify(array(
				"section_id" => $v['section_id'],
				"ext_id" => $v['ext_id'],
			),
			$update
		);

		/* update cache */
		$d_version[0]["next_version"] = $update["next_version"];

		$this->_core_cacher->store(
			$cvar,
			$d_version
		);

		return($d_version);
	}

	public function apply($section, $eid=0) {
		/* get section */
		$d_section = $this->get_section($section);
		if(count($d_section) <= 0) {
			$insert = array(
				"name" => $section
			);
			$this->dao_section->add($insert);
			$d_section = $this->get_section($section);
		}
		$s = &$d_section[0];

		/* get version */
		$cvar = "core_version:ver:".$s["id"].":".$eid;
		$d_version = $this->get_version($s["id"], $eid);
		if(count($d_version) <= 0) {
			$r = rand();
			$insert = array(
				"section_id" => $s["id"],
				"ext_id" => $eid,
				"prev_version" => $r,
				"next_version" => $r,
			);
			$this->dao_version->add($insert);
			$d_version = $this->get_version($s["id"], $eid);

			$this->_core_cacher->store(
				$cvar,
				$d_version
			);

			return($d_version);
		}
		$v = &$d_version[0];

		/* update version */
		$update = array(
			"prev_version" => $v["next_version"],
		);
			
		$this->dao_version->modify(array(
				"section_id" => $v['section_id'],
				"ext_id" => $v['ext_id'],
			),
			$update
		);

		/* update cache */
		$d_version[0]["prev_version"] = $update["prev_version"];

		$this->_core_cacher->store(
			$cvar,
			$d_version
		);

		return($d_version);
	}

	public function get($section, $eid=0) {
		/* get section */
		$d_section = $this->get_section($section);
		if(count($d_section) <= 0) {
			$insert = array(
				"name" => $section
			);
			$this->dao_section->add($insert);
			$d_section = $this->get_section($section);
		}
		$s = &$d_section[0];

		/* get version */
		$cvar = "core_version:ver:".$s["id"].":".$eid;
		$d_version = $this->get_version($s["id"], $eid);
		if(count($d_version) <= 0) {
			$insert = array(
				"section_id" => $s["id"],
				"ext_id" => $eid,
				"prev_version" => rand(),
				"next_version" => rand(),
			);
			$this->dao_version->add($insert);
			$d_version = $this->get_version($s["id"], $eid);

			$this->_core_cacher->store(
				$cvar,
				$d_version
			);

			return($d_version);
		}
		
		return($d_version);
		
	}

	private function get_section($section) {
		$cvar = "core_version:section:".$section;
		$cache = $this->_core_cacher->get($cvar);
		if(count($cache) > 0) {
			echo "HIT\n";
			return($cache);
		}
		
		/* get section */
		$d_section = $this->dao_section->get(array(
			"name" => $section
		));
		if(count($d_section) > 0) {
			$this->_core_cacher->store(
				$cvar,
				$d_section
			);
			return($d_section);
		}
		return(array());
	}

	private function get_version($section_id, $eid=0) {
		$cvar = "core_version:ver:".$section_id.":".$eid;

		$cache = $this->_core_cacher->get($cvar);
		if(count($cache) > 0) {
			echo "HIT\n";
			return($cache);
		}

		/* get version */
		$d_version = $this->dao_version->get(array(
			"section_id" => $section_id,
			"ext_id" => $eid
		));
		if(count($d_version) > 0) {
			$this->_core_cacher->store(
				$cvar,
				$d_version
			);
			return($d_version);
		}
		return(array());
	}


	
}

