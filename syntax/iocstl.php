<?php
/**
 * Plugin iocstl : add a IOC class to a content
 *
 * Syntax: <iocstl textStyle>content</iocstl>
 * 
 * @author     Marc Català <mcatala@ioc.cat>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    27/01/2011
 */
 
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');
 

class syntax_plugin_iocexportl_iocstl extends DokuWiki_Syntax_Plugin {

   var $tipus = '';

   /**
    * Get an associative array with plugin info.
    */
    function getInfo(){
        return array(
            'author' => 'Marc Català',
            'email'  => 'mcatala@ioc.cat',
            'date'   => '2011-01-27',
            'name'   => 'IOC stl Plugin',
            'desc'   => 'Plugin to parse iocstl tags',
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
    
    function getAllowedTypes(){
       return array('container');
    }
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<iocstl.*?>(?=.*?</iocstl>)',$mode,'plugin_iocexportl_iocstl');
    }
    function postConnect() {
        $this->Lexer->addExitPattern('</iocstl>','plugin_iocexportl_iocstl');
    }
 
    /**
     * Handle the match
     */

    function handle($match, $state, $pos, &$handler){
        $opt = array();
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $match = trim(substr($match,7,-1));
                list($class, $nump) = explode(' ', $match, 2);
                $opt['class'] = $class;
                if (!empty($nump)) $opt['nump'] = $nump;
                return array($state, $opt);
            
            case DOKU_LEXER_UNMATCHED :
                return array($state, $match);
            
            default:
                return array($state);
        }
    }

   /**
    * output
    */
    function render($mode, &$renderer, $indata) {
        if ($mode !== 'iocexportl') return false;
        list($state, $data) = $indata;
        switch ($state) {
          case DOKU_LEXER_ENTER :
                $class = '';
                if (!empty($data['class'])){ 
                    $class = $data['class'];
                } 
                //avoid hyphenation
                $renderer->doc .= '\hyphenpenalty=100000'.DOKU_LF;
                $this->tipus = $class;
                break;
            case DOKU_LEXER_UNMATCHED :
                if ($this->tipus === 'textA'){
                    $renderer->doc .= '\textA{'.$this->_parse($data, $mode).'}';
                }elseif ($this->tipus === 'textB'){
                    $matches = array();
                    preg_match('/^\n?(.*?)\n+/', $data, $matches);
                    $title = preg_replace('/\\\\textbf{(.*?)}/', '$1', $this->_parse($matches[1], $mode));
                    $data = preg_replace('/^\n?(.*?)\n+/', '', $data);
                    $renderer->doc .= '\textB{'.$title.'}{'.$this->_parse($data, $mode).'}';
                }elseif($this->tipus === 'notaBreu'){ 
                    $renderer->doc .= '\notaBreu{'.$this->_parse($data, $mode).'}';
                }elseif($this->tipus === 'imgB'){
                    $renderer->doc .= '\bfseries{$data}';
                    $renderer->doc .= '\textB{IMATGE B}{'.($this->_parse('{{'.$data.'}}', $mode)).'}';
                }elseif($this->tipus === 'textD' || $this->tipus === 'textE'){
                    $matches = array();
                    preg_match('/^\n{0,2}(.*?)\n+/', $data, $matches);
                    $title = $this->_parse($matches[1], $mode);
                    $data = preg_replace('/^\n{0,2}(.*?)\n+/', '', $data);
                    $renderer->doc .= '\textX{'.$title.'}{'.$this->_parse($data, $mode).'}{0mm}';                        
                }elseif ($this->tipus === 'textG'){
                    $matches = array();
                    preg_match('/^\n?(.*?)\n+/', $data, $matches);
                    $title = preg_replace('/\\\\textbf{(.*?)}/', '$1', $this->_parse($matches[1], $mode));
                    if (empty($title)){
                        $title = '\textcolor{red}{\textbf{SENSE TÍTOL}}';
                    }
                    $data = preg_replace('/^\n?(.*?)\n+/', '', $data);
                    $renderer->doc .= '\textG{'.$title.'}{'.$this->_parse($data, $mode).'}';
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
        return true;
    }

    function _parse($text, $mode){
        $info = array();
        $_SESSION['iocstl'] = true;
        $instructions = get_latex_instructions($text);
        $text = p_render($mode, $instructions, $info);
        $_SESSION['iocstl'] = false;
        return preg_replace('/\n\n/', '', $text);
    }
}
