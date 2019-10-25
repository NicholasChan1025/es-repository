<?php


namespace Infrastructure\Repository\Utility;


use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Exception\Exception;
use Infrastructure\Repository\Repository\AbstractRepository;

class PreProcess
{
    public static function mappingWhere(QueryBuilder $builder, $whereVal, AbstractRepository $repository)
    {
        // 处理查询条件
        $primaryKey = $repository->getPrimaryKey();
        if (is_int($whereVal)) {
            if (empty($primaryKey)) {
                throw new Exception('Table not have primary key, so can\'t use Model::get($pk)');
            } else {
                $builder->where($primaryKey, $whereVal);
            }
        } else if (is_string($whereVal)) {
            $whereKeys = explode(',', $whereVal);
            $builder->where($primaryKey, $whereKeys, 'IN');
        } else if (is_array($whereVal)) {
            // 如果不相等说明是一个键值数组 需要批量操作where
            if (array_keys($whereVal) !== range(0, count($whereVal) - 1)) {
                foreach ($whereVal as $whereFiled => $whereProp) {
                    if (is_array($whereProp)) {
                        $builder->where($whereFiled, ...$whereVal);
                    } else {
                        $builder->where($whereFiled, $whereProp);
                    }
                }
            } else {  // 否则是一个索引数组 表示查询主键
                $builder->where($primaryKey, $whereVal, 'IN');
            }
        } else if (is_callable($whereVal)) {
            $whereVal($builder);
        }
        return $builder;
    }

    /**
     * 下划线转驼峰
     * @param $str
     * @return string|string[]|null
     */
    private static function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
            return strtoupper($matches[2]);
        },$str);
        return $str;
    }

    /**
     * 驼峰转下划线
     * @param $str
     * @return string|string[]|null
     */
    private static function humpToLine($str){
        $str = preg_replace_callback('/([A-Z]{1})/',function($matches){
            return '_'.strtolower($matches[0]);
        },$str);
        return $str;
    }

    public static function convertHump(array $data){
        $result = [];
        foreach ($data as $key => $item) {
            if (is_array($item) || is_object($item)) {
                $result[self::convertUnderline($key)] = self::convertHump((array)$item);
            } else {
                $result[self::convertUnderline($key)] = $item;
            }
        }
        return $result;
    }

    public static function convertLine(array $data){
        $result = [];
        foreach ($data as $key => $item) {
            if (is_array($item) || is_object($item)) {
                $result[self::humpToLine($key)] = self::convertLine((array)$item);
            } else {
                $result[self::humpToLine($key)] = $item;
            }
        }
        return $result;
    }


}