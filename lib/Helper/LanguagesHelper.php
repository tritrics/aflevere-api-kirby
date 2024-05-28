<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Cms\Language;
use Kirby\Cms\Languages;
use Kirby\Exception\LogicException;
use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Language related helper functions.
 */
class LanguagesHelper
{
  /**
   * Get the language count if it's a multilang installation.
   */
  public static function count(): int
  {
    if (!ConfigHelper::isMultilang()) {
      return 0;
    }
    return self::getAll()->count();
  }

  /**
   * Get a single language as Kirby object defined by $code.
   */
  public static function get(?string $code): ?Language
  {
    try {
      return kirby()->language($code);
    } catch (LogicException $E) {
      return null;
    }
  }

  /**
   * Get all languages as Kirby object.
   */
  public static function getAll(): ?Languages
  {
    try {
      return kirby()->languages();
    } catch (LogicException $E) {
      return null;
    }
  }

  /**
   * Get the default language as Kirby object.
   */
  public static function getDefault(): ?Language
  {
    if (!ConfigHelper::isMultilang()) {
      return null;
    }
    return self::getAll()->default();
  }

  /**
   * Get the locale for a given language.
   */
  public static function getLocale(?string $code): string
  {
    if (!self::isValid($code)) {
      return '';
    }
    $language = self::get($code);
    $php_locale = $language->locale(LC_ALL);
    return str_replace('_', '-', $php_locale);
  }

  /**
   * Get the slug for a given language.
   * setting url = '/' means there is no prefix for default langauge
   * Frontend has it's own logic and always uses code for api-requests.
   * The slug-property is only the vue-router to show or not the code in routes.
   */
  public static function getSlug(?string $code): string
  {
    if (!self::isValid($code)) {
      return '';
    }
    $language = self::get($code);
    $url = parse_url($language->url());
    if ($language->isDefault() && (!isset($url['path']) || $url['path'] === '')) {
      return '';
    }
    return $language->code();
  }

  /**
   * Get the url for a given language.
   */
  public static function getUrl(string $code, string $slug): string
  {
    return '/' . trim(self::getSlug($code) . '/' . $slug, '/');
  }

  /**
   * Check if a given language code is valid.
   * empty string or null in non-multilang installation -> true
   * valid language code in multilang installation -> true
   * rest -> false
   */
  public static function isValid(?string $code): bool
  {
    if (!$code && !ConfigHelper::isMultilang()) {
      return true;
    }
    return self::getAll()->has($code);
  }

  /**
   * List availabe languages for intern use.
   */
  public static function list(): Collection
  {
    $home = kirby()->site()->homePage();
    $res = new Collection();
    foreach (self::getAll() as $language) {
      $res->add($language->code(), [
        'name' => $language->name(),
        'slug' => self::getSlug($language->code()),
        'default' => $language->isDefault(),
        'locale' => self::getLocale($language->code()),
        'direction' => $language->direction(),
        'homeslug' => $home->uri($language->code())
      ]);
    }
    return $res;
  }
}
