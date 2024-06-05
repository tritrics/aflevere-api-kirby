<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's site object
 */
class SiteModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $content = $this->model->content($this->lang);

    $meta = $res->add('meta');
    $meta->add('node', ConfigHelper::isMultilang() ? '/' . $this->lang : '');
    if (ConfigHelper::isMultilang()) {
      $meta->add('lang', $this->lang);
    }
    $meta->add('blueprint', 'site');
    $meta->add('title', $content->title()->value());
    $meta->add('modified',  date('c', $this->model->modified()));

      // adding translations
    if (ConfigHelper::isMultilang()) {
      if ($this->addDetails) {
        $translations = new Collection();
        foreach (LanguagesHelper::getCodes() as $code) {
          $translations->push([
            'lang' => $code,
            'node' => '/' . $code
          ]);
        }
        $meta->add('translations', $translations);
      }
    }

    if ($this->blueprint->has('api', 'meta')) {
      $api = new Collection();
      foreach ($this->blueprint->node('api', 'meta')->get() as $key => $value) {
        $api->add($key, $value);
      }
      if ($api->count() > 0) {
        $meta->add('api', $api);
      }
    }

    // adding homepage
    if ($this->addDetails) {
      $home = $this->model->homePage();
      $blueprint = BlueprintHelper::get($home);
      $res->add(
        'home',
        new PageModel($home, $blueprint, $this->lang, [], false)
      );
    }
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue(): Collection|null
  {
    return $this->fields;
  }
}
