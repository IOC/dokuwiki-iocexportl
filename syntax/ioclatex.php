<?php
/**
 * Latex Syntax Plugin
 * @author     Marc Català <mcatala@ioc.cat>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');


class syntax_plugin_iocexportl_ioclatex extends DokuWiki_Syntax_Plugin {
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Marc Català',
            'email'  => 'mcatala@ioc.cat',
            'date'   => '2011-01-28',
            'name'   => 'IOC latex Plugin',
            'desc'   => 'Plugin to parse latex tags',
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
        $this->Lexer->addEntryPattern('\$\$\n?(?=.*?\n?\$\$)', $mode, 'plugin_iocexportl_ioclatex');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('\$\$', 'plugin_iocexportl_ioclatex');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        return array($state, $match);
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        global $symbols;
        if ($mode === 'ioccounter'){
            list ($state, $text) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $text;
                    break;
                case DOKU_LEXER_EXIT :
                    break;
            }
            return TRUE;
        }elseif ($mode === 'iocexportl'){
            list ($state, $text) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $renderer->doc .= DOKU_LF.'\begin{center}'. DOKU_LF;
                    $renderer->doc .= ' \begin{math}';
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $text = str_ireplace($symbols, ' (INVALID CHARACTER) ', $text);
    				//replace \\ (not supported in math mode) by \break
    				if (!preg_match('/{matrix}/', $text)){
    				    $text = preg_replace('/\\\\\\\\/', '\\\\break', $text);
    				}
    				$text = preg_replace('/(\$)/', '\\\\$1', $text);
                    $renderer->doc .= filter_tex_sanitize_formula($text);
                    break;
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '\end{math} '. DOKU_LF;
                    $renderer->doc .= '\end{center}' . DOKU_LF.DOKU_LF;
                    break;
            }
            return TRUE;
        }elseif ($mode === 'xhtml'){
            list ($state, $text) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $text;
                    break;
                case DOKU_LEXER_EXIT :
                    break;
            }
            return TRUE;
        }
        return FALSE;
    }
}
