<?php

/**
 * Class Database
 * Creates a PDO database connection. This connection will be passed into the models (so we use
 * the same connection for all models and prevent to open multiple connections at once)
 */
class Database extends PDO
{

    public $table_name;
    /**
     * Construct this Database object, extending the PDO object
     * By the way, the PDO object is built into PHP by default
     */
    public function __construct()
    {
        /**
         * set the (optional) options of the PDO connection. in this case, we set the fetch mode to
         * "objects", which means all results will be objects, like this: $result->user_name !
         * For example, fetch mode FETCH_ASSOC would return results like this: $result["user_name] !
         * @see http://www.php.net/manual/en/pdostatement.fetch.php
         */
        $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);

        /**
         * Generate a database connection, using the PDO connector
         * @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
         * Also important: We include the charset, as leaving it out seems to be a security issue:
         * @see http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers#Connecting_to_MySQL says:
         * "Adding the charset to the DSN is very important for security reasons,
         * most examples you'll see around leave it out. MAKE SURE TO INCLUDE THE CHARSET!"
         */
        parent::__construct(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS, $options);
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
        //$this->id = $query->lastInsertId();
        return true;
      } else {
        return false;
      } 
    }

    public function update($parameter,$options) {
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

       $option_pairs = array();
        foreach($options as $k => $val) {
          if (!empty($val)) {
          $option_pairs[] = "{$k} = '{$val}'";
          }
        }

        $sql = "UPDATE ".$this->table_name." SET ";
        $sql .= join(", ", $attribute_pairs);
        $sql .= " WHERE ";
        $sql .= join("AND ", $option_pairs);
        $query = $this->prepare($sql);
      return ($query->execute()) ? true : false;

    }


    
    public function delete($db_col,$item) {
        // Don't forget your SQL syntax and good habits:
        // - DELETE FROM table WHERE condition LIMIT 1
        // - escape all values to prevent SQL injection
        // - use LIMIT 1
      $sql = "DELETE FROM ".$this->table_name;
      $sql .= " WHERE {$db_col} = '{$item}'";
      $sql .= " LIMIT 1";
      $query = $this->prepare($sql);
      $query->execute();
      return ($query->rowCount() == 1) ? true : false;
    
        // NB: After deleting, the instance of User still 
        // exists, even though the database entry does not.
        // This can be useful, as in:
        //   echo $user->first_name . " was deleted";
        // but, for example, we can't call $user->update() 
        // after calling $user->delete().
    }
    

    public function exists($db_col, $item){
        $sql = "SELECT * FROM ".$this->table_name." WHERE {$db_col} = '{$item}'";
        $query = $this->prepare($sql);
        $query->execute();
        return $query->rowCount();
    }

     public function count_all() {
      $sql = "SELECT COUNT(*) FROM ".$this->table_name;
      $query = $this->prepare($sql);
      $query->execute();
      $row = $query->fetchAll();
      return array_shift($row);
    }


     public function find($parameter) {
          $type = 'find'; 
          $attribute_pairs = array();
          foreach($parameter as $key => $value) {
            if (!empty($value)) {
            $attribute_pairs[] = "{$key} = '{$value}'";
            }
          }

          $sql = "SELECT * FROM ".$this->table_name." WHERE ";
          $sql .= join("AND ", $attribute_pairs);
          $sql .= " LIMIT 1";
         $result_array =  $this->find_by_sql($type,$sql);
        
         return !empty($result_array) ? $result_array : false;
    }


   public function find_all($order,$p='DESC') {
         $type = 'find_all';
        return $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." ORDER BY $order $p");
    }


     public function find_all_param($parameter, $order, $format ='DESC') {
          $type = 'find_all'; 
          $attribute_pairs = array();
          foreach($parameter as $key => $value) {
            if (!empty($value)) {
            $attribute_pairs[] = "{$key} = '{$value}'";
            }
          }
          $sql = "SELECT * FROM ".$this->table_name." WHERE ";
          $sql .= join("AND ", $attribute_pairs);
          $sql .= " ORDER BY $order $format";
         $result_array =  $this->find_by_sql($type,$sql);
        
         return !empty($result_array) ? $result_array : false;
    }



     public function find_all_pagination($order, $format='DESC', $start=0, $limit=100) {
         $type = 'find_all'; 
         $result_array =  $this->find_by_sql($type,"SELECT * FROM ".$this->table_name." ORDER BY $order $format LIMIT $start, $limit");
         return !empty($result_array) ? $result_array : false;
    }

    public function find_all_search_pagination($parameter, $order, $format ='DESC', $start =0, $limit=100) {
          $type = 'find_all'; 
          $attribute_pairs = array();
          foreach($parameter as $key => $value) {
            if (!empty($value)) {
            $attribute_pairs[] = "{$key} LIKE '%{$value}%'";
            }
          }
          $sql = "SELECT * FROM ".$this->table_name." WHERE ";
          $sql .= join("OR ", $attribute_pairs);
          $sql .= " ORDER BY $order $format LIMIT $start, $limit";
         $result_array =  $this->find_by_sql($type,$sql);
        
         return !empty($result_array) ? $result_array : false;
    }


   public function find_all_pagination_param($parameter, $order, $format ='DESC', $start =0, $limit=100) {
          $type = 'find_all'; 
          $attribute_pairs = array();
          foreach($parameter as $key => $value) {
            if (!empty($value)) {
            $attribute_pairs[] = "{$key} = '{$value}'";
            }
          }
          $sql = "SELECT * FROM ".$this->table_name." WHERE ";
          $sql .= join("AND ", $attribute_pairs);
          $sql .= " ORDER BY $order $format LIMIT $start, $limit";
         $result_array =  $this->find_by_sql($type,$sql);
        
         return !empty($result_array) ? $result_array : false;
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


}
