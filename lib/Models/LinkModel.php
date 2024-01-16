<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Services\FileService;

/**
 * Model for Kirby's fields: link
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class LinkModel extends Model
{
  /**
   * Linktype, intern use
   * 
   * @var string [http, https, page, file, email, tel, anchor, custom]
   */
  private $linktype;

  /**
   * Constructor with additional initialization.
   * 
   * @param mixed $model 
   * @param mixed $blueprint 
   * @param mixed $lang 
   * @param bool $add_details 
   * @return void 
   */
  public function __construct($model, $blueprint, $lang)
  {
    $value = $model->value();
    if (str_starts_with($value, '#')) {
      $this->linktype = 'anchor';
    } else if (str_starts_with($value, 'mailto:')) {
      $this->linktype = 'email';
    } else if (str_starts_with($value, 'file://')) {
      $model = $model->toFile();
      $this->linktype = 'file';
    } else if (str_starts_with($value, 'page://')) {
      $model = $model->toPage();
      $this->linktype = 'page';
    } else if (str_starts_with($value, 'tel:')) {
      $this->linktype = 'tel';
    } else if (str_starts_with($value, 'http://')) {
      $this->linktype = 'http';
    } else if (str_starts_with($value, 'https://')) {
      $this->linktype = 'https';
    } else {
      $this->linktype = 'custom';
    }
    parent::__construct($model, $blueprint, $lang);
  }

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties()
  {
    $res = new Collection();
    switch($this->linktype) {
      case 'anchor':
        $res->add('link', LinkService::getAnchor($this->model->value()));
        break;
      case 'email':
        $res->add('link', LinkService::getEmail($this->model->value()));
        break;
      case 'file':
        $pathinfo = FileService::getPathinfo($this->model->url());
        $res->add('link', LinkService::getFile($pathinfo['path']));
        break;
      case 'page':
        $res->add('link', LinkService::getPage(
          LanguagesService::getUrl($this->lang, $this->model->uri($this->lang)))
        );
        break;
      case 'tel':
        $res->add('link', LinkService::getTel($this->model->value()));
        break;
      case 'http':
      case 'https':
        $res->add('link', LinkService::getUrl($this->model->value()));
        break;
      default:
        $res->add('link', LinkService::getCustom($this->model->value()));
    }
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return string
   */
  protected function getValue()
  {
    switch ($this->linktype) {
      case 'anchor':
        return substr($this->model->value(), 1);
      case 'email':
        return substr($this->model->value(), 7);
      case 'file':
        $title = (string) $this->model->title()->get();
        if (!$title) {
          $pathinfo = FileService::getPathinfo($this->model->url());
          $title = $pathinfo['file'];
        }
        return $title;
      case 'page':
        return $this->model->title()->get();
      case 'tel':
        return substr($this->model->value(), 4);
      case 'http':
        return substr($this->model->value(), 7);
      case 'https':
        return substr($this->model->value(), 8);
      default:
        return $this->model->value();
    }
  }
}

