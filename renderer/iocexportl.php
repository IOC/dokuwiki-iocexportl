<?php
/**
 * LaTeX Plugin: Export content to LaTeX
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
class renderer_plugin_iocexportl extends Doku_Renderer {
    
    var $code = FALSE;
    var $col_colspan;
    var $col_num = 1;
    static $convert = FALSE;//convert images to $imgext
    var $endimg = FALSE;
    var $formatting = '';
    static $hr_width = 375;
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
	var $id = '';


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
        return "iocexportl";
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

		$this->id = getID();
        //Check whether user can export 
		$exportallowed = (isset($conf['plugin']['iocexportl']['allowexport']) && $conf['plugin']['iocexportl']['allowexport']);
        if (!$exportallowed && !auth_isadmin()) die;

        if (!isset($_SESSION['tmp_dir'])){
            $this->tmp_dir = rand();
        }else{
            $this->tmp_dir = $_SESSION['tmp_dir'];
        }
        if (!file_exists(DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir)){
            mkdir(DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir, 0775, TRUE);
            mkdir(DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media', 0775, TRUE);
        }
        copy(DOKU_PLUGIN.'iocexportl/templates/background.pdf', DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media/background.pdf');

        //Global variables
        $this->_initialize_globals();
    }

    /**
     * Closes the document
     */
    function document_end(){
        $this->doc = preg_replace('/@IOCKEYSTART@/','\{', $this->doc);
        $this->doc = preg_replace('/@IOCKEYEND@/','\}', $this->doc);
        $this->doc = preg_replace('/@IOCBACKSLASH@/',"\\\\", $this->doc);
		$this->_create_refs();
    }
    

	/**
     * NOVA
     */
    function _create_refs(){
		$this->doc = preg_replace('/:figure:(.*?):/',"Figura  \\\\ref{\\1}", $this->doc);
		$this->doc = preg_replace('/:table:(.*?):/',"Taula  \\\\ref{\\1}", $this->doc);
    }
    
	/**
     * NOVA
     */
    function _initialize_globals(){
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
        if (!isset($_SESSION['figlabel'])){
            $_SESSION['figlabel'] = '';
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
     * NOVA
     */
    function _format_text($text){
        $text = $this->_ttEntities(trim($text));//Remove extended symbols 
        if ($_SESSION['iocstl']){
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
        $img = DOKU_INC . 'lib/images/smileys/'. $this->smileys[$smiley];
        $img_aux = $this->_image_convert($img, DOKU_PLUGIN_LATEX_TMP.$this->tmp_dir.'/media');
        $this->doc .= '\includegraphics[height=1em, width=1em]{media/'.basename($img_aux).'}';
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
            $align = 'center';
        }else{//Unit 0
            $align = 'flushleft';
        }
        if ($_SESSION['imgB']){
            $max_width = '[width=35mm]';
            $img_width = FALSE;
        }elseif (!$this->table && $width > self::$p_width){
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
            if (!$this->table && !$_SESSION['imgB'] && !$_SESSION['video_url']){
                $this->doc .= '\begin{figure}[H]'.DOKU_LF;
                $this->doc .= '\begin{'.$align.'}'.DOKU_LF;
            }
            if ($linking !== 'details'){
                $this->doc .= '\href{'.$linking.'}{';
            }
            $hspace = 0;//Align text and image
            if ($title) {
                $title = preg_replace('/<verd>|<\/verd>/', '', $title);
                $title = split('/', $title, 2);
                $title_width = '\textwidth';
                $vspace = '\vspace*{-4mm}';
                if (!$_SESSION['imgB']){
                    if ($img_width){
                        $hspace = ((self::$p_width - $img_width) >> 1) - 23;
                    }
                $this->doc .= '\hspace*{'.$hspace.'pt}\parbox[t]{'.$title_width.'}{\caption{'.trim($this->_xmlEntities($title[0]));
				if (!empty($_SESSION['figlabel'])){
	                $this->doc .= '\label{'.$_SESSION['figlabel'].'}';					
				}
				$this->doc .= '}}'.$vspace.DOKU_LF;
                }else{
					if (empty($title[1])){
						$title[1] = $title[0];
					}
                }
            }
            //Inside table, images will be centered vertically
            if ($this->table && $width > self::$img_max_table){
//                $this->doc .= '\parbox[t]{\linewidth}{';
                $this->doc .= '\resizebox{\linewidth}{!}{';
            }
            $this->doc .= '\includegraphics'.$max_width.'{media/'.basename($img_aux).'}';
            if ($this->table && $width > self::$img_max_table){
                $this->doc .= '}';
                
            }            
			//Close href
            if ($linking !== 'details'){
                $this->doc .= '}';
                if (!$_SESSION['video_url']){
                    $this->doc .= 'DOKU_LF';
                }
            }
            if (!$this->table && !$_SESSION['imgB'] && !$_SESSION['video_url']){
                $this->doc .= '\\\\';
            }
            if (!$_SESSION['video_url']){
                $this->doc .= DOKU_LF;
            }
            if ($title[1]) {
                if (!$_SESSION['imgB']){
                    if ($img_width){
                        $hspace = ($img_width + $hspace).'pt';
                    }else{
                       $hspace = '\textwidth';
                    }
					$vspace = '\vspace*{-2mm}';
                }else{
                    $hspace = '\marginparwidth';
					$vspace = '\vspace{-4mm}';
                }
                $this->doc .=  '\raggedright\parbox[c]{'.$hspace.'}{\textsf{\tiny\begin{flushright}'.$vspace.trim($this->_xmlEntities($title[1])).'\end{flushright}}}';
                
            }
            if (!$this->table && !$_SESSION['imgB'] && !$_SESSION['video_url']){
                $this->doc .= '\end{'.$align.'}';
                $this->doc .= '\end{figure}';
            }
            $this->endimg = TRUE;
        }else{
            $this->doc .= '\textcolor{red}{\textbf{File '. $this->_xmlEntities(basename($src)).' does not exist.}}';
        }
    }
      
    /**
     * Closes the document using a template
     */
    function document_end_template(){
    }

    function render_TOC() {
         return ''; 
    }

    function toc_additem($id, $text, $level) {}

    function cdata($text) {
        if ($this->monospace){
            $text = preg_replace('/\n/', '\\newline', $text);
        }
        $this->doc .= $this->_xmlEntities($text);
    }

    function p_open(){
    }

    function p_close(){
        if (!$this->endimg){
            $this->doc .= DOKU_LF;
        }else{
            $this->endimg = FALSE;
        }
        $this->doc .= DOKU_LF;
    }

    function header($text, $level, $pos){
        global $conf;
        
        if ($_SESSION['activities']){
            $level += 1;
        }
        $levels = array(
    		    1 => '\chapter',
    		    2 => '\section',
    		    3 => '\subsection',
    		    4 => '\subsubsection',
    		    5 => '\paragraph',
    		    );

        if ( isset($levels[$level]) ) {
          $token = $levels[$level];
        } else {
          $token = $levels[1];
        }
        $text = $this->_xmlEntities(trim($text));
        $chapternumber = '';
        if ($_SESSION['u0']){
            $chapternumber = '*';
            $this->doc .= '\headingnonumbers';
        }elseif ($_SESSION['createbook'] && $level === 1 && $_SESSION['chapter'] < 3){
            $chapternumber = '*';
            $_SESSION['chapter'] += 1;
            $this->doc .= '\cleardoublepage\phantomsection\addcontentsline{toc}{chapter}{' . $text . '}'.DOKU_LF;    
        }elseif($level === 1){ //Change chapter style
            $this->doc .= '\headingnumbers';
        }
        $breakline = ($level === 5)?"\hspace*{\\fill}\\\\\\\\":""; 
        $this->doc .= "$token$chapternumber{" . $text . "}". $breakline .DOKU_LF;
    }

    function hr() {
        if (!$this->code){
            $this->doc .= '\begin{center}'.DOKU_LF;
        }
        $this->doc .= '\line(1,0){'.self::$hr_width.'}'.DOKU_LF;
        if (!$this->code){
            $this->doc .= '\end{center}'.DOKU_LF;
        } else {
            $this->code = FALSE;
        }
    }

    function linebreak() {
        if ($this->table && !empty($this->formatting)){
            $this->doc .= '}';
        }
        if ($this->table){
            $this->doc .= '\break ';
        }else{
            $this->doc .= DOKU_LF.DOKU_LF;   
        }
        $this->doc .= $this->formatting;
    }

    function strong_open() {
        if ($this->table){
            $this->formatting = '\textbf{'; 
        }
        $this->doc .= '\textbf{';
    }

    function strong_close() {
        $this->doc .= '}';
        $this->formatting = '';
    }

    function emphasis_open() {
        if ($this->table){
            $this->formatting = '\textit{'; 
        }
        $this->doc .= '\textit{';
    }

    function emphasis_close() {
        $this->doc .= '}';
        $this->formatting = '';        
    }

    function underline_open() {
        if ($this->table){
            $this->formatting = '\underline{'; 
        }
        $this->doc .= '\underline{';
    }

    function underline_close() {
        $this->doc .= '}';
        $this->formatting = '';        
    }

    function monospace_open() {
        $this->monospace = TRUE;
        $this->doc .= '\texttt{';
    }

    function monospace_close() {
        $this->doc .= '}';
        $this->monospace = FALSE;
    }

    function subscript_open() {
        $this->doc .= '\textsubscript{';
    }

    function subscript_close() {
        $this->doc .= '}';
    }

    function superscript_open() {
        $this->doc .= '\textsuperscript{';
    }

    function superscript_close() {
        $this->doc .= '}';
    }

    function deleted_open() {
        $this->doc .= '\sout{';
    }

    function deleted_close() {
        $this->doc .= '}';
    }

    /*
     * Tables
     */
    function table_open($maxcols = NULL, $numrows = NULL){
        $this->table = TRUE;
        $this->tableheader = TRUE;
        $this->max_cols = $maxcols;
        $this->col_num = 1;
        $this->table_align = array();
        $this->doc .= '\fonttable'.DOKU_LF;
        $this->doc .= '\begin{longtabu}{';
        for($i=0; $i < $maxcols; $i++){
            $this->doc .= 'X[-1,l] ';
        }
        $this->doc .= '}';
        if (!empty($_SESSION['table_title'])){
            $this->doc .= '\caption{'.$_SESSION['table_title'].
            			  '\label{'.$_SESSION['table_id'].'}'.
            			  '\vspace*{-4mm}}\\\\'.DOKU_LF;
        }
        $this->doc .= '\hline'.DOKU_LF;
    }

    function table_close(){
        $this->table = FALSE;
        $this->doc .= '\noalign{\vspace{1mm}}'.DOKU_LF;
        $this->doc .= '\hline'.DOKU_LF;
        $this->tableheader_count = 0;
        preg_match('/(?<=@IOCHEADERSTART@)([^@]*)(?=@IOCHEADEREND@)/',$this->doc, $matches);
        $this->doc = preg_replace('/@IOCHEADERSTART@|@IOCHEADEREND@/','', $this->doc);        
        $this->doc = preg_replace('/@IOCHEADERBIS@/',isset($matches[1])?$matches[1]:'', $this->doc, 1);
        $this->doc .= '\tabuphantomline';
        $this->doc .= '\end{longtabu}'.DOKU_LF;
        $this->doc .= '\normalfont\normalsize'.DOKU_LF;
    }

    function tablerow_open(){
        $this->col_num = 1;
    }

    function tablerow_close(){
        if ($this->tableheader_end){ 
            $this->tableheader_count += 1;
            $this->tableheader = TRUE;
        }
        if ($this->tableheader_end && $this->tableheader_count === 1){
            $this->doc .= '@IOCHEADEREND@';
            $this->doc .= '\\\\ \hline \noalign{\vspace{1mm}} \endfirsthead'.DOKU_LF;
            $this->doc .= '\caption[]{(Continuació)\vspace*{-4mm}} \\\\' . DOKU_LF;
            $this->doc .= '\hline' . DOKU_LF;
            $this->doc .= '@IOCHEADERBIS@ \\\\ \hline' . DOKU_LF;
            $this->doc .= '\endhead' . DOKU_LF;
            $this->doc .= '\noalign{\vspace{-2mm}}\multicolumn{'.$this->max_cols.'}{c}{\tableheadrule}' . DOKU_LF;
            $this->doc .= '\endfoot' . DOKU_LF;
            $this->doc .= '\endlastfoot' . DOKU_LF;
        }else{
            $this->doc .= '\\\\'.DOKU_LF;
            if ($this->tableheader_end){
                $this->doc .= '\hline'.DOKU_LF;
            }
        }
        $this->tableheader_end = FALSE;
    }

    function tableheader_open($colspan = 1, $align = NULL, $rowspan = 1){
        $align = 'p{\the\tabucolX * '.$colspan.'}';
        if($this->tableheader){
              $this->doc .= '@IOCHEADERSTART@';
              $this->tableheader = FALSE;
        }
        $this->col_colspan = $colspan;
        if ($colspan > 1){
            $this->doc .= '\multicolumn{'.$colspan.'}{'.$align.'}{';
        }else{
            $this->doc .= '\raggedright ';
        }
        $this->doc .= '\parbox[t]{\linewidth}{\raggedright ';
        $this->formatting = '\textbf{';
        $this->doc .= $this->formatting;
    }

    function tableheader_close(){
        $this->formatting = '';
        $this->doc .= '}';//close format
        $this->doc .= '}';//close parbox        
        $col_num_aux = ($this->col_colspan > 1)?$this->col_num + ($this->col_colspan-1):$this->col_num;
        if ($this->col_colspan > 1){
            $this->doc .= '}';
        }
        if ($col_num_aux < $this->max_cols){
           $this->doc .= '& ';
        }
       $this->col_num += $this->col_colspan;
       $this->tableheader_end = TRUE;
    }

    function tablecell_open($colspan = 1, $align = NULL, $rowspan = 1){
        $align = 'p{\the\tabucolX * '.$colspan.'}';
        $this->tableheader = FALSE;
        if ($colspan > 1){
            $this->doc .= '\multicolumn{'.$colspan.'}{'.$align.'}{';
        }
        $this->col_colspan = $colspan;
        $this->doc .= '\raisebox{-\height}{';
        $this->doc .= '\parbox[t]{\linewidth}{\raggedright ';
    }

    function tablecell_close(){
        $col_num_aux = ($this->col_colspan > 1)?$this->col_num + $this->col_colspan:$this->col_num;
        $this->doc .= '}}';//close parbox and raisebox   
        if ($this->col_colspan > 1) {
            $col_num_aux--;
            $this->doc .= '} ';//close multicolumn           
        }
        if ($col_num_aux < $this->max_cols){
            $this->doc .= ' & ';
        } 
        $this->col_num += $this->col_colspan;
    }

    function footnote_open() {
        $this->doc .= '\footnote{';
    }

    function footnote_close() {
        $this->doc .= '}'.DOKU_LF;
    }

    function listu_open() {
        //Quiz questions are numered
        if ($_SESSION['quizmode']){
            $this->listo_open();
        }else{
            $this->doc .= '\nobreak\begin{itemize}'.DOKU_LF;
        }
    }

    function listu_close() {
        if ($_SESSION['quizmode']){
            $this->listo_close();
        }else{
            $this->doc .= '\end{itemize}'.DOKU_LF;
        }
    }

    function listo_open() {
        $this->doc .= '\nobreak\begin{enumerate}'.DOKU_LF;
    }

    function listo_close() {
        $this->doc .= '\end{enumerate}'.DOKU_LF;
    }

    function listitem_open($level) {
        $this->doc .= '\item ';
    }

    function listitem_close() {
        $this->doc .= DOKU_LF;
    }

    function listcontent_open() {
    }

    function listcontent_close() {
    }

    function unformatted($text) {
        $this->doc .= $this->_latexEntities($text);
    }

    function acronym($acronym) {
        $this->doc .= $this->_latexEntities($acronym);
    }

    function entity($entity) {
        $this->doc .= $this->_xmlEntities($entity);
    }

    function multiplyentity($x, $y) {
        $this->doc .= $x.'x'.$y;
    }

    function singlequoteopening() {
        $this->doc .= "'";
    }

    function singlequoteclosing() {
        $this->doc .= "'";
    }

    function apostrophe() {
        $this->doc .= "'";
    }

    function doublequoteopening() {
        $this->doc .= "''";
    }

    function doublequoteclosing() {
        $this->doc .= "''";
    }

    function php($text, $wrapper='dummy') {
        $this->monospace_open();
        $this->doc .= $this->_xmlEntities($text);
        $this->monospace_close();
    }
    
    function phpblock($text) {
        $this->file($text);
    }

    function html($text, $wrapper='dummy') {
        $this->monospace_open();
        $this->doc .= $this->_xmlEntities($text);
        $this->monospace_close();
    }
    
    function htmlblock($text) {
        $this->file($text);
    }

    function preformatted($text) {
        $this->doc .= '\codeinline ';
//        $text = str_ireplace(array('#','_','&','$'), array('\#','\_','\&','\$'), $text);
        $text = clean_reserved_symbols($text);
        $this->_format_text($text);
    }

    function file($text) {
        $this->preformatted($text);
    }

    function quote_open() {
        $this->doc .= "\textbar";
    }

    function quote_close() {
    }

    function code($text, $language=null, $filename=null) {
        if (preg_match('/html/i', $language)){
            $language = 'HTML'; 
        }
        if(!$_SESSION['iocstl']){
            $this->doc .= '\hspace*{4mm}'. DOKU_LF;    	
            $this->doc .= '\begin{minipage}[c]{\textwidth+\marginparwidth}'. DOKU_LF;
            if ( !$language ) {
                $this->doc .= '\begin{csource}{language=}'.DOKU_LF;
            } else {
                $this->doc .= '\begin{csource}{language='.$language.'}'.DOKU_LF;
            }
            $this->doc .=  $this->_format_text($text);
            $this->doc .= '\end{csource}'.DOKU_LF;        
            $this->doc .= '\end{minipage}'.DOKU_LF.DOKU_LF;
        }else{
            $this->doc .= '\hspace*{\\fill}\\\\\\\\'. DOKU_LF;
            $this->doc .= '\hspace*{4mm}'. DOKU_LF;
            $this->doc .= '\begin{minipage}[c]{.8\textwidth}'. DOKU_LF;
            if ( !$language ) {
                $this->doc .= '\begin{csource}{language=}^^J'.DOKU_LF;
            } else {
                $this->doc .= '\begin{csource}{language='.$language.'}'.DOKU_LF;
            }
            $this->doc .=  $this->_format_text($text) . '^^J';
            $this->doc .= '\end{csource}'.DOKU_LF;        
            $this->doc .= '\end{minipage}'.DOKU_LF;
        }
    }

    function internalmedia ($src, $title=null, $align=null, $width=null,
                            $height=null, $cache=null, $linking=null) {
        global $conf;
        resolve_mediaid(getNS($this->id),$src, $exists);
        list($ext,$mime) = mimetype($src);
        $type = substr($mime,0,5); 
        if($type === 'image'){
            $file = mediaFN($src);
            $this->_latexAddImage($file, $width, $height, $align, $title, $linking);
        }elseif($type === 'appli' && !$_SESSION['u0']){
            if (preg_match('/\.pdf$/', $src)){
                $_SESSION['qrcode'] = TRUE;
                $src = $this->_xmlEntities(DOKU_URL.'lib/exe/fetch.php?media='.$src);
                qrcode_media_url($this, $src, $title, 'pdf');
                /*
                $this->doc .= '\begin{mediaurl}{'.$src.'}';
                $_SESSION['video_url'] = TRUE;
                $this->doc .= '\parbox[c]{\linewidth}{\raggedright ';                
                $this->_latexAddImage(DOKU_PLUGIN . 'iocexportl/templates/pdf.png','32',null,null,null,$src);
                $this->doc .= '}';
                $_SESSION['video_url'] = FALSE;
                $this->doc .= '& \hspace{-2mm}';
                $this->doc .= '\parbox[c]{\linewidth}{\raggedright ';
                $this->externallink($src, $title);
                $this->doc .= '}';                
                $this->doc .= '\end{mediaurl}';*/
            }
        }else{
            if (!$_SESSION['u0']){
                $this->code('FIXME internalmedia ('.$type.'): '.$src);
            }
        }
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
        // default name is based on $id as given
        $default = $this->_simpleTitle($id);
        // now first resolve and clean up the $id
        resolve_pageid(getNS($this->id),$id,$exists);
        $name = $this->_getLinkTitle($name, $default, $isImage, $id);
        list($page, $section) = split('#', $id, 2);
        if (!empty($section)){
          $cleanid = noNS(cleanID($section, TRUE));
        }else{
          $cleanid = noNS(cleanID($id, TRUE));
        }
        $md5 = md5($cleanid);
        
        $this->doc .= '\hyperref[';
        $this->doc .= $md5;
        $this->doc .= ']{';
        $this->doc .= $name;
        $this->doc .= '}';
    }

    /**
     * Add external link
     */
    function externallink($url, $title = NULL) {
        $url = $this->_xmlEntities($url);
        if (!$title){
            $this->doc .= '\url{'.$url.'}';
        } else {
            $title = $this->_getLinkTitle($title, $url, $isImage);
            if (is_string($title)){
                $this->doc .= '\href{'.$url.'}{'.$title.'}';
            }else{//image
                if (preg_match('/http|https|ftp/', $title['src'])){
                    $this->externalmedia($title['src'],null,$title['align'],$title['width'],null,null,$url);
                }else{
                    $this->internalmedia($title['src'],null,$title['align'],$title['width'],null,null,$url);
                }
            }
        }
   }

    /**
     * Just print local links
     *
     * @fixme add image handling
     */
    function locallink($hash, $name = NULL){
        $name  = $this->_getLinkTitle($name, $hash, $isImage);
        $this->doc .= $name;
    }

    /**
     * InterWiki links
     */
    function interwikilink($match, $name = NULL, $wikiName, $wikiUri) {
    }

    /**
     * Just print WindowsShare links
     *
     * @fixme add image handling
     */
    function windowssharelink($url, $name = NULL) {
        $this->unformatted('[['.$link.'|'.$title.']]');
    }

    /**
     * Just print email links
     *
     * @fixme add image handling
     */
    function emaillink($address, $name = NULL) {
        $this->doc .= '\href{mailto:'.$this->_xmlEntities($address).'}{'.$this->_xmlEntities($address).'}';
    }

    /**
     * Construct a title and handle images in titles
     *
     * @author Harry Fuecks <hfuecks@gmail.com>
     */
    function _getLinkTitle($title, $default, & $isImage, $id=null) {
        global $conf;
        
        $isImage = FALSE;
        if ( is_null($title) ) {
            if ($conf['useheading'] && $id) {
                $heading = p_get_first_heading($id);
                if ($heading) {
                      return $this->_latexEntities($heading);
                }
            }
            return $this->_latexEntities($default);
        } else if ( is_string($title) ) {
            return $this->_latexEntities($title);
        } else if ( is_array($title) ) {
            $isImage = TRUE;
            if (isset($title['caption'])) {
                $title['title'] = $title['caption'];
            } else {
                $title['title'] = $default;
            }
            return $title;
        }
    }

    function _xmlEntities($value) {
        global $symbols;
        static $find = array('{','}','\\','_','^','<','>','#','%', '$', '&', '~', '"','−');
        static $replace = array('@IOCKEYSTART@', '@IOCKEYEND@', '\textbackslash ', '@IOCBACKSLASH@_', '@IOCBACKSLASH@^{}',
								'@IOCBACKSLASH@textless ','@IOCBACKSLASH@textgreater ','@IOCBACKSLASH@#','@IOCBACKSLASH@%', '@IOCBACKSLASH@$', '@IOCBACKSLASH@&', '@IOCBACKSLASH@~{}', '@IOCBACKSLASH@textquotedbl ', '-');
        $matches = array();
        //Remove spaces only if we are inside a table
        if ($this->table){
            $value = trim($value);
        }
        if (!$this->monospace){
            //Search mathematical formulas
            //Math mode
            if (preg_match('/\${2}\n?([^\$]+)\n?\${2}/', $value, $matches)){
                $text = str_ireplace($symbols, ' (Invalid character) ', $matches[1]);
                $text = clean_reserved_symbols($text);
                return ' \begin{math}'.filter_tex_sanitize_formula($text).'\end{math} ';
            //Math inline mode    
            }elseif (preg_match('/\$\n?([^\$]+)\n?\$/', $value, $matches)){
                $text = str_ireplace($symbols, ' (Invalid character) ', $matches[1]);
                $text = clean_reserved_symbols($text);
                $value = preg_replace('/\$\n?([^\$]+)\n?\$/', '$ '.$text.' $', $value, 1);
                return filter_tex_sanitize_formula($value);
            }
        }
        if ($this->monospace){
            $value = str_ireplace($find, $replace, $value);
            return preg_replace('/\n/', '\\newline', $value);                        
        }else{
            return str_ireplace($find, $replace, $value);
        }
    }
    
    function _ttEntities($value) {
        global $symbols;
        return str_ireplace($symbols, ' (Invalid character) ', $value);
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
}
