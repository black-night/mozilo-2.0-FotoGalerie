<?php if(!defined('IS_CMS')) die();

/***************************************************************
*
* Plugin für moziloCMS, welches Fotos mit Hilfe von Glisse.js (http://glisse.victorcoulon.fr/) anzeigt.
* by blacknight - Daniel Neef
* 
***************************************************************/

class FotoGalerie extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurück, mit dem die Plugin-Variable ersetzt 
    * wird.
    * 
    ***************************************************************/


    function getContent($value) {
        global $CMS_CONF;
        global $specialchars;
        global $lang_gallery_cms;
        $dir = PLUGIN_DIR_REL."FotoGalerie/";
        $lang_gallery_cms = new Language($dir."sprachen/cms_language_".$CMS_CONF->get("cmslanguage").".txt");
        
        $gal_request = $specialchars->replacespecialchars($specialchars->getHtmlEntityDecode($value),false);
        
        $GALERIE_DIR = BASE_DIR.GALLERIES_DIR_NAME."/".$gal_request."/";
        $GALERIE_DIR_SRC = str_replace("%","%25",URL_BASE.GALLERIES_DIR_NAME."/".$gal_request."/");
        
        global $syntax;
        $syntax->insert_jquery_in_head('jquery');
        $syntax->insert_in_head($this->getHead());

        $alldescriptions = false;
        if(is_file($GALERIE_DIR."texte.conf.php"))
        	$alldescriptions = new Properties($GALERIE_DIR."texte.conf.php");        

        // Galerieverzeichnis einlesen
        $picarray = getDirAsArray($GALERIE_DIR,"img");
        $i = 0;
        $result = "<div id=\"fotogalerie\">";
        for ($i=0; $i<count($picarray); $i++) {        
          $result .=  "<a href=\"".$GALERIE_DIR_SRC.$specialchars->replaceSpecialChars($picarray[$i],true)."\" class=\"thumbnail-link\">"
                      ."<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$i],true)."\" "
          		      ."alt=\"".$specialchars->rebuildSpecialChars($picarray[$i],true,true)."\" class=\"thumbnail\" "
          		      ."data-glisse-big=\"".$GALERIE_DIR_SRC.$specialchars->replaceSpecialChars($picarray[$i],true)."\" "
          		      ."title=\"".$this->getCurrentDescription($picarray[$i],$picarray,$alldescriptions)."\" "
          		      ."rel=\"group1\" "
          		      ." />"
          		      ."</a>";                
        }
        $result .= "<script type=\"text/javascript\"> "
        		   ."$(\"a\").click(function(e) { if ($(this).hasClass('thumbnail-link')) { e.preventDefault(); } });"
        		   ."$(function () { $('.thumbnail').glisse({ "
        		   ." changeSpeed: ".$this->getInteger($this->settings->get("changeSpeed"))
        		   .", speed: ".$this->getInteger($this->settings->get("speed"))
        		   .", effect:'".$this->getEffect()."'"
        		   .", fullscreen: ".$this->getBooleanStr($this->settings->get("fullscreen")) 
        		   .", copyright: '".$this->settings->get("copyright")
                   ."' }); }); </script>";
       	$result .= "<br /><br/ >".$this->settings->get("copyright");
        $result .= "</div>";
        return $result;
    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurück.
    * 
    ***************************************************************/
    function getConfig() {
        global $lang_gallery_admin;

        $config = array();
        $config['copyright'] = array(
        		"type" => "text",
        		"description" => $lang_gallery_admin->get("config_fotogallery_copyright")
        );   
        $config['changeSpeed'] = array(
        		"type" => "text",
        		"description" => $lang_gallery_admin->get("config_fotogallery_changeSpeed"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $lang_gallery_admin->get("config_fotogallery_number_regex_error")        		
        );
        $config['speed'] = array(
        		"type" => "text",
        		"description" => $lang_gallery_admin->get("config_fotogallery_Speed"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $lang_gallery_admin->get("config_fotogallery_number_regex_error")
        );  
        $config['effect'] = array(
        		"type" => "select",
        		"description" => $lang_gallery_admin->get("config_fotogallery_effect"),
        		"descriptions" => array(
        				"bounce" => "bounce",
        				"fadeBig" => "fadeBig",
        				"fade" => "fade",
        				"roll" => "roll",
        				"rotate" => "rotate",
        				"flipX" => "flipX",
        				"flipY" => "flipY"
        		)
        );
        $config['fullscreen'] = array(
        		"type" => "checkbox",
        		"description" => $lang_gallery_admin->get("config_fotogallery_fullscreen")        		
        );
        return $config;            
    } // function getConfig
    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurück. 
    * 
    ***************************************************************/
    function getInfo() {
        global $ADMIN_CONF;
        global $lang_gallery_admin;
        $dir = PLUGIN_DIR_REL."FotoGalerie/";
        $language = $ADMIN_CONF->get("language");
        $lang_gallery_admin = new Properties($dir."sprachen/admin_language_".$language.".txt",false);        
        $info = array(
            // Plugin-Name
            "<b>".$lang_gallery_admin->get("config_fotogallery_plugin_name")."</b> \$Revision: 1 $",
            // CMS-Version
            "2.0",
            // Kurzbeschreibung
            $lang_gallery_admin->get("config_fotogallery_plugin_desc"),
            // Name des Autors
           "blacknight",
            // Download-URL
            "www.black-night.org",
            # Platzhalter => Kurzbeschreibung
            array('{FotoGalerie|}' => $lang_gallery_admin->get("config_fotogallery_plugin_name"))
            );
            return $info;        
    } // function getInfo
    
    /***************************************************************
    *
    * Interne Funktionen
    *
    ***************************************************************/
    function getHead() {   
    	$head = '<style type="text/css"> @import "'.URL_BASE.PLUGIN_DIR_NAME.'/FotoGalerie/css/glisse.css"; </style>'
    	        .'<script type="text/javascript" src="'.URL_BASE.PLUGIN_DIR_NAME.'/FotoGalerie/js/glisse.js"></script>'
    			;
    	return $head;
    } //function getHead
    
    function getCurrentDescription($picname,$picarray,$alldescriptions) {
    	global $specialchars;
    
    	if(!$alldescriptions)
    		return "&nbsp;";
    	// Keine Bilder im Galerieverzeichnis?
    	if (count($picarray) == 0)
    		return "&nbsp;";
    	// Bildbeschreibung einlesen
    	$description = $alldescriptions->get($picname);
    	if(strlen($description) > 0) {
    		return $specialchars->rebuildSpecialChars($description,false,true);
    	} else {
    		return "&nbsp;";
    	}
    }  //function getCurrentDescription

    function getInteger($value) {
    	if (is_numeric($value) and ($value > 0)) {
    		return $value;
    	} else {
    		return 1;    	
    	}
    } //function getInteger
    
    function getEffect() {
    	$effect = $this->settings->get("effect");
    	if (strlen($effect) > 0) {
    		return $effect;
    	} else {
    		return 'bounce';
    	}
    } //function getEffect
    
    function getBooleanStr($value) {
    	if ($value) {
    		return "true";
    	} else {
    		return "false";
    	}
    } //getBooleanStr
} // class FotoGalerie

?>
    