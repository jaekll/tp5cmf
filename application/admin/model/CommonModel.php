<?php
namespace app\admin\model;

use think\Model;

class CommonModel extends Model{

    // 操作状态
    const MODEL_INSERT    = 1; //  插入模型数据
    const MODEL_UPDATE    = 2; //  更新模型数据
    const MODEL_BOTH      = 3; //  包含上面两种方式
    const MUST_VALIDATE   = 1; // 必须验证
    const EXISTS_VALIDATE = 0; // 表单存在字段则验证
    const VALUE_VALIDATE  = 2; // 表单值不为空则验证

    /**
     * 删除表
     * @param $tablename string
     * @return bool
     */
    final public function drop_table($tablename) {
        $tablename = config("DB_PREFIX") . $tablename;
        return $this->query("DROP TABLE $tablename");
    }

    /**
     * 读取全部表名
     */
    final public function list_tables() {
        $tables = array();
        $data = $this->query("SHOW TABLES");
        foreach ($data as $k => $v) {
            $tables[] = $v['tables_in_' . strtolower(config("DB_NAME"))];
        }
        return $tables;
    }

    /**
     * 检查表是否存在
     * @param $table 不带表前缀
     * @return bool
     */
    final public function table_exists($table) {
        $tables = $this->list_tables();
        return in_array(config("DB_PREFIX") . $table, $tables) ? true : false;
    }

    /**
     * 获取表字段
     * $table 不带表前缀
     */
    final public function get_fields($table) {
        $fields = array();
        $table = config("DB_PREFIX") . $table;
        $data = $this->query("SHOW COLUMNS FROM $table");
        foreach ($data as $v) {
            $fields[$v['Field']] = $v['Type'];
        }
        return $fields;
    }

    /**
     * 检查字段是否存在
     * $table 不带表前缀
     */
    final public function field_exists($table, $field) {
        $fields = $this->get_fields($table);
        return array_key_exists($field, $fields);
    }

    /**
     * @param $data
     */
    protected function _before_write(&$data) {

    }
}