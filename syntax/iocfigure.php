<?php
/**
 * Figure Syntax Plugin
 * @author     Marc Català <mcatala@ioc.cat>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');


class syntax_plugin_iocexportl_iocfigure extends DokuWiki_Syntax_Plugin {
    
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Marc Català',
            'email'  => 'mcatala@ioc.cat',
            'date'   => '2011-03-17',
            'name'   => 'IOC figure Plugin',
            'desc'   => 'Plugin to parse figure tags',
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
        $this->Lexer->addEntryPattern('::figure:.*?\n(?=.*?\n:::)', $mode, 'plugin_iocexportl_iocfigure');
    }
    
    function postConnect() {
        $this->Lexer->addExitPattern('\n:::', 'plugin_iocexportl_iocfigure');
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
                if (preg_match('/::figure:(.*?$)/', $match, $matches)){
                    $id = $matches[1];
                }
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
        return array($state, $match, $id, $params);
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if ($mode === 'ioccounter'){
            list ($state, $text) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER : break;
                case DOKU_LEXER_UNMATCHED :
                    $instructions = get_latex_instructions($text);
                    $renderer->doc .= p_latex_render($mode, $instructions, $info);
                    break;
                case DOKU_LEXER_EXIT : 
                    break;
            }
            return true;
        }elseif ($mode === 'iocexportl'){
            list ($state, $text, $id, $params) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER : 
    				$_SESSION['figlabel'] = $renderer->_xmlEntities($id);
    				break;
                case DOKU_LEXER_UNMATCHED :
                    $instructions = get_latex_instructions($text);
                    $renderer->doc .= p_latex_render($mode, $instructions, $info);                
    				$_SESSION['figlabel'] = '';
                    break;
                case DOKU_LEXER_EXIT : 
                    if (isset($params['footer'])){
                        $renderer->doc .='\small '. $params['footer'] .'\\\\';
                    }
                    break;
            }
            return true;
        }
        return false;
    }
}
