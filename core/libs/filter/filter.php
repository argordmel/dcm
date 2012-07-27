<?php
/**
 * KumbiaPHP web & app Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://wiki.kumbiaphp.com/Licencia
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@kumbiaphp.com so we can send you a copy immediately.
 *
 * @category   Kumbia
 * @package    Filter
 * @copyright  Copyright (c) 2005-2012 Kumbia Team (http://www.kumbiaphp.com)
 * @license    http://wiki.kumbiaphp.com/Licencia     New BSD License
 */
/**
 * @see FilterInterface
 * */
require_once CORE_PATH . 'libs/filter/filter_interface.php';

/**
 * Implementación de Filtros para Kumbia
 *
 * @category   Kumbia
 * @package    Filter
 */
class Filter {

    /**
     * Aplica filtro de manera estatica
     *
     * @param mixed $s variable a filtrar
     * @param string $filter filtro
     * @param array $options
     * @return mixed
     */
    public static function get($s, $filter, $options=array()) {
        if (is_string($options)) {
            $filters = func_get_args();
            unset($filters[0]);

            $options = array();
            foreach ($filters as $f) {
                $filter_class = Util::camelcase($f) . 'Filter';
                if (!class_exists($filter_class, false)) {
                    self::_load_filter($f);
                }

                $s = call_user_func(array($filter_class, 'execute'), $s, $options);
            }
        } else {
            $filter_class = Util::camelcase($filter) . 'Filter';
            if (!class_exists($filter_class, false)) {
                self::_load_filter($filter);
            }
            $s = call_user_func(array($filter_class, 'execute'), $s, $options);
        }

        return $s;
    }

    /**
     * Aplica los filtros a un array
     *
     * @param array $s variable a filtrar
     * @param string $filter filtro
     * @param array $options
     * @return array
     */
    public static function get_array($array, $filter, $options=array()) {
        $args = func_get_args();

        foreach ($array as $k => $v) {
            $args[0] = $v;
            $array[$k] = call_user_func_array(array('self', 'get'), $args);
        }

        return $array;
    }

    /**
     * Aplica filtros a un objeto
     *
     * @param mixed $object
     * @param array $options
     * @return object
     */
    public static function get_object($object, $filter, $options=array()) {
        $args = func_get_args();

        foreach ($object as $k => $v) {
            $args[0] = $v;
            $object->$k = call_user_func_array(array('self', 'get'), $args);
        }

        return $object;
    }
    
    /**
     * Aplica filtro para un array de datos de manera personalizada
     *
     * @param array $data Array de datos a filtrar
     * @param array $fields Array de campos a filtrar que están en el array $data
     * @param string $filterAll String con los filtros para aplicar a todo el array $data
     * @param boolean $all Indica si retorna todos los datos del array $data o solo los del array $fields
     * 
     * @example Filter::data(Input::post('form'), array('fiel1'=>'numeric', 'field2'=>'striptags, upper'), 'trim');
     * 
     * @return mixed
     */
    public static function data($data, $fields=array(), $filterAll=NULL, $all=false) {        
        $filtered = array(); //datos filtrados a devolver.
        if($fields) { //Si hay campos a filtrar, de lo contrario aplica el filttersAll para todo                   
            foreach($data as $index => $value) { //Recorro la data
                if(array_key_exists($index, $fields)) { //Verifico si existe in key de la $data en $fields                   
                    $filters = explode(',',$fields[$index]);//convertimos el filtro en arreglo
                    $filters = str_replace(" ","",$filters);//Quito los espacios
                    array_unshift($filters, $data[$index]);
                    $filtered[$index] = call_user_func_array(array('self', 'get'), $filters);                                        
                } else if(in_array($index, $fields) or $all==true) { //Si no tiene key en $fields o si incluye solo los indicados
                    $filtered[$index] = $value;                    
                } else {
                    continue;
                }                                
            }                                                   
        }
        if ($filterAll) { //Si utiliza un filtro para todos los input
            $filterAll = explode(',',$filterAll);
            $filterAll = str_replace(" ","",$filterAll);
            array_unshift($filterAll, $filtered);
            $filtered = call_user_func_array(array('self', 'get_array'), $filterAll);
        } 
        return $filtered;        
    }

    /**
     * Carga un Filtro
     *
     * @param string $filter filtro
     * @throw KumbiaException
     */
    protected static function _load_filter($filter) {
        $file = APP_PATH . "extensions/filters/{$filter}_filter.php";
        if (!is_file($file)) {
            $file = CORE_PATH . "libs/filter/base_filter/{$filter}_filter.php";
            if (!is_file($file)) {
                throw new KumbiaException("Filtro $filter no encontrado");
            }
        }

        include $file;
    }

}