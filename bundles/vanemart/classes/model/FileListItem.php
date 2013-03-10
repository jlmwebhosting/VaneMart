<?php namespace VaneMart;

class FileListItem extends BaseModel {
  static $table = 'file_lists';
  static $typeModels = array('goods' => 'Product', 'posts' => 'Post');

  static function relationTo(Eloquent $model) {
    $class = class_basename($model);
    $type = array_search($class, static::$typeModels);

    if ($type) {
      return static::where('type', '=', $type)->where('object', '=', $model->id);
    } else {
      throw new Error("FileListItem cannot create relation to unknown class [$class].");
    }
  }

  function object() {
    $class = array_get(static::$typeModels, $this->type);

    if ($class) {
      return $this->belongs_to(NS.$class, 'object');
    } else {
      Log::error_FileListItem("Unknown object type [{$this->type}].");
    }
  }

  function file() {
    return $this->belongs_to(NS.'File', 'file');
  }
}
FileListItem::$table = \Config::get('vanemart::general.table_prefix').FileListItem::$table;