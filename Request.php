<?php

/* $event = $Request   ->all($conn,'smt_eventos')                                                                        
                        ->join('smt_descripcion_evento')
                        ->joinTableAlias('tt')
                        ->where(['id','id_evento'])                        
                        ->search('bani',['ciudad'])
                        ->orderBy(['alias.campo','ASC'])
                        ->limit(10,5)
                        ->execute(); */
/**
 * @author Alberto Sanchez - ww.as-wm.com
 * @version 1.0.0
 * 
 * La presente clase puede utilizarse para cualquier proyecto
 * la misma recibe en el metodo all(), 2 parametros: la conexion a la base de datos
 * y el nombre de la tabla a buscar (ver ejemplo de arriba).
 * 
 * Para los demas parametros leer mas abajo en cada metodo.
 * 
 * Los metodos que no tiene comentarios es necesario invocarlos.
 *  
 */

class Request {
    protected static $instance;
    protected $conn;
    protected $sql;
    protected $startPositionOrLenght;
    protected $endLimitOrLenght;
    protected $joinTable;
    protected $joinTableAlias;
    protected $where;
    protected $filter;
    protected $orderBy;
    protected $tableAliasForOrderBy;
    protected $sort;
    protected $value;
    protected $counter;
    protected $toSearch;
    protected $searchField;
    
    protected $tableName;
    /* protected $tableAlias; */
        protected $fieldName;    
            protected $id; 


    public static function singletonRequest() 
    {       
        
        if (!isset(self::$instance)) {
            $myclass = __CLASS__; # __CLASS__ devuelve el nombre de esta clase.
            self::$instance = new $myclass;
        } 

        return self::$instance;

    }
     
    protected function statement()
    {
        $statement = $this->conn->prepare($this->sql);        
        $statement->execute(); 

        if($this->counter)
        {
            $rowCounter = $statement->fetch();    
            return  $rowCounter['COUNT(*)'];
        }

        return $statement->fetchAll();        
    }
   
    protected function selectAll()
    {              
        
        $this->sql = "SELECT ";        

        if ($this->fieldName)
        {
            $this->sql .= join(",",$this->fieldName);
        }
        else
        {            
            if($this->counter)
            {
                $this->sql .= "COUNT(*)";            
            }
            else
            {
                $this->sql .= "*";            
            }
        }


        $this->sql .= " FROM ".$this->tableName." AS tn"; 
        

        if($this->joinTable)
        {
            if( is_array($this->joinTable) and is_array($this->joinTableAlias) )
            {
                $e=0;
                for ($i=0; $i < count($this->joinTable); $i++)
                { 
                    if(!empty($this->tableAlias))                    
                    {
                        if($e < 1)
                        {
                            $this->sql .= " JOIN ".$this->joinTable[$i]." AS ".$this->joinTableAlias[$i];    
                            $this->sql .= " ON ".$this->tableAlias.".".$this->where[$i];    
                            $this->sql .= " = ".$this->joinTableAlias[$i];
                            $this->sql .= ".".$this->where[$i+1];    
                            $e++;
                        }
                        
                        if($i < count($this->joinTable)-1)
                        {
                            $this->sql .= " JOIN ".$this->joinTable[$i+1]." AS ".$this->joinTableAlias[$i+1];       
                                // Verifica si es el ultimo elemento del array.
                                if($i+1 < count($this->joinTable))
                                {
                                    $this->sql .= " ON ".$this->joinTableAlias[$i].".".$this->where[$i+1];                                        
                                }
                                else
                                {
                                    $this->sql .= " WHERE ".$this->joinTableAlias[$i].".".$this->tableAlias;
                                    
                                }
                            $this->sql .= " = ".$this->joinTableAlias[$i+1];
                            $this->sql .= ".".$this->where[$i];    
                        }
                    }    
                }
            }
            else
            {
               $this->sql .= " JOIN ".$this->joinTable." AS ".$this->joinTableAlias;
            }


            if($this->toSearch)
            {
                         
                if($this->where)
                {    
                    if(is_array($this->where))
                    {
                        $this->sql .= " ON tn.id = ".$this->joinTableAlias.".".$this->where[1]." WHERE ";
                    }
                    else
                    {
                        $this->sql .= " ON tn.id = ".$this->joinTableAlias.".".$this->where." WHERE ";                        
                    }
                    // Si es un array incluir alias de campo.
                    if (is_array($this->searchField))
                    {
                        for ($i=0; $i < count($this->searchField); $i++) { 
                            
                            $this->sql .= $this->searchField[$i];
                            $this->sql .= " LIKE '%".$this->toSearch."%'";
                            // Verifica si es el ultimo elemento del array.
                            if($i+1 < count($this->searchField))
                            {
                                $this->sql .= " OR ";
                            }
                            
                        }
                    }
                    else
                    {
                        $this->sql .= $this->joinTableAlias.".".$this->searchField;
                        $this->sql .= " LIKE '%".$this->toSearch."%'";
                    }
                }    
            }                
        }
        
        if(empty($this->toSearch))
        {           
            if ( is_array($this->where) and !is_array($this->joinTableAlias) )
            {
                $this->sql .= " WHERE tn.id = ".$this->joinTableAlias.".".$this->where[1];
            }
            else if (is_array($this->where))
            {
                $this->sql .= " WHERE tn.".$this->where[0]." = ".$this->where[1];
            }            
            
            if($this->filter)
            {
                $this->sql .= " WHERE ".$this->filter;
            }
    
            if($this->value)
            {
                $this->sql .= " =".$this->value;
            }            
        }

        if($this->orderBy)
        {
            $this->sql .= " ORDER BY $this->tableAliasForOrderBy"."$this->orderBy";
        }

        if($this->sort)
        {
            $this->sql .= " $this->sort";
        }

        if( empty($this->counter) )
        {
                        
            if($this->endLimitOrLenght)
            {
                $this->sql .= " LIMIT $this->startPositionOrLenght";
                $this->sql .= ", $this->endLimitOrLenght";
            }
            else
            {
                $this->sql .= " LIMIT $this->startPositionOrLenght";
            }       
        }

        return $this->statement(); 
        // Para visualizar el objeto cambiar por return $this y recibir con var_dump()
        //return $this;               
    }

    // recibe un parametro que indica la cantidad de registros a devolver de la tabla.
    // o un array que indica la posicion inicial que mostrara de la tabla
    // y la cantidad de registros a mostrar 
    // Ejemplo: [5,10] muestra 10 registro empezando en la posicion 5.
    public function limit($startNumber = 1000,$endNumber = null)
    {
        
        $this->startPositionOrLenght = $startNumber;
        $this->endLimitOrLenght = $endNumber;
        return $this;        
    }

    // recibe un array, con el nombre del campo y el valor a buscar.
    // si joinTableAlias es un array, where() recibe un array con  los nombre de campos
    // escritos en el array join()
    public function where($fieldName = null)
    {
        $this->where = $fieldName;
        return $this;        
    }
        
    public function filter($fieldName = null)
    {
        $this->filter = $fieldName;
        return $this;        
    }
    
    public function value($value = null)
    {
        $this->value = $value;
        return $this;        
    }

    // Recibe un un parametro con nombre del campo a ordernar, default sort(DESC)
    // o un array con el nombre del campo y el sort(DESC o ASC)
    // AVISO: escribir el alias del campo si es necesario. (alias.campo) como primer o unico parametro.
    public function orderBy($fieldName = null)
    {
        if(is_array($fieldName))
        {
            $this->orderBy = $fieldName[0];        
            $this->sort(!empty($fieldName[1]) ? $fieldName[1] : 'DESC' );
        }
        else
        {
            $this->orderBy = $fieldName;        
            $this->sort('DESC');
        }
        
        
        return $this;        
    }
    
    public function tableAliasForOrderBy($string = null)
    {
        $this->tableAliasForOrderBy = !empty($string) ? $string.'.' : NULL;
        return $this;        
    }

    // Opcional -> recibe la forma de mostrar los tados DESC o ASC
    // Ejecutar despues de orderBy()
    public function sort($string = null)
    {
        $this->sort = $string;
        return $this;        
    }

    // Devuelve el conteo de los registros encontrados como un string.
    public function counter($value = true)
    {
        $this->counter = $value;
        return $this;        
    }

    // recibe el nombre de la tabla a vincular, un array con las tablas a vincular.
    // si es un array debe utilizar joinTableAlias
    public function join($fieldName = null)
    {
        $this->joinTable = $fieldName;
        return $this;        
    }
    // recibe el alias del campo o un array con los alias de los campos
    // especificados en el array de join()
    public function joinTableAlias($string = 'jt')
    {
        $this->joinTableAlias = $string;
        return $this;        
    }

    public function setFields($fieldName = [])
    {        
        $this->fieldName = func_get_args();                
        return $this;
    }
    
    public function table($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }
    
    public function tableAlias($string = 'tn')
    {
        $this->tableAlias = $string;
        return $this;
    } 

    // Recibe la conexion y el nombre de la tabla a buscar.
    // all() devuelve todas las entidades de una tabla.
    public function all($conn,$tableName = null)
    {
        $this->conn = $conn;
        $this->tableName = $tableName;                    
        
        return $this;               
    }
    // recibe 2 parametros, el primero un string y el segundo un array
    // el primero es el valor a buscar, el segundo es un array que contiene
    // los campos donde buscará el valor del primer parametro.
    public function search($string = NULL,$fieldName = 'titulo_evento')
    {        
        $this->toSearch = $string;
        $this->searchField = $fieldName;
        return $this;               
    }

    public function execute()
    {   
        return $this->selectAll();               
    }

    # --------------------------------------------------
    # Evita que el objeto se pueda clonar
    public function __clone() {
        trigger_error('La clonación de este objeto no está permitida', E_USER_ERROR);
    }
}
