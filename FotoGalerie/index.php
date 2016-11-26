<?php if(!defined('IS_CMS')) die();

/***************************************************************
*
* Plugin fuer moziloCMS, welches Fotos mit Hilfe von Glisse.js (http://glisse.victorcoulon.fr/) anzeigt.
* by black-night - Daniel Neef
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
	const FG_SINGLE = "EinzelFoto";
	const FG_COUNT = "AnzahlFotos";
	
	private $lang_gallery_admin;
	private $lang_gallery_cms;
	
    function getContent($value) {       
        global $specialchars;
        
        $values = explode(",", $value);
        if (count($values) == 1) {
        	$gal_request = $specialchars->replacespecialchars($specialchars->getHtmlEntityDecode($values[0]),false);
        	$result =  $this->getFullGalerie($gal_request);
        }else if (count($values) == 2) {
			$result = $this->getSpezialGalerie($values[0],$values[1]);
        }else if (count($values) == 3) {
            $result = $this->getSingleFoto($values[1],$values[2],'');
        }else if (count($values) == 4) {
            $result = $this->getSingleFoto($values[1],$values[2],$values[3]);
        }
        
        return $result;

    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurueck.
    * 
    ***************************************************************/
    function getConfig() {

        $config = array();
        $config['copyright'] = array(
        		"type" => "text",
        		"description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_copyright")
        );
        $config['changeSpeed'] = array(
        		"type" => "text",
        		"description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_changeSpeed"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_number_regex_error")        		
        );
        $config['speed'] = array(
        		"type" => "text",
        		"description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_Speed"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_number_regex_error")
        );  
        $config['effect'] = array(
        		"type" => "select",
        		"description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_effect"),
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
        		"description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_fullscreen")        		
        );
        $config['idwithgalleryname'] = array(
        		"type" => "checkbox",
        		"description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_idwithgalleryname")
        );
        $config['showDownloadLink'] = array(
        		"type" => "checkbox",
        		"description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_showDownloadLink")
        );
        $config['showMaxLink'] = array(
                "type" => "checkbox",
                "description" => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_showMaxLink")
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
        $dir = $this->PLUGIN_SELF_DIR;
        $language = $ADMIN_CONF->get("language");
        $this->lang_gallery_admin = new Language($dir."sprachen/admin_language_".$language.".txt");        
        $info = array(
            // Plugin-Name
            "<b>".$this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_name")."</b> \$Revision: 8 $",
            // CMS-Version
            "2.0",
            // Kurzbeschreibung
            $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_desc"),
            // Name des Autors
           "black-night",
            // Download-URL
            array("http://software.black-night.org","Software by black-night"),
            # Platzhalter => Kurzbeschreibung
            array('{FotoGalerie|...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_name"),
            	  '{FotoGalerie|'.self::FG_FIRST.',...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_first"),
            	  '{FotoGalerie|'.self::FG_LAST.',...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_last"),
            	  '{FotoGalerie|'.self::FG_NEW.',...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_new"),
            	  '{FotoGalerie|'.self::FG_OLD.',...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_old"),
            	  '{FotoGalerie|'.self::FG_RANDOM.',...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_random"),
                  '{FotoGalerie|'.self::FG_SINGLE.',...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_single"),
                  '{FotoGalerie|'.self::FG_COUNT.',...}' => $this->lang_gallery_admin->getLanguageValue("config_fotogallery_plugin_count")
                 )
            );
            return $info;        
    } // function getInfo
    
    /***************************************************************
    *
    * Interne Funktionen
    *
    ***************************************************************/
    private function getFullGalerie($galleryname) {
    	global $CMS_CONF;
    	global $specialchars;
    	$dir = $this->PLUGIN_SELF_DIR;
    	$this->lang_gallery_cms = new Language($dir."sprachen/cms_language_".$CMS_CONF->get("cmslanguage").".txt");    	
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
    	$group = "group-".md5($galleryname); // md5 verhindert hier Probleme mit Sonderzeichen
    	$i = 0;
    	$result = "<div id=\"".$this->getIDName($galleryname)."\">";
    	for ($i=0; $i<count($picarray); $i++) {
    		$result .=  "<a href=\"".$GALERIE_DIR_SRC.$specialchars->replaceSpecialChars($picarray[$i],true)."\" class=\"thumbnail-link\">"
    				."<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$i],true)."\" "
    						."alt=\"".$specialchars->rebuildSpecialChars($picarray[$i],true,true)."\" class=\"thumbnail\" "
    								."data-glisse-big=\"".$GALERIE_DIR_SRC.$specialchars->replaceSpecialChars($picarray[$i],true)."\" "
    										."title=\"".$this->getCurrentDescription($picarray[$i],$picarray,$alldescriptions)."\" "
    												."rel=\"".$group."\" "
    														." />"
    																."</a>";
    	}
    	$result .= "<br /><br />".$this->settings->get("copyright");
    	$result .= "</div>";
    	return $result;    	
    } //getFullGalerie
    
    private function getSpezialGalerie($typ,$galleryname) {
    	global $specialchars;
    	$GALERIE_DIR = BASE_DIR.GALLERIES_DIR_NAME."/".$galleryname."/";
    	$GALERIE_DIR_SRC = str_replace("%","%25",URL_BASE.GALLERIES_DIR_NAME."/".$galleryname."/");
    	$picarray = getDirAsArray($GALERIE_DIR,"img");
    	if ($typ == self::FG_LAST) {
    		//Letztes Foto der Galerie laden
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[count($picarray)-1],true)."\" alt=\"".$specialchars->rebuildSpecialChars($picarray[count($picarray)-1],true,true)."\" />";
    	}elseif ($typ == self::FG_FIRST) {
    		//Erste Foto der Galerie laden
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[0],true)."\" alt=\"".$specialchars->rebuildSpecialChars($picarray[0],true,true)."\" />";
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
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$index],true)."\" alt=\"".$specialchars->rebuildSpecialChars($picarray[$index],true,true)."\" />";
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
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$index],true)."\" alt=\"".$specialchars->rebuildSpecialChars($picarray[$index],true,true)."\" />";    		
    	}elseif ($typ == self::FG_RANDOM) {
    		$index = rand(0,count($picarray)-1);
    		$result = "<img src=\"".$GALERIE_DIR_SRC.PREVIEW_DIR_NAME."/".$specialchars->replaceSpecialChars($picarray[$index],true)."\" alt=\"".$specialchars->rebuildSpecialChars($picarray[$index],true,true)."\" />";
    	}elseif ($typ == self::FG_COUNT) {
    	    $result = count($picarray);
    	}
    	return $result;
    } //getSpezialGalerie
    
    private function getSingleFoto($preview,$img,$txt) {
        global $CMS_CONF;
        $this->lang_gallery_cms = new Language($this->PLUGIN_SELF_DIR."sprachen/cms_language_".$CMS_CONF->get("cmslanguage").".txt");        
        global $CatPage;
        $file = $CatPage->split_CatPage_fromSyntax($preview,true);        
        $previewFile = $CatPage->get_srcFile($file[0],$file[1]);
        if (!$previewFile) 
            return 'Datei ('.$preview.') existiert nicht!';
        $file = $CatPage->split_CatPage_fromSyntax($img,true);
        $imgFile = $CatPage->get_srcFile($file[0],$file[1]);
        if (!$previewFile)
            return 'Datei ('.$img.') existiert nicht!';
        global $syntax;
        $syntax->insert_jquery_in_head('jquery');
        $syntax->insert_in_head($this->getHead());
        $result = "";
        $result .=  "<a href=\"".$imgFile."\" class=\"thumbnail-link\">"
            				."<img src=\"".$previewFile."\" "
            				        ."alt=\"".$txt."\" class=\"thumbnail\" "
            			            ."data-glisse-big=\"".$imgFile."\" "
            			            ."title=\"".$txt."\" "
            			            ."rel=\"single1\" "
            			    ." />"
            		."</a>";
        return $result;
                
    }//getSingleFoto
    
    private function getHead() {   
    	$head = '<style type="text/css"> @import "'.URL_BASE.PLUGIN_DIR_NAME.'/FotoGalerie/plugin.css"; </style>'
    	        .'<script type="text/javascript" src="'.URL_BASE.PLUGIN_DIR_NAME.'/FotoGalerie/js/glisse.js"></script>'
    	        ."<script type=\"text/javascript\"> $(document).ready(function(){"
                    ."$(\"a\").click(function(e) { if ($(this).hasClass('thumbnail-link')) { e.preventDefault(); } });"
                            ."$(function () { $('.thumbnail').glisse({ "
                                    ." changeSpeed: ".$this->getChangeSpeed()
                                    .", speed: ".$this->getSpeed()
                                    .", effect:'".$this->getEffect()."'"
                                    .", fullscreen: ".$this->getBooleanStr($this->settings->get("fullscreen"))
                                    .", copyright: '".$this->settings->get("copyright")."'"
                                    .", showDownloadLink: ".$this->getBooleanStr($this->settings->get("showDownloadLink"))
                                    .", showMaxLink: ".$this->getBooleanStr($this->settings->get("showMaxLink"))
                                    .", strDownload: '".$this->lang_gallery_cms->getLanguageValue("download")."'"
                                    .", strMax: '".$this->lang_gallery_cms->getLanguageValue("showmax")."'"
                                    .", strNext: '".$this->lang_gallery_cms->getLanguageValue("next")."'"
                                    .", strPrev: '".$this->lang_gallery_cms->getLanguageValue("prev")."'"
                ." }); }); });</script>"
    			;
    	return $head;
    } //function getHead
    
    private function getCurrentDescription($picname,$picarray,$alldescriptions) {
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

    private function getInteger($value) {
    	if (is_numeric($value) and ($value > 0)) {
    		return $value;
    	} else {
    		return 1;    	
    	}
    } //function getInteger
    
    private function getEffect() {
    	$effect = $this->settings->get("effect");
    	if (strlen($effect) > 0) {
    		return $effect;
    	} else {
    		return 'bounce';
    	}
    } //function getEffect
    
    private function getBoolean($value) {
    	return (strtoupper($value)=="TRUE");
    } //function getBoolean
    
    private function getBooleanStr($value) {
    	if ($this->getBoolean($value)) {
    		return "true";
    	} else {
    		return "false";
    	}
    } //function getBooleanStr
    
    private function getIDName($galleryName) {
    	if ($this->getBoolean($this->settings->get("idwithgalleryname"))) {
    		return "fotogalerie".$galleryName;
    	} else {
    		return "fotogalerie";
    	}
    } //function getBooleanStr

    private function getChangeSpeed() {
        if ($this->settings->get("changeSpeed")) {
    		return $this->getInteger($this->settings->get("changeSpeed"));
    	} else {
    		return 1000;
    	}
    }  //function getChangeSpeed
    
    private function getSpeed() {
        if ($this->settings->get("Speed")) {
            return $this->getInteger($this->settings->get("Speed"));
        } else {
            return 300;
        }
    }  //function getChangeSpeed    
} // class FotoGalerie

?>