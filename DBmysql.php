<?php
class DBmysql extends CURD
{
	private static $links = array();//mysql链接数组

    public $dbType = 'read';

	public $_host=''; //数据库所在主机名
    public $_database = '';//当前数据库名
    public $_tablename = '';//当前表的表名
    public $_dt = '';//database.tablename
	public $modelName = ''; //虚拟表名, 对应DBConfig中$TableInfo的键名
    public $isRelease = 0; //查询完成后是否释放
	public $insertId = 0;
	public $affectRows = 0;
	public $custom = FALSE; //是否是直接查询SQL语句, query($sql)

	public $rs;
	public static $data = array(); //被 Data::ini() 使用

	public static $sqls = [];
	public static $currentSql = '';

	//构造函数
    private function __construct($database='', $tablename='', $isRelease=0)
    {
        $this->_database = $database;//数据库名
        $this->_tablename = $tablename;//表名
        $this->_dt = $database.'.'.$tablename;//表名
        $this->isRelease = $isRelease;
    }

	/**
	 * desc 获取链接实例
	 * @param string  $database 数据库名
	 * @param string  $tablename 数据库名
	 * @param int $isRelease 执行完sql语句后是否关闭连接，大并发下需要关闭连接
	 * @return DBmysql|null
	 */
	public static function link($database, $tablename, $isRelease=0)
	{
        return new self($database, $tablename, $isRelease);
	}

	//如果主机没变,并且已经存在MYSQL连接,就不再创建新的连接
	//如果主机改变,就再生成一个实例创建一个连接
    //$type == 'write'或'read'
	private function getConnect($type)
	{
        $this->dbType = $type;

        //随机选取一个数据库连接(区分读写)
        $dbConfig = DBConfig::$$type;
        $randKey = array_rand($dbConfig);
        $config = $dbConfig[$randKey];
        
        //链接数据库
        $this->_host = $host = $config['host'];
        $username = $config['username'];
        $password = $config['password'];
        
		if (empty(self::$links[$host])) {
			
			$mysqli = mysqli_init(); //初始化mysqli
			$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2); //超时2s
			$mysqli->options(MYSQLI_INIT_COMMAND, "set names utf8mb4;"); //支持emoji表情的字符集, 最新版的mysql已设为默认字符集

			if ($mysqli->real_connect($host, $username, $password, $this->_database)) {
				self::$links[$host] = $mysqli;
			} else {
				$this->error(mysqli_connect_error());
			}
		}
	}

	/**
	 * 查询封装
	 * @param string $sql
	 * @return $this
     * @throws Exception
	 */
	public function query($sql='')
	{
		if (!empty($sql)) {
		    $this->custom = TRUE;
			self::$sqls[] = self::$currentSql = $sql;
		} else {
            self::$sqls[] = self::$currentSql = $this->sql();
		}
		
		$sql = strtolower(self::$currentSql);
		$sql = ltrim($sql);
		
		if (strlen($sql) == 0) {
		    $this->error('待执行的SQL语句为空');
        }
		
        if (strpos($sql, 'select') === 0) {
            $this->getConnect('read');//读库
        } else {
            $this->getConnect('write');//写库
        }
        
		$this->clearQueryParam(); //清除查询条件
        
		//执行查询语句
		$this->rs = self::$links[$this->_host]->query(self::$currentSql);
		
		($this->rs === FALSE) && $this->error(self::$links[$this->_host]->error);

		if (strpos($sql, 'replace') === 0) {
			$this->affectRows = self::$links[$this->_host]->affected_rows;

		} elseif (strpos($sql, 'insert') === 0) {
			$this->insertId = self::$links[$this->_host]->insert_id;

		} elseif (strpos($sql, 'delete') === 0) {
			$this->affectRows = self::$links[$this->_host]->affected_rows;

		} elseif (strpos($sql, 'update') === 0) {
			$this->affectRows = self::$links[$this->_host]->affected_rows;
			
		}

		//查询完成后释放链接, 并删除链接对象
		if ($this->isRelease) {
			self::$links[$this->_host]->close();
			unset(self::$links[$this->_host]);
		}

		return $this;
	}

	//一次性获取所有数据到内存
	//如果field不为空，则返回的数组以$field为键重新索引
	public function getAll($field='')
	{
		$rs = [];
		if (empty($field)) {
			return $this->rs->fetch_all(MYSQLI_ASSOC); //该函数只能用于php的mysqlnd驱动
		} else {
			while ($row = $this->rs->fetch_assoc()) {
				$rs[$row[$field]] = $row;
			}
			// $this->rs = $rs;
			return $rs;
		}
	}
	
	//获取一条记录
	public function getOne()
	{
		$rs = $this->rs->fetch_assoc();

		return !empty($rs) ? $rs : [];
	}
    
    /**
     * 获取一条记录的某一个字段的值
     * @param $field
     * @return string
     * @throws Exception
     */
	public function getOneValue($field)
	{
        $rs = $this->rs->fetch_assoc();
        
        if (!empty($rs) && !isset($rs[$field])) {
            $this->error('没有发现字段: '.$field);
        }
		return isset($rs[$field]) ? $rs[$field] : '';
	}

	//获取数据集中所有某个字段的值
	public function getValues($field, $index='')
	{
		$rs = $this->getAll();
		if (!empty($index)) {
			return array_column($rs, $field, $index); //以$index字段的值做索引, 以$field字段的值做值
		} else {
			return array_column($rs, $field);
		}
	}

	//获取总数
	public function getCount()
	{
        $rs = $this->rs->fetch_assoc();
        return isset($rs['SUMMER_N']) ? $rs['SUMMER_N'] : 0;
	}

    //断开数据库连接
    public function close()
    {
		self::$links[$this->_host]->close();
    }
    
    //释放数据
    public function freeResult()
    {
        if ($this->rs instanceof mysqli_result) {
            $this->rs->free_result();
        }
    }

    //事务
    //自动提交开关
    public function autocommit($bool)
    {
		self::$links[$this->_host]->autocommit($bool);
    }
    
    //事务开始
    public function beginTransaction($flag=MYSQLI_TRANS_START_READ_WRITE, $name=null)
    {
        self::$links[$this->_host]->begin_transaction($flag, $name);
    }

    //事务完成提交
    public function commit()
    {
		self::$links[$this->_host]->commit();
    }

    //回滚
    public function rollback()
    {
		self::$links[$this->_host]->rollback();
    }

	//获取当前连接
	public function getCurrentLinks()
	{
		return self::$links;
	}
 
	public function error($str)
    {
        // IError::_SetError($str, 'sql');
        throw new Exception($str.'==sql=='. $this->sql());
    }
}
