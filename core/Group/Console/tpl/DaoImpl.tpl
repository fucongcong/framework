<?php

namespace src\Dao\{{name}}\Impl;

use Dao;
use src\Dao\{{name}}\{{name}}Dao;

class {{name}}DaoImpl extends Dao implements {{name}}Dao
{
    protected $table = "";

    public function get{{name}}($id)
    {
        $queryBuilder = $this->getDefault()->createQueryBuilder();
        $queryBuilder
            ->select("*")
            ->from($this->table)
            ->where('id = ?')
            ->setParameter(0, $id);
            
        return $queryBuilder->execute()->fetch();
    }

    public function add{{name}}($data)
    {
        $conn = $this->getDefault();
        $affected = $conn->insert($this->table, $data);
        if ($affected <= 0) {
            return fasle;
        }
        return $conn->lastInsertId();
    }

    public function edit{{name}}($id, $data)
    {
        return $this->getDefault()->update($this->table, $data, ['id' => $id]);
    }

    public function search{{name}}(array $conditions, array $orderBy, $start, $limit)
    {
        $conn = $this->getDefault();
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from($this->table)
            ->setFirstResult($start)
            ->setMaxResults($limit);

        if (is_array($orderBy[0])) {
            foreach ($orderBy as $sort) {
                $queryBuilder->addOrderBy($sort[0], $sort[1]);
            }
        } else {
            $queryBuilder->orderBy($orderBy[0], $orderBy[1]);
        }

        $this->search($queryBuilder, $this->getCondition(), $conditions);
        return $queryBuilder->execute()->fetchAll();
    }
    
    public function search{{name}}Count(array $conditions)
    {
        $conn = $this->getDefault();
        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder
            ->select('count(id)')
            ->from($this->table);

        $this->search($queryBuilder, $this->getCondition(), $conditions);

        return $queryBuilder->execute()->fetchColumn(0);
    }

    private function getCondition()
    {
        return [];
    }
}
