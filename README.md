# SUMMER-SQL 构建SQL语句

> 可以用来链式组装SQL语句的类

* 用法参考: 

> select

1. 生成SQL语句, 不执行
$rs = Test::link('user')->fields('uid,username')
    ->whereGE('uid', 1)
    ->order('uid desc')
    ->select() //此步生成SQL语句, 并执行sql查询, 如果传入false就不在执行查询操作
    ->sql() //返回SQL语句, 但不执行
2. 执行SQL语句, 获取一条记录
获取一条记录,所有字段
$rs = Test::link('user')
        ->whereGE('uid', 1)
        ->order('uid desc')
        ->limit(1)
        ->select()
        ->getOne();
        //SELECT * FROM test.user WHERE (uid >= 1)   ORDER BY uid desc LIMIT 1
        echo '<pre>';var_dump($rs, Test::$sql); exit();
获取一条记录的部分字段
$rs = Test::link('user')
            ->fields('uid, username') //指定字段
            ->whereGE('uid', 1)
            ->limit(1)
            ->select()
            ->getOne();
        //SELECT uid, username FROM test.user WHERE (uid >= 1)    LIMIT 1
        echo '<pre>';var_dump($rs, Test::$sql); exit();
获取一条记录的指定字段的值
$rs = Test::link('user')
            ->whereGE('uid', 1)
            ->limit(1)
            ->select()
            ->getOneValue('username');
        //SELECT * FROM test.user WHERE (uid >= 1)    LIMIT 1
        echo '<pre>';var_dump($rs, Test::$sql); exit();
3. 获取多条记录
例子
$rs = Test::link('user')
            ->fields('age,username')
            ->whereGT('uid', 1)
            ->order('uid desc')
            ->limit(10)
            ->select()
            ->getAll();
        //SELECT age,username FROM test.user WHERE (uid > 1)   ORDER BY uid desc LIMIT 10
        echo '<pre>';var_dump($rs, Test::$sql); exit();
还有一个类似的 selectIn() 方法, 它可以配合 whereIn() 使用
where语句
调用方法	解释	参数解释	用法举例
where	等于	$arrData: 一维关联数组	where(['uid' => 1, 'age' => 20])
whereGT	大于	$key: 字段名, $value: 值	whereGT('id', 1)
whereGE	大于等于	$key: 字段名, $value: 值	whereGE('id', 1)
whereLT	小于	$key: 字段名, $value: 值	whereLT('id', 1)
whereLE	小于等于	$key: 字段名, $value: 值	whereLE('id', 1)
whereBetween	between and	$key: 字段名, $min: 最小值, $max: 最大值	whereBetween('id', 1, 10)
whereIn	where in	$key: 字段名, $arrData: 一维索引数组	whereIn('id', [1,2,3,4,5,7,9])
addWhere	添加自定义where条件	$str: where条件, 字符串类型	addWhere('a>1'), 注意此方法不会做安全校验
where in
$rs = Test::link('note')
    ->fields('id,content')
    ->whereGE('id', 2)
    ->whereIn('id', [1,2,3,4,5,7,9])
    ->select()
    ->getAll();
//SELECT id,content FROM tiezi WHERE (id >= 2) AND (id IN ( 1,2,3,4,5,7,9 ))
where in 变换
$rs = Test::link('user')
            ->fields('uid,username')
            ->whereIn('uid', [1,2,3,4,5,7])
            ->selectIn() //改写select in, 将数字排序并写成between and 最后用 unin all 联结
            ->getAll();
        //(SELECT uid,username FROM test.user WHERE 1=1 AND (uid BETWEEN 1 AND 5)) UNION ALL (SELECT uid,username FROM test.user WHERE 1=1 AND (uid = 7))

        echo '<pre>';var_dump($rs, Test::$sql, Timer::$list);
selectIn() 跟 whereIn() 方法配合使用会改变最终的SQL语句
是这样改写的:
将 in 中的数字排序
将连续的值改写成 between and 并分组
将分组用 union all 联结
返回结果集中age字段的所有值, 一维数组形式
$rs = Test::link('user')
            ->fields('age, uid')
            ->whereGE('uid', 1)
            ->limit(10)
            ->select()
            ->getValues('age');
        //SELECT age, uid FROM test.user WHERE (uid >= 1)    LIMIT 10
        echo '<pre>';var_dump($rs, Test::$sql); exit();
结果:
Array
(
    [0] => 20
    [1] => 21
    [2] => 22
)
以字段username的值做索引, 以age字段的值做值, 返回关联数组
$rs = Test::link('user')
            ->fields('age,username')
            ->whereGE('uid', 3)
            ->limit(10)
            ->select()
            ->getValues('age', 'username');
        //SELECT id,content FROM tiezi WHERE (id >= 1) LIMIT 10
        echo '<pre>';var_dump($rs, Test::$sql); exit();
结果:
Array
(
    [张三] => 20
    [李四] => 21
    [王五] => 22
)
获取记录总数
$rs = Test::link('user')
            ->whereGE('age', 10)
            ->count();
        //SELECT COUNT(1) AS SUMMER_N FROM test.user WHERE (age >= 10)
        echo '<pre>';var_dump($rs, Test::$sql); exit();


> delete from

删除
$affectRows = Test::link('user')
            ->where(array('uid' => 5))
            ->delete()
            ->affectRows;
        //DELETE FROM test.user WHERE (uid = 5)
        echo '<pre>';var_dump($affectRows, Test::$sql); exit();
当这样调用: delete(false) 时, delete()方法只是组装SQL语句, 并不会去连接数据库进行查询操作

> insert into

插入并返回id
$insertId = Test::link('user')
        ->insert(['username' => '王五', 'age' => 18])
        ->insertId;
        //INSERT INTO test.user (username,age) VALUES ('王五',18)
        echo '<pre>';var_dump($insertId, Test::$sql); exit();
当这样调用: insert($arr, false) 时, insert()方法只是组装SQL语句, 并不会去连接数据库进行查询操作

一次插入多条记录
$insertId = Test::link('user')
            ->insertm('username, age', array(array('李四','19'), array('赵六', '16')))
            ->insertId;
        //INSERT INTO test.user (username, age) VALUES ('李四','19'),('赵六','16')
        //此时 insertId是'李四'的自增id
        echo '<pre>';var_dump($insertId, Test::$sql); exit();
当这样调用: insertm($str, $arr, false) 时, insertm()方法只是组装SQL语句, 并不会去连接数据库进行查询操作

> replace into

$insertId = Test::link('user')
            ->replace(['uid' => 5, 'username' => 'hello'])
            ->insertId;
        //REPLACE INTO test.user (uid,username) VALUES (5,'hello')
        //如果之前存在uid = 5的一条纪录, 此时insertId = 0; affectRows = 1
        //因为插入的数据没有age, 因此sql执行后age变为0
        echo '<pre>';var_dump($insertId, Test::$sql); exit();
当这样调用: replace($arr, false) 时, replace()方法只是组装SQL语句, 并不会去连接数据库进行查询操作

> update

1. 直接更新字段值, 没有算数表达式
生成SQL语句, 不执行
$rs = Test::link('user')
            ->where(['uid' => 2])
            ->updateVal(['username' => '张三'])
            ->updateVal(['mobile' => '13100000000']) //此时mobile在最终的SQL中'会'加上引号
            ->updateVal(['status' => 1]) //此时statys在最终的SQL中'不会'加上引号
            ->update(false)
            ->sql();

// //UPDATE test.user set username = '张三',mobile = '13100000000',status = 1 WHERE (uid = 2)
update() 传入的参数是false, 此时并不会执行SQL语句, 只是组装SQL, sql()方法返回该sql

生成并执行SQL语句, 返回影响的行数
$rs = Test::link('user')
            ->where(['uid' => 2])
            ->updateVal(['username' => '张三'])
            ->updateVal(['mobile' => '13100000000']) //此时mobile在最终的SQL中'会'加上引号
            ->updateVal(['status' => 1]) //此时statys在最终的SQL中'不会'加上引号
            ->update()
            ->affectRows;
        //UPDATE test.user set username = '张三',mobile = '13100000000',status = 1 WHERE (uid = 2)
        echo '<pre>';var_dump($rs, Test::$sql); exit();
此时才会去链接数据库并查询SQL语句

2. 使用算数表达式
加
$affectRows = Test::link('user')
            ->where(['uid' => 1])
            ->updateVal(['age' => 20])
            ->updateInc([['a', 'a', 1], ['a', 'b', 2]])
            ->update()
            ->affectRows;
// UPDATE user set age = 20,a = a + 1,a = b + 2 WHERE (uid = 1)            
减
$affectRows = Test::link('user')
            ->where(['uid' => 1])
            ->updateVal(['age' => 20])
            ->updateDec([['a', 'a', 1], ['a', 'b', 2]])
            ->update()
            ->affectRows;
// UPDATE user set age = 20,a = a - 1,a = b - 2 WHERE (uid = 1)
乘
$affectRows = Test::link('user')
            ->where(['uid' => 1])
            ->updateVal(['age' => 20])
            ->updateMul([['a', 'a', 1], ['a', 'b', 2]])
            ->update()
            ->affectRows;
// UPDATE user set age = 20,a = a * 1,a = b * 2 WHERE (uid = 1)            
除
$affectRows = Test::link('user')
            ->where(['uid' => 1])
            ->updateVal(['age' => 20])
            ->updateDiv([['a', 'a', 1], ['a', 'b', 2]])
            ->update()
            ->affectRows;
// UPDATE user set age = 20,a = a / 1,a = b / 2 WHERE (uid = 1)             
求余
$affectRows = Test::link('user')
            ->where(['uid' => 1])
            ->updateVal(['age' => 20])
            ->updateMod([['a', 'a', 1], ['a', 'b', 2]])
            ->update()
            ->affectRows;
// UPDATE user set age = 20,a = a % 1,a = b % 2 WHERE (uid = 1)
3. 自定义set语句
$affectRows = Test::link('user')
            ->where(['uid' => 1])
            ->updateVal(['age' => 21])
            ->addUpdate('age = age + age')
            ->update()
            ->affectRows;
        //UPDATE test.user set age = 21,age = age + age WHERE (uid = 1)
        echo '<pre>';var_dump($affectRows, Test::$sql); exit();

查看最后一次执行的SQL语句
var_dump(Test::$sql); //最后一次执行的SQL语句
查看执行过的SQL语句
var_dump(Test::$sqls); //所有执行的SQL语句
查看SQL语句的执行时间
var_dump(Timer::$list); //SQL语句所占用的时间

对查询出的数据, 用PHP自带函数后续处理 PHP7+
因为PHP7更新了词法分析器, 我们在自己的类中也可以定义跟PHP自带函数同名的函数了

举例: 获取user表中所有的用户, 并计算他们年龄的和
Test::link('user')
            ->whereGT('uid', 0)     //where 条件
            ->select()              //生成SQL语句
            ->data()                //查询数据, 并初始化 Data 类
            ->array_column('age')   //获取所有的age数据, PHP自带函数 
            ->array_sum()           //求和, PHP自带函数
            ->pre();                //格式化输出到浏览器, 或者换成 ->data; 就可以获取最终结果
注:
可以通过 Test::$data 来获取从数据库里取出的原始数据(关联数组)
适合那些第一个参数为数据的PHP自带函数, 比如array_walk()可以, 但是array_map()就不适合链式调用
对于第一个参数不是数据的函数, 可以在Data类中重写该函数使之可以参与链式调用(参考对 array_map() 的重写)
直接执行SQL语句的方法也适用
$sql = 'select * from user';
Test::link('user')
            ->query($sql)
            ->data()
            ->array_column('age', 'username')
            ->array_sum()
            ->pre();
非PHP自带函数
取出某个值
Test::link('user')
            ->fields('age, username')
            ->whereGT('uid', 0)
            ->select()
            ->data()
            ->reset()          // 取出第一条记录, PHP自带函数
            ->get('age', 20);  // 取出第一条记录的age值, 默认值是20
对结果集分组 (非PHP自带函数)
// 对学生按照年龄分组
Test::link('user')
            ->fields('age, username')
            ->whereGT('uid', 0)
            ->select()
            ->data()
            ->group('age')
            ->pre();
结果:
Array
(
    [20] => Array
        (
            [0] => Array
                (
                    [age] => 20
                    [username] => 张三
                )

        )

    [21] => Array
        (
            [0] => Array
                (
                    [age] => 21
                    [username] => 李四
                )

        )

    [22] => Array
        (
            [0] => Array
                (
                    [age] => 22
                    [username] => 王五
                )

            [1] => Array
                (
                    [age] => 22
                    [username] => 赵六
                )

        )

)

> 数据库配置

配置文件位置
/config/pro(dev)/DBConfig.php

链接MySQL不同的主机, 读写分离
链接数据库的时候, 会根据SQL语句中是否有 select 来判断是读还是写操作, 然后到对应的数组里随机选取一个链接配置
public static $write = array(
    array(
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    )

);

public static $read = array(
    array(
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    )
);
选择不同的数据库, 表
数组$TableInfo的每一项的值都指定了数据库名字和表的名字
而键名是程序中使用的 模型名 或叫做 隔离表名 或叫做 虚拟表名
键名可以使用正则表达式, 便于分库分表的使用
//虚拟表名 => 数据库名, 表名
//最好将所有model在此备案, 方便管理
public static $TableInfo = array(
    'user'          => 'test, user',
    'note'          => 'test, tiezi',
    'pinyin'          => 'test, pinyin',
    'test_(\d+)_(\d+)' => 'test_$1, test_$2'
);
虚拟表名
下边代码说明, 程序会去$TableInfo中找到键为 user 的项, 然后从值中找到数据库 test 和表user
Test::link('user')->select()->getAll();
分库分表
下边代码说明, 程序会去$TableInfo中找到键匹配test_10_10的项, 然后从值中找到数据库test_10和表test_10
不局限于例子中的正则表达式, 可以按需要自己组织
Test::link('user_10_10')->select()->getAll();

> 直接执行SQL语句

$sql = 'select * from user where uid = 1';

$rs = Tset::link('user')
    ->query($sql)
    ->getAll();

$rs = Tset::link('user')
    ->query($sql)
    ->getValues('username');    

$rs = Tset::link('user')
    ->query($sql)
    ->getone();

$rs = Tset::link('user')
    ->query($sql)
    ->getOneValue('age');      

> left join

例子, 查询一个用户的角色
$rs = Test::link('user')
            ->joinFields('user', 'uid, username')
            ->joinFields('role', 'name')
            ->joinTable('user', 'uid', 'role_bind', 'uid', 'LEFT')
            ->joinTable('role_bind', 'roleid', 'role', 'id')
            ->whereGT('user.uid', 0)
            ->limit('5')
            ->order('user.uid asc')
            ->join()
            ->getAll();
        echo '<pre>';var_dump($rs, Test::$sql); exit();

SELECT user.uid,user.username, role.name 
FROM user 
LEFT JOIN role_bind ON user.uid = role_bind.uid 
LEFT JOIN role ON role_bind.roleid = role.id 
WHERE (user.uid > 0)   
ORDER BY user.uid asc LIMIT 5        
结果:
+-----+----------+------+
| uid | username | name |
+-----+----------+------+
|   1 | 张志斌   | 超管 |
|   2 | aaa      | NULL |
|   3 | bbb      | NULL |
+-----+----------+------+
相关方法说明
方法名	解释	参数说明
joinFields	需要查询的字段	一共2个参数: 
$table: 表名 
$fields: 要查询的字段列表(用逗号隔开)
joinTable	需要查询的表,以及关联字段	一共5个参数: 
$table1:表1 
$field1:表1中用来关联的字段名 
$table2: 表2 
$field2: 表2中用来关联的字段名 
$joinMethod: 联结的方法(left, right, inner 不区分大小写, 默认为left)
join	生成SQL语句, 用于执行	一个参数: 
$isQuery: true, 组装SQL语句并查询数据库 
false, 只组装SQL语句, 并不去连接数据库查询
注, 可以调用多次 joinTable 函数进行多个表联结

#### 说明

> 这里的设计原则是, 先组装SQL语句, 然后由用户选择是否执行 <br>
> 所以在写代码的时候会多一步调用, 调用那些专门组装生成SQL语句的函数

#### 继承关系

1. CRUD.php 这个类是用来组装SQL语句的, 他并不会连接数据库进行查询操作
2. DBMysql.php 这个类继承了CRUD, 他会去连接并查询数据库, 并将数据简单处理

#### 执行流程

1. 根据不同的方法调用, 利用CURD类的相关方法去组装SQL语句
2. 如果你直接或间接调用了query()函数, 这时才会去真正的连接数据库并查询, 否则, 只是组装SQL语句, 此时可以用sql()函数去获取这个SQL语句
3. 如果你选择执行SQL, 那么, 程序会根据配置文件找到数据库名和表名去连接数据库, 并判断是连接读库还是写库
4. 执行SQL语句, 记录执行过的SQL语句, 并将执行的结果数据保存或转换成数组


#### query()

1. 成员函数中的 insert(); delete(); update(); select(); 他们只是组装SQL语句, 并不会去真正执行
2. 而query(), 或者封装调用了query()的 getOne(); getAll(); getFields(); getCount(); 等这些函数才会去真正的执行查询语句, 并对结果进行简单处理

#### 数据添加单引号的规则

```
$rs = Test::link('user')
            ->where(['uid' => 1])
            ->updateVal(['a' => '13100000000']) //此时手机号在最终的SQL中'会'加上引号
            ->updateVal(['b' => 13100000000]) //此时手机号在最终的SQL中'不会'加上引号
            ->update()
            ->affectRows;

// UPDATE test.user set a = '13100000000',b = 13100000000 WHERE (uid = 1)             
```
