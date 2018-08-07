<?php
//reuse object coding from https://joshtronic.com/2014/01/06/improve-performance-reusing-objects/
// Instead of doing $object = new Object() you’d simply do $object = Object::getInst() and would be able to interact with the object just like it was instantiated.

class capacity{
    
    var $Turn; //each turn sideways!!!!grey only
    
    var $Stack; //stackable boolean
    
    var $Lay; //each lay down boolean
    
    var $sL; //slot length
    var $sW; //slot width
    var $sH; //slot height

    var $pL; //product length
    var $pW; //product width
    var $pH; //product height
    var $pMCP; //product MCP, not same as SKU MCP
    
    var $minCap; //minumum cap looking for
    var $flex; //percent below cap willing to go to in order to avoid going to the next type of slot
    
    /*
    Going to create an array of caps, depending on the 3 options: turned, stacked, and laid down.
    T0 = no turn, T1 = L=W
    S0 = H=slot height (not stacked, S1 = H=H (stacked)
    L0 = not laid down, L1 = L=H, L2 = W=H
    T1S1L0 would be turned and stacked (L=W,W=L,H=H),
    T1S0L2 would be (L=W,W=H,H=slot height)
    */
    var $cap_arr; //array of capacities, depending on orientation.
    var $ori; //best orientation (coded)
    var $Cap; //capacity to return
    var $Message; //reply message
    
    function __construct($mincap,$flex=.9,$sL,$sW,$sH,$pL,$pW,$pH,$pMCP=1,$turn=0,$stack=0,$lay=0)
	{
	    $this->minCap = $mincap;
        $this->flex = $flex;
        
        $this->sL = $sL;
        $this->sW = $sW;
        $this->sH = $sH;
        
        $this->pL = $pL;
        $this->pW = $pW;
        $this->pH = $pH;
        $this->pMCP = $pMCP;
        
        $this->Turn = $turn;
        $this->Stack = $stack;
        $this->Lay = $lay;
        
        $this->cap_arr['T0S0L0'] = intval($sL / $pL) * intval($sW / $pW) * $pMCP;
        $this->cap_arr['T1S0L0'] = intval($sL / $pW) * intval($sW / $pL) * $pMCP;
        $this->cap_arr['T0S1L0'] = intval($sL / $pL) * intval($sW / $pW) * intval($sH / $pH) * $pMCP;
        $this->cap_arr['T1S1L0'] = intval($sL / $pW) * intval($sW / $pL) * intval($sH / $pH) * $pMCP;
        $this->cap_arr['T0S0L1'] = intval($sL / $pH) * intval($sW / $pW) * $pMCP; //h=pL
        $this->cap_arr['T0S0L2'] = intval($sL / $pL) * intval($sW / $pH) * $pMCP; //h=pW
        $this->cap_arr['T1S0L1'] = intval($sL / $pH) * intval($sW / $pL) * $pMCP; //h=pW
        $this->cap_arr['T1S0L2'] = intval($sL / $pW) * intval($sW / $pH) * $pMCP; //h=pL
        $this->cap_arr['T0S1L1'] = intval($sL / $pH) * intval($sW / $pW) * intval($sH / $pL) * $pMCP; //h=pL
        $this->cap_arr['T0S1L2'] = intval($sL / $pL) * intval($sW / $pH) * intval($sH / $pW) * $pMCP; //h=pW
        $this->cap_arr['T1S1L1'] = intval($sL / $pH) * intval($sW / $pL) * intval($sH / $pW) * $pMCP; //h=pW
        $this->cap_arr['T1S1L2'] = intval($sL / $pW) * intval($sW / $pH) * intval($sH / $pL) * $pMCP; //h=pL

	}    
    
    function set_stat($c,$f=.9){
        $this->minCap = $c;
        $this->flex = $f;
    }
    
    function set_slot($l,$w,$h){
        $this->sL = $l;
        $this->sW = $w;
        $this->sH = $h;
    }
    
    function set_prod($l,$w,$h,$m){
        $this->pL = $l;
        $this->pW = $w;
        $this->pH = $h;
        $this->pMCP = $m;
    }
    
    function get_cap(){
        foreach($this->cap_arr as $key=>$cap){
            if($cap >= $this->minCap * $this->flex){
                $best = $key;
                break;
            }
        }
        
        $ori = "";
        
        if(substr($best,1,1) = 0){
            $ori .= "facing forward";
        }else{
            $ori .= "turned sideways";
        }
        
        if(substr($best,5,1) = 1){
            if(len($ori) > 0){
                $ori .= ", ";
            }
            $ori .= "laid face down";
        }
        
        if(substr($best,5,1) = 2){
            if(len($ori) > 0){
                $ori .= ", ";
            }
            $ori .= "laid on it's side";
        }
        
        if(substr($best,3,1) = 1){
            if(len($ori) > 0){
                $ori .= ", ";
            }
            $ori .= "stacked";
        }
        
        return "The best orientation for this SKU is $best with a cap of ";
    }
    
}




//create new capacity opject w/ mcp of 1, flex of .9
$minCap = 10;
$flex = .9;
$sL = 12;
$sW = 24;
$sH = 12;
$pL = 7;
$pW = 3;
$pH = 8;
$pMCP = 1;
$turn = 0;
$stack = 0;
$lay = 0;
$newSKU = new capacity($minCap,$flex,$sL,$sW,$sH,$pL,$pW,$pH,$pMCP,$turn,$stack,$lay);

echo "</br><pre>";

$exp = var_export(get_object_vars($newSKU),true);

echo "$exp</pre></br>";

echo $newSKU->get_cap();









