# SUMMER-SQL 构建SQL语句

> 可以用来链式组装SQL语句的类

* 用法参考: 

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