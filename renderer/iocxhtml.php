<?php
/**
 * LaTeX Plugin: Export content to HTML
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Marc Català <mcatala@ioc.cat>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('DOKU_PLUGIN_LATEX_TMP')) define('DOKU_PLUGIN_LATEX_TMP',DOKU_PLUGIN.'tmp/latex/');
require_once DOKU_INC.'inc/parser/renderer.php';

/**
 * The Renderer
 */
class renderer_plugin_iocxhtml extends Doku_Renderer {

	/**
	 * 	XHTML variables
	 */
    // @access public
    var $doc = '';        // will contain the whole document
    var $toc = array();   // will contain the Table of Contents

    private $sectionedits = array(); // A stack of section edit data

    var $headers = array();
    var $footnotes = array();
    var $lastlevel = 0;
    var $node = array(0,0,0,0,0);
    var $store = '';

    var $_counter   = array(); // used as global counter, introduced for table classes
    var $_codeblock = 0; // counts the code and file blocks, used to provide download links


    var $code = FALSE;
    var $col_colspan;
    var $col_num = 1;
    static $convert = FALSE;//convert images to $imgext
    var $endimg = FALSE;
    var $formatting = '';
    static $hr_width = 354;
	var $id = '';
    static $imgext = '.pdf';//Format to convert images
    static $img_max_table = 99;//Image max width inside tables
    var $max_cols = 0;
    var $monospace = FALSE;
    static $p_width = 360;//415.12572;
    var $table = FALSE;
    var $tableheader = FALSE;
    var $tableheader_count = 0;//Only one header per table
    var $tableheader_end = FALSE;
    var $tmp_dir = 0;//Value of temp dir


    /**
     * Return version info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }

    /**
     * Returns the format produced by this renderer.
     */
    function getFormat(){
        return "iocxhtml";
    }

    /**
     * Make multiple instances of this class
     */
    function isSingleton(){
        return FALSE;
    }

    function reset(){
        $this->doc = '';
    }

    /**
     * Initialize the rendering
     */
    function document_start() {
        global $USERINFO;
        global $conf;

        //reset some internals
        $this->toc     = array();
        $this->headers = array();

		$this->id = getID();
        //Check whether user can export
		$exportallowed = (isset($conf['plugin']['iocexportl']['allowexport']) && $conf['plugin']['iocexportl']['allowexport']);
        if (!$exportallowed && !auth_isadmin()) die;
/*
        if (!isset($_SESSION['tmp_dir'])){
            $this->tmp_dir = rand();
        }else{
            $this->tmp_dir = $_SESSION['tmp_dir'];
        }
        if (!file_exists(DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir)){
            mkdir(DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir, 0775, TRUE);
            mkdir(DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media', 0775, TRUE);
        }
        if ($_SESSION['u0']){
            //copy(DOKU_PLUGIN.'iocexportl/templates/backgroundu0.pdf', DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media/backgroundu0.pdf');
        }else{
            //copy(DOKU_PLUGIN.'iocexportl/templates/background.pdf', DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media/background.pdf');
        }*/

        //Global variables
        $this->_initialize_globals();
    }

    /**
     * Closes the document
     */
    function document_end(){
/*        $this->doc = preg_replace('/@IOCKEYSTART@/','\{', $this->doc);
        $this->doc = preg_replace('/@IOCKEYEND@/','\}', $this->doc);
        $this->doc = preg_replace('/@IOCBACKSLASH@/',"\\\\", $this->doc);
        $this->doc = preg_replace('/(textbf{)(\s*)(.*?)(\s*)(})/',"$1$3$5", $this->doc);
        $this->doc = preg_replace('/(raggedright)(\s{2,*})/',"$1 ", $this->doc);
		$this->_create_refs();*/
    }


    /**
     * Register a new edit section range
     *
     * @param $type  string The section type identifier
     * @param $title string The section title
     * @param $start int    The byte position for the edit start
     * @return string A marker class for the starting HTML element
     * @author Adrian Lang <lang@cosmocode.de>
     */
    public function startSectionEdit($start, $type, $title = null) {
        static $lastsecid = 0;
        $this->sectionedits[] = array(++$lastsecid, $start, $type, $title);
        return 'sectionedit' . $lastsecid;
    }

    /**
     * Finish an edit section range
     *
     * @param $end int The byte position for the edit end; null for the rest of
                       the page
     * @author Adrian Lang <lang@cosmocode.de>
     */
    public function finishSectionEdit($end = null) {
        list($id, $start, $type, $title) = array_pop($this->sectionedits);
        if (!is_null($end) && $end <= $start) {
            return;
        }
        $this->doc .= "<!-- EDIT$id " . strtoupper($type) . ' ';
        if (!is_null($title)) {
            $this->doc .= '"' . str_replace('"', '', $title) . '" ';
        }
        $this->doc .= "[$start-" . (is_null($end) ? '' : $end) . '] -->';
    }


    /**
     * _getMediaLinkConf is a helperfunction to internalmedia() and externalmedia()
     * which returns a basic link to a media.
     *
     * @author Pierre Spring <pierre.spring@liip.ch>
     * @param string $src
     * @param string $title
     * @param string $align
     * @param string $width
     * @param string $height
     * @param string $cache
     * @param string $render
     * @access protected
     * @return array
     */
    function _getMediaLinkConf($src, $title, $align, $width, $height, $cache, $render)
    {
        global $conf;

        $link = array();
        $link['class']  = 'media';
        $link['style']  = '';
        $link['pre']    = '';
        $link['suf']    = '';
        $link['more']   = '';
        $link['target'] = $conf['target']['media'];
        $link['title']  = $this->_xmlEntities($src);
        $link['name']   = $this->_media($src, $title, $align, $width, $height, $cache, $render);

        return $link;
    }

	/**
     * Creates a linkid from a headline
     *
     * @param string  $title   The headline title
     * @param boolean $create  Create a new unique ID?
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function _headerToLink($title,$create=false) {
        if($create){
            return sectionID($title,$this->headers);
        }else{
            $check = false;
            return sectionID($title,$check);
        }
    }

	/**
     * NOVA
     *//*
    function _create_refs(){
		$this->doc = preg_replace('/:figure:(.*?):/',"\\\\MakeLowercase{\\\\figurename}  \\\\ref{\\1}", $this->doc);
		$this->doc = preg_replace('/:table:(.*?):/',"\\\\MakeLowercase{\\\\tablename}  \\\\ref{\\1}", $this->doc);
    }*/

	/**
     * NOVA
     */
    function _initialize_globals(){
        if (!isset($_SESSION['activities_header'])){
            $_SESSION['activities_header'] = FALSE;
        }
        if (!isset($_SESSION['activities'])){
            $_SESSION['activities'] = FALSE;
        }
        if (!isset($_SESSION['chapter'])){
            $_SESSION['chapter'] = 1;
        }
        if (!isset($_SESSION['createbook'])){
            $_SESSION['createbook'] = FALSE;
        }
        if (!isset($_SESSION['draft'])){
            $_SESSION['draft'] = FALSE;
        }
        if (!isset($_SESSION['figfooter'])){
            $_SESSION['figfooter'] = '';
        }
        if (!isset($_SESSION['figlabel'])){
            $_SESSION['figlabel'] = '';
        }
        if (!isset($_SESSION['figtitle'])){
            $_SESSION['figtitle'] = '';
        }
        if (!isset($_SESSION['figure'])){
            $_SESSION['figure'] = FALSE;
        }
        if (!isset($_SESSION['iocelem'])){
            $_SESSION['iocelem'] = FALSE;
        }
        if (!isset($_SESSION['imgB'])){
            $_SESSION['imgB'] = FALSE;
        }
        if (!isset($_SESSION['qrcode'])){
            $_SESSION['qrcode'] = FALSE;
        }
        if (!isset($_SESSION['quizmode'])){
            $_SESSION['quizmode'] = FALSE;
        }
        if (!isset($_SESSION['table_id'])){
            $_SESSION['table_id'] = '';
        }
        if (!isset($_SESSION['table_footer'])){
            $_SESSION['table_footer'] = '';
        }
        if (!isset($_SESSION['table_large'])){
            $_SESSION['table_large'] = FALSE;
        }
        if (!isset($_SESSION['table_title'])){
            $_SESSION['table_title'] = '';
        }
        if (!isset($_SESSION['u0'])){
            $_SESSION['u0'] = FALSE;
        }
        if (!isset($_SESSION['video_url'])){
            $_SESSION['video_url'] = FALSE;
        }
    }


    /**
     * Use GeSHi to highlight language syntax in code and file blocks
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function _highlight($type, $text, $language=null, $filename=null) {
        global $conf;
        global $ID;
        global $lang;

        if($filename){
            // add icon
            list($ext) = mimetype($filename,false);
            $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
            $class = 'mediafile mf_'.$class;

            $this->doc .= '<dl class="'.$type.'">'.DOKU_LF;
            $this->doc .= '<dt><a href="'.exportlink($ID,'code',array('codeblock'=>$this->_codeblock)).'" title="'.$lang['download'].'" class="'.$class.'">';
            $this->doc .= hsc($filename);
            $this->doc .= '</a></dt>'.DOKU_LF.'<dd>';
        }

        if ($text{0} == "\n") {
            $text = substr($text, 1);
        }
        if (substr($text, -1) == "\n") {
            $text = substr($text, 0, -1);
        }

        if ( is_null($language) ) {
            $this->doc .= '<pre class="'.$type.'">'.$this->_xmlEntities($text).'</pre>'.DOKU_LF;
        } else {
            $class = 'code'; //we always need the code class to make the syntax highlighting apply
            if($type != 'code') $class .= ' '.$type;

            $this->doc .= "<pre class=\"$class $language\">".p_xhtml_cached_geshi($text, $language, '').'</pre>'.DOKU_LF;
        }

        if($filename){
            $this->doc .= '</dd></dl>'.DOKU_LF;
        }

        $this->_codeblock++;
    }

    /**
     * Returns an HTML code for images used in link titles
     *
     * @todo Resolve namespace on internal images
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function _imageTitle($img) {
        global $ID;

        // some fixes on $img['src']
        // see internalmedia() and externalmedia()
        list($img['src'],$hash) = explode('#',$img['src'],2);
        if ($img['type'] == 'internalmedia') {
            resolve_mediaid(getNS($ID),$img['src'],$exists);
        }

        return $this->_media($img['src'],
                              $img['title'],
                              $img['align'],
                              $img['width'],
                              $img['height'],
                              $img['cache']);
    }

    /**
     * NOVA
     */
    function _format_text($text){
        $text = $this->_ttEntities(trim($text));//Remove extended symbols
        if ($_SESSION['iocelem']){
            $text = preg_replace('/\n/',"^^J$1", $text);
        }
        $this->doc .= $text . DOKU_LF;
    }

    /**
     * NOVA
     */
    function label_document() { //For links
        if (isset($this->info['current_file_id'])) {
          $cleanid = $this->info['current_file_id'];
        }
        else {
          $cleanid = noNS(cleanID($this->info['current_id'], TRUE));
        }
        $this->doc .= "\label{" . md5($cleanid) . "}";
        if (isset($this->info['current_file_id'])){
          $this->doc .= "%%Start: " . $cleanid . ' => '
    		   . $this->info['current_file_id'].DOKU_LF;
        } else {
          $this->doc .= "%%Start: " . $cleanid . ' => ' . wikiFN($cleanid).DOKU_LF;
        }
      }

     /**
     * NOVA
     */
    function _latexEntities($string, $ent=null) {
        return $this->_xmlEntities($string);
    }

    /**
     * NOVA
     */
    function smiley($smiley) {
        if ( array_key_exists($smiley, $this->smileys) ) {
            $title = $this->_xmlEntities($this->smileys[$smiley]);
            $this->doc .= '<img src="'.DOKU_BASE.'lib/images/smileys/'.$this->smileys[$smiley].
                '" class="middle" alt="'.
                    $this->_xmlEntities($smiley).'" />';
        } else {
            $this->doc .= $this->_xmlEntities($smiley);
        }
      }

     /**
     * NOVA
     */
      function _image_convert($img, $dest, $width = NULL, $height = NULL){
        $imgdest = tempnam($dest, 'ltx');
        $resize = '';
        if ($width && $height){
            $resize = "-resize $width"."x"."$height";
        }
        @exec("convert $img $resize $imgdest".self::$imgext);
        return $imgdest.self::$imgext;
      }

     /**
     * NOVA
     */
    function _latexAddImage($src, $width = NULL, $height = NULL, $align = NULL, $title = NULL, $linking = NULL, $external = FALSE){
        if (!empty($_SESSION['figtitle'])){
            $title = $_SESSION['figtitle'];
        }
        if (!empty($_SESSION['figfooter'])){
            $title .= '/'.$_SESSION['figfooter'];
        }
        // make sure width and height are available
        if (!$width && !$height) {
            if (file_exists($src)) {
                $info  = getimagesize($src);
                $width  = $info[0];
            }
        }else{
            if (file_exists($src)) {
                $info  = getimagesize($src);
                $ratio = $info[0]/$info[1];
                if(!$width){
                    $width = round($height * $ratio, 0);
                }
            }
        }
        if (!$_SESSION['u0']){
            $align = 'centering';
        }else{//Unit 0
            $align = 'flushleft';
        }
        if (!$this->table && !$_SESSION['figure'] && !$_SESSION['video_url'] && $_SESSION['iocelem'] !== 'textl'){
            $max_width = '[width=35mm]';
            $img_width = FALSE;
        }elseif (!$this->table && $width > self::$p_width && $_SESSION['iocelem'] !== 'textl'){
            $max_width = '[width=\textwidth]';
            $img_width = FALSE;
        }else{
            $max_width = '[width='.$width.'px]';
            $img_width = $width;
        }
        if (self::$convert || $_SESSION['draft'] || $external){
            $img_aux = $this->_image_convert($src, DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media');
        }else{
            $img_aux = DOKU_PLUGIN_LATEX_TMP . $this->tmp_dir . '/media/' . basename($src);
            if (file_exists($src)){
                copy($src, $img_aux);
            }
        }
        if (file_exists($img_aux)){
            if ($_SESSION['iocelem'] === 'textl'){
                $this->doc .=  '\begin{center}'.DOKU_LF;
                if ($width > (.8 * self::$p_width)){
                    $this->doc .= '\resizebox{.8\linewidth}{!}{';
                }
            }elseif (!$this->table && !$_SESSION['figure'] && !$_SESSION['video_url'] && !$_SESSION['u0']){
                $offset = '';
                //Extract offset
                if ($title){
                    $data = preg_replace('/<verd>|<\/verd>/', '', $title);
                    $data = split('/', $title, 2);
                    $title = $data[0].'/';
                    if(!empty($data[1])){
                        $offset = '['.$data[1].'mm]';
                    }
                }
                $this->doc .= '\imgB'.$offset.'{';
            }elseif (!$this->table && $_SESSION['figure'] && !$_SESSION['video_url'] && !$_SESSION['u0']){
                $this->doc .= '\begin{figure}[H]'.DOKU_LF;
            }
            if ($linking !== 'details'){
                $this->doc .= '\href{'.$linking.'}{';
            }
            if ($_SESSION['figure']){
                $this->doc .= '\\' . $align . DOKU_LF;
            }
            $hspace = 0;//Align text and image
            if ($title) {
                $title = preg_replace('/<verd>|<\/verd>/', '', $title);
                $title = split('/', $title, 2);
                $title_width = ($img_width)?$img_width.'px':'\textwidth';
                  if ($_SESSION['figure']){
                    $this->doc .= '\parbox[t]{'.$title_width.'}{\caption{'.trim($this->_xmlEntities($title[0]));
    				if (!empty($_SESSION['figlabel'])){
    	                $this->doc .= '\label{'.$_SESSION['figlabel'].'}';
    				}
    				$this->doc .= '}}\\\\\vspace{2mm}'.DOKU_LF;
                }else{
					if (empty($title[1])){
						$title[1] = $title[0];
					}
                }
            }
            //Inside table, images will be centered vertically
            if ($this->table && $width > self::$img_max_table){
                $this->doc .= '\resizebox{\linewidth}{!}{';
            }
                $this->doc .= '\includegraphics'.$max_width.'{media/'.basename($img_aux).'}';
            if($_SESSION['iocelem'] === 'textl'){
                if ($width > (.8 * self::$p_width)){
                    $this->doc .= '}' . DOKU_LF;
                }
                $this->doc .= '\end{center}' . DOKU_LF;
            }elseif ($this->table && $width > self::$img_max_table){
                $this->doc .= '}';

            }
			//Close href
            if ($linking !== 'details'){
                $this->doc .= '}';
                if (!$_SESSION['video_url']){
                    $this->doc .= 'DOKU_LF';
                }
            }
            if (!$_SESSION['video_url'] && !empty($title[1])){
                $this->doc .= DOKU_LF;
            }
            if ($title[1]) {
                if ($_SESSION['figure']){
                    if ($img_width){
                        $hspace = ($img_width + $hspace).'pt';
                    }else{
                       $hspace = '\textwidth';
                    }
					$vspace = '\vspace{-2mm}';
					$align = '\raggedleft';
                }elseif($_SESSION['iocelem'] === 'textl'){
                        //textboxsize .05
                        $hspace = '.9\linewidth';
                        $vspace = '\vspace{-6mm}';
                        $align = '\raggedleft';
                }else{
                    $hspace = '\marginparwidth';
					$vspace = '\vspace{-4mm}';
					$align = '\iocalignment';
                }
                $this->doc .=  '\raisebox{\height}{\parbox[t]{'.$hspace.'}{'.$align.'\footerspacingline\textsf{\tiny'.$vspace.trim($this->_xmlEntities($title[1])).'}}}';
                $thid->doc .= '}';

            }
            if (!$this->table && $_SESSION['figure'] && !$_SESSION['video_url'] && !$_SESSION['iocelem'] && !$_SESSION['u0']){
                $this->doc .= '\end{figure}';
            }elseif (!$this->table && !$_SESSION['figure'] && !$_SESSION['video_url'] && !$_SESSION['iocelem'] && !$_SESSION['u0']){
                if (!empty($title[1])){
                    $this->doc .= DOKU_LF;
                }
                $this->doc .= '}' . DOKU_LF;
            }
            if ($_SESSION['iocelem'] === 'textl'){
                $this->doc .= '\vspace{1ex}' . DOKU_LF;
            }
            $this->endimg = TRUE;
        }else{
            $this->doc .= '\textcolor{red}{\textbf{File '. $this->_xmlEntities(basename($src)).' does not exist.}}';
        }
    }

    function render_TOC() {
         return '';
    }

    function toc_additem($id, $text, $level) {
    global $conf;

        //handle TOC
        if($level >= $conf['toptoclevel'] && $level <= $conf['maxtoclevel']){
            $this->toc[] = html_mktocitem($id, $text, $level-$conf['toptoclevel']+1);
        }
    }

    function section_open($level) {
        $this->doc .= '<div class="level' . $level . '">' . DOKU_LF;
    }

    function section_close() {
        $this->doc .= DOKU_LF.'</div>'.DOKU_LF;
    }

    function cdata($text) {
        if ($this->monospace){
            $text = preg_replace('/\n/', '<br />', $text);
        }
        $this->doc .= $this->_xmlEntities($text);
    }

    function p_open(){
        $this->doc .= DOKU_LF.'<p>'.DOKU_LF;
    }

    function p_close(){
        $this->doc .= DOKU_LF.'</p>'.DOKU_LF;
    }

    function header($text, $level, $pos){
        global $conf;

        if(!$text) return; //skip empty headlines

        $hid = $this->_headerToLink($text,true);
		/*
        //only add items within configured levels
        $this->toc_additem($hid, $text, $level);

        // adjust $node to reflect hierarchy of levels
        $this->node[$level-1]++;
        if ($level < $this->lastlevel) {
            for ($i = 0; $i < $this->lastlevel-$level; $i++) {
                $this->node[$this->lastlevel-$i-1] = 0;
            }
        }
        $this->lastlevel = $level;

        if ($level <= $conf['maxseclevel'] &&
            count($this->sectionedits) > 0 &&
            $this->sectionedits[count($this->sectionedits) - 1][2] === 'section') {
            $this->finishSectionEdit($pos - 1);
        }*/

        // write the header
        $this->doc .= DOKU_LF.'<h'.$level;
/*        if ($level <= $conf['maxseclevel']) {
            $this->doc .= ' class="' . $this->startSectionEdit($pos, 'section', $text) . '"';
        }*/
        //$this->doc .= '><a name="'.$hid.'" id="'.$hid.'">';
        $this->doc .= '><a id="'.$hid.'" >';
        $this->doc .= $this->_xmlEntities($text);
        $this->doc .= "</a></h$level>".DOKU_LF;
    }

    function hr() {
        $this->doc .= '<hr />'.DOKU_LF;
    }

    function linebreak() {
        $this->doc .= '<br/>'.DOKU_LF;
    }

    function strong_open() {
        $this->doc .= '<strong>';
    }

    function strong_close() {
        $this->doc .= '</strong>';
    }

    function emphasis_open() {
        $this->doc .= '<em>';
    }

    function emphasis_close() {
        $this->doc .= '</em>';
    }

    function underline_open() {
        $this->doc .= '<em class="u">';
    }

    function underline_close() {
        $this->doc .= '</em>';
    }

    function monospace_open() {
       $this->doc .= '<code>';
    }

    function monospace_close() {
       $this->doc .= '</code>';
    }

    function subscript_open() {
        $this->doc .= '<sub>';
    }

    function subscript_close() {
        $this->doc .= '</sub>';
    }

    function superscript_open() {
        $this->doc .= '<sup>';
    }

    function superscript_close() {
        $this->doc .= '</sup>';
    }

    function deleted_open() {
        $this->doc .= '<del>';
    }

    function deleted_close() {
        $this->doc .= '</del>';
    }

    /*
     * Tables
     */
    function table_open($maxcols = NULL, $numrows = NULL){
        global $lang;
        // initialize the row counter used for classes
        $this->_counter['row_counter'] = 0;
        $class = 'table';
        /*if ($pos !== null) {
            $class .= ' ' . $this->startSectionEdit($pos, 'table');
        }*/
        $this->doc .= '<div class="' . $class . '"><table class="inline">' .
                      DOKU_LF;
    }

    function table_close(){
        $this->doc .= '</table></div>'.DOKU_LF;
        if ($pos !== null) {
            $this->finishSectionEdit($pos);
        }
    }

    function tablerow_open(){
        // initialize the cell counter used for classes
        $this->_counter['cell_counter'] = 0;
        $class = 'row' . $this->_counter['row_counter']++;
        $this->doc .= DOKU_TAB . '<tr class="'.$class.'">' . DOKU_LF . DOKU_TAB . DOKU_TAB;
    }

    function tablerow_close(){
        $this->doc .= DOKU_LF . DOKU_TAB . '</tr>' . DOKU_LF;
    }

    function tableheader_open($colspan = 1, $align = NULL, $rowspan = 1){
        $class = 'class="col' . $this->_counter['cell_counter']++;
        if ( !is_null($align) ) {
            $class .= ' '.$align.'align';
        }
        $class .= '"';
        $this->doc .= '<th ' . $class;
        if ( $colspan > 1 ) {
            $this->_counter['cell_counter'] += $colspan-1;
            $this->doc .= ' colspan="'.$colspan.'"';
        }
        if ( $rowspan > 1 ) {
            $this->doc .= ' rowspan="'.$rowspan.'"';
        }
        $this->doc .= '>';
    }

    function tableheader_close(){
        $this->doc .= '</th>';
    }

    function tablecell_open($colspan = 1, $align = NULL, $rowspan = 1){
        $class = 'class="col' . $this->_counter['cell_counter']++;
        if ( !is_null($align) ) {
            $class .= ' '.$align.'align';
        }
        $class .= '"';
        $this->doc .= '<td '.$class;
        if ( $colspan > 1 ) {
            $this->_counter['cell_counter'] += $colspan-1;
            $this->doc .= ' colspan="'.$colspan.'"';
        }
        if ( $rowspan > 1 ) {
            $this->doc .= ' rowspan="'.$rowspan.'"';
        }
        $this->doc .= '>';
    }

    function tablecell_close(){
        $this->doc .= '</td>';
    }

    function footnote_open() {}

    function footnote_close() {}

    function listu_open() {
        $this->doc .= '<ul>'.DOKU_LF;
    }

    function listu_close() {
        $this->doc .= '</ul>'.DOKU_LF;
    }

    function listo_open() {
        $this->doc .= '<ol>'.DOKU_LF;
    }

    function listo_close() {
        $this->doc .= '</ol>'.DOKU_LF;
    }

    function listitem_open($level) {
        $this->doc .= '<li class="level'.$level.'">';
    }

    function listitem_close() {
        $this->doc .= '</li>'.DOKU_LF;
    }

    function listcontent_open() {
        $this->doc .= '<div class="li">';
    }

    function listcontent_close() {
        $this->doc .= '</div>'.DOKU_LF;
    }

    function unformatted($text) {
        $this->doc .= $this->_xmlEntities($text);
    }

    function acronym($acronym) {
        if ( array_key_exists($acronym, $this->acronyms) ) {

            $title = $this->_xmlEntities($this->acronyms[$acronym]);

            $this->doc .= '<acronym title="'.$title
                .'">'.$this->_xmlEntities($acronym).'</acronym>';

        } else {
            $this->doc .= $this->_xmlEntities($acronym);
        }
    }

    function entity($entity) {
        if ( array_key_exists($entity, $this->entities) ) {
            $this->doc .= $this->entities[$entity];
        } else {
            $this->doc .= $this->_xmlEntities($entity);
        }
    }

    function multiplyentity($x, $y) {
        $this->doc .= "$x&times;$y";
    }

    function singlequoteopening() {
        global $lang;
        $this->doc .= $lang['singlequoteopening'];
    }

    function singlequoteclosing() {
        global $lang;
        $this->doc .= $lang['singlequoteclosing'];
    }

    function apostrophe() {
        global $lang;
        $this->doc .= $lang['apostrophe'];
    }

    function doublequoteopening() {
        global $lang;
        $this->doc .= $lang['doublequoteopening'];
    }

    function doublequoteclosing() {
        global $lang;
        $this->doc .= $lang['doublequoteclosing'];;
    }

    function php($text, $wrapper='dummy') {
        global $conf;

        if($conf['phpok']){
          ob_start();
          eval($text);
          $this->doc .= ob_get_contents();
          ob_end_clean();
        } else {
          $this->doc .= p_xhtml_cached_geshi($text, 'php', $wrapper);
        }
    }

    function phpblock($text) {
        $this->php($text, 'pre');
    }

    function html($text, $wrapper='dummy') {
        global $conf;

        if($conf['htmlok']){
          $this->doc .= $text;
        } else {
          $this->doc .= p_xhtml_cached_geshi($text, 'html4strict', $wrapper);
        }
    }

    function htmlblock($text) {
        $this->html($text, 'pre');
    }

    function preformatted($text) {
        $this->doc .= '<pre class="code">' . trim($this->_xmlEntities($text),"\n\r") . '</pre>'. DOKU_LF;
    }

    function file($text) {
        $this->_highlight('file',$text,$language,$filename);
    }

    function quote_open() {
        $this->doc .= '<blockquote><div class="no">'.DOKU_LF;
    }

    function quote_close() {
        $this->doc .= '</div></blockquote>'.DOKU_LF;
    }

    function code($text, $language=null, $filename=null) {
        $this->_highlight('code',$text,$language,$filename);
    }

    function internalmedia ($src, $title=null, $align=null, $width=null,
                            $height=null, $cache=null, $linking=null) {
        global $ID;
        list($src,$hash) = explode('#',$src,2);
        resolve_mediaid(getNS($ID),$src, $exists);

        $noLink = false;
        $render = ($linking == 'linkonly') ? false : true;
        $link = $this->_getMediaLinkConf($src, $title, $align, $width, $height, $cache, $render);

        list($ext,$mime,$dl) = mimetype($src,false);
        if(substr($mime,0,5) == 'image' && $render){
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),($linking=='direct'));
        }elseif($mime == 'application/x-shockwave-flash' && $render){
            // don't link flash movies
            $noLink = true;
        }else{
            // add file icons
            $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
            $link['class'] .= ' mediafile mf_'.$class;
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),true);
        }

        if($hash) $link['url'] .= '#'.$hash;

        //markup non existing files
        if (!$exists)
          $link['class'] .= ' wikilink2';

        //output formatted
        //if ($linking == 'nolink' || $noLink) $this->doc .= $link['name'];
        //else $this->doc .= $this->_formatLink($link);
        $this->doc .= $link['name'];
    }

    function externalmedia ($src, $title=NULL, $align=NULL, $width=NULL,
                            $height=NULL, $cache=NULL, $linking=NULL) {
        global $conf;
        list($ext,$mime) = mimetype($src);
        if(substr($mime,0,5) == 'image'){
            $tmp_name = tempnam(DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media', 'ext');
            $client = new DokuHTTPClient;
            $img = $client->get($src);
            if (!$img) {
                $this->externallink($src, $title);
            } else {
                $tmp_img = fopen($tmp_name, "w") or die("Can't create temp file $tmp_img");
                fwrite($tmp_img, $img);
                fclose($tmp_img);
				//Add and convert image to pdf
                $this->_latexAddImage($tmp_name, $width, $height, $align, $title, $linking, TRUE);
            }
        }else{
            $this->externallink($src, $title);
        }
    }

    function camelcaselink($link) {
        $this->internallink($link,$link);
    }

    /**
     * Render an internal Wiki Link
     */
    function internallink($id, $name = NULL) {
    global $conf;
        global $ID;

        $params = '';
        $parts = explode('?', $id, 2);
        if (count($parts) === 2) {
            $id = $parts[0];
            $params = $parts[1];
        }

        // For empty $id we need to know the current $ID
        // We need this check because _simpleTitle needs
        // correct $id and resolve_pageid() use cleanID($id)
        // (some things could be lost)
        if ($id === '') {
            $id = $ID;
        }

        // default name is based on $id as given
        $default = $this->_simpleTitle($id);

        // now first resolve and clean up the $id
        resolve_pageid(getNS($ID),$id,$exists);

        $name = $this->_getLinkTitle($name, $default, $isImage, $id, $linktype);
        if ( !$isImage ) {
            if ( $exists ) {
                $class='wikilink1';
            } else {
                $class='wikilink2';
                $link['rel']='nofollow';
            }
        } else {
            $class='media';
        }

        //keep hash anchor
        list($id,$hash) = explode('#',$id,2);
        if(!empty($hash)) $hash = $this->_headerToLink($hash);

        //prepare for formating
        $link['target'] = $conf['target']['wiki'];
        $link['style']  = '';
        $link['pre']    = '';
        $link['suf']    = '';
        // highlight link to current page
        if ($id == $ID) {
            $link['pre']    = '<span class="curid">';
            $link['suf']    = '</span>';
        }
        $link['more']   = '';
        $link['class']  = $class;
        $link['url']    = wl($id, $params);
        $link['name']   = $name;
        $link['title']  = $id;
        //add search string
        if($search){
            ($conf['userewrite']) ? $link['url'].='?' : $link['url'].='&amp;';
            if(is_array($search)){
                $search = array_map('rawurlencode',$search);
                $link['url'] .= 's[]='.join('&amp;s[]=',$search);
            }else{
                $link['url'] .= 's='.rawurlencode($search);
            }
        }

        //keep hash
        if($hash) $link['url'].='#'.$hash;

        //output formatted
        if($returnonly){
            return $this->_formatLink($link);
        }else{
            $this->doc .= $this->_formatLink($link);
        }
    }

    /**
     * Add external link
     */
    function externallink($url, $title = NULL) {
        global $conf;

        $name = $this->_getLinkTitle($name, $url, $isImage);

        // url might be an attack vector, only allow registered protocols
        if(is_null($this->schemes)) $this->schemes = getSchemes();
        list($scheme) = explode('://',$url);
        $scheme = strtolower($scheme);
        if(!in_array($scheme,$this->schemes)) $url = '';

        // is there still an URL?
        if(!$url){
            $this->doc .= $name;
            return;
        }

        // set class
        if ( !$isImage ) {
            $class='urlextern';
        } else {
            $class='media';
        }

        //prepare for formating
        $link['target'] = $conf['target']['extern'];
        $link['style']  = '';
        $link['pre']    = '';
        $link['suf']    = '';
        $link['more']   = '';
        $link['class']  = $class;
        $link['url']    = $url;

        $link['name']   = $name;
        $link['title']  = $this->_xmlEntities($url);
        if($conf['relnofollow']) $link['more'] .= ' rel="nofollow"';

        //output formatted
        $this->doc .= $this->_formatLink($link);
   }

    /**
     * Just print local links
     *
     * @fixme add image handling
     */
    function locallink($hash, $name = NULL){
        global $ID;
        $name  = $this->_getLinkTitle($name, $hash, $isImage);
        $hash  = $this->_headerToLink($hash);
        $title = $ID.' &crarr;';
        $this->doc .= '<a href="#'.$hash.'" title="'.$title.'" class="wikilink1">';
        $this->doc .= $name;
        $this->doc .= '</a>';
    }

    /**
     * InterWiki links
     */
    function interwikilink($match, $name = NULL, $wikiName, $wikiUri) {
        global $conf;

        $link = array();
        $link['target'] = $conf['target']['interwiki'];
        $link['pre']    = '';
        $link['suf']    = '';
        $link['more']   = '';
        $link['name']   = $this->_getLinkTitle($name, $wikiUri, $isImage);

        //get interwiki URL
        $url = $this->_resolveInterWiki($wikiName,$wikiUri);

        if ( !$isImage ) {
            $class = preg_replace('/[^_\-a-z0-9]+/i','_',$wikiName);
            $link['class'] = "interwiki iw_$class";
        } else {
            $link['class'] = 'media';
        }

        //do we stay at the same server? Use local target
        if( strpos($url,DOKU_URL) === 0 ){
            $link['target'] = $conf['target']['wiki'];
        }

        $link['url'] = $url;
        $link['title'] = htmlspecialchars($link['url']);

        //output formatted
        $this->doc .= $this->_formatLink($link);
    }

    /**
     * Just print WindowsShare links
     *
     * @fixme add image handling
     */
    function windowssharelink($url, $name = NULL) {
        global $conf;
        global $lang;
        //simple setup
        $link['target'] = $conf['target']['windows'];
        $link['pre']    = '';
        $link['suf']   = '';
        $link['style']  = '';

        $link['name'] = $this->_getLinkTitle($name, $url, $isImage);
        if ( !$isImage ) {
            $link['class'] = 'windows';
        } else {
            $link['class'] = 'media';
        }


        $link['title'] = $this->_xmlEntities($url);
        $url = str_replace('\\','/',$url);
        $url = 'file:///'.$url;
        $link['url'] = $url;

        //output formatted
        $this->doc .= $this->_formatLink($link);
    }

    /**
     * Just print email links
     *
     * @fixme add image handling
     */
    function emaillink($address, $name = NULL) {
        global $conf;
        //simple setup
        $link = array();
        $link['target'] = '';
        $link['pre']    = '';
        $link['suf']   = '';
        $link['style']  = '';
        $link['more']   = '';

        $name = $this->_getLinkTitle($name, '', $isImage);
        if ( !$isImage ) {
            $link['class']='mail';
        } else {
            $link['class']='media';
        }

        $address = $this->_xmlEntities($address);
        $address = obfuscate($address);
        $title   = $address;

        if(empty($name)){
            $name = $address;
        }

        if($conf['mailguard'] == 'visible') $address = rawurlencode($address);

        $link['url']   = 'mailto:'.$address;
        $link['name']  = $name;
        $link['title'] = $title;

        //output formatted
        $this->doc .= $this->_formatLink($link);
    }

    /**
     * Construct a title and handle images in titles
     *
     * @author Harry Fuecks <hfuecks@gmail.com>
     */
    function _getLinkTitle($title, $default, & $isImage, $id=null) {
        global $conf;

        $isImage = false;
        if ( is_array($title) ) {
            $isImage = true;
            return $this->_imageTitle($title);
        } elseif ( is_null($title) || trim($title)=='') {
            if (useHeading($linktype) && $id) {
                $heading = p_get_first_heading($id);
                if ($heading) {
                    return $this->_xmlEntities($heading);
                }
            }
            return $this->_xmlEntities($default);
        } else {
            return $this->_xmlEntities($title);
        }
    }

    function _xmlEntities($value) {
        global $symbols;
        $matches = array();
        if (!$this->monospace){
            //Search mathematical formulas
            //echo $value.'INICIAL'.DOKU_LF;
            list($value, $replace) = $this->_latexElements($value);
            //echo $value.'FINAL';
            if ($replace){
                return $value;
            }
        }
        static $find = array('{','}','\\','_','^','<','>','#','%', '$', '&', '~', '"','−');
        static $replace = array('@IOCKEYSTART@', '@IOCKEYEND@', '\textbackslash ', '@IOCBACKSLASH@_', '@IOCBACKSLASH@^{}',
								'@IOCBACKSLASH@textless{}','@IOCBACKSLASH@textgreater{}','@IOCBACKSLASH@#','@IOCBACKSLASH@%', '@IOCBACKSLASH@$', '@IOCBACKSLASH@&', '@IOCBACKSLASH@~{}', '@IOCBACKSLASH@textquotedbl{}', '-');

        if ($this->monospace){
            $value = str_ireplace($find, $replace, $value);
            return preg_replace('/\n/', '\\newline ', $value);
        }else{
            return str_ireplace($find, $replace, $value);
        }
    }

    function _ttEntities($value) {
        global $symbols;
        return str_ireplace($symbols, ' (Invalid character) ', $value);
    }

    function _latexElements($value){
        //LaTeX mode
        $replace = FALSE;
        $value = preg_replace('/<latex>.*?<\/latex>/', '',$value);
        //Math block mode
        while(preg_match('/\${2}\n?([^\$]+)\n?\${2}/', $value, $matches)){
            $text = str_ireplace($symbols, ' (Invalid character) ', $matches[1]);
			$text = preg_replace('/(\$)/', '\\\\$1', $text);
            $value = preg_replace('/\${2}\n?([^\$]+)\n?\${2}/', '\begin{center}\begin{math}'.filter_tex_sanitize_formula($text).'\end{math}\end{center}', $value, 1);
            $replace = TRUE;
        }
        //Math inline mode
        if(preg_match_all('/\$\n?([^\$]+)\n?\$/', $value, $matches, PREG_SET_ORDER)){
            foreach($matches as $m){
                $text = str_ireplace($symbols, ' (Invalid character) ', $m[1]);
    			$text = preg_replace('/(\$)/', '\\\\$1', $text);
                $value = str_replace($m[0], '$ '.filter_tex_sanitize_formula($text).' $', $value);
                $replace = TRUE;
            }
        }
        return array($value, $replace);
    }

    function rss ($url,$params){
        global $lang;
        global $conf;

        require_once(DOKU_INC.'inc/FeedParser.php');
        $feed = new FeedParser();
        $feed->feed_url($url);

        //disable warning while fetching
        if (!defined('DOKU_E_LEVEL')) { $elvl = error_reporting(E_ERROR); }
        $rc = $feed->init();
        if (!defined('DOKU_E_LEVEL')) { error_reporting($elvl); }

        //decide on start and end
        if($params['reverse']){
            $mod = -1;
            $start = $feed->get_item_quantity()-1;
            $end   = $start - ($params['max']);
            $end   = ($end < -1) ? -1 : $end;
        }else{
            $mod   = 1;
            $start = 0;
            $end   = $feed->get_item_quantity();
            $end   = ($end > $params['max']) ? $params['max'] : $end;;
        }

        $this->listu_open();
        if($rc){
            for ($x = $start; $x != $end; $x += $mod) {
                $item = $feed->get_item($x);
                $this->listitem_open(0);
                $this->listcontent_open();
                $this->externallink($item->get_permalink(),
                                    $item->get_title());
                if($params['author']){
                    $author = $item->get_author(0);
                    if($author){
                        $name = $author->get_name();
                        if(!$name) $name = $author->get_email();
                        if($name) $this->cdata(' '.$lang['by'].' '.$name);
                    }
                }
                if($params['date']){
                    $this->cdata(' ('.$item->get_date($conf['dformat']).')');
                }
                if($params['details']){
                    $this->cdata(strip_tags($item->get_description()));
                }
                $this->listcontent_close();
                $this->listitem_close();
            }
        }else{
            $this->listitem_open(0);
            $this->listcontent_open();
            $this->emphasis_open();
            $this->cdata($lang['rssfailed']);
            $this->emphasis_close();
            $this->externallink($url);
            $this->listcontent_close();
            $this->listitem_close();
        }
        $this->listu_close();
    }


/*************************************
 * 				UTILS				 *
**************************************/


    /**
     * Build a link
     *
     * Assembles all parts defined in $link returns HTML for the link
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function _formatLink($link){
        //make sure the url is XHTML compliant (skip mailto)
        if(substr($link['url'],0,7) != 'mailto:'){
            $link['url'] = str_replace('&','&amp;',$link['url']);
            $link['url'] = str_replace('&amp;amp;','&amp;',$link['url']);
        }
        //remove double encodings in titles
        $link['title'] = str_replace('&amp;amp;','&amp;',$link['title']);

        // be sure there are no bad chars in url or title
        // (we can't do this for name because it can contain an img tag)
        $link['url']   = strtr($link['url'],array('>'=>'%3E','<'=>'%3C','"'=>'%22'));
        $link['title'] = strtr($link['title'],array('>'=>'&gt;','<'=>'&lt;','"'=>'&quot;'));

        $ret  = '';
        $ret .= $link['pre'];
        $ret .= '<a href="'.$link['url'].'"';
        if(!empty($link['class']))  $ret .= ' class="'.$link['class'].'"';
        if(!empty($link['target'])) $ret .= ' target="'.$link['target'].'"';
        if(!empty($link['title']))  $ret .= ' title="'.$link['title'].'"';
        if(!empty($link['style']))  $ret .= ' style="'.$link['style'].'"';
        if(!empty($link['rel']))    $ret .= ' rel="'.$link['rel'].'"';
        if(!empty($link['more']))   $ret .= ' '.$link['more'];
        $ret .= '>';
        $ret .= $link['name'];
        $ret .= '</a>';
        $ret .= $link['suf'];
        return $ret;
    }

    /**
     * Renders internal and external media
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function _media ($src, $title=NULL, $align=NULL, $width=NULL,
                      $height=NULL, $cache=NULL, $render = true) {

        $ret = '';

        list($ext,$mime,$dl) = mimetype($src);
        if(substr($mime,0,5) == 'image'){
            // first get the $title
            if (!is_null($title)) {
                $title  = $this->_xmlEntities($title);
            }elseif($ext == 'jpg' || $ext == 'jpeg'){
                //try to use the caption from IPTC/EXIF
                require_once(DOKU_INC.'inc/JpegMeta.php');
                $jpeg =new JpegMeta(mediaFN($src));
                if($jpeg !== false) $cap = $jpeg->getTitle();
                if($cap){
                    $title = $this->_xmlEntities($cap);
                }
            }
            if (!$render) {
                // if the picture is not supposed to be rendered
                // return the title of the picture
                if (!$title) {
                    // just show the sourcename
                    $title = $this->_xmlEntities(basename(noNS($src)));
                }
                return $title;
            }
            if ($_SESSION['figure']){
                $ret .= '<figure>'.DOKU_LF;
                $title = $_SESSION['fig_title'];
                if ($title) {
                    $ret .= '<figcaption>'.$title.'</figcaption>'.DOKU_LF;
                }
            }
            //add image tag
            //$ret .= '<img src="'.ml($src,array('w'=>$width,'h'=>$height,'cache'=>$cache)).'"';
            $ret .= '<img src="../media/'.basename(str_replace(':', '/', $src)).'"';
/*            if ($width && $height){
                $ret .= ' width="'.$width.'" height="'.$height.'"';
            }*/

            if (!$_SESSION['figure']){
                $ret .= ' class="imgB"';
            }else{
                $ret .= ' class="media'.$align.'"';
            }

            // make left/right alignment for no-CSS view work (feeds)
            if($align == 'right') $ret .= ' align="right"';
            if($align == 'left')  $ret .= ' align="left"';

            if ($title) {
                $ret .= ' title="' . $title . '"';
                $ret .= ' alt="'   . $title .'"';
            }else{
                $ret .= ' alt=""';
            }

            if ( !is_null($width) )
                $ret .= ' width="'.$this->_xmlEntities($width).'"';

            if ( !is_null($height) )
                $ret .= ' height="'.$this->_xmlEntities($height).'"';

            $ret .= ' />';
            if ($_SESSION['figure']){
                $ret .= '</figure>'.DOKU_LF;
            }

        }elseif($mime == 'application/x-shockwave-flash'){
            if (!$render) {
                // if the flash is not supposed to be rendered
                // return the title of the flash
                if (!$title) {
                    // just show the sourcename
                    $title = basename(noNS($src));
                }
                return $this->_xmlEntities($title);
            }

            $att = array();
            $att['class'] = "media$align";
            if($align == 'right') $att['align'] = 'right';
            if($align == 'left')  $att['align'] = 'left';
            $ret .= html_flashobject(ml($src,array('cache'=>$cache),true,'&'),$width,$height,
                                     array('quality' => 'high'),
                                     null,
                                     $att,
                                     $this->_xmlEntities($title));
        }elseif($title){
            // well at least we have a title to display
            $ret .= $this->_xmlEntities($title);
        }else{
            // just show the sourcename
            $ret .= $this->_xmlEntities(basename(noNS($src)));
        }

        return $ret;
    }
}
