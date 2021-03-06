<?php namespace Vane;

\Bundle::$bundles['vane']['ignorePlarx'] = array('Str', 'HLEx', 'Log', 'Event', 'Validator');

if (!\Bundle::exists('plarx')) {
  throw new Error('Vane requires Plarx bundle installed.');
} else {
  \Bundle::start('plarx');
  \Px\Plarx::supersede(__NAMESPACE__, \Bundle::option('vane', 'ignorePlarx'));
}

if (!\Bundle::exists('squall')) {
  throw new Error('Vane requires Squall bundle installed.');
} else {
  \Bundle::start('squall');
  \Squall\initEx(__NAMESPACE__);
}

\Autoloader::namespaces(array(__NAMESPACE__ => __DIR__.DS.'classes'));

require_once __DIR__.DS.'core.php';
overrideHTMLki('vane::', __NAMESPACE__);

// vane::auth[:[!]perm[:[!]perm.2[...]]] [|filter:2 [|...]]
//
// This filter will always deny access for non-authorized users even if guest
// permissions allow for given features - this is so because protected controllers
// rely on current user being logged in.
\Route::filter('vane::auth', function ($feature_1 = null) {
  $features = is_array($feature_1) ? $feature_1 : func_get_args();
  $block = is_object(end($features)) ? array_pop($features) : null;
  $user = \Auth::user();

  if ($user and ! $user instanceof UserInterface) {
    $msg = "When using vane::auth filter object returned by Auth::user()".
           " (".get_class($user)." here) must implement Vane\\UserInterface.".
           " This is not so - returned 403 for user {$user->id}.";
    $deny = Log::error_Auth($msg);
  } elseif (!$user) {
    $name = $block ? ' '.$block->name : '';
    $deny = Log::info_Auth("Block$name needs authorized user, denying access for guest.");
  } elseif ($features) {
    list($toMiss, $toHave) = S::divide($features, '?[0] === "!"');

    $having = array_filter(S($toMiss, array('.substr', 1)), array($user, 'can'));
    $missing = array_omit($toHave, array($user, 'can'));

    $reasons = array();
    $having and $reasons[] = "present flag(s): ".join(', ', $having);
    $missing and $reasons[] = "missing permission(s): ".join(', ', $missing);

    if ($reasons) {
      $name = $block ? ' '.$block->name : '';
      $msg = "Denied access to block$name via vane::auth for user {$user->id} due to ".
             join(' and ', $reasons).'.';
      $deny = Log::info_Auth($msg);
    }
  }

  if (!empty($deny)) {
    return $block ? $block->toResponse(false) : false;
  }
});
