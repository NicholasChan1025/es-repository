<?php


namespace Infrastructure\Repository\Contracts;


interface RepositoryInterface
{
    /**
     * @param string $column
     * @param string $key
     * @return mixed|array
     */
    public function lists(string $column, string $key = null);

    /**
     * @param string $column
     * @return mixed
     */
    public function pluck(string $column);

    /**
     * @param string $columns
     * @return mixed|array
     */
    public function all(string $columns = '*');

    /**
     * @param array $numRows
     * @param string $columns
     * @return mixed|array
     */
    public function paginate(array $numRows = null, string $columns = '*');

    /**
     * @param int $id
     * @param string $columns
     * @return mixed
     */
    public function find(int $id, string $columns = '*');

    /**
     * @param string $field
     * @param $value
     * @param string $columns
     * @return mixed|array
     */
    public function findByField(string $field, $value, string $columns = '*');

    /**
     * @param array $where
     * @param string $columns
     * @return mixed
     */
    public function findWhere(array $where, string $columns = '*');

    /**
     * @param string $field
     * @param array $values
     * @param string $columns
     * @return mixed
     */
    public function findWhereIn(string $field, array $values, string $columns = '*');

    /**
     * @param string $field
     * @param array $values
     * @param string $columns
     * @return mixed
     */
    public function findWhereNotIn(string $field, array $values,string $columns = '*');

    /**
     * @param string $field
     * @param array $values
     * @param string $columns
     * @return mixed
     */
    public function findWhereBetween(string $field, array $values,string $columns = '*');

    /**
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes);

    /**
     * @param array $attributes
     * @param int $id
     * @return mixed
     */
    public function update(array $attributes,int $id);

    /**
     * @param int $id
     * @return mixed
     */
    public function delete(int $id);

    /**
     * @param string $column
     * @param string $direction
     * @return mixed
     */
    public function orderBy(string $column,string $direction = 'asc');

}