<?php
/**
 * Plugin iocelems : add a IOC class to a content
 *
 * Syntax: ::elem:
 *          :key:value
 *          content
 *         :::
 *
 * @author     Marc Català <mcatala@ioc.cat>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    27/04/2011
 */

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');


class syntax_plugin_iocexportl_iocelems extends DokuWiki_Syntax_Plugin {

   var $tipus = '';

   /**
    * Get an associative array with plugin info.
    */
    function getInfo(){
        return array(
            'author' => 'Marc Català',
            'email'  => 'mcatala@ioc.cat',
            'date'   => '2011-01-27',
            'name'   => 'IOC elems Plugin',
            'desc'   => 'Plugin to parse style elems',
            'url'    => 'http://ioc.gencat.cat/',
        );
    }

    function getType(){
        return 'container';
    }

    function getPType(){
        return 'block';
    } //stack, block, normal

    function getSort(){
        return 514;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('::(?:text|note|reference|quote|important|example):.*?\n+(?=.*?\n:::)',$mode,'plugin_iocexportl_iocelems');
    }
    function postConnect() {
        $this->Lexer->addExitPattern('\n:::','plugin_iocexportl_iocelems');
    }

    /**
     * Handle the match
     */

    function handle($match, $state, $pos, &$handler){
        $matches = array();
		$params = array();
        switch ($state) {
            case DOKU_LEXER_ENTER :
                break;
            case DOKU_LEXER_UNMATCHED :
                preg_match_all('/\s{2}:(\w+):(.*?)\n/', $match, $matches, PREG_SET_ORDER);
                foreach($matches as $m){
                    $params[$m[1]] = $m[2];
                }
                $match = preg_replace('/\s{2}:\w+:.*?\n/', '',  $match);
                break;
            case DOKU_LEXER_EXIT :
                break;
        }
        return array($state, $match, $params);
    }

   /**
    * output
    */
    function render($mode, &$renderer, $indata) {
        if($mode === 'ioccounter'){
            list($state, $text, $params) = $indata;
            switch ($state) {
              case DOKU_LEXER_ENTER :
                  break;
              case DOKU_LEXER_UNMATCHED :
                  $renderer->doc .= (isset($params['title']))?$params['title']:'';
                  $instructions = get_latex_instructions($text);
                  $renderer->doc .= p_latex_render($mode,$instructions,$info);
                  break;
              case DOKU_LEXER_EXIT :
                  break;
            }
            return TRUE;
        }elseif ($mode === 'iocexportl'){
            list($state, $data, $params) = $indata;
            switch ($state) {
              case DOKU_LEXER_ENTER :
                    $matches = array();
                    //avoid hyphenation
                    $renderer->doc .= '\hyphenpenalty=100000'.DOKU_LF;
                    preg_match('/::([^:]*):/', $data, $matches);
                    $this->tipus = (isset($matches[1]))?$matches[1]:'';
                    break;
                case DOKU_LEXER_UNMATCHED :
                    //IMPORTANT
                    if($this->tipus === 'important'){
                        $renderer->doc .= '\iocimportant{'.$this->_parse($data, $mode).'}';
                    //TEXT
                    }elseif($this->tipus === 'text'){
                        if (isset($params['large'])){
                            $this->tipus = 'ioctextl';
                        }else{
                            $this->tipus = 'ioctext';
                        }
                        $title = (isset($params['title']))?$renderer->_xmlEntities($params['title']):'';
                        $offset = (isset($params['offset']))?'['.$params['offset'].'mm]':'';
                        $renderer->doc .= '\\'.$this->tipus.$offset.'{'.$title.'}{'.$this->_parse($data, $mode).'}';
                    //NOTE
                    }elseif($this->tipus === 'note'){
                        $offset = (isset($params['offset']))?'['.$params['offset'].'mm]':'';
                        $renderer->doc .= '\iocnote'.$offset.'{'.$this->_parse($data, $mode).'}';
                    //QUOTE
                    }elseif($this->tipus === 'quote'){
                        $renderer->doc .= '\iocquote{'.$this->_parse($data, $mode).'}';
                    //EXAMPLE
                    }elseif($this->tipus === 'example'){
                        $title = (isset($params['title']))?$renderer->_xmlEntities($params['title']):'';
                        $renderer->doc .= '\iocexample{'.$title.'}{'.$this->_parse($data, $mode).'}';
                    //REFERENCE
                    }elseif($this->tipus === 'reference'){
                        $offset = (isset($params['offset']))?'['.$params['offset'].'mm]':'';
                        $renderer->doc .= '\iocreference'.$offset.'{'.$this->_parse($data, $mode).'}';
                    }else{
                        $renderer->doc .= $this->_parse($data, $mode);
                    }
                    break;
                case DOKU_LEXER_EXIT :
                    //allow hyphenation
                    $renderer->doc .= '\hyphenpenalty=1000'.DOKU_LF;
                    $this->tipus = '';
                    break;
            }
            return TRUE;
        }elseif ($mode === 'xhtml'){
            list($state, $data, $params) = $indata;
            switch ($state) {
                    case DOKU_LEXER_ENTER :
                        $matches = array();
                        preg_match('/::([^:]*):/', $data, $matches);
                        $this->tipus = (isset($matches[1]))?$matches[1]:'';
                        break;
                    case DOKU_LEXER_UNMATCHED :
                        $title = (isset($params['title']))?$renderer->_xmlEntities($params['title']):'';
                        //TEXT LARGE
                        if($this->tipus === 'text' && isset($params['large'])){
                            $this->tipus = 'textl';
                        }
                        $renderer->doc .= '<div class="ioc'.$this->tipus.'">';
                        $renderer->doc .= '<div class="ioccontent">';
                        if (!empty($title)){
                            $renderer->doc .= '<p class="ioctitle">'.$title.'</p>';
                        }
                        $instructions = p_get_instructions($data);
                        $renderer->doc .= p_render($mode, $instructions, $info);
                        $renderer->doc .= '</div>';
                        $renderer->doc .= '</div>';
                        break;
                    case DOKU_LEXER_EXIT :
                        break;
            }
            return TRUE;
        }elseif ($mode === 'iocxhtml'){
            list($state, $data, $params) = $indata;
            switch ($state) {
                    case DOKU_LEXER_ENTER :
                        $matches = array();
                        preg_match('/::([^:]*):/', $data, $matches);
                        $this->tipus = (isset($matches[1]))?$matches[1]:'';
                        break;
                    case DOKU_LEXER_UNMATCHED :
                        $title = (isset($params['title']))?$renderer->_xmlEntities($params['title']):'';
                        //TEXT LARGE
                        if($this->tipus === 'text' && isset($params['large'])){
                            $this->tipus = 'textl';
                        }
                        $renderer->doc .= '<div class="ioc'.$this->tipus.'">';
                        if (!empty($title)){
                            $renderer->doc .= '<p class="ioctitle">'.$title.'</p>';
                        }
                        $instructions = get_latex_instructions($data);
                        $renderer->doc .= p_latex_render($mode, $instructions, $info);
                        $renderer->doc .= '</div>';
                        break;
                    case DOKU_LEXER_EXIT :
                        break;
            }
       }
       return FALSE;
    }

    function _parse($text, $mode){
        $info = array();
        $_SESSION['iocelem'] = ($this->tipus === 'example' || $this->tipus === 'ioctextl' || $this->tipus === 'quote')?'textl':TRUE;
        $instructions = get_latex_instructions($text);
        $text = p_latex_render($mode, $instructions, $info);
        $_SESSION['iocelem'] = FALSE;
        return preg_replace('/(.*?)(\n*)$/', '$1', $text);
    }
}
