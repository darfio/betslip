<?php

//namespace Tests;

use Betslip\Bet;


class Testing extends PHPUnit_Framework_TestCase
{
	
	private function get_betslip($id, $amount, $selections){
		return $betslip = [
		    'player_id' => $id,
		    'stake_amount' => $amount,
		    'errors' => [],
		    'selections' => $selections,
		]; 
	}

	private function get_selections($count, $odds){

		$selections = [];
		for ($i=1; $i<=$count; $i++){
			$selection = [ 'id'=>$i, 'odds'=>$odds, 'errors'=>[] ];
			$selections[] = $selection;
		}
		return $selections;
	}


	public function testSuccess(){

		$bet = new Bet;
		$selections = [ ['id'=>1, 'odds'=>1.601, 'errors'=>[] ] ];

		//betslip success
		$betslip = $this->get_betslip(1, 99.99, $selections);
		$bet->make($betslip);

		$this->assertTrue($bet->success);
		$this->assertEquals($betslip['errors'], []);
	}

	public function testStructureErrors(){

		$bet = new Bet;
		$selections = [ ['id'=>1, 'odds'=>1.601, 'errors'=>[] ] ];

		//player id not int (not valid structure)
		$betslip = $this->get_betslip('1', 99.99, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['errors'], [1]);
	}

	public function testStakeAmountErrors(){
		$bet = new Bet;
		$selections = [ ['id'=>1, 'odds'=>1.601, 'errors'=>[] ] ];

		// min stake amount > stake amount
		$betslip = $this->get_betslip(1, 0.2, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['errors'], [2]);	


		// max stake amount < stake amount
		$betslip = $this->get_betslip(1, 10001.00, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['errors'], [3]);		
	}

	public function testSelectionsIntervalErrors(){
		$bet = new Bet;
		$selections = [ ['id'=>1, 'odds'=>1.601, 'errors'=>[] ] ];

		//error = 4  => 'Minimum number of selections is :min_selections'
		$selections = $this->get_selections(0, 1.601);
		$betslip = $this->get_betslip(1, 99.00, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['errors'], [1]); //wrong structure if empty
		//

		//error = 5  => 'Maximum number of selections is :max_selections'
		$selections = $this->get_selections(21, 1.601);
		$betslip = $this->get_betslip(1, 99.00, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['errors'], [5]);	
	}

	public function testSelectionOddsIntervalErrors(){
		$bet = new Bet;
		$selections = [ ['id'=>1, 'odds'=>1.601, 'errors'=>[] ] ];


		//error = 6  //Selection odds < min_odds
		$selections = [ ['id'=>1, 'odds'=>0.9, 'errors'=>[] ] ];
		$betslip = $this->get_betslip(1, 99.00, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['selections'][0]['errors'], [6]);	


		//error = 7  //Selection odds > max_odds
		$selections = [ ['id'=>1, 'odds'=>10000.01, 'errors'=>[] ] ];
		$betslip = $this->get_betslip(1, 99.00, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['selections'][0]['errors'], [7]);		
	}

	public function testSelectionsWithSameIdErrors(){
		$bet = new Bet;
		//player cant bet on selections with same ID
		$selections = [ 
			['id'=>1, 'odds'=>100.00, 'errors'=>[] ], 
			['id'=>1, 'odds'=>1000.00, 'errors'=>[] ] //same id
		];
		$betslip = $this->get_betslip(1, 99.00, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['selections'][1]['errors'], [8]);			
	}

	public function testMaxWinAmountErrors(){

		$bet = new Bet;

		//error = 9  => win_amount > max_win_amount (20000)
		$selections = $this->get_selections(10, 200.00);	
		$betslip = $this->get_betslip(1, 99.00, $selections);
		$bet->make($betslip);

		$this->assertFalse($bet->success);
		$this->assertEquals($betslip['errors'], [9]);			
	} 
}
