<?php
/**
 * Query
 */
class Query
{
    private $_select = array();
    private $_joins = array();
    private $_from = "";
    private $_limit = null;
    private $_start = 0;
    private $_fetch = 0;
    private $_sqlText = "";
    private $_mainTable = "";
    private $_where = null;
    private $_orderBy = null;
    private $_groupBy = null;
    private $_debug = false;

    private function regenerateQuery()
    {
        $this->_select = array();
        $this->_joins = array();
        $this->_from = array();
        $this->_limit = null;
        $this->_start = 0;
        $this->_fetch = 0;
        $this->_sqlText = "";
        $this->_mainTable = "";
        $this->_where = null;
        $this->_orderBy = null;
        $this->_groupBy = null;

    }

    public function limits($limits = null)
    {
        $this->_limit = $limits;
        return $this;
    }

    public function orderBy($orderBy = array())
    {
        $this->_orderBy = $orderBy;
        return $this;
    }

    public function groupBy($groupBy = array())
    {
        $this->_groupBy = $groupBy;
        return $this;
    }

    public function from($from)
    {
        $this->_mainTable = $from;
        return $this;
    }

    public function mainTable($mainTable)
    {
        $this->_mainTable = $mainTable;
        return $this;
    }

    public function where($where)
    {
        $this->_where = $where;
        return $this;
    }

    public function select($selectArr = array())
    {
        $this->regenerateQuery();
        $this->_select = $selectArr;
        return $this;
    }

    public function join($type = "", $table = "", $on = array())
    {
        array_push($this->_joins, array("type" => $type, "table" => $table, "on" => $on));
        return $this;
    }

    public function order($order = array())
    {
        $this->order = $order;
        return $this;
    }

    private function conditions($param = array(), $logic = "and")
    {

        if (is_array($param)) {
            $isMultiDimensional = @is_array($param[0]);

            if ($isMultiDimensional) {
                foreach ($param as $item) {

                    if ($this->_debug) {
                        echo "<pre>";
                        print_r($item);
                        echo "</pre>";
                        exit();
                    }

                    $operator = "=";

                    if(@is_array($item)) {
                        if (isset($item["type"])) {
                            if ($item["type"] == "subset") {
                                if (isset($item["items"])) {
                                    if (is_array($item["items"])) {
                                        $this->_sqlText .= " " . $logic . " ( 1<>1 ";

                                        foreach ($item["items"] as $subsetItem) {
                                            $this->conditions($subsetItem, "or");
                                        }

                                        $this->_sqlText .= ")";

                                        continue;
                                    }
                                }
                            }
                        }

                        if (isset($item["operator"])) {
                            $operator = $item["operator"];
                        }

                        if(isset($item["logic"])) {
                            $logic = $item["logic"];
                        }

                        if(isset($item["logic"])) {
                            $logic = $item["logic"];
                        }

                        $groupStart = isset($item["group_start"]) && $item["group_start"]==true ? ' ( ' : '';
                        $groupEnd = isset($item["group_end"]) && $item["group_end"]==true ? ' ) ' : '';

                        if ($operator == "in" || $operator == "is") {
                            $this->_sqlText .= " " . $logic . $groupStart . " " . $item["column"] . " " . $operator . " " . $item["value"]  .  " " . $groupEnd;
                        } else {
                            $this->_sqlText .= " " . $logic . $groupStart . " " . $item["column"] . $operator . "'" . $item["value"]  . "'" . $groupEnd;
                        }

                    } else {

                        $this->_sqlText .= " " . $item;

                    }

                }

            } else {

                if (count($param)) {
                    $operator = "=";

                    if (isset($param["operator"])) {
                        $operator = $param["operator"];
                    }

                    if(isset($param["logic"])) {
                        $logic = $param["logic"];
                    }

                    $groupStart = isset($item["group_start"]) && $item["group_start"]==true ? ' ( ' : '';
                    $groupEnd = isset($item["group_end"]) && $item["group_end"]==true ? ' ) ' : '';

                    if ($operator == "in" || $operator == "is") {
                        $this->_sqlText .= " " . $logic . $groupStart . " " . $param["column"] . " " . $operator . " " . $param["value"] . $groupEnd . " ";
                    } else {
                        $this->_sqlText .= " " . $logic . $groupStart . " " . $param["column"] . $operator . "'" . $param["value"] . $groupEnd . "'";
                    }
                }
            }
        } else {


            $this->_sqlText .= " " . $param;

        }
    }

    public function build()
    {

        $this->_sqlText = "";
        $this->_sqlText .= "select " . implode(", ", $this->_select);
        $this->_sqlText .= " from " . $this->_mainTable;

        foreach ($this->_joins as $item) {
            $this->_sqlText .= " " . $item["type"] . " join " . $item["table"] . " on " . implode(" and ", $item["on"]);
        }

        $this->_sqlText.=" where 1=1 ";

        $this->_sqlText .= " ";

        $this->conditions($this->_where);

        if ($this->_groupBy) {
            $groupStr = " group by ";
            foreach ($this->_groupBy as $item) {
                $groupStr .= $item . ",";
            }
            $groupStr = substr($groupStr, 0, strlen($groupStr) - 1);
            $this->_sqlText .= $groupStr;
        }

        if ($this->_orderBy) {
            if (!is_array($this->_orderBy[0])) {
                $this->_orderBy = array($this->_orderBy);
            }
            $orderStr = " order by ";
            foreach ($this->_orderBy as $item) {
                $orderStr .= $item["field"] . " " . $item["dir"] . ",";
            }
            $orderStr = substr($orderStr, 0, strlen($orderStr) - 1);
            $this->_sqlText .= $orderStr;
        }

        if ($this->_limit) {
            $this->_sqlText .= " limit " . $this->_limit["start"] . "," . $this->_limit["limit"];
        }
        return $this->_sqlText;
    }
}
?>
