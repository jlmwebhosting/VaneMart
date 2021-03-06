<?php namespace VaneMart;

class Order extends BaseModel {
  static $fields = array('id', 'user', 'manager', 'status', 'sum', 'name', 'surname',
                         'address', 'phone', 'notes', 'created_at', 'updated_at');

  static $table = 'orders';
  static $hasURL = true;

  // Used by 'order.change_lines' event. 'manager' is present to avoid showing
  // internal company operations in the public.
  static $ignoreLogFields = array('manager', 'created_at', 'updated_at');

  //= array of str 'new', 'paid', etc.
  static function statuses() {
    return array_keys((array) __('vanemart::order.status')->get());
  }

  //= str
  static function generatePassword() {
    return (string) Event::result('order.new.password', function ($password) {
      return strlen($password) < 1 ? 'a blank string' : true;
    });
  }

  static function createBy(User $user, array $info) {
    $fields = array('name', 'surname', 'address', 'phone', 'notes');
    $fields = userFields($fields, 'order');

    $order = with(new static)
      ->fill_raw(array_intersect_key($info, array_flip($fields)))
      ->fill_raw(array(
        'password'        => static::generatePassword(),
        'user'            => $user->id,
        'manager'         => \Vane\Current::config('general.new_order_manager'),
        'sum'             => Cart::subtotal(),
        'ip'              => Request::ip(),
      ));

    return Event::insertModel($order, 'order');
  }

  //= Order, null if $user hasn't placed any orders yet
  static function latestOfUser($user) {
    is_object($user) and $user = $user->id;

    return static
      ::where('user', '=', $user)
      ->order_by('created_at', 'desc')
      ->first();
  }

  function regeneratePassword() {
    $this->password = static::generatePassword();
    return $this;
  }

  function url() {
    return route('vanemart::order', $this->id).'?code='.$this->password;
  }

  function goods() {
    return $this->has_many(NS.'OrderProduct', 'order');
  }

  function user() {
    return $this->belongs_to(NS.'User', 'user');
  }

  function manager() {
    return $this->belongs_to(NS.'User', 'manager');
  }

  function posts() {
    return $this->has_many(NS.'Post', 'post')->where('type', '=', 'order');
  }

  function filter_sum($prefix, $query, $value) {
    return $query->filterInt($prefix.'sum', $value);
  }

  function filter_name($prefix, $query, $value) {
    return $query->filterStr($prefix.'name', $value);
  }

  function filter_surname($prefix, $query, $value) {
    return $query->filterStr($prefix.'surname', $value);
  }

  function filter_city($prefix, $query, $value) {
    return $query->filterStr($prefix.'city', $value);
  }

  function filter_address($prefix, $query, $value) {
    return $query->filterStr($prefix.'address', $value);
  }

  function filter_phone($prefix, $query, $value) {
    return $query->filterStr($prefix.'phone', $value);
  }

  function filter_notes($prefix, $query, $value) {
    return $query->filterStr($prefix.'notes', $value);
  }

  function filter_ip($prefix, $query, $value) {
    return $query->filterStr($prefix.'ip', $value);
  }

  function filter_created_at($prefix, $query, $value) {
    return $query->filterDate($prefix.'created_at', $value);
  }

  function filter_updated_at($prefix, $query, $value) {
    return $query->filterDate($prefix.'updated_at', $value);
  }

  function changeMessages() {
    $result = array();
    Event::fire('order.change_lines', array(&$result, $this));
    return $result;
  }

  function isOf(User $user = null) {
    return Event::until('order.belongs', array($this, $user)) !== false;
  }

  //= null  order has no assigned $user
  //= bool
  function isLatest() {
    if ($this->user) {
      $latest = static::latestOfUser($this->user);
      return $latest and $latest->id == $this->id;
    }
  }
}
Order::$table = \Config::get('vanemart::general.table_prefix').Order::$table;
Order::$fields = userFields(Order::$fields, 'order');