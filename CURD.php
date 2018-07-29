<?php
class CURD
{
    public $tablename = '';//当前表的表名
    public $_dt = '';//当前表的表名
    
    public $fields = '*';
    public $arrWhere = [];
    public $order = '';
    public $arrOrder = [];
    public $limit = '';
    public $groupBy = '';
    public $having = '';
    public $arrUpdate = [];
    
    public $joinField = [];
    public $joinTable = [];
    public $joinOn = [];
    
    public static $sql = '';
    
    /**
     * 查询数据库, 谁包含了本类, 需要重新实现该函数
     */
    protected function query()
    {
        echo 'CURD';
    }
    
    /**
     * 查询语句
     * @param bool $isQuery 组装完sql语句是否立即查询
     * @return $this
     */
    public function select($isQuery=TRUE)
    {
        $where = $this->getWhere();
        $order = $this->getOrder();
        
        self::$sql =  "SELECT {$this->fields} FROM {$this->_dt} {$where} {$this->groupBy} {$this->having} {$order} {$this->limit}";
        
        $isQuery && $this->query();
        return $this;
    }
    
    /**
     * 增, 另注: 主从切换时注意读写权限
     * @param $arrData
     * @param bool $isQuery 组装完sql语句是否立即查询
     * @return $this
     */
    public function insert($arrData, $isQuery=TRUE)
    {
        $this->safe($arrData);
        
        $fields = [];
        $values = [];
        foreach ($arrData as $key=>$value) {
            $fields[] = $key;
            $values[] = !is_string($value) ? $value : "'{$value}'";
        }
        $strFields = implode(',', $fields);
        $strValues = implode(',', $values);
        self::$sql = "INSERT INTO {$this->_dt} ($strFields) VALUES ($strValues)";
        
        $isQuery && $this->query();
        return $this;
    }
    
    /**
     * 增, 注意高并发下不要用 replace into 效率低而且容易死锁
     * @param $arrData
     * @param bool $isQuery 组装完sql语句是否立即查询
     * @return $this
     */
    public function replace($arrData, $isQuery=TRUE)
    {
        $this->safe($arrData);
        foreach ($arrData as $key=>$value) {
            $fields[] = $key;
            $values[] = !is_string($value) ? $value : "'{$value}'";
        }
        $strFields = implode(',', $fields);
        $strValues = implode(',', $values);
        self::$sql = "REPLACE INTO {$this->_dt} ($strFields) VALUES ($strValues)";
        
        $isQuery && $this->query();
        return $this;
    }
    
    /**
     * 每次插入多条记录
     * 每条记录的字段相同,但是值不一样
     * @param $strFields
     * @param $arrData
     * @param bool $isQuery 组装完sql语句是否立即查询
     * @return $this
     */
    public function insertm($strFields, $arrData, $isQuery=TRUE)
    {
        foreach ($arrData as $values) {
            foreach ($values as $k => $v) {
                $values[$k] = !is_string($v) ? $v : "'$v'";
            }
            
            $data[] = '('.implode(',', $values).')';
        }
        
        $strData = implode(',', $data);
        
        self::$sql = "INSERT INTO {$this->_dt} ($strFields) VALUES {$strData}";
        
        $isQuery && $this->query();
        return $this;
    }
    
    /**
     * 删除
     * @param bool $isQuery 组装完sql语句是否立即查询
     * @return $this
     */
    public function delete($isQuery=TRUE)
    {
        $where = $this->getWhere();
        if (empty($where)) {
            $this->error('删除时where条件不能为空!');
        }
        
        self::$sql = "DELETE FROM {$this->_dt} {$where} {$this->limit}";
        
        $isQuery && $this->query();
        return $this;
    }
    
    /**
     * 改, 组装update语句
     * @param bool $isQuery  组装完sql语句是否立即查询
     * @return $this
     */
    public function update($isQuery=TRUE)
    {
        $where = $this->getWhere();
        if (empty($where)) {
            $this->error('更新时where条件不能为空!');
        }
        
        $strSql = implode(',', $this->arrUpdate);
        
        self::$sql = "UPDATE {$this->_dt} set {$strSql} {$where} {$this->limit}";
        
        $isQuery && $this->query();
        return $this;
    }
    
    //改, 自定义set语句
    public function addUpdate($str)
    {
        $this->arrUpdate[] = $str;
        return $this;
    }
    
    //改, 重新复制: a = 1, a = 'b'
    public function updateVal($arrData)
    {
        foreach ($arrData as $field => $v) {
            if (is_scalar($v)) {
                $this->arrUpdate[] = !is_string($v) ? "{$field} = $v" : "{$field} = '$v'";
            } else {
                $this->error('字段: '.$field.'的值不是数字或字符串');
            }
            
        }
        return $this;
    }
    
    //改, 加法: a = a + 1
    //$arrData  = array(['a', 'a', 1], ['b', 'c', 1])
    public function updateInc($arrData)
    {
        $where = $this->getWhere();
        if (empty($where)) {
            $this->error('更新时where条件不能为空!');
        } else {
            foreach ($arrData as $k => $row) {
                if (!is_array($row) || count($row) != 3) {
                    $this->error("第 {$k} 项应该是数组, 并且其元素个数应为3个!");
                } else {
                    list($targetField, $sourceField, $numeric) = $row;
                    if (!is_numeric($numeric)) {
                        $this->error("{$targetField} = {$sourceField} + {$numeric} 中值不是数字");
                    } else {
                        $this->arrUpdate[] = "{$targetField} = {$sourceField} + {$numeric}";
                    }
                }
            }
        }
        return $this;
    }
    
    //改, 减法: a = a - 1
    //$arrData  = array(['a', 'a', 1], ['b', 'c', 1])
    public function updateDec($arrData)
    {
        $where = $this->getWhere();
        if (empty($where)) {
            $this->error('更新时where条件不能为空!');
        } else {
            foreach ($arrData as $k => $row) {
                if (!is_array($row) || count($row) != 3) {
                    $this->error("第 {$k} 项应该是数组, 并且其元素个数应为3个!");
                } else {
                    list($targetField, $sourceField, $numeric) = $row;
                    if (!is_numeric($numeric)) {
                        $this->error("{$targetField} = {$sourceField} - {$numeric} 中值不是数字");
                    } else {
                        $this->arrUpdate[] = "{$targetField} = {$sourceField} - {$numeric}";
                    }
                }
            }
        }
        return $this;
    }
    
    //改, 乘法: a = a * 1
    //$arrData  = array(['a', 'a', 1], ['b', 'c', 1])
    public function updateMul($arrData)
    {
        $where = $this->getWhere();
        if (empty($where)) {
            $this->error('更新时where条件不能为空!');
        } else {
            foreach ($arrData as $k => $row) {
                if (!is_array($row) || count($row) != 3) {
                    $this->error("第 {$k} 项应该是数组, 并且其元素个数应为3个!");
                } else {
                    list($targetField, $sourceField, $numeric) = $row;
                    if (!is_numeric($numeric)) {
                        $this->error("{$targetField} = {$sourceField} * {$numeric} 中值不是数字");
                    } else {
                        $this->arrUpdate[] = "{$targetField} = {$sourceField} * {$numeric}";
                    }
                }
            }
        }
        return $this;
    }
    
    //改, 除法: a = a / 1
    //$arrData  = array(['a', 'a', 1], ['b', 'c', 1])
    public function updateDiv($arrData)
    {
        $where = $this->getWhere();
        if (empty($where)) {
            $this->error('更新时where条件不能为空!');
        } else {
            foreach ($arrData as $k => $row) {
                if (!is_array($row) || count($row) != 3) {
                    $this->error("第 {$k} 项应该是数组, 并且其元素个数应为3个!");
                } else {
                    list($targetField, $sourceField, $numeric) = $row;
                    if (!is_numeric($numeric)) {
                        $this->error("{$targetField} = {$sourceField} / {$numeric} 中值不是数字");
                    } else {
                        $this->arrUpdate[] = "{$targetField} = {$sourceField} / {$numeric}";
                    }
                }
            }
        }
        return $this;
    }
    
    //改, 求余: a = a % 1
    //$arrData  = array(['a', 'a', 1], ['b', 'c', 1])
    public function updateMod($arrData)
    {
        $where = $this->getWhere();
        if (empty($where)) {
            $this->error('更新时where条件不能为空!');
        } else {
            foreach ($arrData as $k => $row) {
                if (!is_array($row) || count($row) != 3) {
                    $this->error("第 {$k} 项应该是数组, 并且其元素个数应为3个!");
                } else {
                    list($targetField, $sourceField, $numeric) = $row;
                    if (!is_numeric($numeric)) {
                        $this->error("{$targetField} = {$sourceField} % {$numeric} 中值不是数字");
                    } else {
                        $this->arrUpdate[] = "{$targetField} = {$sourceField} % {$numeric}";
                    }
                }
            }
        }
        return $this;
    }
    
    /**
     * 获取总数
     * @param bool $isQuery  组装完sql语句是否立即查询
     * @return $this|string
     */
    public function count($isQuery=TRUE)
    {
        $where = $this->getWhere();
        self::$sql = "SELECT COUNT(1) AS SUMMER_N FROM {$this->_dt} {$where}";
        
        if ($isQuery) {
            $this->query();
            return $this->getCount();
        } else {
            return $this;
        }
    }
    
    /**
     * 需要被子类实现的方法, 获取查询后的count值
     * @return string
     */
    protected function getCount()
    {
        return 'parent::getCount()';
    }
    
    /**
     * 改写select in 为 between and
     * @param bool $isQuery 组装完sql语句是否立即查询
     * @return $this
     */
    public function selectIn($isQuery=TRUE)
    {
        //取出where条件中的in语句
        $wherein = '';
        foreach ($this->arrWhere as $k => $v) {
            if (strpos($v, 'IN')) {
                $wherein = $v;
                unset($this->arrWhere[$k]);
                break;
            }
        }
        
        //整理数据
        list($field, $ids) = explode('IN', $wherein);
        $field = trim($field,'( '); //去掉括号和空格
        $ids = trim($ids,'() '); //去掉括号和空格
        $ids = str_replace(' ', '', $ids);
        
        $arrId = explode(',', $ids);
        $arrId = array_filter($arrId);
        $arrId = array_unique($arrId);
        
        //分组
//		sort($arrId);
        $len = count($arrId);
        $group = 0;
        
        $new = array(array($arrId[0]));
        for ($i = 1; $i < $len; $i++) {
            if (($arrId[$i] - $arrId[$i-1]) == 1 ) { //连续的整数
                $new[$group][] = $arrId[$i];
            } else {
                $group = $i;
                $new[$group][] = $arrId[$i];
            }
        }
        
        $where = $this->getWhere();
        $order = $this->getOrder();
        
        $where = strlen($where) ? $where : 'WHERE 1=1';
        $arrSql = array();
        foreach ($new as $v) {
            if (count($v) > 1) {
                $start = reset($v);
                $end = end($v);
                $tmp = $where." AND ($field BETWEEN $start AND $end)";
                $sql = "(SELECT {$this->fields} FROM {$this->_dt} {$tmp} {$this->groupBy} {$this->having} {$order} {$this->limit})";
            } else {
                $start = reset($v);
                $tmp = $where. " AND ($field = $start)"; //默认为where条件中的值为数值型
                $sql = "(SELECT {$this->fields} FROM {$this->_dt} {$tmp} {$this->groupBy} {$this->having} {$order} {$this->limit})";
            }
            
            $arrSql[] = $sql;
        }
        
        self::$sql = implode(' UNION ALL ', $arrSql);
        
        $isQuery && $this->query();
        return $this;
    }
    
    //where
    public function where($arrData)
    {
        if (empty($arrData)) {
            return $this;
        }
        
        $this->safe($arrData);
        
        foreach ($arrData as $k => $v) {
            if (is_null($v) || is_bool($v) || is_object($v) || is_array($v)) {
                $this->error("第 {$k} 个值的数据类型不对!");
                unset($arrData[$k]);
            } else {
                $str = !is_string($v) ? "({$k} = {$v})" : "({$k} = '{$v}')";
                $this->addWhere($str);
            }
        }
        
        return $this;
    }
    
    //where in
    public function whereIn($key, $arrData)
    {
        if (empty($arrData)) {
            $str = "({$key} IN (0))";
            $this->addWhere($str);
        }
        
        $this->safe($arrData);
        
        $arrData = array_unique($arrData);

//		sort($arrData);
        
        foreach ($arrData as $k => $v) {
            if (is_null($v) || is_bool($v) || is_object($v) || is_array($v)) {
                $this->error("第 {$k} 个值的数据类型不对!");
                unset($arrData[$k]);
            } else {
                $arrData[$k] = !is_string($v) ? $v : "'{$v}'";
            }
        }
        
        $strData = implode(',', $arrData);
        
        $this->addWhere("({$key} IN ( {$strData} ))");
        
        return $this;
    }
    
    //between and
    public function whereBetween($key, $min, $max)
    {
        $this->safe($min);
        $this->safe($max);
        
        $min = !is_string($min) ? $min : "'{$min}'";
        $max = !is_string($max) ? $max : "'{$max}'";
        
        $str = "({$key} BETWEEN {$min} AND {$max})";
        $this->addWhere($str);
        return $this;
    }
    
    //where a>b
    public function whereGT($key, $value)
    {
        $this->safe($value);
        $str = !is_string($value) ? "({$key} > {$value})" : "({$key} > '{$value}')";
        
        $this->addWhere($str);
        return $this;
    }
    
    //where a<b
    public function whereLT($key, $value)
    {
        $this->safe($value);
        $str = !is_string($value) ? "({$key} < {$value})" : "({$key} < '{$value}')";
        
        $this->addWhere($str);
        return $this;
    }
    
    //where a>=b
    public function whereGE($key, $value)
    {
        $this->safe($value);
        $str = !is_string($value) ? "({$key} >= {$value})" : "({$key} >= '{$value}')";
        
        $this->addWhere($str);
        return $this;
    }
    
    //where a<=b
    public function whereLE($key, $value)
    {
        $this->safe($value);
        $str = !is_string($value) ? "({$key} <= {$value})" : "({$key} <= '{$value}')";
        
        $this->addWhere($str);
        return $this;
    }
    
    //添加自定义where条件
    public function addWhere($str)
    {
        $this->arrWhere[] = $str;
        return $this;
    }
    
    //获取最终查询用的where条件
    public function getWhere()
    {
        if (!empty($this->arrWhere)) {
            return 'WHERE '.implode(' AND ', $this->arrWhere);
        } else {
            return '';
        }
    }
    
    //以逗号隔开
    public function fields($fields)
    {
        $this->safe($fields);
        $this->fields = $fields;
        return $this;
    }
    
    // order by a desc
    public function order($order)
    {
        $this->arrOrder[] = $order;
        return $this;
    }
    
    //获取order语句
    public function getOrder()
    {
        if (empty($this->arrOrder)) {
            return '';
        } else {
            $str = implode(',', $this->arrOrder);
            $this->order = "ORDER BY {$str}";
        }
        return $this->order;
    }
    
    public function groupBy($str)
    {
        $this->groupBy = "GROUP BY {$str}";
        return $this;
    }
    
    public function having($str)
    {
        $this->having = "HAVING {$str} ";
    }
    
    //e.g. '0, 10'
    //用limit的时候可以加where条件优化：select ... where id > 1234 limit 0, 10
    public function limit($limit)
    {
        $this->safe($limit);
        $this->limit = 'LIMIT '.$limit;
        return $this;
    }
    
    /**
     * 组装最终的left join 语句
     * @param bool $isQuery 组装完sql语句是否立即查询
     * @return $this
     */
    public function join($isQuery=TRUE)
    {
        $where = $this->getWhere();
        $order = $this->getOrder();
        $joinFields = $this->getJoinFields();
        $joinTable = $this->getJoinTable();
        
        self::$sql = "SELECT {$joinFields} FROM {$this->_dt} {$joinTable} {$where} {$this->groupBy} {$this->having} {$order} {$this->limit}";
        
        $isQuery && $this->query();
        return $this;
    }
    
    /**
     * 连接查询, 设置查询字段
     * 可多次调用
     * @param string $table 表名
     * @param string $fields 该表的字段
     * @return $this
     */
    public function joinFields($table, $fields)
    {
        $fields = str_replace(' ', '', $fields);
        $fields = explode(',', $fields);
        foreach ($fields as $k => $v) {
            $fields[$k] = $table.'.'.$v;
        }
        $this->joinField[] = implode(',', $fields);
        
        return $this;
    }
    
    /**
     * 组装要查询的字段
     * @return string
     */
    public function getJoinFields()
    {
        return implode(', ', $this->joinField);
    }
    
    /**
     * 组装 left join .. on ..
     * @param string $table1 左表的表名
     * @param string $field1 关联字段
     * @param string $table2 右表的表名
     * @param string $field2 关联字段
     * @param string $joinMethod join方式, 默认LEFT, 还可以是 RIGHT, INNER
     * @return $this
     */
	public function joinTable($table1, $field1, $table2, $field2, $joinMethod = 'LEFT')
	{
        $str = $joinMethod. " JOIN {$table2} ON {$table1}.{$field1} = {$table2}.{$field2}";
        $this->joinTable[] = $str;
        
        return $this;
    }
    
    public function getJoinTable()
    {
        return implode(' ', $this->joinTable);
    }
    
    public function sql()
    {
        return self::$sql;
    }
    
    public function safe(&$value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (!is_numeric($v)) {
                    $value[$k] = addslashes($v);
                }
            }
        } elseif (!is_numeric($value)) {
            $value = addslashes($value);
        }
        
    }
    
    public function clearQueryParam()
    {
        $this->arrWhere = [];
        $this->order = '';
        $this->arrOrder = [];
        $this->limit = '';
        $this->groupBy = '';
        $this->having = '';
        $this->joinField = [];
        $this->joinTable = [];
        $this->joinOn = [];
    }
    
    public function error($str)
    {
        // $this->error = $str;
        
        throw new Exception($str.'@=@'.self::$sql);
    }
}
