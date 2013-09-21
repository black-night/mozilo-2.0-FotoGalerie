<?php if(!defined('IS_CMS')) die();

/***************************************************************
*
* Plugin fuer moziloCMS, welches Fotos mit Hilfe von Glisse.js (http://glisse.victorcoulon.fr/) anzeigt.
* by blacknight - Daniel Neef
* 
***************************************************************/

class FotoGalerie extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurueck, mit dem die Plugin-Variable ersetzt 
    * wird.
    * 
    ***************************************************************/	
	const FG_LAST = "LetztesFoto";
	const FG_FIRST = "ErstesFoto";
	const FG_NEW = "NeustesFoto";
	const FG_OLD = "ÄltestesFoto";
	const FG_RANDOM = "ZufälligesFoto";
	
    function getContent($value) {       
        global $specialchars;
        
        $values = explode(",", $value);
        if (count($values) == 1) {
        	$gal_request = $specialchars->replacespecialchars($specialchars->getHtmlEntityDecode($values[0]),false);
        	$result =  $this->getFullGalerie($gal_request);
        }else if (count($values) == 2) {
        	$gal_request = $specialchars->replacespecialchars($specialchars->getHtmlEntityDecode($values[1]),false);
			$result = $this->getSpezialGalerie($values[0],$values[1]);
        }
        
        return $result;

    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurueck.
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
        $config['idwithgalleryname'] = array(
        		"type" => "checkbox",
        		"description" => $lang_gallery_admin->get("config_fotogallery_idwithgalleryname")
        );
        $config['showDownloadLink'] = array(
        		"type" => "checkbox",
        		"description" => $lang_gallery_admin->get("config_fotogallery_showDownloadLink")
        );
        return $config;            
    } // function getConfig
    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurueck. 
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
            "<b>".$lang_gallery_admin->get("config_fotogallery_plugin_name")."</b> \$Revision: 3 $",
            // CMS-Version
            "2.0",
            // Kurzbeschreibung
            $lang_gallery_admin->get("config_fotogallery_plugin_desc"),
            // Name des Autors
           "black-night",
            // Download-URL
            array("http://software.black-night.org","Software by black-night"),
            # Platzhalter => Kurzbeschreibung
            array('{FotoGalerie|...}' => $lang_gallery_admin->get("config_fotogallery_plugin_name"),
            	  '{FotoGalerie|'.self::FG_FIRST.',...}' => $lang_gallery_admin->get("config_fotogallery_plugin_first"),
            	  '{FotoGalerie|'.self::FG_LAST.',...}' => $lang_gallery_admin->get("config_fotogallery_plugin_last"),
            	  '{FotoGalerie|'.self::FG_NEW.',...}' => $lang_gallery_admin->get("config_fotogallery_plugin_new"),
            	  '{FotoGalerie|'.self::FG_OLD.',...}' => $lang_gallery_admin->get("config_fotogallery_plugin_old"),
            	  '{FotoGalerie|'.self::FG_RANDOM.',...}' => $lang_gallery_admin->get("config_fotogallery_plugin_random")
                 )
            );
            return $info;        
    } // function getInfo
    
    /***************************************************************
    *
    * Interne Funktionen
    *
    ***************************************************************/
    function getFullGalerie($galleryname) {
    	global $CMS_CONF;
    	global $lang_gallery_cms;
    	global $specialchars;
    	$dir = PLUGIN_DIR_REL."FotoGalerie/";
    	$lang_gallery_cms = new Language($dir."sprachen/cms_language_".$CMS_CONF->get("cmslanguage").".txt");    	
    	$GALERIE_DIR = BASE_DIR.GALLERIES_DIR_NAME."/".$galleryname."/";
    	$GALERIE_DIR_SRC = str_replace("%","%25",URL_BASE.GALLERIES_DIR_NAME."/".$galleryname."/");
    	
    	global $syntax;
    	$syntax->insert_jquery_in_head('jquery');
    	$syntax->insert_in_head($this->getHead());
    	
    	$alldescriptions = false;
    	if(is_file($GALERIE_DIR."texte.conf.php"))
    		$alldescriptions = new Properties($GALERIE_DIR."texte.conf.php");
    	
    	// Galerieverzeichnis einlesen
    	$picarray = getDirAsArray($GALERIE_DIR,"img");
    	$i = 0;
    	$result = "<div id=\"".$this->getIDName($galleryname)."\">";
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
    							.", copyright: '".$this->settings->get("copyright")."'"
    							.", showDownloadLink: ".$this->getBooleanStr($this->settings->get("showDownloadLink"))
    							." }); }); </script>";
    	$result .= "<br /><br/ >".$this->settings->get("copyright");
    	$result .= "</div>";
    	return $result;    	
    } //getFullGalerie
    
    function getSpezialGalerie($typ,$galleryname) {
    	global $specialchars;
    	$GALERIE_DIR = BASE_DIR.GALLERIES_DIR_NAME."/".$galleryname."/";
    	$GALERIE_DIR_SRC = str_replace("%","%25",URL_BASE.GALLERIES_DIR_NAME."/".$galleryname."/");
    	$picarray = getDirAsArray($GALERIE_DIR,"img");
    	if ($typ == self::FG_LAST) {
    		//Letztes Foto der Galerie laden
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[count($picarray)-1],true)."\" />";
    	}elseif ($typ == self::FG_FIRST) {
    		//Erste Foto der Galerie laden
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[0],true)."\" />";    		
    	}elseif ($typ == self::FG_NEW) {
    		//Neustes Foto der Galerie laden
    		$chdate = 0;
    		$index = 0;
    		for ($i = 0; $i < count($picarray); $i++) {
    			$tempchdate = filemtime($GALERIE_DIR."/".$picarray[$i]);
    			if ($tempchdate>$chdate) {
    				$chdate = $tempchdate;
    				$index = $i;
    			}
    		}
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$index],true)."\" />";
    	}elseif ($typ == self::FG_OLD) {
    		//Aeltestes Foto der Galerie laden
    		$chdate = time();
    		$index = 0;
    		for ($i = 0; $i < count($picarray); $i++) {
    			$tempchdate = filemtime($GALERIE_DIR."/".$picarray[$i]);
    			if ($tempchdate<$chdate) {
    				$chdate = $tempchdate;
    				$index = $i;
    			}
    		}
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$index],true)."\" />";    		
    	}elseif ($typ == self::FG_RANDOM) {
    		$index = rand(0,count($picarray)-1);
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$index],true)."\" />";
    	}
    	return $result;
    } //getSpezialGalerie
    
    function getHead() {   
    	$head = '<style type="text/css"> @import "'.URL_BASE.PLUGIN_DIR_NAME.'/FotoGalerie/plugin.css"; </style>'
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
    
    function getBoolean($value) {
    	return (strtoupper($value)=="TRUE");
    } //function getBoolean
    
    function getBooleanStr($value) {
    	if ($this->getBoolean($value)) {
    		return "true";
    	} else {
    		return "false";
    	}
    } //function getBooleanStr
    
    function getIDName($galleryName) {
    	if ($this->getBoolean($this->settings->get("idwithgalleryname"))) {
    		return "fotogalerie".$galleryName;
    	} else {
    		return "fotogalerie";
    	}
    } //function getBooleanStr    
} // class FotoGalerie

?>