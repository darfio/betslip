<?php

namespace Betslip;

use Matchmaker;
/**
* 
*/
class Bet
{
    public $success = true; 
    private $stake_amount_interval = ['min' => 0.3, 'max' => 10000]; //
    private $min_selection_count = 1;
    private $max_selection_count = 20;   
    private $odds_interval = ['min' => 1, 'max' => 10000];
    private $max_expected_win_ammount = 20000;

    public function make(&$betslip = [])
    {

    	if ($this->validate($betslip))
    		$this->success = true;
    	else
    		$this->success = false;
    }


    private function validate(&$betslip){
    	$error = 0; //unknown error


    	//error = 1  => 'Betslip structure mismatch'
    	if (!$this->valid_structure($betslip)){
    		$error = 1; 
    		array_push($betslip['errors'], $error);
    		return false;
    	}   	

 		//error = 2  => 'Minimum stake amount is :min_amount'
		//error = 3  => 'Maximum stake amount is :max_amount', 
    	$error = $this->stake_interval($betslip['stake_amount']);
    	if ($error === 2 || $error === 3){
    		array_push($betslip['errors'], $error);	
    		return false;
    	}

    	//error = 4  => 'Minimum number of selections is :min_selections'
    	if (count($betslip['selections']) < $this->min_selection_count){
    		$error = 4;
    		array_push($betslip['errors'], $error);
    		return false; 
    	}      	

    	//error = 5  => 'Maximum number of selections is :max_selections'
    	if (count($betslip['selections']) > $this->max_selection_count){
    		$error = 5;
    		array_push($betslip['errors'], $error);
    		return false;
    	}


	    	//odds_interval
	    	if (!$this->selection_odds_interval($betslip['selections'])){
	    		//selections error = 6; 7;

	    		//$error = 10; //10 => 'Your previous action is not finished yet'
	    		//array_push($betslip['errors'], $error);	    		
	    		return false;
	    	}

	    	//player cant bet on selections with same ID
	    	if (!$this->selection_unique_id($betslip['selections'])){
	    		//selections error = 8;

	    		//$error = 10; //10 => 'Your previous action is not finished yet'
	    		//array_push($betslip['errors'], $error);	    		
	    		return false;    		
	    	}


	    //error = 9  => 'Maximum win amount is :max_win_amount'
    	if (!$this->max_win_amount($betslip['stake_amount'], $betslip['selections'])){
    		$error = 9;
    		array_push($betslip['errors'], $error);
    		return false;     		
    	}


    	//---------------------

    	return true;
    }


    //(global) `$betslip` structure should be valid
    public function valid_structure($betslip){
    
		$pattern = [
	        'player_id' => ':int',
	        'stake_amount' => ':float',
	        'errors' => ':array',
		    'selections' => [
		        [
		            'id' => ':int',
		            'odds' => ':float',
		            'errors' => ':array',
		        ],
		    ],
		];

		$match = matchmaker\matches($betslip, $pattern); // true

		return $match;

    }

    // * (global) `stake_amount` should be in interval between [min, max]
    private function stake_interval($stake_amount){

    	$min = $this->stake_amount_interval['min'];
    	$max = $this->stake_amount_interval['max'];

    	//var_dump($stake_amount);
    	if ($stake_amount >= $min && $stake_amount <= $max)
    		return true;

    	if ($stake_amount < $min)
    		return 2; //Minimum stake amount is :min_amount
    	if ($stake_amount > $max)
    		return 3;  //Maximum stake amount is :max_amount  	

    	return 0; //Unknown error
    }


    //* (selection) odds should be in interval between [min, max]
    private function selection_odds_interval(&$selections){
    	
    	$min = $this->odds_interval['min'];
    	$max = $this->odds_interval['max'];

    	//var_dump($selections);

    	$nr=0;
    	$rez = true; //valid selection
    	foreach($selections as $selection){

	    	if ($selection['odds'] < $min){
	    		array_push($selections[$nr]['errors'], 6); //Minimum odds are :min_odds
	    		$rez = false;
	    	}
	    	if ($selection['odds'] > $max){
	    		array_push($selections[$nr]['errors'], 7); //Maximum odds are :max_odds
	    		$rez = false;
	    	}
	    	$nr++;	    	
    	}

    	if($rez)
    		return true;

    	return false;    	
    }

    //* (selection) player cant bet on selections with same ID
    private function selection_unique_id(&$selections){

    	$is_unique_ids = true;
    	$ids = []; //temp ids
    	foreach($selections as $selection){ //all selection IDs
    		array_push($ids, $selection['id']);
    	}

    	$unique_ids = array_unique($ids);
    	if(count($ids) > count($unique_ids)){ //duplicated ids
    		echo "duplicated";
	    	//
	    	foreach($unique_ids as $unique_id){ //all selection IDs
	    		
	    		$nr = 0;
	    		$founded = 0;
	    		foreach($selections as $selection){
	    			if ($selection['id'] == $unique_id){
	    				$founded++;
	    				if ($founded > 1){
	    					//8  => 'Duplicate IDs are not allowed'
	    					array_push($selections[$nr]['errors'], 8);
	    					$is_unique_ids = false;
	    				}
	    			}

	    			$nr++;
	    		}
	    		//
	    	}      		
    	}

    	return $is_unique_ids;
    }    

    //* (global) maximum `expected win amount` is max_expected_win_ammount
    private function max_win_amount($stake_amount, $selections){
    	
    	$odds_sum = 0;
    	foreach($selections as $selection){
    		$odds_sum += $selection['odds'] * 1.05 * 1.001;
    	}    	

    	//`expected win amount` = `stake_amount` \* all selection `odds`. (1 \* 1.05 \* 1.001)
    	$expected_win_amount = $stake_amount * $odds_sum;

    	if ($expected_win_amount > $this->max_expected_win_ammount)
    		return false;

    	return true;
    } 


}