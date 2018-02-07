<?php

namespace App\Util;

use Illuminate\Database\Query\Builder as DataBaseQueryBuilder;

class DbTool
{

 /**
     * Get a collection of stdClass containing column name and type from a query builder
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return Illuminate\Support\Collection $columns_with_type
     */
    public static function getTableColumnsFromDBQueryBuilder(DataBaseQueryBuilder $db_query_builder){
        $table_name = $db_query_builder->from;
        $schemaManager = $db_query_builder->getConnection()->getDoctrineSchemaManager();
        $columns = $schemaManager->listTableColumns($table_name);

        $columns_with_type = collect([]);

        // In some cases the schema does not return the columns list (sqlserver views, for example)
        // So, we have to check
        if(count($columns) > 0) {
            foreach ($columns as $column) {
                $cl = new \stdClass();
                $cl->name = $column->getName();
                $cl->type = $column->getType()->getName();
                $columns_with_type->push($cl);
            }
        }
        return $columns_with_type;
    }

     /**
     * Get a text describing the entity properties and the type of each property
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  string $prefix
     * @return string entity desc
     */
    public static function getEntityDesc(DataBaseQueryBuilder $query, $prefix = null ){
        $prefix = isset($prefix)? $prefix : "The available properties are: ";

        // Build a string with the table's <column-name> (datatype) list
        $desc = self::buildColumnsDesc($query);

        // return the prefix and the columns description
        if($desc != ''){
            $desc = $prefix.' '.$desc;
        }
        else{
           $desc = 'It was not possible to retrive the entity properties' ;
        }
        return  $desc;
    }

    /**
     * Get a text listing all the columns, and its types, of a table
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @return string $columnsDesc containing a string listing columns and its types
     */
    private static function buildColumnsDesc(DataBaseQueryBuilder $query){

        $columnsDesc = '';
        $columns = DbTool::getTableColumnsFromDBQueryBuilder($query);

        // In some cases the schema does not return the columns list (sqlserver views, for example)
        // In these cases we can not validate the filters
        if($columns->count() > 0){
            $columns->each(function ($item) use (&$columnsDesc) {
                if($columnsDesc != ''){
                    $columnsDesc .= ', ';
                }
                $columnsDesc .= "$item->name ($item->type)";
            });
        }
        return $columnsDesc;
    }
}
