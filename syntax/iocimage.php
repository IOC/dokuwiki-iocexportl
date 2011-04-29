<?php
/**
 * Image Syntax Plugin
 * @author     Marc Català <mcatala@ioc.cat>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');


class syntax_plugin_iocexportl_iocimage extends DokuWiki_Syntax_Plugin {
    
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Marc Català',
            'email'  => 'mcatala@ioc.cat',
            'date'   => '2011-04-12',
            'name'   => 'IOC image Plugin',
            'desc'   => 'Plugin to parse image tags',
            'url'    => 'http://ioc.gencat.cat/',
        );
    }
 
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'container';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 513;
    }
    
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('::image:\n(?=.*?\n:::)', $mode, 'plugin_iocexportl_iocimage');
    }
    
    function postConnect() {
        $this->Lexer->addExitPattern('\n:::', 'plugin_iocexportl_iocimage');
    }
    
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $matches = array();
        $data = array();
		$id = '';
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
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if ($mode !== 'iocexportl') return false;
        list ($state, $text, $params) = $data;
        switch ($state) {
            case DOKU_LEXER_ENTER : 
				break;
            case DOKU_LEXER_UNMATCHED :
                $_SESSION['imgB'] = true;
                $instructions = get_latex_instructions($text);            
                $renderer->doc .= '\imgB{';
                $renderer->doc .= p_latex_render($mode, $instructions, $info);                
                $renderer->doc .= '}'.DOKU_LF;
                $_SESSION['imgB'] = false;
                break;
            case DOKU_LEXER_EXIT : 
                break;
        }
        return true;
    }
}
