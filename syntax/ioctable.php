<?php
/**
 * Table Syntax Plugin
 * @author     Marc Català <mcatala@ioc.cat>
 * syntax
 * 	::table:id
   	  :title:
   	  :footer:
      :large: (bool)
	:::
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');

class syntax_plugin_iocexportl_ioctable extends DokuWiki_Syntax_Plugin {

    var $id;
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
        $this->Lexer->addEntryPattern('::table:.*?\n(?=.*?\n:::)', $mode, 'plugin_iocexportl_ioctable');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('\n:::', 'plugin_iocexportl_ioctable');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $matches = array();
		$id = '';
		$params = array();
        switch ($state) {
            case DOKU_LEXER_ENTER :
                if (preg_match('/::table:(.*?)\n/', $match, $matches)){
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
            list ($state, $text, $id, $params) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= (isset($params['title']))?$params['title']:'';
                    $instructions = get_latex_instructions($text);
                    $renderer->doc .= p_latex_render($mode, $instructions, $info);
                    $renderer->doc .= (isset($params['footer']))?$params['footer']:'';
                    break;
                case DOKU_LEXER_EXIT :
                    break;
            }
            return TRUE;
        }elseif ($mode === 'iocexportl'){
            list ($state, $text, $id, $params) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $this->id = trim($id);
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $_SESSION['table_title'] = (isset($params['title']))?$params['title']:'';
                    //Transform quotes
                    $_SESSION['table_title'] = preg_replace('/(")([^"]+)(")/', '``$2\'\'', $_SESSION['table_title']);
                    $_SESSION['table_footer'] = (isset($params['footer']) && !isset($params['large']))?trim($renderer->_xmlEntities($params['footer'])):'';
                    $_SESSION['table_id'] = $this->id;
                    if (isset($params['large'])){
                        $renderer->doc .= '\checkoddpage\ifthenelse{\boolean{oddpage}}{}{\hspace*{-\marginparwidth}\hspace*{-11mm}}'.DOKU_LF;
                        $renderer->doc .= '\parbox[c]{\marginparwidth+\marginparsep}{'.DOKU_LF;
                        $_SESSION['table_large'] = TRUE;
                    }elseif (isset($params['small'])){
                        $_SESSION['table_small'] = TRUE;
                        $renderer->doc .= '\begin{SCtable}[1][h]'.DOKU_LF;
                    }elseif (isset($params['vertical'])){
                        $renderer->doc .= '\begin{landscape}'.DOKU_LF;
                    }
                    $instructions = get_latex_instructions($text);
                    $renderer->doc .= p_latex_render($mode, $instructions, $info);
                    if (isset($params['footer']) && isset($params['large'])) {
                        $hspace = '[\textwidth+\marginparwidth+10mm]';
                        $vspace = '\vspace{4mm}';
                        $renderer->doc .=  $vspace.'\tablefooterlarge'.$hspace.'{'.trim($renderer->_xmlEntities($params['footer'])).'}';
                    }
                    if (isset($params['large'])){
                        $renderer->doc .= '}'.DOKU_LF;
                    }elseif (isset($params['vertical'])){
                        $renderer->doc .= '\end{landscape}'.DOKU_LF;
                    }elseif (isset($params['small'])){
                        $renderer->doc .= '\end{SCtable}'.DOKU_LF;
                    }
                    $renderer->doc .= '\vspace{-2ex}\par'.DOKU_LF;
                    $_SESSION['table_id'] = '';
                    $_SESSION['table_title'] = '';
                    $_SESSION['table_footer'] = '';
                    $_SESSION['table_large'] = FALSE;
                    $_SESSION['table_small'] = FALSE;
                    break;
                case DOKU_LEXER_EXIT :
                    $this->id = '';
                    break;
            }
            return TRUE;
        }elseif ($mode === 'xhtml'){
            list ($state, $text, $id, $params) = $data;
            switch ($state) {
                    case DOKU_LEXER_ENTER :
                        $renderer->doc .= '<div class="ioctable">';
                        $renderer->doc .= '<div class="iocinfo">';
                        $renderer->doc .= '<a name="'.$id.'">';
                        $renderer->doc .= '<strong>ID:</strong> '.$id.'<br />';
                        $renderer->doc .= '</a>';
                        break;
                    case DOKU_LEXER_UNMATCHED :
                        if (isset($params['title'])){
                            $instructions = p_get_instructions($params['title']);
                            $title = preg_replace('/(<p>)(.*?)(<\/p>)/s','<span>$2</span>',p_render($mode, $instructions, $info));
                            $renderer->doc .= '<strong>T&iacute;tol:</strong> '.$title.'<br />';
                        }
                        if (isset($params['footer'])){
                            $renderer->doc .= '<strong>Peu:</strong> '.$params['footer'].'<br />';
                        }
                        $renderer->doc .= '</div>';
                        $instructions = p_get_instructions($text);
                        $renderer->doc .= p_render($mode, $instructions, $info);
                        $renderer->doc .= '</div>';
                        break;
                    case DOKU_LEXER_EXIT :
                        break;
                }
            return TRUE;
        }elseif ($mode === 'iocxhtml'){
            list ($state, $text, $id, $params) = $data;
            switch ($state) {
                    case DOKU_LEXER_ENTER :
                        $renderer->doc .= '<div class="ioctable">';
                        $renderer->doc .= '<a name="'.$id.'">';
                        $renderer->doc .= '<strong>ID:</strong> '.$id.'<br />';
                        $renderer->doc .= '</a>';
                        break;
                    case DOKU_LEXER_UNMATCHED :
                        if (isset($params['title'])){
                            $renderer->doc .= '<strong>T&iacute;tol:</strong> '.$params['title'].'<br />';
                        }
                        if (isset($params['footer'])){
                            $renderer->doc .= '<strong>Peu:</strong> '.$params['footer'].'<br />';
                        }
                        $instructions = get_latex_instructions($text);
                        $renderer->doc .= p_latex_render($mode, $instructions, $info);
                        $renderer->doc .= '</div>';
                        break;
                    case DOKU_LEXER_EXIT :
                        break;
                }
            return TRUE;
        }
        return FALSE;
    }
}
