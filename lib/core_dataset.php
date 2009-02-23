<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * OpenWF - Open source Web Framework                    *
 * BinarySEC (c) (2000-2009) / www.binarysec.com         *
 * Author: Michael Vergoz <mv@binarysec.com>             *
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~         *
 *  Avertissement : ce logiciel est protégé par la       *
 *  loi du copyright et par les traités internationaux.  *
 *  Toute personne ne respectant pas ces dispositions    *
 *  se rendra coupable du délit de contrefaçon et sera   *
 *  passible des sanctions pénales prévues par la loi.   *
 *  Il est notamment strictement interdit de décompiler, *
 *  désassembler ce logiciel ou de procèder à des        *
 *  opération de 'reverse engineering'.                  *
 *                                                       *
 *  Warning : this software product is protected by      *
 *  copyright law and international copyright treaties   *
 *  as well as other intellectual property laws and      *
 *  treaties. Is is strictly forbidden to reverse        *
 *  engineer, decompile or disassemble this software     *
 *  product.                                             *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/*
TODO: regarder les slides 
filtre:
	- nom du champs de la zone = array()
		- traduction SELECT / SLIDE / ACTIVATE
		
		- si SELECT 
			- description = texte
			- traduction = array()
				nom en base = traduction
			
		- si ACTIVATE
			- description = texte
			- on = contrainte base
			- off = contrainte base
		
		
order = array()
	liste des champs de la zone 

vue des champs = array()
	- nom du champs de la zone
		- description
		% callback par ligne
		
champs de control = array()
	- array()
		- nom du head
		- défini si le champs est libre (graphiquement)
		- type CALLBACK / TEXT
		
		- si CALLBACK = callback
		- si TEXT = texte à afficher
		
*/


class core_dataset {

	public function __construct(
			$zone, // nom de la zone
			$filters, // filtre possible
			$order, // organisation possible
			$views, // vue des champs
			$control, // champs de control (permet d'ajouter des champs)
			$pager // nombre max d'element par page
		) {
	
	}
	
	
	public function draw() {
	
	}
}

