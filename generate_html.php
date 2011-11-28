<?php
/**
 * LaTeX Plugin: Generate HTML document
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

//static $def_unit_href = 'introduccio.html';
static $def_section_href = 'continguts';
$exportallowed = FALSE;
$id = getID();
static $max_menu = 100;
static $max_navmenu = 70;
static $media_path = 'lib/exe/fetch.php?media=';
$menu_html = '';
static $meta_params = array('adaptacio', 'autoria', 'ciclenom', 'coordinacio', 'copylink', 'copylogo', 'copytext', 'creditcodi', 'creditnom', 'familia', 'data', 'familypic');
$tree_names = array();
static $web_folder = 'WebContent';


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
$_SESSION['latex_images'] = array();
if (!file_exists(DOKU_PLUGIN_LATEX_TMP.$tmp_dir)){
    mkdir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir, 0775, TRUE);
}
//get all pages and activitites
$data = getData();

$zip = new ZipArchive;
$res = $zip->open(DOKU_PLUGIN_LATEX_TMP.$tmp_dir.'/'.$output_filename.'.zip', ZipArchive::CREATE);
if ($res === TRUE) {
    list($menu_html, $files_name) = createMenu($data[0]);
    //Get build.js and add which filenames will be used to search
    $build = io_readFile(DOKU_PLUGIN_TEMPLATES_HTML.'_/js/build.js');
    $build = preg_replace('/"@IOCFILENAMES@"/', implode(',', $files_name), $build, 1);
    $zip->addFromString('_/js/build.js', $build);
    getFiles(DOKU_PLUGIN_TEMPLATES_HTML,$zip);
    //Get index source
    $text_index = io_readFile(DOKU_PLUGIN_TEMPLATES_HTML.'index.html');
    $text_index = preg_replace('/@IOCHEADDOCUMENT@/', $data[1]['creditnom'], $text_index, 2);
    $text_index = preg_replace('/@IOCREFLICENSE@/', $data[1]['copylink'], $text_index, 1);
    //Get search source
    $text_search = io_readFile(DOKU_PLUGIN_TEMPLATES_HTML.'search.html');
    $text_search = preg_replace('/@IOCHEADDOCUMENT@/', $data[1]['creditnom'], $text_search, 2);
    $text_search = preg_replace('/@IOCREFLICENSE@/', $data[1]['copylink'], $text_search, 1);
    //Get template source
    $text_template = io_readFile(DOKU_PLUGIN_TEMPLATES_HTML.'template.html');
    $text_template = preg_replace('/@IOCHEADDOCUMENT@/', $data[1]['creditnom'], $text_template, 2);
    $text_template = preg_replace('/@IOCREFLICENSE@/', $data[1]['copylink'], $text_template, 1);
    $text_template = preg_replace('/@IOCCOPYTEXT@/', $data[1]['copytext'], $text_template, 1);
    //Create index page
    $menu_html_index = preg_replace('/@IOCSTARTUNIT@|@IOCENDUNIT@/', '', $menu_html);
    $menu_html_index = preg_replace('/@IOCSTARTINTRO@|@IOCENDINTRO@/', '', $menu_html_index);
    $menu_html_index = preg_replace('/@IOCSTARTINDEX@(.*?)@IOCENDINDEX@/', '', $menu_html_index);
    $menu_html_index = preg_replace('/@IOCSTARTEXPANDER@(.*?)@IOCENDEXPANDER@/', '', $menu_html_index);
    $menu_html_index = preg_replace('/(expander|id="\w+")/', '', $menu_html_index);
    $html = preg_replace('/@IOCTOC@/', $menu_html_index, $text_index, 1);
    $html = preg_replace('/@IOCMETA@/',createMeta($data[1]), $html, 1);
    $html = preg_replace('/@IOCMETABC@/',createMetaBC($data[1]), $html, 1);
    $html = preg_replace('/@IOCMETABR@/',createMetaBR($data[1]), $html, 1);
    addMetaMedia($data[1],$zip);
    $html = preg_replace('/@IOCPATH@/', '', $html);
    $zip->addFromString('index.html', $html);
    //Create search page
    $navmenu = createNavigation('');
    $html = preg_replace('/@IOCCONTENT@/', '<div id="search-results"></div>', $text_search, 1);
    $html = preg_replace('/@IOCTITLE@/', 'Cerca', $html, 1);
    $html = preg_replace('/@IOCTOC@/', '', $html, 1);
    $html = preg_replace('/@IOCPATH@/', '', $html);
    $html = preg_replace('/@IOCNAVMENU@/', $navmenu, $html, 1);
    $zip->addFromString('search.html', $html);
    //Remove menu index and expander tags
    $menu_html = preg_replace('/@IOCSTARTINDEX@|@IOCENDINDEX@/', '', $menu_html);
    $menu_html = preg_replace('/@IOCSTARTEXPANDER@|@IOCENDEXPANDER@/', '', $menu_html);
    if (isset($data[0]['intro'])){
        if(preg_match('/@IOCSTARTINTRO@(.*?)@IOCENDINTRO@/', $menu_html, $matches)){
            $menu_html_intro = $matches[1];
            $menu_html = preg_replace('/@IOCSTARTINTRO@.*?@IOCENDINTRO@/', '', $menu_html, 1);
        }
        //Intro
        foreach ($data[0]['intro'] as $page){
           $text = io_readFile(wikiFN($page[1]));
           list($header, $text) = extractHeader($text);
           $navmenu = createNavigation('',array($page[0]), array(''));
           $instructions = get_latex_instructions($text);
           $html = p_latex_render('iocxhtml', $instructions, $info);
           $html = preg_replace('/@IOCCONTENT@/', $html, $text_template, 1);
           $html = preg_replace('/@IOCMENUNAVIGATION@/', $menu_html_intro, $html, 1);
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
        if(preg_match('/@IOCSTARTUNIT@(.*?)@IOCENDUNIT@/', $menu_html, $matches)){
            $menu_html_unit = $matches[1];
            $menu_html = preg_replace('/@IOCSTARTUNIT@.*?@IOCENDUNIT@/', '', $menu_html, 1);
        }
        $def_unit_href = $unit['def_unit_href'];
        unset($unit['def_unit_href']);
        foreach ($unit as $ks => $section){
            if (is_array($section)){
                //Activities
                $_SESSION['activities'] = TRUE;
                foreach ($section as $ka => $act){
                    $text = io_readFile(wikiFN($act));
                    list($header, $text) = extractHeader($text);
                    $navmenu = createNavigation('../../../',array($unitname,$tree_names[$ku][$ks]['sectionname'],$tree_names[$ku][$ks][$ka]), array('../'.$def_unit_href.'.html',$def_section_href.'.html',''));
                    preg_match_all('/\{\{([^}|?]+)[^}]*\}\}/', $text, $matches);
                    array_push($files, $matches[1]);
                    $instructions = get_latex_instructions($text);
                    $html = p_latex_render('iocxhtml', $instructions, $info);
                    $html = preg_replace('/@IOCCONTENT@/', $html, $text_template, 1);
                    $html = preg_replace('/@IOCMENUNAVIGATION@/', $menu_html_unit, $html, 1);
                    //preg_match_all('/(<span class="(blocklatex|inlinelatex)">.*?<\/span>)/', $html, $matches);
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
                $navmenu = createNavigation('../../',array($unitname,$tree_names[$ku][$ks]), array($def_unit_href.'.html',''));
                preg_match_all('/\{\{([^}|?]+)[^}]*\}\}/', $text, $matches);
                array_push($files, $matches[1]);
                $instructions = get_latex_instructions($text);
                $html = p_latex_render('iocxhtml', $instructions, $info);
                $html = preg_replace('/@IOCCONTENT@/', $html, $text_template, 1);
                $html = preg_replace('/@IOCMENUNAVIGATION@/', $menu_html_unit, $html, 1);
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
        foreach($_SESSION['latex_images'] as $l){
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
            $result = 'Error en la creació del arxiu: ' . $filename;
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

        $file = wikiFN($id);
        if (@file_exists($file)) {
            $matches = array();
            $txt =  io_readFile($file);
            preg_match_all('/(?<=\={5} [T|t]oc \={5})\n+(\s{2,4}\*\s+\[\[[^\]]+\]\] *)+/is', $txt, $matches);
            $pages = (isset($matches[0][0]))?$matches[0][0]:'';
            unset($matches);
            preg_match_all('/\[\[([^|\]]+)\|?(.*?)\]\] */', $pages, $matches, PREG_SET_ORDER);
            foreach ($matches as $match){
                $sort = FALSE;
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
                    $def_unit_href='';
                    $unit_act = '';
                    if (preg_match('/^[I|i]ndex/', $content)){
                        $result = explode(DOKU_LF,$content);
                        $result = array_filter($result);
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
                            if (empty($section) && empty($def_unit_href)){
                                $def_unit_href = preg_replace('/([^:]*:)+/','',$pagename);
                            }
                            if (!empty($unit[1]) && !isset($data[$unit[1]])){
                                $data[$unit[1]] = array();
                                $unit_act = $unit[1];
                            }
                            if (isset($data[$unit_act]) && empty($data[$unit_act]['def_unit_href'])){
                                $data[$unit_act]['def_unit_href'] = $def_unit_href;
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
    function setMenu($type='', $name='', $href='', $id='',$index=FALSE){
        global $max_menu;
        global $def_section_href;

        if (strlen($name) > $max_menu){
            $name = substr($name, 0, $max_menu) . '...';
        }
        if ($type === 'root'){
            $class = ($index)?'indexnode':'rootnode';
            $menu_html = '<li id="'.$id.'" class="'.$class.'">';
            $menu_html .= '<p><a href="'.$href.'">'.$name.'</a></p>';
            $menu_html .= '</li>';
        }elseif ($type === 'unit'){
            $menu_html = '<li id="'.$id.'" class="parentnode">';
            $menu_html .= '<p><a href="'.$href.'">'.$name.'</a></p>';
            $menu_html .= '<ul class="expander">';
        }elseif ($type === 'section'){
            $menu_html = '<li id="'.$id.'" class="tocsection">';
            $menu_html .= '<p id="'.$id.$def_section_href.'"><a href="'.$href.'">'.$name.'</a>';
            $menu_html .= '@IOCSTARTEXPANDER@<span class="buttonexp"></span>@IOCENDEXPANDER@';
            $menu_html .= '</p>';
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
     * Create side menu elements and return path to filenames
     */
    function createMenu($elements){
        global $web_folder;
        global $tree_names;
        global $def_section_href;

        $files = array();
        $menu_html = '';
        if (isset($elements['intro'])){
            //Intro
            $menu_html .= '@IOCSTARTINTRO@';
            foreach ($elements['intro'] as $kp => $page){
                $href = '@IOCPATH@'.basename(wikiFN($page[1]),'.txt').'.html';
                //$menu_html .= setMenu('root', $page[0], $href, 'c'.$kp);
                $menu_html .= setMenu('root', $page[0], $href, basename(str_replace(':','/',$page[1])));
                array_push($files, '"'.str_replace('@IOCPATH@', '', $href).'"');
            }
            $menu_html .= '@IOCENDINTRO@';
            unset($elements['intro']);
        }
        foreach ($elements as $ku => $unit){
            $menu_html .= '@IOCSTARTUNIT@';
            $tree_names[$ku] = array();
            //Section
            $menu_html .= setMenu('unit',$unit['iocname'], '@IOCPATH@'.$web_folder.'/'.$ku.'/'.$unit['def_unit_href'].'.html', $ku);
            unset($unit['iocname']);
            //First main pages
            unset($unit['def_unit_href']);
            foreach ($unit as $ks => $section){
                if (!is_array($section)){
                    $text = io_readFile(wikiFN($section));
                    $act_href = '@IOCPATH@'.$web_folder.'/'.$ku.'/'.basename(wikiFN($section),'.txt').'.html';
                    preg_match('/\={6}([^=]+)\={6}/', $text, $matches);
                    $act_name = (!empty($matches[1]))?trim($matches[1]):'HEADER LEVEL 1 NOT FOUND';
                    $tree_names[$ku][$ks]=$act_name;
                    $menu_html .= setMenu('activity', $act_name, $act_href, $ku.$ks);
                    array_push($files, '"'.str_replace('@IOCPATH@', '', $act_href).'"');
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
                //Comprovar si existeix continguts.html $def_section_href i enllaçar la secció
                $act_href = '@IOCPATH@'.$web_folder.'/'.$ku.'/'.$ks.'/'.$def_section_href.'.html';
                $menu_html .= setMenu('section', $section_name, $act_href, $ku.$ks);
                foreach ($section as $ka => $act){
                    $text = io_readFile(wikiFN($act));
                    $act_href = '@IOCPATH@'.$web_folder.'/'.$ku.'/'.$ks.'/'.basename(wikiFN($act),'.txt').'.html';
                    if ($ka !== 'continguts'){
                        preg_match('/\={6}([^=]+)\={6}/', $text, $matches);
                        $act_name = (!empty($matches[1]))?trim($matches[1]):'HEADER LEVEL 1 NOT FOUND';
                        $tree_names[$ku][$ks][$ka]=$act_name;
                        $menu_html .= setMenu('activity', $act_name, $act_href, $ku.$ks.$ka);
                    }else{//File continguts has a short name
                        $act_name = 'Contingut';
                        $tree_names[$ku][$ks]['continguts']=$act_name;
                    }
                    array_push($files, '"'.str_replace('@IOCPATH@', '', $act_href).'"');
                }
                //Close menu activities
                $menu_html .= setMenu();
            }
            $menu_html .= setMenu();
            //Link to index
            $menu_html .= '@IOCSTARTINDEX@';
            $href = '@IOCPATH@index.html';
            $menu_html .= setMenu('root', 'Tornar a l&#39;&iacute;ndex general', $href, '', TRUE);
            $menu_html .= '@IOCENDINDEX@';
            $menu_html .= '@IOCENDUNIT@';
        }
        return array($menu_html, $files);
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
            $ignore = array('index.html','search.html','template.html','build.js');
            $directoryHandle = opendir($directory);
            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;
                    if(is_dir($path)) {
                        $dirname = str_replace(DOKU_PLUGIN_TEMPLATES_HTML,'',$path);
                        $zip->addEmptyDir($dirname);
                        getFiles($path, $zip);
                    }else{
                        if (!in_array($contents, $ignore)){
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
     * Create Meta data
     */
    function createMeta($data){

        $meta .= '<h1 class="headmainindex">'.(isset($data['creditnom'])?$data['creditnom']:'').'</h1>';
        $meta .= '<div class="metainfo">';
        $meta .= '<img src="img/portada.png" alt="'.(isset($data['familia'])?$data['familia']:'').'" />';
        $meta .= '<ul>';

        $coord = (isset($data['coordinacio'])?$data['coordinacio']:'');
        if (!empty($coord)){
            $meta .= '<li><strong>Coordinaci&oacute;</strong></li>';
        }
        $coord = preg_split('/\s?\\\\\s?/', $coord);
        foreach ($coord as $co){
            if (!empty($co)){
                $meta .= '<li>'.$co.'</li>';
            }
        }
        $authors = (isset($data['autoria'])?$data['autoria']:'');
        if (!empty($authors)){
            $meta .= '<li><strong>Redacci&oacute;</strong></li>';
        }
        $authors = preg_split('/\s?\\\\\s?/', $authors);
        foreach ($authors as $auth){
            if (!empty($auth)){
                $meta .= '<li>'.$auth.'</li>';
            }
        }
        $adapt = (isset($data['adaptacio'])?$data['adaptacio']:'');
        if (!empty($adapt)){
            $meta .= '<li><strong>Adaptaci&oacute;</strong></li>';
        }
        $adapt = preg_split('/\s?\\\\\s?/', $adapt);
        foreach ($adapt as $ad){
            if (!empty($ad)){
                $meta .= '<li>'.$ad.'</li>';
            }
        }
        $meta .= '</ul>';
        $meta .= '</div>';
        return $meta;
    }

    /**
    *
    * Create Meta data located at the bottom centered
    */
    function createMetaBC($data){

        $meta .= '<ul>';
        $meta .= '<li>'.(isset($data['familia'])?$data['familia']:'').'</li>';
        $meta .= '<li><strong>'.(isset($data['creditcodi'])?$data['creditcodi']:'').'</strong></li>';
        $meta .= '<li><strong>'.(isset($data['ciclenom'])?$data['ciclenom']:'').'</strong></li>';
        return $meta;
    }

    /**
    *
    * Create Meta data located at the bottom right aligned
    */
    function createMetaBR($data){

        $meta .= 'Primera edició: <strong>'.(isset($data['data'])?$data['data']:'').'</strong>';
        $meta .= ' &copy; Departament d&#39;Ensenyament';
        return $meta;
    }

    /**
     *
     * Add media files from meta info
     * @param Array $data
     * @param ZIP $zip
     */
    function addMetaMedia($data, &$zip){
        if (isset($data['familypic'])){
            preg_match('/\{\{([^}|?]+)[^}]*\}\}/',$data['familypic'],$matches);
            resolve_mediaid(getNS($matches[1]),&$matches[1],&$exists);
            if ($exists){
                $zip->addFile(mediaFN($matches[1]), 'img/portada.png');
            }
        }

        if (isset($data['copylogo'])){
            preg_match('/\{\{([^}|?]+)[^}]*\}\}/',$data['copylogo'],$matches);
            resolve_mediaid(getNS($matches[1]),&$matches[1],&$exists);
            if ($exists){
                $zip->addFile(mediaFN($matches[1]), 'img/license.png');
            }
        }

        if(isset($data['familia'])){
            $urlfamily = DOKU_PLUGIN_TEMPLATES;
            if (preg_match('/administraci/i', $data['familia'])){
                $urlfamily .= 'gad';
            }elseif (preg_match('/electricitat/i', $data['familia'])){
                $urlfamily .= 'iea';
            }elseif (preg_match('/socioculturals/', $data['familia'])){
                $urlfamily .= 'edi';
            }else{
                $urlfamily .= 'asix';
            }
            $urlfamily .= '_family_icon.png';
            $zip->addFile($urlfamily, 'img/family_icon.png');
        }
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

        $navigation = '<ul class="webnav"><li><a href="'.$index_path.'index.html" title="Tornar a l&#39;&iacute;ndex general">Inici</a></li>';
        if (!is_null($options)){
            foreach ($options as $k => $op){
                if ($op != 'Contingut'){
                    if ((strlen($op) > $max_navmenu) && $k < (count($options)-1)){
                        $op = substr($op, 0, $max_navmenu) . '...';
                    }
                    $navigation .= '<li>';
                    if (!empty($refs[$k]) && (isset($options[$k+1]) && $options[$k+1] != 'Contingut')){
                        $navigation .= '<a href="'.$refs[$k].'">';
                    }
                    $navigation .= $op;
                    if (!empty($refs[$k]) && (isset($options[$k+1]) && $options[$k+1] != 'Contingut')){
                        $navigation .= '</a>';
                    }
                    $navigation .= '</li>';
                }
            }
        }
        $navigation .= '</ul>';
        return $navigation;
    }