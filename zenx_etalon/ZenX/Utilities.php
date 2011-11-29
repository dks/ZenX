<?php
/**
 * "ZenX" PHP data manupulation library.
 *
 * @author Konstantin Dvortsov <kostya.dvortsov@gmail.com>. You can 
 * also track me down at {@link http://dvortsov.tel dvortsov.tel }
 * @version 1.0
 * @package ZenX 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
/**
 * Reorders an array by keys according to a list of values.
 * @param array $array the array to reorder. Passed by reference
 * @param array $list the list to reorder by
 * @param boolean $keepRest if set to FALSE, anything not in the $list array will be removed.
 * @param boolean $prepend if set to TRUE, will prepend the remaining values instead of appending them
 * @author xananax AT yelostudio DOT com
 */
function array_reorder(array &$array,array $list,$keepRest=TRUE,
  $prepend=FALSE,$preserveKeys=TRUE,$addMissing=TRUE){

    $temp = array();
    foreach($list as $i){
        if(isset($array[$i])){
            $tempValue = array_slice(
                $array,
                array_search($i,array_keys($array)),
                1,
                $preserveKeys
            );
            $temp[$i] = array_shift($tempValue);
            unset($array[$i]);
        } else if ($addMissing) $temp[$i]=null;
    }
    $array = $keepRest ?
        ($prepend?
            $array+$temp
            :$temp+$array
        )
        : $temp;
}
