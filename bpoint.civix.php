<?php

// AUTO-GENERATED FILE -- Civix may overwrite if you're not careful.

/**
 * The ExtensionUtil class provides small functions shared by many extensions.
 */
class CRM_Bpoint_ExtensionUtil {
  const SHORT_NAME = "bpoint";
  const LONG_NAME = "nz.co.fuzion.bpointprocessor";
  const CLASS_PREFIX = "CRM_Bpoint";

  /**
   * Translate a string using the extension's domain.
   *
   * If the extension is not fully initialized, it will try to autoload the
   * extension class.
   *
   * @param string $text
   *   Unteranslated text to translate.
   * @param array $params
   *   Additional translation parameters (see ts() function).
   *
   * @return string
   *   Translated text.
   */
  public static function ts($text, $params = []) {
    if (!isset(Civi::$statics[__CLASS__]['enabled'])) {
      Civi::$statics[__CLASS__]['enabled'] = \CRM_Extension_System::singleton()->getMapper()->isActiveModule(self::LONG_NAME);
    }
    if (!Civi::$statics[__CLASS__]['enabled']) {
      return $text;
    }
    return \ts($text, array_merge(['domain' => self::LONG_NAME], $params));
  }

  /**
   * Get the URL of a file within this extension.
   *
   * @param string $file
   *   The file path, relative to this extension's root directory.
   *
   * @return string
   *   The absolute URL of that file.
   */
  public static function url($file = NULL) {
    $event = \Civi::service('extension_system')->getManager()->getStatus(self::LONG_NAME);
    if ($event->isActive) {
      $url = \CRM_Core_Resources::singleton()
        ->getUrl(self::LONG_NAME, $file);
    } else {
      // Fallback
      $url = \CRM_Utils_System::url('civicrm/admin/extensions', 'action=view&key=' . self::LONG_NAME);
    }
    return $url;
  }

  /**
   * Get the directory of a file within this extension.
   *
   * @param string $file
   *   The file path, relative to this extension's root directory.
   *
   * @return string
   *   The absolute path of that file.
   */
  public static function path($file = NULL) {
    static $basedir;

    if ($basedir === NULL) {
      $basedir = dirname(__DIR__) . '/nz.co.fuzion.bpointprocessor';
    }

    if ($file === NULL) {
      return $basedir;
    }

    return $basedir . DIRECTORY_SEPARATOR . $file;
  }

  /**
   * Get a list of hooks that this extension implements.
   *
   * @return array
   *   List of CiviCRM hooks implemented by this extension.
   */
  public static function findHooks($civicrm_api_version, $prefix = '') {
    $hooks = [];
    $camelNames = \CRM_Utils_String::findMatches(file_get_contents(__FILE__), '/function ' . $prefix . '(\w+)\(/', '$1');
    foreach ($camelNames as $camelName) {
      $hooks[] = \CRM_Utils_String::convertToUnderscore($camelName);
    }
    return $hooks;
  }

  /**
   * Get a list of entities/tables provided by this extension.
   *
   * @return array
   *   List of custom entity types.
   */
  public static function findEntities($prefix = '') {
    $entities = [];
    $file = preg_grep('/\.entityType\.php$/', (array) glob(dirname(__DIR__) . '/nz.co.fuzion.bpointprocessor/*.entityType.php')) ?: [];
    foreach ($file as $f) {
      $camelNames = \CRM_Utils_String::findMatches(file_get_contents($f), '/entityType = \'(\w+)\'/', '$1');
      foreach ($camelNames as $camelName) {
        $entities[] = $camelName;
      }
    }
    return $entities;
  }

}

/**
 * (Stub) Implement hook_civicrm_config().
 */
function _bpoint_civix_civicrm_config(&$config) {
  static $configured = FALSE;
  if ($configured) return;
  $configured = TRUE;

  $template = CRM_Core_Smarty::singleton();

  $extRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'nz.co.fuzion.bpointprocessor' . DIRECTORY_SEPARATOR;
  $extDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'nz.co.fuzion.bpointprocessor';
  $c = CRM_Core_Config::singleton();

  // Process templates directory.
  if (is_dir($extRoot . 'templates')) {
    if (in_array($extRoot . 'templates', $template->template_dir) === FALSE) {
      array_unshift($template->template_dir, $extRoot . 'templates');
    }
  }
}

/**
 * (Stub) Implement hook_civicrm_xmlMenu().
 */
function _bpoint_civix_civicrm_xmlMenu(&$menu) {
}

/**
 * (Stub) Implement hook_civicrm_install().
 */
function _bpoint_civix_civicrm_install() {
  _bpoint_civix_civicrm_config(CRM_Core_Config::singleton());
}

/**
 * (Stub) Implement hook_civicrm_postInstall().
 */
function _bpoint_civix_civicrm_postInstall() {
}

/**
 * (Stub) Implement hook_civicrm_uninstall().
 */
function _bpoint_civix_civicrm_uninstall() {
}

/**
 * (Stub) Implement hook_civicrm_enable().
 */
function _bpoint_civix_civicrm_enable() {
  _bpoint_civix_civicrm_config(CRM_Core_Config::singleton());
}

/**
 * (Stub) Implement hook_civicrm_disable().
 */
function _bpoint_civix_civicrm_disable() {
}

/**
 * (Stub) Implement hook_civicrm_upgrade().
 */
function _bpoint_civix_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return TRUE;
}

/**
 * (Stub) Implement hook_civicrm_managed().
 */
function _bpoint_civix_civicrm_managed(&$entities) {
  $files = _bpoint_civix_find_files(__DIR__, '*.mgd.php');
  foreach ($files as $file) {
    $es = include $file;
    foreach ($es as $e) {
      if (empty($e['module'])) {
        $e['module'] = 'nz.co.fuzion.bpointprocessor';
      }
      $entities[] = $e;
    }
  }
}

/**
 * (Stub) Implement hook_civicrm_caseTypes().
 */
function _bpoint_civix_civicrm_caseTypes(&$caseTypes) {
}

/**
 * (Stub) Implement hook_civicrm_angularModules().
 */
function _bpoint_civix_civicrm_angularModules(&$angularModules) {
}

/**
 * (Stub) Implement hook_civicrm_alterSettingsFolders().
 */
function _bpoint_civix_civicrm_alterSettingsFolders(&$metaFolders = NULL) {
}

/**
 * (Stub) Implement hook_civicrm_entityTypes().
 */
function _bpoint_civix_civicrm_entityTypes(&$entityTypes) {
}

/**
 * Find files.
 *
 * @param string $dir
 *   Base directory.
 * @param string $pattern
 *   Glob pattern.
 *
 * @return array
 *   List of files.
 */
function _bpoint_civix_find_files($dir, $pattern) {
  $todos = [$dir];
  $done = [];
  $outputs = [];
  while (!empty($todos)) {
    $subdir = array_shift($todos);
    if ($subdir && is_dir($subdir) && is_readable($subdir) && !isset($done[$subdir])) {
      $done[$subdir] = 1;
      foreach ((array) glob("$subdir/$pattern") as $file) {
        if (is_file($file)) {
          $outputs[] = $file;
        }
      }
      foreach ((array) glob("$subdir/*/") as $file) {
        $todos[] = $file;
      }
    }
  }
  return $outputs;
}
