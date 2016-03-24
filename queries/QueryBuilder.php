<?php
namespace powerorm\queries;


class QueryBuilder extends P_QB{

    public function no_reset_get($table = '', $limit = NULL, $offset = NULL)
    {
        if ($table !== '')
        {
            $this->_track_aliases($table);
            $this->from($table);
        }

        if ( ! empty($limit))
        {
            $this->limit($limit, $offset);
        }

        $result = $this->query($this->_compile_select());

        return $result;
    }
}