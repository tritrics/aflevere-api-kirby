<?php

namespace Tritrics\AflevereApi\v1\Models;

use Collator;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;

/**
 * Model for Kirby's fields: list, slug, text, textarea, writer
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 * 
 * Possible Texttypes:
 * 
 * -------------------------------------------------------------------------------------------
 * | FIELD-TYPE   | FIELD-DEF              | FORMATTING | LINEBREAKS        | API-TYPE       | 
 * |--------------|------------------------|------------|-------------------|----------------|
 * | text, slug   |                        | ./.        | ./.               | string         |
 * |--------------|------------------------|------------|-------------------|----------------|
 * | textarea     | buttons: false         | ./.        | \n                | text           |
 * |--------------|------------------------|------------|-------------------|----------------|
 * | textarea     | buttons: true          | markdown   | \n                | markdown       |
 * |--------------|------------------------|------------|-------------------|----------------|
 * | textarea     | buttons: false|true    | html       | <blocks>          | html           |
 * |              | api: html: true        |            | <br>              |                |
 * |--------------|------------------------|------------|-------------------|----------------|
 * | writer, list | inline: false          | html       | <blocks>          | html           |
 * |              |                        |            | <br>              |                |
 * |--------------|------------------------|------------|-------------------|----------------|
 * | writer       | inline: true           | html       | <br>              | html           |
 * |              | or nor breaks in text  |            |                   | without elem   |
 * -------------------------------------------------------------------------------------------
 * 
 * Textarea parsing as html is provided for older Kirby-projects, where writer-field was
 * not existing. In new projects writer should be used for html and textarea for text/markdown.
 * The possible combination: $textarea->kirbytext()->inline() is not provided, because the
 * field-buttons contains the block-elements headline and lists. These blocks are
 * stripped out by inline() which only makes sense, when the buttons are configured without.
 */
class TextModel extends Model
{
  /**
   * Type retured in response.
   * 
   * @var string [html, text, markdown, string]
   */
  private $type;

  /**
   * Constructor with additional initialization.
   * 
   * @param mixed $model 
   * @param mixed $blueprint 
   * @param mixed $lang 
   * @param bool $add_details 
   * @return void 
   */
  public function __construct($model, $blueprint = null, $lang = null)
  {
    switch ($blueprint->node('type')->get()) {
      case 'textarea':
        if ($blueprint->node('api', 'html')->is(true)) {
          $this->type = 'html';
        } elseif ($blueprint->node('buttons')->is(false)) {
          $this->type = 'text';
        } else {
          $this->type = 'markdown';
        }
        break;
      case 'list':
        $this->type = 'html';
        break;
      case 'writer':
        $this->type = 'html';
        break;
      default: // text, slug
        $this->type = 'string';
    }
    parent::__construct($model, $blueprint, $lang);
  }

  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   * 
   * @return string 
   */
  protected function getType ()
  {
    return $this->type;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * return value can be:
   * 
   * 1. A string for non-html fields
   * 
   * 2. A simple text
   * { text: 'the text' }
   * 
   * 3. A single block-element
   * { elem: 'h1', text: 'the text' }
   * 
   * 4. An array with more than one of the above where every
   * possible sub-element is in node children.
   * [ { elem: 'h1', text: 'the text' }, { elem: 'p', children: [] }]
   * 
   * @return array|string
   */
  protected function getValue ()
  {
    if ($this->type !== 'html') {
      return '' . $this->model->value();
    }

    $fieldtype = $this->blueprint->node('type')->get();
    if ($fieldtype === 'textarea') {
      $buffer = $this->model->kirbytext();
    } else if ($fieldtype === 'writer') {
      $buffer = $this->model->text();
    } else if ($fieldtype === 'list') {
      $buffer = $this->model->list();
    } else { // error
      return '';
    }

    // delete line breaks
    $buffer = str_replace(["\n", "\r", "\rn"], "", $buffer);

    // delete special elements
    $buffer = str_replace(["<figure>", "</figure>"], "", $buffer);

    // make HTML editabel and get as array
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML('
      <!DOCTYPE html>
      <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        </head>
        <body>' . $buffer . '</body>
      </html>'
    );
    $nodelist = $dom->getElementsByTagName('body');
    $res = $this->htmlToArray($nodelist->item(0));
    unset($res['elem']); // body

    if (isset($res['value'])) {
      if (is_array($res['value']) && count($res['value']) === 1) {
        $res = array_shift($res['value']);
      } else {
        $res = $res['value'];
      }
    }
    return $res;
  }

  /**
   * Helper to convert DOMDocument to array.
   * Credits to https://gist.github.com/yosko/6991691
   * 
   * @param mixed $root 
   * @return array|void 
   * @throws InvalidArgumentException 
   * @throws LogicException 
   */
  function htmlToArray($root)
  {
    // node with nodetype
    if ($root->nodeType == XML_ELEMENT_NODE) {
      $res = [ 'elem' => strtolower($root->nodeName) ];
      if ($root->hasChildNodes()) {
        $res['value'] = [];
        $children = $root->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
          $child = $this->htmlToArray($children->item($i));
          if (!empty($child)) {
            $res['value'][] = $child;
          }
        }

        // if it's only a block-element with simple text, then remove children
        if (count($res['value']) === 1 && count($res['value'][0]) === 1 && isset($res['value'][0]['value'])) {
          $res['value'] = $res['value'][0]['value'];
        }
      }

      // add attributes as optional 3rd entry
      if ($root->hasAttributes()) {
        $res['attr'] = [];
        foreach ($root->attributes as $attribute) {
          $res['attr'][$attribute->name] = $attribute->value;
        }

        // change attributes, if it's a link
        if ($res['elem'] === 'a') {
          $res['attr'] = LinkService::getInline(
            $this->lang,
            $res['attr']['href'],
            (isset($res['attr']['title']) ? $res['attr']['title'] : null),
            (isset($res['attr']['target']) && $res['attr']['target'] === '_blank')
          );
        }
      }
      return $res;
    }

    // text node
    if ($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
      $value = $root->nodeValue;
      if (!empty($value)) {
        return [
          'value' => $value
        ];
      }
    }
  }
}