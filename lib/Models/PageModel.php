<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Page;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
use Tritrics\Ahoi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's page object
 */
class PageModel extends BaseModel
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
   * Method called by setModelData()
   */
  protected function getProperties (): Collection
  {
    $res = new Collection();
    $content = $this->model->content($this->lang);
    $attr = LinkHelper::get($this->model, null, false, $this->lang, 'page');

    $meta = $res->add('meta');
    if ($this->model instanceof Page) {
      // $meta->add('id', $this->model->id()); not needed
      $meta->add('slug', $this->model->slug($this->lang));
      $meta->add('href', $attr['href']);
    } else {
      $meta->add('href', $attr['href']);
    }
    $meta->add('node', '/' . trim($this->lang . '/' . $this->model->uri($this->lang), '/'));
    if ($this->model instanceof Page) {
      $meta->add('blueprint', (string) $this->model->intendedTemplate());
      $meta->add('status', $this->model->status());
      $meta->add('sort', (int) $this->model->num());
      $meta->add('home', $this->model->isHomePage());
    } else {
      $meta->add('blueprint', 'site');
    }
    $meta->add('title', $content->title()->value());
    $meta->add('modified',  date('c', $this->model->modified()));
    if (ConfigHelper::isMultilang()) {
      $meta->add('lang', $this->lang);
      $meta->add('locale', LanguagesHelper::getLocale($this->lang));
      if ($this->addDetails) {
        $translations = new Collection();
        foreach (LanguagesHelper::getCodes() as $code) {
          $attr = LinkHelper::get($this->model, null, false, $code, 'page');
          $translations->add($code, [
            'href' => $attr['href'],
            'node' => '/' . trim($code . '/' . $this->model->uri($code), '/')
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