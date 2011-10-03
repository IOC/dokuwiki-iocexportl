<?php
/**
 * LaTeX Plugin: Generate Latex document
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Marc Català <mcatala@ioc.cat>
 */

if (!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_TEMPLATES')) define('DOKU_PLUGIN_TEMPLATES',DOKU_PLUGIN.'iocexportl/templates/');
if (!defined('DOKU_PLUGIN_TEMPLATES_HTML')) define('DOKU_PLUGIN_TEMPLATES_HTML',DOKU_PLUGIN_TEMPLATES.'html/');
if (!defined('DOKU_PLUGIN_LATEX_TMP')) define('DOKU_PLUGIN_LATEX_TMP',DOKU_PLUGIN.'tmp/latex/');

require_once(DOKU_INC.'/inc/init.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');
require_once DOKU_INC.'inc/parser/xhtml.php';

global $conf;

$id = getID();
$tmp_dir = '';
$media_path = 'lib/exe/fetch.php?media=';
$exportallowed = FALSE;
$meta_params = array('autoria', 'ciclenom', 'copylink', 'copylogo', 'creditcodi', 'creditnom', 'familia');
$img_src = array('familyicon_administracio.png','familyicon_electronica.png', 'familyicon_infantil.png', 'familyicon_informatica.png');
$ioclanguage = array('CA' => 'catalan', 'DE' => 'german', 'EN' => 'english','ES' => 'catalan','FR' => 'frenchb','IT' => 'italian');
$ioclangcontinue = array('CA' => 'continuació', 'DE' => 'fortsetzung', 'EN' => 'continued','ES' => 'continuación','FR' => 'suite','IT' => 'continua');
$menu_html = '';
$web_folder = 'WebContent';
$max_navmenu = 35;
$max_menu = 75;
$def_unit_href = 'introduccio.html';
$def_section_href = 'continguts.html';
$tree_names = array();

if (!checkPerms()) return FALSE;
$exportallowed = isset($conf['plugin']['iocexportl']['allowexport']);
if (!$exportallowed && !auth_isadmin()) return FALSE;

$time_start = microtime(TRUE);

//get seccions to export
if (empty($_POST['toexport'])){
  echo json_encode('Empty exportation!');
  return FALSE;
}
$toexport = explode(',',preg_replace('/:index(,|$)/',',',$_POST['toexport']));

$output_filename = str_replace(':','_',$id);
if ($_POST['mode'] !== 'zip') return FALSE;
session_start();
$tmp_dir = rand();
$_SESSION['tmp_dir'] = $tmp_dir;
if (!file_exists(DOKU_PLUGIN_LATEX_TMP.$tmp_dir)){
    mkdir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir, 0775, TRUE);
}
//get all pages and activitites
$data = getData();

$zip = new ZipArchive;
$res = $zip->open(DOKU_PLUGIN_LATEX_TMP.$tmp_dir.'/'.$output_filename.'.zip', ZipArchive::CREATE);
if ($res === TRUE) {
    getFiles(DOKU_PLUGIN_TEMPLATES_HTML,$zip);
    $menu_html = createMenu($data[0]);
    $text_template = io_readFile(DOKU_PLUGIN_TEMPLATES_HTML.'index.html');
    $text_template = preg_replace('/@IOCMENUNAVIGATION@/', $menu_html, $text_template, 1);
    $text_template = preg_replace('/@IOCHEADDOCUMENT@/', $data[1]['creditnom'], $text_template, 2);
    //Create index page
    $navmenu = createNavigation('');
    $menu_index = preg_replace('/(expander|id="\w+")/', '', $menu_html);
    $html = preg_replace('/@IOCCONTENT@/', '<ul>'.$menu_index.'</ul>', $text_template, 1);
    $html = preg_replace('/@IOCTITLE@/', 'TOC', $html, 1);
    $html = preg_replace('/@IOCTOC@/', '', $html, 1);
    $html = preg_replace('/@IOCPATH@/', '', $html);
    $html = preg_replace('/@IOCNAVMENU@/', $navmenu, $html, 1);
    $zip->addFromString('index.html', $html);
    if (isset($data[0]['intro'])){
        //Intro
        foreach ($data[0]['intro'] as $page){
           $text = io_readFile(wikiFN($page[1]));
           list($header, $text) = extractHeader($text);
           $navmenu = createNavigation('',array($page[0]), array(''));
           $instructions = get_latex_instructions($text);
           $html = p_latex_render('iocxhtml', $instructions, $info);
           $html = preg_replace('/@IOCCONTENT@/', $html, $text_template, 1);
           $html = preg_replace('/@IOCTITLE@/', $header, $html, 1);
           $html = preg_replace('/@IOCTOC@/', '', $html, 1);
           $html = preg_replace('/@IOCPATH@/', '', $html);
           $html = preg_replace('/@IOCNAVMENU@/', $navmenu, $html, 1);
           $zip->addFromString(basename(wikiFN($page[1]),'.txt').'.html', $html);
         }
         unset($data[0]['intro']);
    }
     //Content468
     foreach ($data[0] as $ku => $unit){
        //Section
        //var to attach all url media files
        $files = array();
        $latex = array();
        $unitname = $unit['iocname'];
        unset($unit['iocname']);
        foreach ($unit as $ks => $section){
            if (is_array($section)){
                //Activities
                $_SESSION['activities'] = TRUE;
                foreach ($section as $ka => $act){
                    $text = io_readFile(wikiFN($act));
                    list($header, $text) = extractHeader($text);
                    $toc = getTOC($text);
                    $navmenu = createNavigation('../../../',array($unitname,$tree_names[$ku][$ks]['sectionname'],$tree_names[$ku][$ks][$ka]), array('../'.$def_unit_href,$def_section_href,''));
                    preg_match_all('/\{\{([^}|?]*)[^}]*\}\}/', $text, $matches);
                    array_push($files, $matches[1]);
                    preg_match_all('/(\${1,2}[^\$]+\${1,2})/', $text, $matches);
                    if (!empty($matches[1])){
                        foreach ($matches[1] as $match){
                            list($text,$path) = _latexpreElements($text, $match);
                            array_push($latex, $path);
                        }
                    }
                    $instructions = get_latex_instructions($text);
                    $html = p_latex_render('iocxhtml', $instructions, $info);
                    $html = preg_replace('/@IOCCONTENT@/', $html, $text_template, 1);
                    preg_match_all('/@STARTLATEX@[BI]#[^@]+@ENDLATEX@/', $html, $matches, PREG_SET_ORDER);
                    if (!empty($matches)){
                        foreach ($matches as $match){
                            $html = _latexpostElements($html, $match[0]);
                        }
                    }
                    $html = preg_replace('/@IOCTITLE@/', $header, $html, 1);
                    $html = preg_replace('/@IOCTOC@/', $toc, $html, 1);
                    $html = preg_replace('/@IOCPATH@/', '../../../', $html);
                    $html = preg_replace('/@IOCNAVMENU@/', $navmenu, $html, 1);
                    $zip->addFromString($web_folder.'/'.$ku.'/'.$ks.'/'.basename(wikiFN($act),'.txt').'.html', $html);
                }
                $_SESSION['activities'] = FALSE;
            }else{
                $text = io_readFile(wikiFN($section));
                list($header, $text) = extractHeader($text);
                $navmenu = createNavigation('../../',array($unitname,$tree_names[$ku][$ks]), array($def_unit_href,''));
                preg_match_all('/\{\{([^}|]+)[^}]*\}\}/', $text, $matches);
                array_push($files, $matches[1]);
                preg_match_all('/(\${1,2}[^\$]+\${1,2})/', $text, $matches);
                if (!empty($matches[1])){
                    foreach ($matches[1] as $match){
                        list($text,$path) = _latexpreElements($text, $match);
                        array_push($latex, $path);
                    }
                }
                $instructions = get_latex_instructions($text);
                $html = p_latex_render('iocxhtml', $instructions, $info);
                $html = preg_replace('/@IOCCONTENT@/', $html, $text_template, 1);
                preg_match_all('/@STARTLATEX@[BI]#[^@]+@ENDLATEX@/', $html, $matches, PREG_SET_ORDER);
                if (!empty($matches)){
                    foreach ($matches as $match){
                        $html = _latexpostElements($html, $match[0]);
                    }
                }
                $html = preg_replace('/@IOCTITLE@/', $header, $html, 1);
                $html = preg_replace('/@IOCTOC@/', '', $html, 1);
                $html = preg_replace('/@IOCPATH@/', '../../', $html);
                $html = preg_replace('/@IOCNAVMENU@/', $navmenu, $html, 1);
                $zip->addFromString($web_folder.'/'.$ku.'/'.basename(wikiFN($section),'.txt').'.html', $html);
            }
        }
        //Attach media files
        foreach($files as $sf){
            foreach($sf as $f){
                resolve_mediaid(getNS($f),&$f,&$exists);
                if ($exists){
                    $zip->addFile(mediaFN($f), $web_folder.'/'.$ku.'/media/'.basename(mediaFN($f)));
                }
            }
        }
        //Attach latex files
        foreach($latex as $l){
            if (file_exists($l)){
                $zip->addFile($l, $web_folder.'/'.$ku.'/media/'.basename($l));
            }
        }
    }
    $zip->close();
    returnData(DOKU_PLUGIN_LATEX_TMP.$tmp_dir, $output_filename.'.zip');
}else{
    $result = 'No es pot iniciar l\'arxiu zip';
}
session_destroy();

removeDir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir);


    /**
     *
     * Returns zip file info
     * @param string $path
     * @param string $filename
     */
    function returnData($path, $filename){
        global $id;
        global $media_path;
        global $conf;
        global $time_start;
        if (file_exists($path.'/'.$filename)){
            $error = '';
            $filesize = filesize($path . "/" . $filename);
            $filesize = filesize_h($filesize);

            $dest = preg_replace('/:/', '/', $id);
            $dest = dirname($dest);
            if (!file_exists($conf['mediadir'].'/'.$dest)){
                mkdir($conf['mediadir'].'/'.$dest, 0755, TRUE);
            }
            copy($path.'/'.$filename, $conf['mediadir'].'/'.$dest .'/'.$filename);
            $dest = preg_replace('/\//', ':', $dest);
            $time_end = microtime(TRUE);
            $time = round($time_end - $time_start, 2);
            $result = array('zip', $media_path.$dest.':'.$filename.'&time='.gettimeofday(TRUE), $filename, $filesize, $time, $error);
        }else{
            $result = 'Error en la creació del arixu: ' . $filename;
        }
        echo json_encode($result);
    }

	/**
	 *
     * Remove specified dir
     * @param string $directory
     */
    function removeDir($directory) {
        if(!file_exists($directory) || !is_dir($directory)) {
            return FALSE;
        } elseif(!is_readable($directory)) {
            return FALSE;
        } else {
            $directoryHandle = opendir($directory);

            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;

                    if(is_dir($path)) {
                        removeDir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            closedir($directoryHandle);

            if(file_exists($directory)) {
                if(!rmdir($directory)) {
                    return FALSE;
                }
            }
            return TRUE;
        }
    }

    /**
     *
     * Check whether user has right acces level
     */
    function checkPerms() {
        global $ID;
        global $USERINFO;
        $ID = getID();
        $user = $_SERVER['REMOTE_USER'];
        $groups = $USERINFO['grps'];
        $aclLevel = auth_aclcheck($ID,$user,$groups);
        // AUTH_ADMIN, AUTH_READ,AUTH_EDIT,AUTH_CREATE,AUTH_UPLOAD,AUTH_DELETE
        return ($aclLevel >=  AUTH_UPLOAD);
      }

    /**
     *
     * Fill data var with wiki pages using a customized structure
     * @param array $data
     * @param boolean $struct
     */
    function getPageNames(&$data){
        global $id;
        global $conf;
        global $toexport;
        $sort = FALSE;

        $file = wikiFN($id);
        if (@file_exists($file)) {
            $matches = array();
            $txt =  io_readFile($file);
            preg_match_all('/(?<=\={5} [T|t]oc \={5})\n+(\s{2,4}\*\s+\[\[[^\]]+\]\] *)+/is', $txt, $matches);
            $pages = (isset($matches[0][0]))?$matches[0][0]:'';
            unset($matches);
            preg_match_all('/\[\[([^|\]]+)\|?(.*?)\]\] */', $pages, $matches, PREG_SET_ORDER);
            foreach ($matches as $match){
                $ns = resolve_id(getNS($id),$match[1]);
                if (!in_array($ns, $toexport)){
                    continue;
                }
                if (page_exists($ns)){
                    if(!isset($data['intro'])){
                        $data['intro'] = array();
                    }
                    array_push($data['intro'], array($match[2],$ns));
                }else{
                    $ns = preg_replace('/:/' ,'/', $ns);
                    $content = io_readFile($conf['datadir'].'/'.$ns.'/index.txt');
                    $result = array();
                    if (preg_match('/^[I|i]ndex/', $content)){
                        $result = explode(DOKU_LF,$content);
                        @array_shift($result);
                        $ns = str_replace('/', ':', $ns);
                        $sort = TRUE;
                    }else{
                        search($result,$conf['datadir'], 'search_allpages', null, $ns);
                    }
                    foreach ($result as $pagename){
                        if ($sort){
                            $pagename = $ns.':'.$pagename;
                        }else{
                            $pagename = $pagename['id'];
                        }
                        if (!preg_match('/:(pdfindex|imatges|index)$/', $pagename)){
                            preg_match('/:(u\d+):/', $pagename, $unit);
                            preg_match('/:(a\d+):/', $pagename, $section);
                            if (!empty($unit[1]) && !isset($data[$unit[1]])){
                                $data[$unit[1]] = array();
                            }
                            if (!empty($section[1]) && !isset($data[$unit[1]][$section[1]])){
                                $data[$unit[1]][$section[1]] = array();
                            }
                            //Save unit name
                            $data[$unit[1]]['iocname'] = $match[2];
                            preg_match('/([^:]*:)+([^\.]*)$/', $pagename, $name);
                            if (!empty($section[1])){
                                $data[$unit[1]][$section[1]][$name[2]] = $pagename;
                             }else{
                                $data[$unit[1]][$name[2]] = $pagename;
                            }
                        }
                    }
                }
            }
        }
     }

    /**
     *
     * Get and return uri wiki pages
     */
    function getData(){
        global $id;
        global $meta_params;

        $data = array();
        $data[0] = array();
        $data[1] = array();
        $file = wikiFN($id);
        $inf = NULL;
        if (@file_exists($file)) {
            $info = io_grep($file, '/(?<=\={6} )[^\=]*/', 0, TRUE);
            $data[1]['nomcomplert'] = $info[0][0];
            $text = io_readFile($file);
            $info = array();
            preg_match_all('/(?<=\={5} [M|m]eta \={5}\n\n)\n*( {2,4}\* \*\*.*?\*\*:.*\n?)+/', $text, $info, PREG_SET_ORDER);
            if (!empty($info[0][0])){
                $text = $info[0][0];
                preg_match_all('/ {2,4}\* (\*\*(.*?)\*\*:)(.*)/m', $text, $info, PREG_SET_ORDER);
                foreach ($info as $i){
                    $key = trim($i[2]);
                    if (in_array($key, $meta_params)){
                        $data[1][$key] = trim($i[3]);
                    }else{
                        $instructions = get_latex_instructions(trim($i[1].$i[3]));
                        $latex = p_latex_render('iocxhtml', $instructions, $inf);
                        $data[1]['extra'] = $latex;
                    }
                }
            }
            //get page names
            getPageNames($data[0]);
            return $data;
        }
        return FALSE;
    }

    /**
     *
     * Create side menu elements
     */
    function setMenu($type='', $name='', $href='', $id=''){
        global $max_menu;

        if (strlen($name) > $max_menu){
            $name = substr($name, 0, $max_menu) . '...';
        }
        if ($type === 'root'){
            $menu_html = '<li id="'.$id.'">';
            $menu_html .= '<h4><a href="'.$href.'">'.$name.'</a></h4>';
            $menu_html .= '</li>';
        }elseif ($type === 'unit'){
            $menu_html = '<li id="'.$id.'">';
            $menu_html .= '<h4><a href="'.$href.'">'.$name.'</a></h4>';
            $menu_html .= '<ul class="expander">';
        }elseif ($type === 'section'){
            $menu_html = '<li id="'.$id.'">';
            $menu_html .= '<h4><a href="'.$href.'">'.$name.'</a></h4>';
            $menu_html .= '<ul>';
        }elseif ($type === 'activity'){
            $menu_html = '<li id="'.$id.'">';
            $menu_html .= '<a href="'.$href.'">'.$name.'</a>';
            $menu_html .= '</li>';
        }else{
            $menu_html = '</ul></li>';
        }
        return $menu_html;
    }

        /**
     *
     * Create side menu elements
     */
    function createMenu($elements){
        global $web_folder;
        global $tree_names;

        $menu_html = '';
        if (isset($elements['intro'])){
            //Intro
            foreach ($elements['intro'] as $kp => $page){
                $href = '@IOCPATH@'.basename(wikiFN($page[1]),'.txt').'.html';
                $menu_html .= setMenu('root', $page[0], $href, 'c'.$kp);
            }
            unset($elements['intro']);
        }
        foreach ($elements as $ku => $unit){
            $tree_names[$ku] = array();
            //Section
            $menu_html .= setMenu('unit',$unit['iocname'], '#', $ku);
            unset($unit['iocname']);
            //First main pages
            foreach ($unit as $ks => $section){
                if (!is_array($section)){
                    $text = io_readFile(wikiFN($section));
                    $act_href = '@IOCPATH@'.$web_folder.'/'.$ku.'/'.basename(wikiFN($section),'.txt').'.html';
                    preg_match('/\={6}([^=]+)\={6}/', $text, $matches);
                    $act_name = (!empty($matches[1]))?trim($matches[1]):'HEADER LEVEL 1 NOT FOUND';
                    $tree_names[$ku][$ks]=$act_name;
                    $menu_html .= setMenu('activity', $act_name, $act_href, $ku.$ks);
                    unset($unit[$ks]);
                }
            }
            //Only sections with content
            foreach ($unit as $ks => $section){
                $tree_names[$ku][$ks] = array();
                //Activities
                $text = io_readFile(wikiFN($section['continguts']));
                preg_match('/\={6}([^=]+)\={6}/', $text, $matches);
                if (!empty($matches[1])){
                    $section_name = $matches[1];
                }else{
                     $section_name = $ks;
                }
                $tree_names[$ku][$ks]['sectionname']=$section_name;
                $menu_html .= setMenu('section', $section_name, '#', $ku.$ks);
                foreach ($section as $ka => $act){
                    $text = io_readFile(wikiFN($act));
                    $act_href = '@IOCPATH@'.$web_folder.'/'.$ku.'/'.$ks.'/'.basename(wikiFN($act),'.txt').'.html';
                    if ($ka !== 'continguts'){
                        preg_match('/\={6}([^=]+)\={6}/', $text, $matches);
                        $act_name = (!empty($matches[1]))?trim($matches[1]):'HEADER LEVEL 1 NOT FOUND';
                        $tree_names[$ku][$ks][$ka]=$act_name;
                    }else{//File continguts has a short name
                        $act_name = 'Contingut';
                        $tree_names[$ku][$ks]['continguts']=$act_name;
                    }
                    $menu_html .= setMenu('activity', $act_name, $act_href, $ku.$ks.$ka);
                }
                //Close menu activities
                $menu_html .= setMenu();
            }
        }
        $menu_html .= setMenu();
        return $menu_html;
    }


    /**
    *
    * Fill zip var with all media files stored on directory var
    * @param string $directory
    * @param string $zip
    */
    function getFiles($directory, &$zip){
        if(!file_exists($directory) || !is_dir($directory)) {
            return FALSE;
        } elseif(!is_readable($directory)) {
            return FALSE;
        } else {
            $directoryHandle = opendir($directory);
            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;
                    if(is_dir($path)) {
                        $dirname = str_replace(DOKU_PLUGIN_TEMPLATES_HTML,'',$path);
                        $zip->addEmptyDir($dirname);
                        getFiles($path, $zip);
                    }else{
                        if ($contents !== 'index.html'){
                            $dirname = str_replace(DOKU_PLUGIN_TEMPLATES_HTML,'',$directory);
                            $zip->addFile($path, $dirname ."/".$contents);
                        }
                    }
                }
            }
            closedir($directoryHandle);
            return TRUE;
        }
    }

     /**
     *
     * Get Table Of Contents
     */
    function getTOC($text){
        $matches = array();
        $headers = array();
        $toc = '<div class="toc">';
        $toc .= '<span>Taula de continguts</span><br /><ul>';
        preg_match_all('/\={5}([^=]+)\={5}/', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $m){
            $toc .= '<li>';
            $toc .= '<a href="#'.sectionID($m[1],$headers).'">'.trim($m[1]).'</a>';
            $toc .= '</li>';
        }
        $toc .= '</ul></div>';
        return $toc;
    }

     /**
     *
     * Extract main header from text
     */
    function extractHeader($text){
        $check = array();
        if (preg_match('/\={6}([^=]+)\={6}/', $text, $matches)){
            $text = preg_replace('/\={6}[^=]+\={6}/', '', $text);
            $id = sectionID($matches[1], $check);
            $header = '<a id="'.$id.'">'.$matches[1].'</a>';
            return array($header, $text);
        }
        return array('',$text);
    }

     /**
     *
     * Create menu navigation
     */
    function createNavigation($index_path, $options=NULL,$refs=NULL){
        global $max_navmenu;

        $navigation = '<ul class="webnav"><li><a href="'.$index_path.'index.html" title="Tornar a l\'inici">Inici</a></li>';
        if (!is_null($options)){
            foreach ($options as $k => $op){
                if ((strlen($op) > $max_navmenu) && $k < (count($options)-1)){
                    $op = substr($op, 0, $max_navmenu) . '...';
                }
                $navigation .= '<li>';
                if (!empty($refs[$k])){
                    $navigation .= '<a href="'.$refs[$k].'">';
                }
                $navigation .= $op;
                if (!empty($refs[$k])){
                    $navigation .= '</a>';
                }
                $navigation .= '</li>';
            }
        }
        $navigation .= '</ul>';
        return $navigation;
    }


    function _latexpreElements($html, $value){
        $block = preg_match('/^\${2}/', $value);

        $renderer = new Doku_Renderer_xhtml();
        $xhtml = $renderer->render($value);

        if (preg_match('/<img src="(.*?\?media=(.*?))"/', $xhtml, $match)) {
            $path = mediaFN($match[2]);
        } else {
            $path = DOKU_INC . "lib/plugins/latex/images/renderfail.png";
        }
        //Math block mode
        if ($block){
            $html = preg_replace('/\${2}\n?([^\$]+)\n?\${2}/', '@STARTLATEX@B#'.$path.'@ENDLATEX@', $html, 1);
        }else{//Math inline mode
            $html = preg_replace('/\$\n?([^\$]+)\n?\$/', '@STARTLATEX@I#'.$path.'@ENDLATEX@', $html, 1);
        }
        return array($html,$path);
    }

    function _latexpostElements($html, $value){
        $block = preg_match('/@B#/', $value);
        $class = ($block)?'blocklatex':'inlinelatex';

        $html = preg_replace('/(@STARTLATEX@)([BI]#)/', '$1', $html, 1);
        preg_match('/@STARTLATEX@([^@]+)@ENDLATEX@/', $html, $match);
        $html = preg_replace('/@STARTLATEX@[^@]+@ENDLATEX@/', '<span class="'.$class.'"><img src="../media/'.basename($match[1]).'" /></span>', $html, 1);
        return $html;
    }