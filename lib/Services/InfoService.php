<?php

namespace Tritrics\Ahoi\v1\Services;

use Kirby\Exception\DuplicateException;
use Kirby\Exception\LogicException;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;
use Tritrics\Ahoi\v1\Models\SiteModel;
use Tritrics\Ahoi\v1\Models\LanguageModel;

/**
 * Service for API's info interface.
 */
class InfoService
{
  /**
   * Main method to respond to "info" action.
   *
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get(): Collection
  {
    $expose = kirby()->option('debug', false);
    $isMultilang = ConfigHelper::isMultilang();
    $body = new Collection();

    // languages and analization
    // langdetect is true, if combination of origin and slug is unique
    // -> frontend is able to detect the language of a given path
    $langdetect = true;
    $urls = [];
    if ($isMultilang) {
      $languages = new Collection();
      foreach (LanguagesHelper::getAll() as $model) {
        if (in_array($model->url(), $urls)) {
          $langdetect = false;
        }
        $urls[] = $model->url();
        $languages->push(new LanguageModel($model));
      }
    }

    // Type
    $body->add('type', 'info');

    // Meta
    $meta = $body->add('meta');
    $meta->add('multilang', $isMultilang);
    if ($isMultilang) {
      $meta->add('langdetect', $langdetect);
    }
    if ($expose) {
      $meta->add('api', ConfigHelper::getVersion());
      $meta->add('plugin', ConfigHelper::getPluginVersion());
      $meta->add('kirby', kirby()->version());
      $meta->add('php', phpversion());
      $meta->add('slug', ConfigHelper::getconfig('slug', ''));
      $meta->add('field_name_separator',  ConfigHelper::getconfig('field_name_separator', ''));
    }

    // Interface
    if ($expose) {
      $interface = $body->add('interface');
      $Request = kirby()->request();
      $url = substr($Request->url()->toString(), 0, -5); // the easy way
      if (ConfigHelper::isEnabledAction()) {
        $interface->add('action', $url . '/action');
      }
      if (ConfigHelper::isEnabledFile()) {
        $interface->add('file', $url . '/file');
      }
      if (ConfigHelper::isEnabledFiles()) {
        $interface->add('files', $url . '/files');
      }
      if (ConfigHelper::isEnabledInfo()) {
        $interface->add('info', $url . '/info',);
      }
      if (ConfigHelper::isEnabledLanguage()) {
        $interface->add('language', $url . '/language',);
      }
      if (ConfigHelper::isEnabledPage()) {
        $interface->add('page', $url . '/page');
      }
      if (ConfigHelper::isEnabledPages()) {
        $interface->add('pages', $url . '/pages');
      }
      if (ConfigHelper::isEnabledSite()) {
        $interface->add('site', $url . '/site');
      }
    }

    // add languages
    if ($isMultilang) {
      $body->add('languages', $languages);
    }

    // add sites
    $site = site();
    $blueprint = BlueprintHelper::get($site);
    if ($isMultilang) {
      $sites = $body->add('sites');
      foreach(LanguagesHelper::getCodes() as $code) {
        $sites->push(new SiteModel($site, $blueprint, $code, [], false));
      }
    } else {
      $body->add('site', new SiteModel($site, $blueprint, '', [], false));
    }
    return $body;
  }
}