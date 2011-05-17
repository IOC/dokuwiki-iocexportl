<?php

if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_INC . 'inc/parser/renderer.php';

static $symbols = array('α','β','Γ','γ','Δ','δ','ε','ζ','η','Θ','ι','κ','Λ','λ','μ','Ξ','Π','π','ρ','Σ','σ','Τ','τ','υ','Φ','φ','χ','Ψ','ψ','Ω','Ω','ω','≠','≤','≥','Ф','∑','∞');
static $reserved_symbols = array('#', '$', '%', '&', '~');
static $replacement_symbols = array('\#', '\$', '\%', '\&', '\~');

    /**
     *
     * Replace all invalid ocurrences in latex formulas
     * @param string $texexp
     */
    function filter_tex_sanitize_formula($texexp) {
        /// Check $texexp against blacklist (whitelisting could be more complete but also harder to maintain)
        $tex_blacklist = array(
            'include','command','loop','repeat','open','toks','output',
            'input','catcode','name','^^',
            '\def','\edef','\gdef','\xdef',
            '\every','\errhelp','\errorstopmode','\scrollmode','\nonstopmode',
            '\batchmode','\read','\write','csname','\newhelp','\uppercase',
            '\lowercase','\relax','\aftergroup',
            '\afterassignment','\expandafter','\noexpand','\special',
            '\let', '\futurelet','\else','\fi','\chardef','\makeatletter','\afterground',
            '\noexpand','\line','\mathcode','\item','\section','\mbox','\declarerobustcommand'
        );

        return  str_ireplace($tex_blacklist, 'forbiddenkeyword', $texexp);
    }

    /**
     *
     * Replace all reserved symbols
     * @param string $text
     */
    function clean_reserved_symbols($text){
        global $reserved_symbols;
        global $replacement_symbols;
        return str_ireplace($reserved_symbols, $replacement_symbols, $text);
    }

    /**
     *
     * Prints media url and generate a valid qrcode
     * @param string $url
     * @param string $title
     * @param string $type
     */
    function qrcode_media_url(&$renderer, $url, $title, $type){
        $renderer->doc .= '\begin{mediaurl}{'.$url.'}';
        $_SESSION['video_url'] = TRUE;
        $renderer->doc .= '\parbox[c]{\linewidth}{\raggedright ';
        $renderer->_latexAddImage(DOKU_PLUGIN . 'iocexportl/templates/'.$type.'.png','32',null,null,null,$url);
        $renderer->doc .= '}';
        $_SESSION['video_url'] = FALSE;
        $renderer->doc .= '& \hspace{-2mm}';
        $renderer->doc .= '\parbox[c]{\linewidth}{\raggedright ';
        $renderer->externallink($url, $title);
        $renderer->doc .= '}';
        $renderer->doc .= '\end{mediaurl}';
    }

    /**
     *
     * Convert a text into instructions
     * @param string $text
     */
    function get_latex_instructions($text){

      //Call our customized function get_parsermodes
      $modes = get_latex_parsermodes();

      // Create the parser
      $Parser = new Doku_Parser();

      // Add the Handler
      $Parser->Handler = new Doku_Handler();

      //add modes to parser
      foreach($modes as $mode){
        $Parser->addMode($mode['mode'],$mode['obj']);
      }

      // Do the parsing
      trigger_event('PARSER_WIKITEXT_PREPROCESS', $text);
      $p = $Parser->parse($text);
      return $p;
    }

    /**
     *
     * returns own parser syntax modes in correct order
     */
    function get_latex_parsermodes(){
      global $conf;

      //reuse old data
      static $modes = null;
      if($modes != null){
        return $modes;
      }

      //import parser classes and mode definitions
      require_once DOKU_INC . 'inc/parser/parser.php';

      // we now collect all syntax modes and their objects, then they will
      // be sorted and added to the parser in correct order
      $modes = array();

      // add own syntax plugins
      $pluginlist = array();
      getPlugins($pluginlist);

      if(count($pluginlist)){
        global $PARSER_MODES;
        $obj = null;
        foreach($pluginlist as $p){
          require_once DOKU_PLUGIN . 'iocexportl/syntax/'.$p.'.php';
          $class_name = 'syntax_plugin_iocexportl_'. $p;
          $obj = new $class_name();//attempt to load plugin into $obj
          $PARSER_MODES[$obj->getType()][] = "plugin_$p"; //register mode type
          //add to modes
          $modes[] = array(
                       'sort' => $obj->getSort(),
                       'mode' => "plugin_$p",
                       'obj'  => $obj,
                     );
          unset($obj); //remove the reference
        }
      }

      // add default modes
      $std_modes = array('listblock','preformatted','notoc','nocache',
                         'header','table','linebreak','footnote','hr',
                         'unformatted','php','html','code','file','quote',
                         'internallink','rss','media','externallink',
                         'emaillink','windowssharelink','eol');
      if($conf['typography']){
        $std_modes[] = 'quotes';
        $std_modes[] = 'multiplyentity';
      }
      foreach($std_modes as $m){
        $class = "Doku_Parser_Mode_$m";
        $obj   = new $class();
        $modes[] = array(
                     'sort' => $obj->getSort(),
                     'mode' => $m,
                     'obj'  => $obj
                   );
      }

      // add formatting modes
      $fmt_modes = array('strong','emphasis','underline','monospace',
                         'subscript','superscript','deleted');
      foreach($fmt_modes as $m){
        $obj   = new Doku_Parser_Mode_formatting($m);
        $modes[] = array(
                     'sort' => $obj->getSort(),
                     'mode' => $m,
                     'obj'  => $obj
                   );
      }

      // add modes which need files
      $obj     = new Doku_Parser_Mode_smiley(array_keys(getSmileys()));
      $modes[] = array('sort' => $obj->getSort(), 'mode' => 'smiley','obj'  => $obj );
      $obj     = new Doku_Parser_Mode_acronym(array_keys(getAcronyms()));
      $modes[] = array('sort' => $obj->getSort(), 'mode' => 'acronym','obj'  => $obj );
      $obj     = new Doku_Parser_Mode_entity(array_keys(getEntities()));
      $modes[] = array('sort' => $obj->getSort(), 'mode' => 'entity','obj'  => $obj );


      // add optional camelcase mode
      if($conf['camelcase']){
        $obj     = new Doku_Parser_Mode_camelcaselink();
        $modes[] = array('sort' => $obj->getSort(), 'mode' => 'camelcaselink','obj'  => $obj );
      }

      //sort modes
      usort($modes,'p_sort_modes');

      return $modes;
    }

    /**
     *
     * Fill plugins var with own syntax plugins
     * @param array $plugins
     */
    function getPlugins(&$plugins){
        $dir = 'iocexportl/syntax';
        if ($dp = @opendir(DOKU_PLUGIN."$dir/")) {
            while (FALSE !== ($component = readdir($dp))) {
                if (substr($component,0,1) == '.' || strtolower(substr($component, -4)) != ".php") continue;
                if (is_file(DOKU_PLUGIN."$dir/$component")) {
                    array_push($plugins,substr($component, 0, -4));
                }
            }
            closedir($dp);
        }
    }

    /**
     *
     * Renders a list of instruction to the specified output mode
     * @param string $mode
     * @param array $instructions
     * @param array $info
     */
    function p_latex_render($mode,$instructions,&$info){
        if(is_null($instructions)) return '';

        require_once DOKU_PLUGIN . 'iocexportl/renderer/'.$mode.'.php';
        $class = "renderer_plugin_".$mode;
        $Renderer = new $class;

        if (is_null($Renderer)) return null;

        $Renderer->reset();

        $Renderer->smileys = getSmileys();
        $Renderer->entities = getEntities();
        $Renderer->acronyms = getAcronyms();
        $Renderer->interwiki = getInterwiki();

        // Loop through the instructions
        foreach ( $instructions as $instruction ) {
            // Execute the callback against the Renderer
            call_user_func_array(array(&$Renderer, $instruction[0]),$instruction[1]);
        }

        //set info array
        $info = $Renderer->info;

        // Post process and return the output
        $data = array($mode,& $Renderer->doc);
        trigger_event('RENDERER_CONTENT_POSTPROCESS',$data);
        return $Renderer->doc;
    }
