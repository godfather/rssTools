<?php

/**
 * RssFeedParserToJson class file
 * this class interate between rss feed itens and generate a json file
 * the json file will be formated based on nodes list (see usage below) 
 *
 * @author Santiago Carmo <santiagocca@gmail.com>
 * @copyright Copyright &copy; 2012 Santiago Carmo
 * @license http://www.gnu.org/copyleft/fdl.html
 * @version 1
**/

/**
 * ------------------------------------------------------------
 * USAGE
 * ------------------------------------------------------------
 *
 * require_once 'RssFeedParserToJson.php';
 *
 * //You can pass a optional list of params:
 * $paramns = array('nodes'      => ARRAY_OF_NODE_NAMES,     //defaul is array('title', 'pubDate', 'link', 'description')
 *                  'limit'      => NUMBER_OF_ITENS_TO_FIND, //default is NULL 
 *                  'name'       => OUTPUT_FILE_NAME,        //default is a timestamp time()
 *                  'cache_path' => PATH_TO_OUTPUT_FILE      // default is the current script directory
 *                );
 *
 * $url = 'feed://URL_FROM_FEED';
 * $rss = new RssFeedParserToJson($url, $params);
 * $rss->dump_to_json_file(); // output a json file
 *
**/


class RssFeedParserToJson {
  
  //this constant store the xml scope where the class will work
  const XPATH_BASE = '/rss/channel/item';
  
  private $name;
  private $nodes;
  private $rss_url;
  private $load_limit;
  private $cache_file;
  private $rss_feed_content;
  private $json_object_item;
  private $simple_xml  = NULL;
  private $json_object = array();
  
  public function __construct($url, $params = array()) {
    $default = array('nodes' => array('title', 'pubDate', 'link', 'description'), 'limit' => NULL, 'name' => time(), 'cache_path' => dirname(__FILE__));
    $params  = array_merge($default, $params);

    $this->nodes      = $params['nodes'];
    $this->rss_url    = $this->prepare_rss_url($url);
    $this->load_limit = $params['limit'];
    $this->cache_file = "{$params['cache_path']}/{$params['name']}.json";
  }
  
  public function __get($property) {
    if(!method_exists($this, $property)) return $this->get_xml_attribute("/{$property}");
    else return $this->$property;
  }
    
  public function count_rss_itens() {
    return count($this->get_xml_attribute());
  }

  public function dump_to_json_file() {
    $this->convert_to_json();
    return file_put_contents($this->cache_file, $this->json_object);
  }

  //private methods
  private function prepare_rss_url($rss_url) {
    return preg_replace('/feed:/', 'http:', $rss_url);
  }
  
  private function load_rss_feed() {
    $this->simple_xml = new SimpleXmlElement(file_get_contents($this->rss_url));
    return $this;
  }
  
  private function generate_json_object_items() {
    $item        = array();
    $total_itens = $this->count_rss_itens();
    $total_itens = is_null($this->load_limit) || $this->load_limit > $total_itens ? $total_itens : $this->load_limit;
    for($i = 0; $i < $total_itens; $i++) {
      foreach($this->nodes as $node) {
        $array_node = $this->$node;
        $item[$node] = self::prepare_node($node, $array_node[$i]);
      }
      $this->add_item_to_json_object($item);
    }

    return $this;
  }

  private function add_item_to_json_object($json_object_item) {
    $this->json_object[] = (object)$json_object_item;
    return $this;
  }
  
  private function get_xml_attribute($attribute = '') {
    if(is_null($this->simple_xml)) $this->load_rss_feed();
    $nodes = $this->simple_xml->xpath(self::XPATH_BASE . "{$attribute}");
    return empty($nodes) ? $this->simple_xml->xpath(self::XPATH_BASE . "/description") : $nodes;
  }
  
  private static function get_image($node) {
    preg_match('/<img [^>]*src=["|\']([^"|\']+)/i', $node, $matches);
    return $matches[1];
  }

  private static function node_content_cleanup($node) {
    return strip_tags($node);
  }
  
  private function prepare_node($node_name, $node_value) {
    return $node_name == 'image' ? self::get_image($node_value) : self::node_content_cleanup($node_value);
  }
  
  private function convert_to_json() {
    $this->generate_json_object_items();
    $this->json_object = json_encode((object)$this->json_object);
    return $this;
  }  
}
?>
