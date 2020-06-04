<?php

namespace src\Services\{{group}};

interface {{name}}Service
{
    public function get{{name}}($id);
    
    public function add{{name}}($data);

    public function edit{{name}}($id, $data);

    public function delete{{name}}($id);

    public function search{{name}}(array $conditions, array $orderBy, $start, $limit);
    
    public function search{{name}}Count(array $conditions);
}