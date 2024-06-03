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
   * Cache some data.
   */
  private static $cache = [];

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
   * List availabe languages for intern use.
   */
  public static function getCodes(): array
  {
    $res = [];
    foreach (self::getAll() as $language) {
      $res[] = $language->code();
    }
    return $res;
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
   * Get the origin for a given language. Can be set in
   * url in language config or empty (= no specific origin).
   */
  public static function getOrigin(string $code): string
  {
    if (!self::isValid($code)) {
      return '';
    }
    $language = self::get($code);
    $url = UrlHelper::parse($language->url());
    $urlHost = UrlHelper::buildHost($url);
    $self = UrlHelper::getSelfUrl();
    if ($self === $urlHost) {
      return '';
    }
    return rtrim(UrlHelper::buildHost($url), '/');
  }

  /**
   * Get the link part of a given language.
   */
  public static function getHref(string $code): string
  {
    if (!self::isValid($code)) {
      return '';
    }
    $language = self::get($code);
    $url = UrlHelper::parse($language->url());
    return rtrim(UrlHelper::buildPath($url), '/');
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
}
