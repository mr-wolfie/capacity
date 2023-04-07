<?php

namespace Mrwolfie\Oop;

/* Creating this to be able to quickly run through the possible capacities of 
*  a list of SKU's, giving every possible capacity/orientation available. I will 
*  be writing in options which can be passed from a csv type dataset, so if we
*  know these SKUs are liter bottles and need to stay upright, but these other 
*  SKUs are color boxes and can be laid down, it can all be handled without 
*  having to deal with extra data that wouldn't work anyway
*/

class EveryCap extends Capacity{
    
    /* Quick map of types of slots:
    *  0 - Grey Shelf (12", 16", 24")
    *  1 - Old Flow (101", 108", 113" 120")
    *  2 - New Flow (187")
    *  4 - Half Flow (38", currently EA)
    */
    
  protected $pdo;
  protected $whs;
  protected $dims;
  protected $items;
  protected $cntry;

  public function __construct(Conn $pdo = null, int $whs = null, string $cntry = 'USA'){
    parent::__construct(1,1,1);
    
    if(!is_null($whs)){
      $this->whs = $_SESSION['WHS']['WHS_NUM'] = $whs;
    }elseif(isset($_SESSION['WHS']['WHS_NUM'])){
      $this->whs = $_SESSION['WHS']['WHS_NUM'];
    }elseif(isset($_SESSION['warehouse'])){
      $this->whs = $_SESSION['WHS']['WHS_NUM'] = (int)$_SESSION['warehouse'];
    }else{
      $this->whs = 54;
    }
    
    $this->pdo = $pdo;
    if(is_null($this->pdo)){
      $this->pdo = new Conn(null,$this->whs);
    }
    
    $this->cntry = $cntry;
    
    $this->dims = null;
    $this->items = null;
    $this->caps = null;
    
  }
  
  //returns a count of the dimensions from the global database and sets them in
  //an array in $this->dims['LOC_ID']
  protected function set_slot_dims(): int{
    $query = 'GET_EVC_DIM_MAP';
    $area = 'EVC';
    $results = $this->pdo->query('READ',$query,array($this->whs,$area));
    if(!is_array($results)) throw new \Exception("Error: $query returned a " . gettype($results));
    $this->dims = array();
    foreach($results as $dim){
      $this->dims[$dim['LOC_ID']] = array(
        'TYPE'=>$dim['SHELF_TYPE'],
        'LEN'=>$dim['LEN'],
        'WID'=>$dim['WID'],
        'HGT'=>$dim['HGT'],
        'BUFF'=>$dim['BUFF']);
    }
    return count($this->dims);
  }
  
  public function get_slot_dims(): array{
    return $this->dims;
  }
  
  public function set_items(array $skus): bool{
    if(!is_array($skus)){
      throw new \Exception("Error: expected an array, received a " . gettype($results));
    }
    if(empty($skus)) return false;
    
    foreach($skus as $sku){
      if(!is_numeric($sku)){
        throw new \Exception("Error: expected an integer, received a " . gettype($results));
      }
      $this->items[$sku] = array();
    }
    
    return true;
  }
  
  public function get_items(): array{
    return $this->items;
  }
  
  public function get_cubes(): bool{
    $skus = implode(",", array_keys($this->items)) ;
    $query = 'GET_CUBE_SKU_MULTIPLE';
    $results = $this->pdo->query('READ',$query,array($this->cntry,$skus));
    if(!is_array($results)) throw new \Exception("Error: $query returned a " . gettype($results));
    foreach($results as $cube){
      $this->items[$cube['SKU']]['IN_MCP'] = $cube['IN_MCP'];
      $this->items[$cube['SKU']]['EA_LN'] = $cube['EA_LN'];
      $this->items[$cube['SKU']]['EA_WD'] = $cube['EA_WD'];
      $this->items[$cube['SKU']]['EA_HT'] = $cube['EA_HT'];
      $this->items[$cube['SKU']]['EA_WT'] = $cube['EA_WT'];
      $this->items[$cube['SKU']]['ICP_LN'] = $cube['ICP_LN'];
      $this->items[$cube['SKU']]['ICP_WD'] = $cube['ICP_WD'];
      $this->items[$cube['SKU']]['ICP_HT'] = $cube['ICP_HT'];
      $this->items[$cube['SKU']]['ICP_WT'] = $cube['ICP_WT'];
      $this->items[$cube['SKU']]['CS_LN'] = $cube['CS_LN'];
      $this->items[$cube['SKU']]['CS_WD'] = $cube['CS_WD'];
      $this->items[$cube['SKU']]['CS_HT'] = $cube['CS_HT'];
      $this->items[$cube['SKU']]['CS_WT'] = $cube['CS_WT'];
    }
    return true;
  }
  
  public function get_stats(): bool{
    $skus = implode(",", array_keys($this->items));
    $query = 'GET_PROD_SKU_MULTIPLE';
    $results = $this->pdo->query('READ',$query,array($skus));
    if(!is_array($results)) throw new \Exception("Error: $query returned a " . gettype($results));
    foreach($results as $stats){
      $this->items[$stats['SKU']]['DESCR'] = $stats['DESCRIP'];
      $this->items[$stats['SKU']]['SIZE'] = $stats['SIZE'];
      $this->items[$stats['SKU']]['MCP'] = $stats['MCP'];
    }
    return true;
  }
  
  public function get_meta(): bool{
    /*
    //this is not currently set up, but will handle things like if the box is
    //allowed to be laid down and where, if it's stackable, etc
    $skus = implode(",", array_keys($this->items));
    $query = 'GET_META_SKU_MULTIPLE';
    $results = $this->pdo->query('READ',$query,array($this->whs,$skus));
    if(!is_array($results)) throw new \Exception("Error: $query returned a " . gettype($results));
    foreach($results as $meta){
      $this->items[$meta['SKU']]['TURN'] = $meta['DESCRIP'];
      $this->items[$meta['SKU']]['SIZE'] = $meta['SIZE'];
      $this->items[$meta['SKU']]['MCP'] = $meta['MCP'];
    }
    */
    
    //bypass and manually set general guide
    foreach($this->items as $sku=>$meta){
      $this->items[$sku]['META'][0]['TURN'] = 1;
      $this->items[$sku]['META'][0]['STACK'] = 1;
      $this->items[$sku]['META'][0]['LAY'] = 1;
      $this->items[$sku]['META'][1]['TURN'] = 0;
      $this->items[$sku]['META'][1]['STACK'] = 0;
      $this->items[$sku]['META'][1]['LAY'] = 0;
      $this->items[$sku]['META'][2]['TURN'] = 0;
      $this->items[$sku]['META'][2]['STACK'] = 0;
      $this->items[$sku]['META'][2]['LAY'] = 0;
      $this->items[$sku]['META'][4]['TURN'] = 0;
      $this->items[$sku]['META'][4]['STACK'] = 0;
      $this->items[$sku]['META'][4]['LAY'] = 1;
    }
    return true;
  }
  
  public function get_cap_arr(int $sku): bool{
    $this->set_prod(
      $this->items[$sku]['EA_LN'], 
      $this->items[$sku]['EA_WD'],
      $this->items[$sku]['EA_HT'],
      1);
      
    foreach($this->dims as $key=>$dim){
      $this->set_opt(
        $this->items[$sku]['META'][$dim['TYPE']]['TURN'],
        $this->items[$sku]['META'][$dim['TYPE']]['STACK'],
        $this->items[$sku]['META'][$dim['TYPE']]['LAY']);
      $this->set_slot($dim['LEN'], $dim['WID'], $dim['HGT'], $dim['TYPE']);
      $this->Cap = $this->ori = null;
      $this->get_cap();
      $this->items[$sku]['CAPS']['EA'][$key]['ALL'] = $this->spit_arr();
      $this->items[$sku]['CAPS']['EA'][$key]['BEST']['CAP'] = $this->Cap;
      $this->items[$sku]['CAPS']['EA'][$key]['BEST']['ORI'] = $this->ori;
    }
    
    if($this->items[$sku]['MCP'] > 1){
      $this->set_prod(
      $this->items[$sku]['CS_LN'], 
      $this->items[$sku]['CS_WD'],
      $this->items[$sku]['CS_HT'],
      $this->items[$sku]['MCP']);
      foreach($this->dims as $key=>$dim){
        $this->set_opt(
          $this->items[$sku]['META'][$dim['TYPE']]['TURN'],
          $this->items[$sku]['META'][$dim['TYPE']]['STACK'],
          $this->items[$sku]['META'][$dim['TYPE']]['LAY']);
        $this->set_slot($dim['LEN'], $dim['WID'], $dim['HGT'], $dim['TYPE']);
        $this->get_cap();
        $this->items[$sku]['CAPS']['EA'][$key]['ALL'] = $this->spit_arr();
        $this->items[$sku]['CAPS']['EA'][$key]['BEST']['CAP'] = $this->Cap;
        $this->items[$sku]['CAPS']['EA'][$key]['BEST']['ORI'] = $this->ori;
      }
    }
    
    if($this->items[$sku]['IN_MCP'] > 0){
      $this->set_prod(
      $this->items[$sku]['ICP_LN'], 
      $this->items[$sku]['ICP_WD'],
      $this->items[$sku]['ICP_HT'],
      $this->items[$sku]['IN_MCP']);
      foreach($this->dims as $key=>$dim){
        $this->set_opt(
          $this->items[$sku]['META'][$dim['TYPE']]['TURN'],
          $this->items[$sku]['META'][$dim['TYPE']]['STACK'],
          $this->items[$sku]['META'][$dim['TYPE']]['LAY']);
        $this->set_slot($dim['LEN'], $dim['WID'], $dim['HGT'], $dim['TYPE']);
        $this->get_cap();
        $this->items[$sku]['CAPS']['EA'][$key]['ALL'] = $this->spit_arr();
        $this->items[$sku]['CAPS']['EA'][$key]['BEST']['CAP'] = $this->Cap;
        $this->items[$sku]['CAPS']['EA'][$key]['BEST']['ORI'] = $this->ori;
      }
    }
    
    return true;
    
  }
  
  public function collect_caps(): bool{
    foreach($this->items as $sku=>$array) $this->get_cap_arr($sku);
    return true;
  }
  
  public function format(): bool{
    $format = array();
    foreach($this->items as $sku=>$data){
      $format[$sku] = array(
        'DESCR'=>$data['DESCR'],
        'SIZE'=>$data['DESCR'],
        'MCP'=>$data['DESCR']
        );
    }
  }
  
  public function test_it(array $skus){
    echo "Settings up SKU arrays for " . count($skus) . " items.\n";
    echo "->set_items returned :" . $this->set_items($skus) . "\n";
    
    echo "->items is currently: \n";
    var_export($this->items);
    
    echo "Settings up Slot Dimensions for options in warehouse " . $this->whs . ".\n";
    echo "->set_slot_dims returned : " . $this->set_slot_dims() . "\n";
    echo "Successfully set up " . count($this->dims) . " possible slots.\n";
    
    echo "->dims is currently: \n";
    var_export($this->dims);
    
    echo "Collecting cubing information for items.";
    echo "->get_cubes returned : " . $this->get_cubes() . "\n";
    
    echo "Export of items at this step: \n";
    var_export($this->items);
    
    echo "Collecting statistical information for items.\n";
    echo "->get_stats returned : " . $this->get_stats() . "\n";
    
    echo "Export of items at this step: \n";
    var_export($this->items);
    
    echo "Collecting metadata for product positioning.\n";
    echo "->get_meta() returned : " . $this->get_meta() . "\n";
    
    echo "Export of items at this step: \n";
    var_export($this->items);
    
    echo "Calculating capacities for items...\n";
    echo "->collect caps returned : " . $this->collect_caps() . "\n";
    
    echo "Export of items at this step: \n";
    var_export($this->items);
    
    echo "Compiling for datablast...\n";
    $json = json_encode($this->items);
    return $json;
    
  }
  
  public function do_it(array $skus){
    echo "Settings up SKU arrays for " . count($skus) . " items.\n";
    $this->set_items($skus);
    
    echo "Settings up Slot Dimensions for options in warehouse " . $this->whs . ".\n";
    $this->set_slot_dims();
    echo "Successfully set up " . count($this->dims) . " possible slots.\n";
    
    echo "Collecting cubing information for items.\n";
    $this->get_cubes();
    
    echo "Collecting statistical information for items.\n";
    $this->get_stats();
    
    echo "Collecting metadata for product positioning.\n";
    $this->get_meta();
    
    echo "Calculating capacities for items...\n";
    $this->collect_caps();
    
    echo "Compiling for datablast...\n";
    $json = json_encode($this->items);
    echo $json;
  }
  
  public function just_do_it(array $skus){
    $this->set_items($skus);
    $this->set_slot_dims();
    $this->get_cubes();
    $this->get_stats();
    $this->get_meta();
    $this->collect_caps();
    $json = json_encode($this->items);
    $formmated = 0;
    $reply = array('JSON'=>$json);
    return $json;
  }
}



?>