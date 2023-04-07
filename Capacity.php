<?php
/*This class creates a CAPACITY object with varous statistics, and then tries to find a slot to fit it into*/

/*Edited on 2020-03-03 to add the if logic for product with zero length, width, or height,
    and short circuit the calculations. This will hopefully eliminate the issues with 
    throwing several error lines per useage.
*/

/*Edited on 2023-03-01 because I want to add clearer documentation on the useage
    of this object. It's been three years since last edit and I'm relearning it
    easily enough, but that's because I still comprehend the way I was thinking
    at the time. This edit will give a (hopefully) quick and easy way to give
    an input and get a parsable output.
*/

namespace Mrwolfie\Oop;

class capacity{

    protected $Turn; //each turn sideways!!!!grey only
    protected $Stack; //stackable boolean
    protected $Lay; //each lay down boolean

    protected $sL; //slot length
    protected $sW; //slot width
    protected $sH; //slot height

    protected $pL; //product length
    protected $pW; //product width
    protected $pH; //product height
    protected $pMCP; //product MCP, not same as SKU MCP

    protected $minCap; //minumum cap looking for
    protected $flex; //percent below cap willing to go to in order to avoid going to the next type of slot

    protected $heightCheck; //initial height check to see if the product will even fit, and move on without further testing otherwise

    /*
    Going to create an array of possible capacity configurations, depending on the 3 options: turned, stacked, and laid down.
    T0 = no turn, T1 = L=W (turned around vertical axis)
    S0 = H=slot height (not stacked, S1 = H=H (stacked)
    L0 = not laid down, L1 = L=H (turned around lateral axis), L2 = W=H (turned around longitudinal axis)
    T1S1L0 would be turned and stacked (L=W,W=L,H=H),
    T1S0L2 would be (L=W,W=H,H=slot height)
    */

    /*
    key for slot type is:
    Grey Shelf = 0, no space between rows
    Old Flow = 1, 1/2" between rows, 1/2" on each side
    New Flow = 2, 1" between rows, 1/2" on each side
    Buffer Override = 3, buffer set to whatever $buff is, and slot reduction set to whatever $slOver is
    */
    protected $slotType; //see the above comment for an explanation here
    protected $buffer; //space between boxes
    protected $slOver; /*amount to reduce the length of the slot for calculation purposes.
                    This is similar to buffer, but doesnt take away extra space from
                    between rows of the the same product.
                */

    protected $cap_arr; //array of capacities, depending on orientation.
    protected $best; //best type of location
    protected $ori; //best orientation (coded)
    protected $Cap; //capacity to return
    protected $Message; //reply message


/*
To create a capacity object, supply the construct with, at a minimum and in order:
Product Length,
Product Width,
Product Height

If no additional options are selected, it will be assumed that:
Product is not turned, not stacked, not laid down, has an MCP of 1, a minimum cap of 1,
a flex on that minimum cap of .9
put in a grey 48" long by 24" deep by 12" high shelf with no buffer change and
no override on that buffer.

Additional options, in order again, are:
Turn BOOL (turned around the vertical axis, so the side is facing forward),
Stack BOOL (product stacked on top of itself),
Lay BOOL (product laid on side, so the top is facing forward OR to the side),
MCP INT (this is assumed to be 1 if not specified),
Minimum Cap INT (to filter results that are not large enough to be considered),
Flex FLOAT (how much give and take you want to allow for that Minimum Cap, as in you want 400 but a Grey Shelf could get you 480, and that might be good enough),
Slot Type 0/1/2, as follows, with extra space given for flow racks:
    Grey Shelf = 0, no space between rows
    Old Flow = 1, 1/2" between rows, 1/2" on each side
    New Flow = 2, 1" between rows, 1/2" on each side
Slot FLOAT Length,
Slot FLOAT Width,
Slot FLOAT Height,
Buffer FLOAT  (specify the space between boxes),
Slot Override 0/1 (to override the space between rows of the same product for caclulation purposes)

After creating an capacity object:
Use the get_cap() method to return a capacity with the given stats, which is an
array with the following keys:
'cap' = INT of the capacity
'text' = STRING with a message describing how it is positioned

An alternative for just data would be to use the spit_arr() method, which would
simply return an array of 
'T0S0L0' - Not turned, Not stacked, Not laid down
'T1S0L0' - YES turned, Not stacked, Not laid down
'T0S1L0' - Not turned, YES stacked, Not laid down
'T1S1L0' - YES turned, Yes stacked, Not laid down
'T0S0L1' - Not turned, Not stacked, YES laid down top forward
'T0S0L2' - Not turned, Not stacked, YES laid down top to side
'T1S0L1' - YES turned, Not stacked, YES laid down top forward
'T1S0L2' - YES turned, Not stacked, YES laid down top to side
'T0S1L1' - Not turned, YES stacked, YES laid down top forward
'T0S1L2' - Not turned, YES stacked, YES laid down top to side
'T1S1L1' - YES turned, YES stacked, YES laid down top forward
'T1S1L2' - YES turned, YES stacked, YES laid down top  to side

I believe, for bulk calculation which this edit has been created for, it would 
be most efficient if 3 capacities were created for each product form 
(each, icp, and case) as the 3 sets of measurements are retrieved at once (or in
mass, which would be better than multiple calls). Then update the capacity object
with the new dimensions with the 
set_prod(length, width, height, mcp) method 
and then slot size with the 
set_slot(length, width, height, slot type (0/1/2), buffer(defaults to 0), slot override) method. 
set_slot() runs the cap_rearr() method, which recalculates the capacities in the 
array, which can be retrieved with get_cap() for full text, or spit_arr() for 
pure data. 
This allows objects to be recycled instead of having to be created every. single. time.

If things such as minimum cap or flex need to be changed, this can be accomplished
with the set_stat(Minimum Cap, Flex-defaults to .9) method.

Other methods not otherwise specified but return data are:
get_stat() - returns array of minCap, flex
get_slot() - returns array of sL (slot length), sW (slot width), sH (slot height), slotType (cheezeburger)
get_prod() - returns array of pL (prod length), pw (prod width), pH (prod height), pMCP (prod units)
get_opt() - returns array of Turn, Stack, Lay (all booleans)
*/
    function __construct($pL,$pW,$pH,$turn=0,$stack=0,$lay=0,$pMCP=1,$mincap=1,$flex=.9,$slot=0,$sL=48,$sW=24,$sH=12,$buff=0,$slOver=0)
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

        $this->slotType = $slot;
        $this->buffer = $buff;
        $this->slotOver = $slOver;

        $this->add_space();
        if($this->pL > 0 && $this->pW > 0 && $this->pH > 0){
            $this->cap_arr['T0S0L0'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pW) * $this->heightCheck($this->pH) * $this->pMCP;
            $this->cap_arr['T1S0L0'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pL) * $this->heightCheck($this->pH) * $this->pMCP * $this->Turn;
            $this->cap_arr['T0S1L0'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pW) * intval($this->sH / $this->pH) * $this->pMCP * $this->Stack;
            $this->cap_arr['T1S1L0'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pL) * intval($this->sH / $this->pH) * $this->pMCP * $this->Turn * $this->Stack;
            $this->cap_arr['T0S0L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pW) * $this->heightCheck($this->pL) * $this->pMCP * $this->Lay; //h=pL
            $this->cap_arr['T0S0L2'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pH) * $this->heightCheck($this->pW) * $this->pMCP * $this->Lay; //h=pW
            $this->cap_arr['T1S0L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pL) * $this->heightCheck($this->pW) * $this->pMCP * $this->Turn * $this->Lay; //h=pW
            $this->cap_arr['T1S0L2'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pH) * $this->heightCheck($this->pL) * $this->pMCP * $this->Turn * $this->Lay; //h=pL
            $this->cap_arr['T0S1L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pW) * intval($this->sH / $this->pL) * $this->pMCP * $this->Stack * $this->Lay; //h=pL
            $this->cap_arr['T0S1L2'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pH) * intval($this->sH / $this->pW) * $this->pMCP * $this->Stack * $this->Lay; //h=pW
            $this->cap_arr['T1S1L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pL) * intval($this->sH / $this->pW) * $this->pMCP * $this->Turn * $this->Stack * $this->Lay; //h=pW
            $this->cap_arr['T1S1L2'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pH) * intval($this->sH / $this->pL) * $this->pMCP * $this->Turn * $this->Stack * $this->Lay; //h=pL
        }else{
            $this->cap_arr['T0S0L0'] = 0;
            $this->cap_arr['T1S0L0'] = 0;
            $this->cap_arr['T0S1L0'] = 0;
            $this->cap_arr['T1S1L0'] = 0;
            $this->cap_arr['T0S0L1'] = 0;
            $this->cap_arr['T0S0L2'] = 0;
            $this->cap_arr['T1S0L1'] = 0;
            $this->cap_arr['T1S0L2'] = 0;
            $this->cap_arr['T0S1L1'] = 0;
            $this->cap_arr['T0S1L2'] = 0;
            $this->cap_arr['T1S1L1'] = 0;
            $this->cap_arr['T1S1L2'] = 0;
            $this->Cap = 0;
        }
	}

	public function cap_rearr(){
        if($this->pL > 0 && $this->pW > 0 && $this->pH > 0){
    	    $this->cap_arr['T0S0L0'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pW) * $this->heightCheck($this->pH) * $this->pMCP;
            $this->cap_arr['T1S0L0'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pL) * $this->heightCheck($this->pH) * $this->pMCP * $this->Turn;
            $this->cap_arr['T0S1L0'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pW) * intval($this->sH / $this->pH) * $this->pMCP * $this->Stack;
            $this->cap_arr['T1S1L0'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pL) * intval($this->sH / $this->pH) * $this->pMCP * $this->Turn * $this->Stack;
            $this->cap_arr['T0S0L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pW) * $this->heightCheck($this->pL) * $this->pMCP * $this->Lay; //h=pL
            $this->cap_arr['T0S0L2'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pH) * $this->heightCheck($this->pW) * $this->pMCP * $this->Lay; //h=pW
            $this->cap_arr['T1S0L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pL) * $this->heightCheck($this->pW) * $this->pMCP * $this->Turn * $this->Lay; //h=pW
            $this->cap_arr['T1S0L2'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pH) * $this->heightCheck($this->pL) * $this->pMCP * $this->Turn * $this->Lay; //h=pL
            $this->cap_arr['T0S1L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pW) * intval($this->sH / $this->pL) * $this->pMCP * $this->Stack * $this->Lay; //h=pL
            $this->cap_arr['T0S1L2'] = intval($this->sL / ($this->buffer + $this->pL)) * intval($this->sW / $this->pH) * intval($this->sH / $this->pW) * $this->pMCP * $this->Stack * $this->Lay; //h=pW
            $this->cap_arr['T1S1L1'] = intval($this->sL / ($this->buffer + $this->pH)) * intval($this->sW / $this->pL) * intval($this->sH / $this->pW) * $this->pMCP * $this->Turn * $this->Stack * $this->Lay; //h=pW
            $this->cap_arr['T1S1L2'] = intval($this->sL / ($this->buffer + $this->pW)) * intval($this->sW / $this->pH) * intval($this->sH / $this->pL) * $this->pMCP * $this->Turn * $this->Stack * $this->Lay; //h=pL
    	}else{
            $this->cap_arr['T0S0L0'] = 0;
            $this->cap_arr['T1S0L0'] = 0;
            $this->cap_arr['T0S1L0'] = 0;
            $this->cap_arr['T1S1L0'] = 0;
            $this->cap_arr['T0S0L1'] = 0;
            $this->cap_arr['T0S0L2'] = 0;
            $this->cap_arr['T1S0L1'] = 0;
            $this->cap_arr['T1S0L2'] = 0;
            $this->cap_arr['T0S1L1'] = 0;
            $this->cap_arr['T0S1L2'] = 0;
            $this->cap_arr['T1S1L1'] = 0;
            $this->cap_arr['T1S1L2'] = 0;
            $this->Cap = 0;
        }
	}

	protected function heightCheck($height){
	    if($this->sH <= $height){
	        return 0;
	    }else{
	        return 1;
	    }
	}

	protected function add_space($buff=0,$slOver=0){
	    switch($this->slotType){
	        case 0:
	            $this->buffer = 0;
	            break;
	        case 1:
	            $this->sL = $this->sL - .5;
	            $this->buffer = .5;
	            break;
            case 2:
                $this->sL = $this->sL - .5;
	            $this->buffer = 1;
	            break;
            case 3:
                $this->sL = $this->sL - $slOver;
	            $this->buffer = $buff;
	            break;
	    }
	}

    public function set_stat($minCap,$flex=.9){
        $this->minCap = $minCap;
        $this->flex = $flex;
    }

    public function get_stat(){
        $arr = ["minCap"=>$this->minCap,"flex"=>$this->flex];
        return $arr;
    }

    public function set_slot($l,$w,$h,$s,$buff=0,$slOver=0){
        $this->sL = $l;
        $this->sW = $w;
        $this->sH = $h;
        $this->slotType = $s;
        $this->add_space($buff,$slOver);
        $this->cap_rearr();
    }

    public function get_slot(){
        $arr = ["sL"=>$this->sL,"sW"=>$this->sW,"sH"=>$this->sH,"slotType"=>$this->slotType];
        return $arr;
    }

    public function set_prod($l,$w,$h,$m){
        $this->pL = $l;
        $this->pW = $w;
        $this->pH = $h;
        $this->pMCP = $m;
    }

    public function get_prod(){
        $arr = ["pL"=>$this->pL,"pW"=>$this->pW,"pH"=>$this->pH,"pMCP"=>$this->pMCP];
        return $arr;
    }

    public function set_opt($turn=0,$stack=0,$lay=0){
        $this->Turn = $turn;
        $this->Stack = $stack;
        $this->Lay = $lay;
    }

    public function get_opt(){
        $arr = ["Turn"=>$this->Turn,"Stack"=>$this->Stack,"Lay"=>$this->Lay];
        return $arr;
    }

    public function get_cap(){
        $this->ori = false;
        $this->Message = array();
        $this->Cap = 0;
        foreach($this->cap_arr as $key=>$cap){
            if(($cap >= ($this->minCap * $this->flex)) && $cap > $this->Cap){
                $this->best = $key;
                $this->Cap = $cap;
            }
        }

        if($this->best == "T0S0L0"){
            $this->ori ="Product upright, facing forward";
        }

        if($this->best == "T1S0L0" && $this->Turn > 0){
            $this->ori ="Product upright, turned 90 degrees longitudinally";
        }

        if($this->best == "T0S1L0" && $this->Stack > 0){
            $this->ori ="Product upright, facing forward, and stacked on top of itself";
        }

        if($this->best == "T1S1L0" && $this->Turn > 0 && $this->Stack > 0){
            $this->ori ="Product upright, turned 90 degrees longitudinally, and stacked on top of itself";
        }

        if($this->best == "T0S0L1" && $this->Lay > 0){
            $this->ori ="Product rotated 90 laterally, top forward";
        }

        if($this->best == "T0S0L2" && $this->Lay > 0){
            $this->ori ="Product rotated 90 laterally, top to the side";
        }

        if($this->best == "T1S0L1" && $this->Turn > 0 && $this->Lay > 0){
            $this->ori ="Product turned 90 degrees longitudinally, laying down, top forward";
        }

        if($this->best == "T1S0L2" && $this->Turn > 0 && $this->Lay > 0){
            $this->ori ="Product turned 90 degrees longitudinally, laying down, top to the side";
        }

        if($this->best == "T0S1L1" && $this->Stack > 0 && $this->Lay > 0){
            $this->ori ="Product rotated 90 laterally, top forward, and stacked on top of itself";
        }

        if($this->best == "T0S1L2" && $this->Stack > 0 && $this->Lay > 0){
            $this->ori ="Product rotated 90 laterally, top to the side, and stacked on top of itself";
        }

        if($this->best == "T1S1L1" && $this->Turn > 0 && $this->Stack > 0  && $this->Lay > 0){
            $this->ori ="Product turned 90 degrees longitudinally and laterally, top forward, and stacked on top of itself";
        }

        if($this->best == "T1S1L2" && $this->Turn > 0 && $this->Stack > 0  && $this->Lay > 0){
            $this->ori ="Product turned 90 degrees longitudinally and laterally, top to the side, and stacked on top of itself";
        }

        if($this->ori == false){
            $this->Message['cap'] = 0;
            $this->Message['text'] = "This product cannot meet the minimum capacity in this location.";
        }else{
            $this->Message['cap'] = $this->Cap;
            $this->Message['text'] = "The best orientation for this SKU is $this->ori with a cap of $this->Cap";
        }

        return $this->Message;
    }

    public function spit_arr(){
        return $this->cap_arr;
    }

}

?>
