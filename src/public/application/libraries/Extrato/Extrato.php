<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ExtratoHelper
{

  protected $CI;
  protected $source;
  protected $value = [];
  protected $element = [];
  protected $responseApi = [];

  const API = 'api';
  const TELA = 'tela';

  public function __construct()
  {
    $this->CI = &get_instance();
  }

  /**
   * Manipular Tags Html
   *
   * @todo	Imprime uma tag html
   * @param	string	$elem	Tag html para abrir 
   * @param	array	$attr	Atributos da tag html (class, id, border, etc)
   * @param	string	$text	Texto a ser mostrado dentro da tag
   * @param	bool	$closeTag	Fechar a tag html na mesma linha
   * @return string
   */
  protected function openTag($elem = "", $attr = [], $text = "", $closeTag = false)
  {

    if ($this->source == self::API) {
      return;
    }

    $text = utf8_decode($text);

    $array = $attr;
    array_walk($array, function (&$value, $key) {
      $value = "{$key}=\"$value\"";
    });
    echo "<$elem " . implode(' ', $array) . ">$text" . ($closeTag ? "</$elem>" : "");
  }

  protected function closeTag($elem = "")
  {
    if ($this->source == self::API) {
      return;
    }
    echo "</$elem>";
  }

  protected function buildResponse($key = "", $bold = false)
  {
    $value = $this->value[$key] ?? "";
    if ($this->source == self::API) {
      if (!empty($key)) {
        $this->element["$key"] = $value;
      }
      return;
    }
    if ($bold) {
      $value = "<strong>" . $value . "</strong>";
    }
    echo utf8_decode("<td>" . $value . "</td>");
  }

  protected function drawRow($start = true)
  {
    if ($this->source == self::API) {
      return;
    }
    if ($start) {
      echo "<tr>";
    } else {
      echo "</tr>";
    }
  }

  protected function resetArray()
  {
    array_push($this->responseApi, $this->element);
    $this->element = [];
  }

}
