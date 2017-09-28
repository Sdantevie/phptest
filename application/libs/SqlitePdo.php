<?php
// PDO helper functions.
// Copyright (c) 2012-2013 PHP Desktop Authors. All rights reserved.
// License: New BSD License.
// Website: http://code.google.com/p/phpdesktop/
/**
* 
*/
class SqlitePdo extends PDO
{
    
    public $table_name;

     public function __construct($dsn)
    {
        /**
         * Generate a database connection, using the PDO connector
         * @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
         * Also important: We include the charset, as leaving it out seems to be a security issue:
         * @see http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers#Connecting_to_MySQL says:
         * "Adding the charset to the DSN is very important for security reasons,
         * most examples you'll see around leave it out. MAKE SURE TO INCLUDE THE CHARSET!"
         */
        // parent::__construct("sqlite:".$dsn, $user='', $password='');
        // parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

         $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);
         parent::__construct("sqlite:".$dsn, $user='', $password='', $options);
     }

    public function PDO_FetchOne($query, $params=null)
    {
        if (isset($params)) {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->query($query);
        }
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if ($row) {
            return $row[0];
        } else {
            return false;
        }
    }
 
    public function PDO_FetchRow($query, $params=null)
    {
        
        if (isset($params)) {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->query($query);
        }
        return $stmt->fetch();
    }
    public function PDO_FetchAll($query, $params=null)
    {
      
        if (isset($params)) {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->query($query);
        }
        return $stmt->fetchAll();
    }


    public function PDO_FetchAssoc($query, $params=null)
    {
        
        if (isset($params)) {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->query($query);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
       
    }

    public function PDO_Execute($query, $params=null)
    {
      
        if (isset($params)) {
            $stmt = $this->prepare($query);
            return ($stmt->execute($params))? true : false;
        } else {
            return ($this->query($query))? true : false;
        }

    }

    public function PDO_LastInsertId()
    {
        return $this->lastInsertId();
    }




    public function create($parameter) {
        // Don't forget your SQL syntax and good habits:
        // - INSERT INTO table (key, key) VALUES ('value', 'value')
        // - single-quotes around all values
        // - escape all values to prevent SQL injection
      //$attributes = $this->sanitized_attributes();
      //$array = array_merge($attributes,$app);

        $sql = "INSERT INTO ".$this->table_name." (";
        $sql .= join(", ", array_keys($parameter));
        $sql .= ") VALUES ('";
        $sql .= join("', '", array_values($parameter));
        $sql .= "')";     
        $query = $this->prepare($sql);
        $query->execute();
        $count =  $query->rowCount(); 

      if($count == 1) { 
        $this->id = $this->lastInsertID();
        $feedback = $this->find('id', $this->id);
       
        return $feedback;
      } else {
        return false;
      } 
    }

    public function update($parameter,$db_col,$db_val) { 
        // Don't forget your SQL syntax and good habits:
        // - UPDATE table SET key='value', key='value' WHERE condition
        // - single-quotes around all values
        // - escape all values to prevent SQL injection
        //$attributes = $this->sanitized_attributes();
        //$array = array_merge($attributes,$app);
        $attribute_pairs = array();
        foreach($parameter as $key => $value) {
          if (!empty($value)) {
          $attribute_pairs[] = "{$key}='{$value}'";
          }
        }
        $sql = "UPDATE ".$this->table_name." SET ";
        $sql .= join(", ", $attribute_pairs);
        $sql .= " WHERE {$db_col} = '".strip_tags($db_val)."' ";
        $query = $this->prepare($sql);
      return ($query->execute()) ? $db_val : false;
    }

    public function update_all_except($parameter,$db_col,$db_val) { 
        // Don't forget your SQL syntax and good habits:
        // - UPDATE table SET key='value', key='value' WHERE condition
        // - single-quotes around all values
        // - escape all values to prevent SQL injection
        //$attributes = $this->sanitized_attributes();
        //$array = array_merge($attributes,$app);
        $attribute_pairs = array();
        foreach($parameter as $key => $value) {
          if (!empty($value)) {
          $attribute_pairs[] = "{$key}='{$value}'";
          }
        }

        $values_pairs = array();
        foreach ($db_val as $value) {
          $values_pairs[] = strip_tags($value);
        }
        $sql = "UPDATE ".$this->table_name." SET ";
        $sql .= join(", ", $attribute_pairs);
        $sql .= " WHERE {$db_col} NOT IN (";
        $sql .= join(", ", $values_pairs).")";

        $query = $this->prepare($sql);
      return ($query->execute()) ? $db_val : false;
    }

    public function delete($db_col,$item) {
        // Don't forget your SQL syntax and good habits:
        // - DELETE FROM table WHERE condition LIMIT 1
        // - escape all values to prevent SQL injection
        // - use LIMIT 1
      $sql = "DELETE FROM ".$this->table_name;
      $sql .= " WHERE {$db_col} = '".$item."'";
      return ($this->query($sql))? true : false;
    
    }


    public function delete_all() {
        
      $sql = "DELETE FROM ".$this->table_name;
      return ($this->query($sql))? true : false;
    
    }
    
   
    public function find($db_col, $item){
       $type = 'find';
       $result_array = $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." WHERE {$db_col} = '{$item}' LIMIT 1");
        return !empty($result_array) ? $result_array : false;
    }


    public function find_all($order='id',$sort='DESC') {
         $type = 'find_all';
        return $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." ORDER BY $order $sort");
    }


     public function find_all_except($order='id',$sort='DESC',$db_col,$exclude=array()) {
         $type = 'find_all';

        $values_pairs = array();
         foreach ($exclude as $value) {
          $values_pairs[] = strip_tags($value);
         }
        $sql = "SELECT * FROM ".$this->table_name;
        $sql .= " WHERE {$db_col} NOT IN (";
        $sql .= join(", ", $values_pairs).")";
        $sql .= " ORDER BY $order $sort";

        return $this->find_by_sql($type,$sql);
    }


     public function find_all_limit($start,$limit,$order,$sort='DESC') {
         $type = 'find_all';
        return $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." ORDER BY $order $sort LIMIT $start, $limit");
    }



     public function find_all_col($db_col, $item,$sort='DESC') {
         $type = 'find_all';
        return $this->find_by_sql($type,"SELECT * FROM ".$this->table_name."  WHERE {$db_col} = '{$item}' ORDER BY id $sort");
    }
     public function find_all_col_limit($start,$limit,$db_col, $item,$sort='DESC') {
         $type = 'find_all';
        return $this->find_by_sql($type,"SELECT * FROM ".$this->table_name."  WHERE {$db_col} = '{$item}' ORDER BY id $sort LIMIT $start, $limit");
      }
      public function exists($db_col, $item){
        $sql = "SELECT * FROM ".$this->table_name." WHERE {$db_col} = '{$item}'";
        $query = $this->prepare($sql);
        $query->execute();
        return $query->rowCount();
    }
    public function exists_col($db_col,$db_col2, $item, $item2){
        $sql = "SELECT * FROM ".$this->table_name." WHERE {$db_col} = '{$item}' AND {$db_col2} = '{$item2}'";
        $query = $this->prepare($sql);
        $query->execute();
        return $query->rowCount();
    }
     public function find_by_sql($type,$sql) {
        if ($sql && isset($type)) {
          $query = $this->prepare($sql);
         
          $query->execute();
                switch ($type) {
                    case 'find_all':
                $result = $query->fetchAll();
                        break;
                    
                   case 'find':
                $result = $query->fetch();
                        break;
                }
         return $result;
            } else {
         return false;
          }
        }



public function find_by_three($db_col,$db_col1,$db_col2,$item,$item1,$item2){
    $type = 'find';
    $result_array = $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." WHERE {$db_col} = '{$item}' AND {$db_col1} = '{$item1}' AND {$db_col2} = '{$item2}'");
     return !empty($result_array) ? $result_array : false;
}
public function find_all_by_three($db_col,$db_col1,$db_col2,$item,$item1,$item2,$sort = 'DESC'){
    $type = 'find_all';
    $result_array = $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." WHERE {$db_col} = '{$item}' AND {$db_col1} = '{$item1}' AND {$db_col2} = '{$item2}' ORDER BY id $sort");
     return !empty($result_array) ? $result_array : false;
}
public function find_joint($dbcon2table,$dbcon2table1, $db1_col, $db2_col){
        $type = 'find_all';
        $result_array  = $this->find_by_sql($type, "SELECT * FROM  {$dbcon2table} JOIN {$dbcon2table1} ON {$dbcon2table}.{$db1_col}={$dbcon2table1}.{$db2_col}");
}



public function find_by_two($db_col,$db_col1,$item,$item1,$sort='DESC'){

    $type = 'find_all';
    $result_array = $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." WHERE {$db_col} = '{$item}' COLLATE NOCASE AND {$db_col1} = '{$item1}' COLLATE NOCASE ORDER BY id $sort");
     return !empty($result_array) ? $result_array : false;
}


  public function find_all_by_greater($db_col, $item,$sort='DESC') {
         $type = 'find_all';
        return $this->find_by_sql($type,"SELECT * FROM ".$this->table_name."  WHERE {$db_col} > '{$item}' ORDER BY id $sort");
  }




// public function find_by_five($db_col2,$db_col3,$db_col4,$db_col5,$db_col6,$item2,$item3,$item4,$item5,$item6){
//     $type = 'find';
//     $result_array = $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." WHERE {$db_col3} = '{$item3}'  AND {$db_col4} = '{$item4}'  AND {$db_col5} = '{$item5}'  AND {$db_col6} = '{$item6}'");
//      return !empty($result_array) ? $result_array : false;

// }

public function find_by_four($db_col3,$db_col4,$db_col5,$db_col6,$item3,$item4,$item5,$item6, $sort = 'DESC'){
    $type = 'find_all';
    $result_array = $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." WHERE {$db_col3} = '{$item3}'  AND {$db_col4} = '{$item4}'  AND {$db_col5} = '{$item5}'  AND {$db_col6} = '{$item6}'");
     return !empty($result_array) ? $result_array : false;

}
public function livesearcher($dbcol1,$dbcol2,$dbcol3, $item,$sort ='DESC'){
    $type = 'find_all';
    $result_array = $this->find_by_sql($type, "SELECT *
FROM   ".$this->table_name."
WHERE {$dbcol1} LIKE  '%{$item}%' OR {$dbcol2} LIKE '%{$item}%' OR
       {$dbcol3} LIKE  '%{$item}%' ORDER BY id $sort
    ");
      return !empty($result_array) ? $result_array : false;
}

}

?>