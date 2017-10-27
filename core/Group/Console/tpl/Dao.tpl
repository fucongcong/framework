<?php

namespace src\Dao\{{name}};

interface {{name}}Dao
{   
    public function get{{name}}($id);
    
    public function add{{name}}($data);

    public function edit{{name}}($id, $data);

    public function search{{name}}(array $conditions, array $orderBy, $start, $limit);
    
    public function search{{name}}Count(array $conditions);
}

