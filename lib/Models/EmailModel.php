<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;

/**
 * Model for Kirby's fields: email
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class EmailModel extends Model
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('link', LinkService::getEmail($this->model->value()));
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return string
   */
  protected function getValue ()
  {
    return $this->model->value();
  }
}