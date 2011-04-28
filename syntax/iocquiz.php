<?php
/**
 * Iocquiz tag Syntax Plugin
 *
 * @author     Marc Català <mcatala@ioc.cat>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');
 

class syntax_plugin_iocexportl_iocquiz extends DokuWiki_Syntax_Plugin {

    var $class;
   /**
    * Get an associative array with plugin info.
    */
    function getInfo(){
        return array(
            'author' => 'Marc Català',
            'email'  => 'mcatala@ioc.cat',
            'date'   => '2011-03-21',
            'name'   => 'IOC quiz Plugin',
            'desc'   => 'Plugin to parse quiz tags',
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
        $this->Lexer->addEntryPattern('<quiz.*?>(?=.*?</quiz>)',$mode,'plugin_iocexportl_iocquiz');
    }
    function postConnect() {
        $this->Lexer->addExitPattern('</quiz>','plugin_iocexportl_iocquiz');
    }
 
    /**
     * Handle the match
     */

    function handle($match, $state, $pos, &$handler){
        $opt = array();
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $class = trim(substr($match,5,-1));
                return array($state, $class);
            
            case DOKU_LEXER_UNMATCHED :
                return array($state, $match);
            
            default:
                return array($state);
        }
    }

   /**
    * output
    */
    function render($mode, &$renderer, $data) {
        if($mode === 'ioccounter'){
            list($state, $text) = $data;
            switch ($state) {
              case DOKU_LEXER_ENTER :
                  break;
              case DOKU_LEXER_UNMATCHED :
                  $instructions = get_latex_instructions($text);
                  $renderer->doc .= p_latex_render($mode, $instructions, $info);
                  break;
              case DOKU_LEXER_EXIT :
                  break;
            }
            return true;
        }elseif($mode === 'iocexportl'){
            list($state, $text) = $data;
            switch ($state) {
              case DOKU_LEXER_ENTER :
                  $this->class = $text;
                  break;
              case DOKU_LEXER_UNMATCHED :
                  //convert unnumered lists to numbered
                  $_SESSION['quizmode'] = $this->class;
                  if ($this->class !== 'complete' && $this->class !== 'relations'){
                     $text = $this->getsolutions($text);     
                  }
                  if ($this->class === 'relations'){
                      $text = preg_replace('/(\n)(\n  \*)/', '$1'.DOKU_LF.'@IOCRELATIONS@'.DOKU_LF.'$2', $text, 1);
                  }
                  $instructions = get_latex_instructions($text);
                  //$renderer->doc .= p_render($mode, $instructions, $info);
                  $renderer->doc .= p_latex_render($mode, $instructions, $info);              
                  $_SESSION['quizmode'] = false;
                  break;
              case DOKU_LEXER_EXIT :
                  if ($this->class === 'relations'){
                      $this->printoptions($renderer);
                  }
                  $this->printsolutions($renderer);
                  $this->class='';         
                  unset($_SESSION['quizsol']);     
                  break;
            }
            return true;
        }
        return false;
    }
    
    function getsolutions($text){
        $matches = array();
        $_SESSION['quizsol'] = array();
        if ($this->class === 'choice'){
            preg_match_all('/  \*.*?\n/', $text, $matches);
            $count = 1;
            foreach ($matches[0] as $match){
                if (preg_match('/\(ok\)/',$match)){
                    array_push($_SESSION['quizsol'], $count);
                }
                $count += 1;
            }
            $text = preg_replace('/(  \*.*?)\(ok\)/', '$1', $text);
        }elseif ($this->class === 'vf'){
            preg_match_all('/  \*.*?\((V|F)\)/', $text, $matches);
            foreach ($matches[1] as $match){
                array_push($_SESSION['quizsol'], $match);    
            }
            $text = preg_replace('/(  \*.*?)\((V|F)\)/', '$1', $text);
        }
        return $text;
    }
    
    function printsolutions($renderer){
        if (!empty($_SESSION['quizsol'])){
            $renderer->doc .= '\rotatebox[origin=c]{180}{'.DOKU_LF;
            $renderer->doc .= '\parbox{\textwidth}{ \small';
            $renderer->doc .= '\textbf{Solució: }';
            if ($this->class !== 'choice'){
                $renderer->doc .= '\begin{inparaenum}'.DOKU_LF;
            }
            $count = count($_SESSION['quizsol']);
            $separator = ($this->class === 'choice')?',':';';
            foreach ($_SESSION['quizsol'] as $key => $sol){
              if ($this->class !== 'choice'){
                  $renderer->listitem_open(1);
              }
              $renderer->doc .= $sol;//.'\hspace{2mm}';
              if ($key < $count-1){
                  $renderer->doc .= $separator.'\hspace{1mm}';
              }
              
            }
            if ($this->class !== 'choice'){
                $renderer->doc .= '\end{inparaenum}'.DOKU_LF;
            }
            $renderer->doc .= '}}'.DOKU_LF;
            unset ($_SESSION['quizsol']);
       }
    }
    
    function printoptions($renderer){
      if (!empty($_SESSION['quizsol'])){
          $sol = array();
          $aux = array();
          foreach ($_SESSION['quizsol'] as $s){
              array_push($sol,$s);
              array_push($aux,$s);
          }
          $_SESSION['quizsol'] = array();
          //Sort solutions
          sort($sol);          
          foreach ($aux as $s){
              $pos = array_search($s, $sol, true);
              array_push($_SESSION['quizsol'],chr(ord('a')+$pos));
          }
          $text = '\optrelations{'.DOKU_LF;
          $count = count($sol);
          foreach ($sol as $key => $s){
              $text .= '\mbox{';
              $text .= '\item ';
              $text .= $s .'}';
              if ($key < $count-1){ 
              	$text .= '\hspace{5mm}';
              }
          }
          $text .= '}'.DOKU_LF;
          $renderer->doc = preg_replace('/@IOCRELATIONS@/',$text, $renderer->doc, 1);
      }
    }
}
